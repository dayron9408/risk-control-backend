<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Incident",
 *     type="object",
 *     title="Incidencia",
 *     description="Registro de violación de una regla de riesgo",
 *     required={"rule_id", "account_id", "severity"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="Identificador único",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="rule_id",
 *         type="integer",
 *         description="ID de la regla violada",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="account_id",
 *         type="integer",
 *         description="ID de la cuenta involucrada",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="trade_id",
 *         type="integer",
 *         description="ID del trade relacionado (si aplica)",
 *         example=1,
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="severity",
 *         type="string",
 *         enum={"HARD", "SOFT"},
 *         description="Severidad del incidente",
 *         example="HARD"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="Descripción del incidente",
 *         example="Trade cerrado en menos de 60 segundos"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Fecha y hora de creación del incidente"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time"
 *     )
 * )
 */
class Incident extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_id',
        'account_id',
        'trade_id',
        'severity',
        'description',
    ];

    protected $casts = [
        'rule_id' => 'integer',
        'account_id' => 'integer',
        'trade_id' => 'integer',
        'severity' => 'string',
    ];

    /**
     * Relación: Un incidente pertenece a una regla
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(RiskRule::class, 'rule_id', 'id');
    }

    /**
     * Relación: Un incidente pertenece a una cuenta
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Relación: Un incidente puede tener un trade asociado
     */
    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    /**
     * Relación: Un incidente tiene logs de acciones (SI creas tabla notifications)
     * NOTA: Esto depende de si creas la tabla notifications o usas otra solución
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Contar incidentes recientes por cuenta y regla
     * Para lógica de SOFT rules (incidents_before_action)
     */
    public static function countRecentIncidents(int $accountId, int $ruleId, ?string $timeWindow = '24 hours'): int
    {
        return self::where('account_id', $accountId)
            ->where('rule_id', $ruleId)
            ->where('created_at', '>=', now()->sub($timeWindow))
            ->count();
    }

    /**
     * Obtener incidentes por cuenta con filtros (para API)
     */
    public static function getByAccount(int $accountId, array $filters = [])
    {
        $query = self::where('account_id', $accountId)
            ->with(['rule', 'trade']);

        if (isset($filters['rule_id'])) {
            $query->where('rule_id', $filters['rule_id']);
        }

        if (isset($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        if (isset($filters['trade_id'])) {
            $query->where('trade_id', $filters['trade_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Verificar si se debe ejecutar acción (para SOFT rules)
     */
    public function shouldExecuteAction(): bool
    {
        if ($this->rule->severity === 'HARD') {
            return true; // Hard rules ejecutan inmediatamente
        }

        // Soft rules: contar incidentes recientes
        $recentCount = self::countRecentIncidents(
            $this->account_id,
            $this->rule_id,
            '24 hours'
        );

        return $recentCount >= $this->rule->incidents_before_action;
    }

    /**
     * Obtener descripción formateada
     */
    public function getFormattedDescription(): string
    {
        $account = $this->account->login ?? 'Unknown';
        $rule = $this->rule->name ?? 'Unknown Rule';

        return "[{$this->created_at->format('Y-m-d H:i:s')}] Account {$account} - {$rule}: {$this->description}";
    }
}
