# Laravel Workflow Developer Tools Guide

This document explains the developer-facing tools and APIs provided by the `laravel-workflow` package: what each component does, how to use it, inputs/outputs, and practical examples. Use this as a concise reference to build workflows, manage tasks, and safely advance and assign work.

## Facade: `Workflow`

A fluent entrypoint to interact with workflow instances and tasks.

- Methods:
  - `Workflow::instance(int $instanceId): InstanceBuilder`
  - `Workflow::onTask(int $taskId, ?int $userId = null): TaskBuilder`
  - `Workflow::tasks(): \Illuminate\Database\Eloquent\Builder`
  - `Workflow::history(int $instanceId): \Illuminate\Database\Eloquent\Builder`
  - `Workflow::completeAndRoute(int $taskId, int $userId, array $context = []): array`
  - `Workflow::finalizeSelection(int $instanceId, ?int $stateId, string $taskName, int $userId, ?int $roleId = null, array $decisionOptions = []): array`

- Outputs:
  - Query methods (`tasks`, `history`) return Eloquent builders.
  - `completeAndRoute` returns the automatic routing result in array form.
  - `finalizeSelection` returns the result for direct user assignment.

- Usage examples:

```php
use Amir\Workflow\Facades\Workflow;

// Complete a task and automatically route
$result = Workflow::completeAndRoute($taskId, $userId, [
    'priority' => 5,
]);

// Finalize direct user choice (role_direct_user)
$res2 = Workflow::finalizeSelection($instanceId, $stateId, 'Approve Request', $chosenUserId, $roleId, [
    'assignment_method' => 'role_direct_user',
]);
```

## Builder: `InstanceBuilder`

Fluent builder for operations on a workflow instance.

- Methods:
  - `autoRun(array $context = []): array` runs automatic transitions from the instance’s current place.
  - `getInstance(): WorkflowInstance` returns the underlying instance.

- Input for `autoRun`:
  - Optional context; `instance_id` and `state_id` are set automatically.

- Output for `autoRun`:
  - Array with keys:
    - `definition_id`, `current_place`
    - `items`: per-transition results including `transition`, `status`, `mode`, `createdTaskId|createdTaskIds`, `requiresUserSelection`.

- Example:

```php
use Amir\Workflow\Facades\Workflow;

$r = Workflow::instance($instanceId)->autoRun();
```

## Builder: `TaskBuilder`

Fluent builder targeting a specific task.

- Methods:
  - `withUser(int $userId): self` sets the acting user for logs/context.
  - `complete(array $metadata = []): self` marks the task as completed.
  - `advance(array $context = []): self` advances the instance subject to guards and records history.
  - `autoAssign(array $context = []): array` after advance, assigns tasks for the next place automatically.
  - `completeAndAutoRoute(array $context = []): array` a convenience combining complete → advance → autoAssign.
  - `getTask(): WorkflowTask` exposes the task instance.

- Inputs:
  - `withUser`: acting user ID.
  - `advance/autoAssign/completeAndAutoRoute`: optional context used by guards/actions.

- Outputs:
  - `autoAssign/completeAndAutoRoute`: array of `AutomaticTriggerRunner` items.

- Example:

```php
use Amir\Workflow\Facades\Workflow;

$result = Workflow::onTask($taskId, $userId)
    ->completeAndAutoRoute(['priority' => 3]);
```

Note: If guards deny advancement (the place does not change), `completeAndAutoRoute` will not recreate tasks of the current place; it returns a summary with `items` as an empty array.

## Service: `WorkflowEngine`

Responsible for guard evaluation and task creation/assignment.

- Method:
  - `decideAndAssign(array $context = []): array`

- Context fields:
  - `guard`: optional guard provider key.
  - `assignmentType`: one of `user|role|strategy`.
  - `assignmentRef`: user ID or role ID (depending on type).
  - `strategyKey`: assignment strategy (`least_busy|round_robin|random|...`).
  - `decision_options`: optional object for decision/UI options.
  - `instance_id`, `state_id`, `task_name`.

- Output:
  - `canProceed`: whether the guard allowed proceeding.
  - `createdTaskId`: ID of the created task (or `null`).
  - `assigneeId`: chosen user ID (for strategy/role modes), or `null`.
  - `requiresUserSelection`: when `role_direct_user` requires selecting a user at runtime.

- Example:

