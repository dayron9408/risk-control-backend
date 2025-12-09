<?php

namespace App\Services\RiskRules;

use App\Models\RiskRule;
use App\Models\Account;
use App\Models\Trade;

interface RuleInterface
{
    /**
     * Evaluar regla para una cuenta (evaluación periódica)
     */
    public function evaluateForAccount(RiskRule $rule, Account $account): bool;

    /**
     * Evaluar regla para un trade (evaluación por evento)
     */
    public function evaluateForTrade(RiskRule $rule, Trade $trade, Account $account): bool;

    /**
     * Obtener datos de la violación
     */
    public function getViolationData(): array;
}
