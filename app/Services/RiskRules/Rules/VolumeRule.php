<?php

namespace App\Services\RiskRules\Rules;

use App\Services\RiskRules\RuleInterface;
use App\Models\RiskRule;
use App\Models\Account;
use App\Models\Trade;

class VolumeRule implements RuleInterface
{
    private array $violationData = [];

    public function evaluateForAccount(RiskRule $rule, Account $account): bool
{

    $this->violationData = [];

    // Buscar el último trade cerrado SIN incidente para esta regla
    $latestTrade = $account->trades()
        ->where('status', 'closed')
        ->whereDoesntHave('incidents', function ($query) use ($rule) {
            $query->where('rule_id', $rule->id);
        })
        ->latest('close_time')
        ->first();

    if (!$latestTrade) {
        return false;
    }

    return $this->evaluateSingleTrade($latestTrade, $rule, $account);
}

public function evaluateForTrade(RiskRule $rule, Trade $trade, Account $account): bool
{

    $this->violationData = [];

    // Verificar si YA existe incidente para este trade
    if ($trade->incidents()->where('rule_id', $rule->id)->exists()) {
        return false;
    }

    return $this->evaluateSingleTrade($trade, $rule, $account);
}

    private function evaluateSingleTrade(Trade $trade, RiskRule $rule, Account $account): bool
    {
        // Obtener trades históricos
        $historicalTrades = $account->trades()
            ->where('status', 'closed')
            ->where('id', '!=', $trade->id)
            ->orderBy('close_time', 'desc')
            ->limit($rule->lookback_trades)
            ->get();

        if ($historicalTrades->isEmpty()) {
            return false;
        }

        $averageVolume = $historicalTrades->avg('volume');
        $minAllowed = $averageVolume * $rule->min_factor;
        $maxAllowed = $averageVolume * $rule->max_factor;
        $currentVolume = (float) $trade->volume;

        if ($currentVolume < $minAllowed || $currentVolume > $maxAllowed) {
            $this->violationData = [
                'current_volume' => $currentVolume,
                'average_volume' => $averageVolume,
                'min_expected' => $minAllowed,
                'max_expected' => $maxAllowed,
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
