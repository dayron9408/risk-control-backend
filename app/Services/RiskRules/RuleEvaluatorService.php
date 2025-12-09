<?php

namespace App\Services\RiskRules;

use App\Models\Account;
use App\Models\Trade;
use App\Models\RiskRule;
use App\Models\Incident;
use App\Services\RiskRules\Rules\DurationRule;
use App\Services\RiskRules\Rules\VolumeRule;
use App\Services\RiskRules\Rules\OpenTradesRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RuleEvaluatorService
{
    private array $ruleHandlers = [];

    public function __construct(
        private DurationRule $durationRule,
        private VolumeRule $volumeRule,
        private OpenTradesRule $openTradesRule
    ) {
        $this->ruleHandlers = [
            RiskRule::TYPE_DURATION => $this->durationRule,
            RiskRule::TYPE_VOLUME => $this->volumeRule,
            RiskRule::TYPE_OPEN_TRADES => $this->openTradesRule,
        ];
    }

    /**
     * Evaluar reglas para una cuenta específica
     */
    public function evaluateAccount(Account $account): array
    {
        $results = [];
        $activeRules = RiskRule::where('is_active', true)->get();

        foreach ($activeRules as $rule) {
            $result = $this->evaluateRuleForAccount($rule, $account);
            if ($result['violated']) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Evaluar reglas para un trade específico (evento)
     */
    public function evaluateTrade(Trade $trade): array
    {
        // Solo evaluar si el trade está cerrado
        if (!$trade->isClosed()) {
            return [];
        }

        $results = [];
        $account = $trade->account;
        $activeRules = RiskRule::where('is_active', true)->get();

        foreach ($activeRules as $rule) {
            $result = $this->evaluateRuleForTrade($rule, $trade, $account);
            if ($result['violated']) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Evaluar todas las cuentas activas (para evaluación periódica)
     */
    public function evaluateAllActiveAccounts(): array
    {
        $results = [];
        $activeAccounts = Account::where('status', 'enable')
            ->where('trading_status', 'enable')
            ->get();

        foreach ($activeAccounts as $account) {
            // Usar lock para evitar evaluaciones simultáneas
            $lockKey = "account_evaluation_{$account->id}";

            if (Cache::has($lockKey)) {
                Log::debug("Cuenta {$account->id} ya está siendo evaluada, saltando...");
                continue;
            }

            // Lock por 1 minuto
            Cache::put($lockKey, true, 60);

            try {
                $accountResults = $this->evaluateAccount($account);

                foreach ($accountResults as $result) {
                    if ($result['violated']) {
                        $result['account_id'] = $account->id;
                        $result['account_login'] = $account->login;
                        $results[] = $result;
                    }
                }
            } finally {
                Cache::forget($lockKey);
            }
        }

        return $results;
    }

    /**
     * Evaluar una regla específica para una cuenta
     */
    private function evaluateRuleForAccount(RiskRule $rule, Account $account): array
    {
        $handler = $this->getHandler($rule->type);

        if (!$handler) {
            return [
                'violated' => false,
                'message' => "No handler for rule type: {$rule->type}"
            ];
        }

        // VERIFICAR DUPLICADOS ANTES de evaluar
        if ($this->hasRecentIncident($rule, $account, null)) {
            Log::debug("Ya existe incidente reciente para regla {$rule->id} en cuenta {$account->id}, saltando...");
            return ['violated' => false];
        }

        $violated = $handler->evaluateForAccount($rule, $account);

        if ($violated) {
            return $this->createIncident($rule, $account, null, $handler->getViolationData());
        }

        return ['violated' => false];
    }

    /**
     * Evaluar una regla específica para un trade
     */
    private function evaluateRuleForTrade(RiskRule $rule, Trade $trade, Account $account): array
    {
        $handler = $this->getHandler($rule->type);

        if (!$handler) {
            return [
                'violated' => false,
                'message' => "No handler for rule type: {$rule->type}"
            ];
        }

        // VERIFICAR DUPLICADOS ANTES de evaluar
        if ($this->hasRecentIncident($rule, $account, $trade)) {
            Log::debug("Ya existe incidente reciente para regla {$rule->id} en trade {$trade->id}, saltando...");
            return ['violated' => false];
        }

        $violated = $handler->evaluateForTrade($rule, $trade, $account);

        if ($violated) {
            return $this->createIncident($rule, $account, $trade, $handler->getViolationData());
        }

        return ['violated' => false];
    }

    /**
     * Verificar si ya existe un incidente reciente
     */
    private function hasRecentIncident(RiskRule $rule, Account $account, ?Trade $trade): bool
    {
        $query = Incident::where('account_id', $account->id)
            ->where('rule_id', $rule->id)
            ->where('created_at', '>=', now()->subMinutes($this->getDuplicatePreventionMinutes($rule)));

        // Manejar diferentes tipos de reglas
        if ($rule->type === RiskRule::TYPE_OPEN_TRADES) {
            // OpenTradesRule no tiene trade_id específico
            $query->whereNull('trade_id');
        } elseif ($trade) {
            // Otras reglas con trade específico
            $query->where('trade_id', $trade->id);
        } else {
            // Para evaluación por cuenta sin trade específico
            $query->whereNull('trade_id');
        }

        return $query->exists();
    }

    /**
     * Obtener minutos para prevenir duplicados según tipo de regla
     */
    private function getDuplicatePreventionMinutes(RiskRule $rule): int
    {
        return match($rule->type) {
            RiskRule::TYPE_OPEN_TRADES => 30, // 30 minutos para OpenTradesRule
            default => 10, // 10 minutos para otras reglas
        };
    }

    /**
     * Obtener el handler para un tipo de regla
     */
    private function getHandler(string $ruleType): ?RuleInterface
    {
        return $this->ruleHandlers[$ruleType] ?? null;
    }

    /**
     * Crear incidente y ejecutar acciones
     */
    private function createIncident(RiskRule $rule, Account $account, ?Trade $trade, array $violationData): array
    {
        // Determinar trade_id según tipo de regla
        $tradeId = $rule->type === RiskRule::TYPE_OPEN_TRADES ? null : $trade?->id;

        // Usar transacción para evitar race conditions
        return DB::transaction(function () use ($rule, $account, $trade, $tradeId, $violationData) {
            // Doble verificación dentro de la transacción
            $recentIncident = Incident::where('account_id', $account->id)
                ->where('rule_id', $rule->id)
                ->where('trade_id', $tradeId)
                ->where('created_at', '>=', now()->subMinutes($this->getDuplicatePreventionMinutes($rule)))
                ->exists();

            if ($recentIncident) {
                Log::warning("Se intentó crear incidente duplicado en transacción para cuenta {$account->id}, regla {$rule->id}");
                return [
                    'violated' => false,
                    'message' => 'Incidente duplicado detectado en transacción'
                ];
            }

            // Crear incidente
            $incident = Incident::create([
                'rule_id' => $rule->id,
                'account_id' => $account->id,
                'trade_id' => $tradeId,
                'severity' => $rule->severity,
                'description' => $this->generateDescription($rule, $violationData),
            ]);

            Log::info("Incidente creado", [
                'incident_id' => $incident->id,
                'account_id' => $account->id,
                'rule_id' => $rule->id,
                'trade_id' => $tradeId,
                'rule_type' => $rule->type,
            ]);

            // Contar incidentes recientes
            $recentIncidentsCount = Incident::where('account_id', $account->id)
                ->where('rule_id', $rule->id)
                ->where('created_at', '>=', now()->subHours(24))
                ->count();

            // Para reglas SOFT, verificar si se alcanzó el límite
            if ($rule->isSoftRule()) {
                if ($recentIncidentsCount < $rule->incidents_before_action) {
                    return [
                        'violated' => true,
                        'incident_created' => true,
                        'action_executed' => false,
                        'message' => "Incidente creado ({$recentIncidentsCount}/{$rule->incidents_before_action} en 24h)",
                        'incident_id' => $incident->id,
                        'account_id' => $account->id,
                        'account_login' => $account->login,
                        'rule_id' => $rule->id,
                        'rule_name' => $rule->name,
                        'severity' => $rule->severity,
                        'rule_type' => $rule->type,
                        'trade_id' => $tradeId,
                    ];
                }
            }

            // Ejecutar acciones
            $actionsExecuted = $this->executeActions($rule, $incident);

            return [
                'violated' => true,
                'incident_created' => true,
                'action_executed' => $actionsExecuted,
                'message' => 'Incidente creado y acciones ejecutadas',
                'incident_id' => $incident->id,
                'account_id' => $account->id,
                'account_login' => $account->login,
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'severity' => $rule->severity,
                'rule_type' => $rule->type,
                'trade_id' => $tradeId,
                'created_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Ejecutar acciones asociadas a una regla
     */
    private function executeActions(RiskRule $rule, Incident $incident): bool
    {
        $actions = $rule->actions()->orderBy('order')->get();
        $anyExecuted = false;

        foreach ($actions as $action) {
            $executed = $this->executeSingleAction($action, $incident);
            if ($executed) {
                $anyExecuted = true;
            }
        }

        return $anyExecuted;
    }

    /**
     * Ejecutar una acción individual
     */
    private function executeSingleAction($action, Incident $incident): bool
    {
        try {
            $actionService = new ActionService();
            return $actionService->execute($action->action_type, $incident);
        } catch (\Exception $e) {
            Log::error("Error executing action: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generar descripción del incidente
     */
    private function generateDescription(RiskRule $rule, array $violationData): string
    {
        try {
            if (empty($violationData)) {
                return "Violación de regla: {$rule->name} (Tipo: {$rule->type})";
            }

            switch ($rule->type) {
                case RiskRule::TYPE_DURATION:
                    $duration = $violationData['duration_seconds'] ?? 'desconocido';
                    $minDuration = $violationData['min_duration_seconds'] ?? $rule->min_duration_seconds ?? 'no configurado';
                    return "Trade cerrado en {$duration}s (mínimo requerido: {$minDuration}s)";

                case RiskRule::TYPE_VOLUME:
                    $currentVolume = $violationData['current_volume'] ?? $violationData['volume'] ?? 'desconocido';
                    $minExpected = $violationData['min_expected'] ?? $violationData['min_allowed'] ?? 'N/A';
                    $maxExpected = $violationData['max_expected'] ?? $violationData['max_allowed'] ?? 'N/A';
                    return "Volumen {$currentVolume} fuera de rango ({$minExpected} - {$maxExpected})";

                case RiskRule::TYPE_OPEN_TRADES:
                    $currentCount = $violationData['current_count'] ?? $violationData['open_trades_count'] ?? 'desconocida';
                    $timeWindow = $violationData['time_window_minutes'] ?? $rule->time_window_minutes ?? 'desconocido';
                    $minAllowed = $violationData['min_allowed'] ?? $rule->min_open_trades ?? 'sin mínimo';
                    $maxAllowed = $violationData['max_allowed'] ?? $rule->max_open_trades ?? 'sin máximo';

                    if ($maxAllowed !== null && $currentCount > $maxAllowed) {
                        return "Cuenta tiene {$currentCount} trades abiertos en los últimos {$timeWindow} minutos (máximo permitido: {$maxAllowed})";
                    } elseif ($minAllowed !== null && $currentCount < $minAllowed) {
                        return "Cuenta tiene {$currentCount} trades abiertos en los últimos {$timeWindow} minutos (mínimo requerido: {$minAllowed})";
                    } else {
                        return "Violación de regla de trades abiertos: {$currentCount} trades";
                    }

                default:
                    return "Violación de regla: {$rule->name}";
            }
        } catch (\Exception $e) {
            Log::error('Error en generateDescription: ' . $e->getMessage(), [
                'rule_id' => $rule->id,
                'rule_type' => $rule->type,
                'violation_data' => $violationData,
            ]);
            return "Violación detectada - {$rule->name}";
        }
    }
}