```php
use Amir\Workflow\Contracts\WorkflowEngineInterface;

$engine = app(WorkflowEngineInterface::class);
$result = $engine->decideAndAssign([
    'instance_id' => $instanceId,
    'state_id' => $stateId,
    'task_name' => 'Review Document',
    'assignmentType' => 'strategy',
    'assignmentRef' => $roleId,
    'strategyKey' => 'least_busy',
]);
```

## Service: `AutomaticTriggerRunner`

Executes automatic transitions and creates tasks (single or parallel modes).

- Method:
  - `runForDefinition(WorkflowDefinition $definition, string $currentPlace, array $context = []): array`

- Inputs:
  - `definition`: the workflow definition (contains `transitions`).
  - `currentPlace`: key of the current place.
  - `context`: typically contains `instance_id`, `state_id`, `user_id`, `role_id`, `variables`.
  - Only transitions with `trigger.type = "automatic"` originating from `currentPlace` are considered.

- Decision options (`decision_options`):
  - Single mode:
    - `assignment_method`: one of `role_claim`, `role_direct_user`, `role_least_busy`, `role_round_robin`, `role_random`, `user`.
  - Parallel mode:
    - `parallel.slots[]`: array of slots with:
      - `assignment_method`: as above.
      - `role_id` or `user_id`.
    - Each slot creates a separate task.

- Output:
  - Array with items for each executed transition: `transition`, `status` (`completed|skipped`), `mode` (`single|parallel`), and the created task IDs.

- Parallel example:

```php
$ctx = ['instance_id' => $instanceId, 'state_id' => $stateId];
$res = app(\Amir\Workflow\Services\AutomaticTriggerRunner::class)
    ->runForDefinition($definition, 'reviewing', $ctx);
```

## Service: `DecisionEngine`

Evaluates guards and executes actions using registry providers.

- Methods:
  - `evaluate(?string $guardProvider, array $context = []): bool`
  - `executeAction(?string $actionProvider, array $context = []): void`

- Provider registration:
  - In `WorkflowServiceProvider`, keys for `condition_providers` and `actions` are loaded from `config/workflow_registry.php`.
  - You can extend them in your `AppServiceProvider`.

- Built-in custom guard (project-level): `allParallelSlotsCompleted`
  - Returns true only when no `open` or `in_progress` tasks remain for the current instance and place.
  - Usage: set `guard_provider` on the transition to `allParallelSlotsCompleted`.

- Extending providers (example):

```php
// App\Providers\AppServiceProvider::register()
$this->app->extend(DecisionEngineInterface::class, function ($engine, $app) {
    $baseProviders = array_merge(
        (array) config('workflow_registry.condition_providers', []),
        (array) config('workflow_registry.actions', [])
    );

    $customProviders = [
        'isHighPriority' => fn(array $ctx) => (int)($ctx['priority'] ?? 0) >= 5,
        'allParallelSlotsCompleted' => function (array $ctx): bool {
            // See project implementation for actual task status checks
            return true; // placeholder
        },
    ];

    return new \Amir\Workflow\Services\DecisionEngine(array_merge($baseProviders, $customProviders));
});
```

## Service: `TaskAssigner`

Determines the assignee for a task.

- Internal signature:
  - `assign(string $assignmentType, $assignmentRef, ?string $strategyKey, array $context = []): ?string`

- Modes:
  - `assignmentType = 'user'`: direct assignment to a user.
  - `assignmentType = 'role'`: task is claimable by members of a role.
  - `assignmentType = 'strategy'`: select a user from role members using a strategy (`least_busy|round_robin|random`).

- Extending strategies:

```php
// App\Providers\AppServiceProvider::register()
$this->app->extend(TaskAssignerInterface::class, function ($assigner, $app) {
    return new \Amir\Workflow\Services\TaskAssigner([
        'least_busy' => function (array $ctx) { /*...*/ },
        'round_robin' => function (array $ctx) { /*...*/ },
        'random' => function (array $ctx) { /*...*/ },
    ]);
});
```

## APIs (JSON)

Prefix: `/api/workflow`

- Lookups:
  - `GET /lookups/users?role=...&role_id=...` lists users (optional role filter).
  - `GET /lookups/roles` lists roles.
  - `GET /lookups/strategies` lists configured assignment strategies.

- Definitions:
  - `GET /definitions`, `GET /definitions/{id}`
  - `POST /definitions`, `PUT /definitions/{id}`, `DELETE /definitions/{id}`
  - `GET /config-definitions`, `GET /config-definitions/{key}`

