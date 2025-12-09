<?php

namespace App\Services\RiskRules\Rules;

use App\Services\RiskRules\RuleInterface;
use App\Models\RiskRule;
use App\Models\Account;
use App\Models\Trade;
use App\Models\Incident;

class OpenTradesRule implements RuleInterface
{
    private array $violationData = [];

    public function evaluateForAccount(RiskRule $rule, Account $account): bool
    {

        $this->violationData = [];

        // Para OpenTradesRule, verificamos incidentes por CUENTA (no por trade)
        // Solo verificamos incidentes creados en la misma ventana de tiempo
        $recentIncident = Incident::where('account_id', $account->id)
            ->where('rule_id', $rule->id)
            ->where('created_at', '>=', now()->subMinutes($rule->time_window_minutes))
            ->exists();

        if ($recentIncident) {

            return false;
        }

        return $this->checkOpenTradesCount($rule, $account);
    }

    public function evaluateForTrade(RiskRule $rule, Trade $trade, Account $account): bool
    {

        $this->violationData = [];
        return $this->evaluateForAccount($rule, $account);
    }

    private function checkOpenTradesCount(RiskRule $rule, Account $account): bool
    {
        $timeWindow = now()->subMinutes($rule->time_window_minutes);
        $openTradesCount = $account->trades()
            ->where('status', 'open')
            ->where('open_time', '>=', $timeWindow)
            ->count();

        $violated = false;

        if ($rule->max_open_trades !== null && $openTradesCount > $rule->max_open_trades) {
            $violated = true;
        }

        if ($rule->min_open_trades !== null && $openTradesCount < $rule->min_open_trades) {
            $violated = true;
        }

        if ($violated) {
            $this->violationData = [
                'current_count' => $openTradesCount,
                'time_window_minutes' => $rule->time_window_minutes,
                'min_allowed' => $rule->min_open_trades,
                'max_allowed' => $rule->max_open_trades,
                'account_id' => $account->id,
            ];
        }

        return $violated;
    }

    public function getViolationData(): array
    {
        return $this->violationData;
    }
}
