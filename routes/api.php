<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\TradeController;
use App\Http\Controllers\Api\RiskRuleController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\RuleActionController;
use App\Http\Controllers\Api\RiskEvaluationController;
use App\Http\Middleware\ApiKeyMiddleware;
use Illuminate\Support\Facades\Artisan;

Route::middleware([ApiKeyMiddleware::class])->group(function () {

Route::prefix('v1')->middleware('throttle:120,1')->group(function () {

    // Accounts
    Route::apiResource('accounts', AccountController::class);
    Route::get('/accounts/{account}/risk-status', [AccountController::class, 'riskStatus']);
    Route::post('/accounts/{account}/disable-trading', [AccountController::class, 'disableTrading']);
    Route::post('/accounts/{account}/enable-trading', [AccountController::class, 'enableTrading']);

    // Trades
    Route::apiResource('trades', TradeController::class);
    Route::post('/trades/{trade}/close', [TradeController::class, 'closeTrade']);

    // Risk Rules
    Route::apiResource('rules', RiskRuleController::class);
    Route::get('/rules/types/info', [RiskRuleController::class, 'getTypesInfo']);
    Route::post('/rules/{rule}/toggle-active', [RiskRuleController::class, 'toggleActive']);
    Route::post('/rules/{rule}/actions', [RiskRuleController::class, 'assignActions']);
    Route::delete('/rules/{rule}/actions/{action}', [RiskRuleController::class, 'removeAction']);

    // Incidents
    Route::get('/incidents', [IncidentController::class, 'index']);
    Route::get('/incidents/statistics', [IncidentController::class, 'statistics']);
    Route::get('/accounts/{account}/incidents', [IncidentController::class, 'byAccount']);
    Route::get('/rules/{rule}/incidents', [IncidentController::class, 'byRule']);

    // Actions
    Route::get('/actions/types', [RuleActionController::class, 'getActionTypes']);

});


Route::prefix('v1')->middleware('throttle:100,1')->group(function () {
    Route::post('/evaluate/account/{account}', [RiskEvaluationController::class, 'evaluateAccount']);
    Route::post('/evaluate/all-active', [RiskEvaluationController::class, 'evaluateAllActive']);
    Route::get('/check-notifications', function () {
    $notifications = \App\Models\Notification::with(['incident.account', 'incident.rule'])
        ->latest()
        ->limit(10)
        ->get();

    $stats = [
        'total_notifications' => \App\Models\Notification::count(),
        'by_status' => \App\Models\Notification::groupBy('status')->selectRaw('status, COUNT(*) as count')->get(),
        'by_action_type' => \App\Models\Notification::groupBy('action_type')->selectRaw('action_type, COUNT(*) as count')->get(),
        'recent_notifications' => $notifications,
    ];

    return response()->json($stats);
});
});
    });


