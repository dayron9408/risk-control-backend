<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Artisan;

// Evaluación periódica de riesgo cada 5 minutos
Schedule::call(function () {
    try {
        $evaluator = app()->make(\App\Services\RiskRules\RuleEvaluatorService::class);
        $results = $evaluator->evaluateAllActiveAccounts();

        logger()->info('✅ Evaluación periódica de riesgo completada', [
            'timestamp' => now()->toISOString(),
            'total_accounts' => count(array_unique(array_column($results, 'account_id'))),
            'violations_found' => count(array_filter($results, fn($r) => $r['violated'])),
        ]);
    } catch (\Exception $e) {
        logger()->error('Error en evaluación periódica: ' . $e->getMessage());
    }
})->dailyAt('03:00')->name('risk-evaluation')->withoutOverlapping();

// Comando para evaluación manual
Artisan::command('risk:evaluate', function () {
    $this->info('Iniciando evaluación de reglas de riesgo...');

    $evaluator = app()->make(\App\Services\RiskRules\RuleEvaluatorService::class);
    $results = $evaluator->evaluateAllActiveAccounts();

    $violations = count(array_filter($results, fn($r) => $r['violated']));

    $this->info("Evaluación completada. Violaciones encontradas: {$violations}");

    if ($violations > 0) {
        $this->table(
            ['Account ID', 'Rule', 'Severity', 'Acción'],
            array_map(function($r) {
                return [
                    $r['account_id'] ?? 'N/A',
                    $r['rule_id'] ?? 'N/A',
                    $r['severity'] ?? 'N/A',
                    isset($r['action_executed']) && $r['action_executed'] ? 'Ejecutada' : 'Pendiente',
                ];
            }, array_filter($results, fn($r) => $r['violated']))
        );
    }

    return 0;
})->purpose('Evaluar manualmente todas las reglas de riesgo');

