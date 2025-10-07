<?php

namespace AsetKita\LaravelApprovalWorkflow;

use Illuminate\Support\ServiceProvider;
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalService;

class ApprovalWorkflowServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/approval-workflow.php', 'approval-workflow'
        );

        $this->app->singleton(ApprovalService::class, function ($app) {
            return new ApprovalService();
        });

        $this->app->alias(ApprovalService::class, 'approval-workflow');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/approval-workflow.php' => config_path('approval-workflow.php'),
            ], 'approval-workflow-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'approval-workflow-migrations');
        }
    }
}
