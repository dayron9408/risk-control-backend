<?php

namespace App\Services\RiskRules\Rules;

use App\Services\RiskRules\RuleInterface;
use App\Models\RiskRule;
use App\Models\Account;
use App\Models\Trade;

class DurationRule implements RuleInterface
{
    private array $violationData = [];

    public function evaluateForAccount(RiskRule $rule, Account $account): bool
    {
        $this->violationData = [];

        $recentClosedTrades = $account->trades()
            ->where('status', 'closed')
            ->where('close_time', '>=', now()->subHours(24))
            ->whereDoesntHave('incidents', function ($query) use ($rule) {
                $query->where('rule_id', $rule->id);
            })
            ->get();

        foreach ($recentClosedTrades as $trade) {
            $duration = $trade->getDurationInSeconds();

            if ($duration !== null && $duration < $rule->min_duration_seconds) {

                $this->violationData = [
                    'duration_seconds' => $duration,
                    'min_duration_seconds' => $rule->min_duration_seconds,
                    'trade_id' => $trade->id,
                    'account_id' => $account->id,
                ];
                return true;
            }
        }

        return false;
    }

    public function evaluateForTrade(RiskRule $rule, Trade $trade, Account $account): bool
    {
        $this->violationData = [];

        // Verificar si YA existe incidente para este trade
    $existingIncident = $trade->incidents()
        ->where('rule_id', $rule->id)
        ->exists();

    if ($existingIncident) {
        return false;
    }

        $duration = $trade->getDurationInSeconds();

        if ($duration === null) {
            return false;
        }

        if ($duration < $rule->min_duration_seconds) {
            $this->violationData = [
                'duration_seconds' => $duration,
                'min_duration_seconds' => $rule->min_duration_seconds,
                'trade_id' => $trade->id,
                'account_id' => $account->id,
            ];
            return true;
        }

        return false;
    }

    public function getViolationData(): array
    {
        return $this->violationData;
    }
}