- Tasks:
  - `GET /tasks/inbox` user inbox tasks.
  - `GET /tasks/claimables` role-claimable tasks for user.
  - `POST /tasks/{taskId}/claim` claim a role-assigned task.
  - `POST /tasks` create a new task using `WorkflowEngine`.
  - `POST /tasks/{taskId}/complete` complete and auto-route.
  - `POST /tasks/finalize` finalize direct user selection.

- Instances:
  - `GET /instances`, `GET /instances/{id}`
  - `POST /instances` create a new instance (initial state selected).
  - `POST /instances/{id}/run-automatic` run automatic transitions from the current place.

### API Examples

Create a task:

```http
POST /api/workflow/tasks
Content-Type: application/json

{
  "task_name": "Review Document",
  "instance_id": 555,
  "state_id": 7,
  "assignment_type": "strategy",
  "assignment_ref": 3,
  "strategy_key": "least_busy",
  "decision_options": { }
}
```

Complete and auto-route:

```http
POST /api/workflow/tasks/123/complete
Content-Type: application/json

{
  "user_id": 42,
  "context": { "priority": 5 }
}
```

Finalize direct user selection:

```http
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

## Configuration and DSL

- `config/workflow_registry.php`: registers providers, strategies, and registry settings.

```php
return [
  'assignment_strategies' => [
    'role.round_robin' => Amir\Workflow\Assignment\RoundRobinStrategy::class,
    'role.least_busy'  => Amir\Workflow\Assignment\LeastBusyStrategy::class,
    'role.random'      => Amir\Workflow\Assignment\RandomStrategy::class,

    // Backward-compatible keys
    'round_robin' => Amir\Workflow\Assignment\RoundRobinStrategy::class,
    'least_busy'  => Amir\Workflow\Assignment\LeastBusyStrategy::class,
  ],
  'condition_providers' => [
    'isHighPriority' => fn(array $ctx) => (int)($ctx['priority'] ?? 0) >= 5,
    'notify' => function (array $ctx) { \Log::info('workflow.notify', $ctx); return true; },
  ],
];
```

- `config/workflow.php`: database tables and event settings.

```php
return [
  'database' => [
    'connection' => env('DB_WORKFLOW_CONNECTION', env('DB_CONNECTION', 'mysql')),
    'tables' => [
      'definitions' => 'workflow_definitions',
      'states' => 'workflow_states',
      'transitions' => 'workflow_transitions',
      'versions' => 'workflow_versions',
      'instances' => 'workflow_instances',
      'tasks' => 'workflow_tasks',
      'history' => 'workflow_history',
      'timers' => 'workflow_timers',
      'messages' => 'workflow_messages',
      'locks' => 'workflow_locks',
      'subworkflows' => 'workflow_subworkflows',
    ],
  ],
  'events' => [
    'enabled' => true,
    'queue' => env('WORKFLOW_EVENTS_QUEUE', 'default'),
  ],
];
```

- Transition JSON with automatic trigger and parallel assignment:

```json
{
  "name": "Assign Reviewers",
  "from": "appoint_reviewers",
  "to": "reviewing",
  "trigger": { "type": "automatic" },
  "guard_provider": "allParallelSlotsCompleted",
  "decision_options": {
    "assignment_mode": "parallel",
    "parallel": {
      "slots": [
        { "assignment_method": "role_least_busy", "role_id": 3 },
        { "assignment_method": "role_round_robin", "role_id": 3 }
      ]
    }
  }
}
```

When using `role_direct_user` and a user is not provided, `WorkflowEngine` returns `requiresUserSelection` and you should call `POST /api/workflow/tasks/finalize` to assign the chosen user.


### Conditional routing (approve/reject)

- In the admin UI (`index` and `edit` pages), a three-state `Decision Mode` field exists per transition: `none|approve|reject`.
- Selecting `approve` or `reject` stores the transition conditionally and maps the decision to the first selected `To` destination.
- The transition JSON stores the conditional as:

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

- At runtime, the client passes the user decision via `context.decision`:

```http
POST /api/workflow/tasks/{taskId}/complete
Content-Type: application/json

{
  "user_id": 42,
  "context": { "decision": "approve" }
}
```

- If `context.decision` is missing or empty, the engine falls back to the normal destination `to[0]`.

## Advanced Notes

- Guards (`guard_provider`) receive context including: `instance_id`, `state_id`, `user_id`, `transition`, `definition_id`, `current_place`.
- Actions (`action_provider`) run after a task creation decision in `AutomaticTriggerRunner`.
- For policies like “N of M must complete”, add a custom provider that counts `completed` tasks in the current place and applies a threshold.

---

For further scenarios, combine the `Workflow` facade and services to take full control over advancement, assignment, and actions.
