<?php

namespace Amir\Workflow;

use Illuminate\Support\ServiceProvider;
use Amir\Workflow\Contracts\WorkflowEngineInterface;
use Amir\Workflow\Services\WorkflowEngine;
use Amir\Workflow\Contracts\TaskAssignerInterface;
use Amir\Workflow\Contracts\DecisionEngineInterface;
use Amir\Workflow\Services\TaskAssigner;
use Amir\Workflow\Services\DecisionEngine;
use Amir\Workflow\Services\AutomaticTriggerRunner;
use Amir\Workflow\Services\WorkflowManager;

class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/workflow.php', 'workflow');
        $this->mergeConfigFrom(__DIR__ . '/../config/workflow_registry.php', 'workflow_registry');

        $this->app->singleton(TaskAssignerInterface::class, function ($app) {
            return new TaskAssigner(config('workflow_registry.assignment_strategies', []));
        });

        $this->app->singleton(DecisionEngineInterface::class, function ($app) {
            $providers = array_merge(
                (array) config('workflow_registry.condition_providers', []),
                (array) config('workflow_registry.actions', [])
            );
            return new DecisionEngine($providers);
        });

        $this->app->singleton(WorkflowEngineInterface::class, function ($app) {
            return new WorkflowEngine(
                $app->make(DecisionEngineInterface::class),
                $app->make(TaskAssignerInterface::class),
            );
        });

        // Generic runner for automatic transitions
        $this->app->singleton(AutomaticTriggerRunner::class, function ($app) {
            return new AutomaticTriggerRunner(
                $app->make(DecisionEngineInterface::class),
                $app->make(WorkflowEngineInterface::class),
            );
        });

        // Facade accessor binding for fluent DSL
        $this->app->singleton('workflow', function ($app) {
            return new WorkflowManager(
                $app->make(AutomaticTriggerRunner::class),
                $app->make(DecisionEngineInterface::class),
                $app->make(WorkflowEngineInterface::class),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/workflow.php' => config_path('workflow.php'),
            __DIR__ . '/../config/workflow_registry.php' => config_path('workflow_registry.php'),
        ], 'workflow-config');

        // Publish migrations only if the migrations directory exists
        if (is_dir(__DIR__ . '/../database/migrations')) {
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'workflow-migrations');
        }

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/workflow'),
        ], 'workflow-views');

        // API routes (JSON endpoints)
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Web routes (Blade pages)
        if (is_file(__DIR__ . '/../routes/web.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'workflow');
    }
}
