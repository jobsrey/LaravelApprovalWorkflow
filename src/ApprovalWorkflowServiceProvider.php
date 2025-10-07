<?php

namespace AsetKita\LaravelApprovalWorkflow;

use Illuminate\Support\ServiceProvider;
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

class ApprovalWorkflowServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/approval-workflow.php',
            'approval-workflow'
        );

        $this->app->singleton('approval-workflow', function ($app) {
            $companyId = config('approval-workflow.default_company_id', 1);
            return new ApprovalHandler($companyId);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/approval-workflow.php' => config_path('approval-workflow.php'),
        ], 'approval-workflow-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'approval-workflow-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
