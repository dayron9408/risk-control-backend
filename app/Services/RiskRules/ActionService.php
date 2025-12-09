<?php

namespace App\Services\RiskRules;

use App\Models\Incident;
use App\Models\IncidentLog;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class ActionService
{
    public function execute(string $actionType, Incident $incident): bool
    {
        try {
            $methodName = 'execute' . ucfirst(strtolower($actionType));

            if (method_exists($this, $methodName)) {
                return $this->$methodName($incident);
            }

            Log::warning("Action type not implemented: {$actionType}");
            return false;

        } catch (\Exception $e) {
            $this->logAction($incident, $actionType, 'FAILED', $e->getMessage());
            return false;
        }
    }

    /**
     * Ejecutar acción: Enviar email (mock)
     */
    private function executeEmail(Incident $incident): bool
    {
        // Mock: escribir en log
        Log::channel('risk_actions')->info("EMAIL sent for incident {$incident->id}", [
            'incident_id' => $incident->id,
            'account_id' => $incident->account_id,
            'rule_id' => $incident->rule_id,
            'description' => $incident->description,
        ]);

        $this->logAction($incident, 'EMAIL', 'EXECUTED', 'Mock email sent to logs');
        return true;
    }

    /**
     * Ejecutar acción: Enviar a Slack (mock)
     */
    private function executeSlack(Incident $incident): bool
    {
        // Mock: escribir en log
        Log::channel('risk_actions')->info("SLACK notification for incident {$incident->id}", [
            'incident_id' => $incident->id,
            'account_id' => $incident->account_id,
            'rule_id' => $incident->rule_id,
            'severity' => $incident->severity,
        ]);

        $this->logAction($incident, 'SLACK', 'EXECUTED', 'Mock Slack notification sent to logs');
        return true;
    }

    /**
     * Ejecutar acción: Deshabilitar cuenta
     */
    private function executeDisableAccount(Incident $incident): bool
    {
        $account = $incident->account;
        $account->disableAccount();

        Log::channel('risk_actions')->warning("ACCOUNT DISABLED for incident {$incident->id}", [
            'incident_id' => $incident->id,
            'account_id' => $account->id,
            'login' => $account->login,
            'rule_id' => $incident->rule_id,
        ]);

        $this->logAction($incident, 'DISABLE_ACCOUNT', 'EXECUTED', "Account {$account->login} disabled");
        return true;
    }

    /**
     * Ejecutar acción: Deshabilitar trading
     */
    private function executeDisableTrading(Incident $incident): bool
    {
        $account = $incident->account;
        $account->disableTrading();

        Log::channel('risk_actions')->warning("TRADING DISABLED for incident {$incident->id}", [
            'incident_id' => $incident->id,
            'account_id' => $account->id,
            'login' => $account->login,
            'rule_id' => $incident->rule_id,
        ]);

        $this->logAction($incident, 'DISABLE_TRADING', 'EXECUTED', "Trading disabled for account {$account->login}");
        return true;
    }

    /**
     * Registrar acción en la base de datos
     */
    private function logAction(Incident $incident, string $actionType, string $status, string $details): void
    {
        Notification::create([
            'incident_id' => $incident->id,
            'action_type' => $actionType,
            'status' => $status,
            'details' => $details,
            'executed_at' => $status === 'EXECUTED' ? now() : null,
        ]);
    }
}
