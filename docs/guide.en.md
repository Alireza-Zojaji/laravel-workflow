# Laravel Workflow — Developer Guide

A general-purpose workflow engine for Laravel with task assignment, automatic transitions, and an admin UI. This guide covers setup, UI, APIs, fluent builders, services, models, strategies, and extension points.

## Overview
- Core features: definitions, states, transitions, tasks, automatic triggers, guards/actions, assignment strategies, history.
- Admin UI: create and edit workflows, define places and transitions, configure assignment (single/parallel), triggers, and decision mode.
- JSON APIs: manage definitions, instances, tasks, and lookups.
- Fluent API: `Workflow` facade with `InstanceBuilder` and `TaskBuilder`.
- Extensible: register guard/action providers and assignment strategies via configuration.

## Installation
- Require the package: `composer require zojaji/laravel-workflow`
- Publish assets:
  - `php artisan vendor:publish --tag=workflow-config`
  - `php artisan vendor:publish --tag=workflow-migrations`
  - `php artisan vendor:publish --tag=workflow-views`
- Run migrations: `php artisan migrate`

## Dependencies
- `spatie/laravel-permission` is used for roles and user-role relations.
- Add `Spatie\Permission\Traits\HasRoles` to your `User` model.

## Configuration
- File: `config/workflow.php`
  - `models.role`: role model class
  - `models.user`: user model class
  - `database.connection`: connection name
  - `database.tables.*`: table names
  - `events.enabled`, `events.queue`
- File: `config/workflow_registry.php`
  - `assignment_strategies`: map of strategy keys to handlers
  - `condition_providers`: guard providers
  - `actions`: action providers
  - `calendars`, `message_channels`: reserved keys for advanced features

## Routes and UI
- API prefix: `/api/workflow/*`
- Admin UI prefix: `/admin/workflows/*`
- Admin pages:
  - Index: list DB definitions and read-only config definitions
  - Create: add name, label, marking store, places, transitions
  - Edit: update places/transitions and activation state
- UI capabilities:
  - Places: add/remove
  - Transitions: define name, from/to, guard provider, trigger (`manual|automatic`), decision mode (`none|approve|reject`)
  - Assignment:
    - Mode: `single` or `parallel`
    - Single: direct user, role claim, role direct user (runtime selection), least busy, round robin, random
    - Parallel: N slots, each slot configured independently (user or role with method)
  - Lookups: roles and users fetched via API

## JSON APIs
- Lookups:
  - `GET /api/workflow/lookups/users?role=...&role_id=...`
  - `GET /api/workflow/lookups/roles`
  - `GET /api/workflow/lookups/strategies`
- Definitions:
  - `GET /api/workflow/definitions`
  - `GET /api/workflow/definitions/{id}`
  - `POST /api/workflow/definitions`
  - `PUT /api/workflow/definitions/{id}`
  - `DELETE /api/workflow/definitions/{id}`
  - `GET /api/workflow/config-definitions`
  - `GET /api/workflow/config-definitions/{key}`
- Tasks:
  - `GET /api/workflow/tasks/inbox`
  - `GET /api/workflow/tasks/claimables`
  - `POST /api/workflow/tasks/{taskId}/claim`
  - `POST /api/workflow/tasks`
  - `POST /api/workflow/tasks/{taskId}/complete`
  - `POST /api/workflow/tasks/finalize`
- Instances:
  - `GET /api/workflow/instances`
  - `GET /api/workflow/instances/{id}`
  - `POST /api/workflow/instances`
  - `POST /api/workflow/instances/{id}/run-automatic`

### API Examples
- Create a task
```
POST /api/workflow/tasks
Content-Type: application/json

{
  "task_name": "Review Document",
  "instance_id": 555,
  "state_id": 7,
  "assignment_type": "strategy",
  "assignment_ref": 3,
  "strategy_key": "least_busy",
  "decision_options": {}
}
```
- Complete and auto-route
```
POST /api/workflow/tasks/123/complete
Content-Type: application/json

{
  "user_id": 42,
  "context": { "priority": 5 }
}
```
- Finalize direct user selection
```
POST /api/workflow/tasks/finalize
Content-Type: application/json

{
  "instance_id": 555,
  "state_id": 7,
  "task_name": "Approve Request",
  "user_id": 42,
  "role_id": 3,
  "decision_options": { "assignment_method": "role_direct_user" }
}
```

## Facade and Fluent Builders
- Facade: `Zojaji\Workflow\Facades\Workflow`
  - `Workflow::instance(int $instanceId): InstanceBuilder`
  - `Workflow::onTask(int $taskId, ?int $userId = null): TaskBuilder`
  - `Workflow::tasks(): \Illuminate\Database\Eloquent\Builder`
  - `Workflow::history(int $instanceId): \Illuminate\Database\Eloquent\Builder`
  - `Workflow::completeAndRoute(int $taskId, int $userId, array $context = []): array`
  - `Workflow::finalizeSelection(int $instanceId, ?int $stateId, string $taskName, int $userId, ?int $roleId = null, array $decisionOptions = []): array`

### InstanceBuilder
- `autoRun(array $context = []): array`
- `getInstance(): WorkflowInstance`
- Runs automatic transitions from current place and returns per-transition results.

