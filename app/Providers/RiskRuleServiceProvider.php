<?php

namespace App\Providers;

use App\Services\RiskRules\RuleEvaluatorService;
use Illuminate\Support\ServiceProvider;
use App\Services\RiskRules\Rules\DurationRule;
use App\Services\RiskRules\Rules\VolumeRule;
use App\Services\RiskRules\Rules\OpenTradesRule;

class RiskRuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DurationRule::class);
        $this->app->singleton(VolumeRule::class);
        $this->app->singleton(OpenTradesRule::class);

        $this->app->singleton(RuleEvaluatorService::class, function ($app) {
            return new RuleEvaluatorService(
                $app->make(DurationRule::class),
                $app->make(VolumeRule::class),
                $app->make(OpenTradesRule::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