### TaskBuilder
- `withUser(int $userId): self`
- `complete(array $metadata = []): self`
- `advance(array $context = []): self`
- `autoAssign(array $context = []): array`
- `completeAndAutoRoute(array $context = []): array`
- `getTask(): WorkflowTask`

### Usage
```php
use Zojaji\Workflow\Facades\Workflow;

// Complete a task and route automatically
$result = Workflow::completeAndRoute($taskId, $userId, ['priority' => 3]);

// Explicit chain
$items = Workflow::onTask($taskId, $userId)
  ->complete(['note' => 'done'])
  ->advance(['decision' => 'approve'])
  ->autoAssign();

// Run automatic transitions for an instance
$auto = Workflow::instance($instanceId)->autoRun();
```

## Services and Contracts
### WorkflowEngine
- `decideAndAssign(array $context = []): array`
- Context:
  - `guard` (string provider key), `instance_id`, `state_id`, `task_name`
  - `assignmentType` (`user|role|strategy`), `assignmentRef` (user/role id), `strategyKey`
  - `decision_options` (UI options)
- Output: `canProceed`, `createdTaskId`, `assigneeId`, `requiresUserSelection`

### AutomaticTriggerRunner
- `runForDefinition(WorkflowDefinition $definition, string $currentPlace, array $context = []): array`
- Executes transitions with `trigger.type = "automatic"` from `currentPlace`
- Supports single and parallel assignment modes

### DecisionEngine
- `evaluate(?string $guardProvider, array $context = []): bool`
- `executeAction(?string $actionProvider, array $context = []): void`
- Providers are loaded from `config/workflow_registry.php` and can be extended in application code

### TaskAssigner
- `assign(?string $assignmentType, ?string $assignmentRef, ?string $strategyKey, array $context = []): ?string`
- Modes: direct user, role claim, strategy-based selection
- Strategies: `least_busy`, `round_robin`, `random` (default set)

## Models
- `WorkflowDefinition`: `name`, `label`, `description`, `marking_store`, `places[]`, `transitions[]`, `schema`, `version`, `is_active`
- `WorkflowState`: `definition_id`, `key`, `label`, `type (initial|normal|final)`, `metadata`
- `WorkflowInstance`: `definition_id`, `current_state_id`, `status`, `model_type`, `model_id`, `variables`, `started_at`, `completed_at`
- `WorkflowTask`: `instance_id`, `state_id`, `name`, `assigned_to`, `assignment_type`, `assignment_ref`, `strategy_key`, `due_at`, `status`, `metadata`, `decision_options`
- `WorkflowHistory`: `instance_id`, `transition_id`, `from_state_id`, `to_state_id`, `performed_by`, `metadata`

## Assignment Strategies
- Built-in strategy keys:
  - `least_busy`: select least busy member
  - `round_robin`: cyclic distribution
  - `random`: random member
- Extend by adding to `workflow_registry.assignment_strategies` with a class implementing `AssignmentStrategyInterface` or a callable.

## Conditional Routing
- In transitions, set `conditional` to route by a decision key:
```json
{
  "name": "Review Decision",
  "from": "reviewing",
  "to": ["approved", "rejected"],
  "conditional": {
    "key": "decision",
    "routes": [
      { "value": "approve", "to": "approved" },
      { "value": "reject",  "to": "rejected" }
    ]
  }
}
```
- Provide `context.decision` when completing a task.

## Database Schema
- Tables: `workflow_definitions`, `workflow_states`, `workflow_transitions`, `workflow_instances`, `workflow_tasks`, `workflow_history`
- Migrations are published under the `workflow-migrations` tag.

## Extension Points
- Guard providers: implement business rules receiving context (`instance_id`, `state_id`, `user_id`, `transition`, `definition_id`, `current_place`, ...)
- Action providers: side-effects executed after task creation decision
- Assignment strategies: custom selection logic; optionally use role-based pools

## Security Notes
- Do not expose secrets in guard/action providers
- Ensure authorization for admin routes and APIs according to your application policies
- Validate role membership when finalizing direct user selection

## Examples
- Register custom providers and strategies in your application service provider
```php
// App\Providers\AppServiceProvider::register()
$this->app->extend(\Zojaji\Workflow\Contracts\DecisionEngineInterface::class, function ($engine, $app) {
  $base = array_merge((array) config('workflow_registry.condition_providers', []), (array) config('workflow_registry.actions', []));
  $custom = [
    'isHighPriority' => fn(array $ctx) => (int)($ctx['priority'] ?? 0) >= 5,
    'notify' => function (array $ctx) { \Log::info('workflow.notify', $ctx); return true; },
  ];
  return new \Zojaji\Workflow\Services\DecisionEngine(array_merge($base, $custom));
});

$this->app->extend(\Zojaji\Workflow\Contracts\TaskAssignerInterface::class, function ($assigner, $app) {
  return new \Zojaji\Workflow\Services\TaskAssigner([
    'least_busy' => \Zojaji\Workflow\Assignment\LeastBusyStrategy::class,
    'round_robin' => \Zojaji\Workflow\Assignment\RoundRobinStrategy::class,
    'random' => \Zojaji\Workflow\Assignment\RandomStrategy::class,
  ]);
});
```

---

For quick references to developer APIs, also see `docs/developer-tools.en.md`.
