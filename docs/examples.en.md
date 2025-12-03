# Workflow Examples — Full Coverage

This document provides a complete end-to-end example workflow that exercises all engine features: initial/normal/final states, automatic and manual triggers, single and parallel task assignment, role claim, role direct user, strategy-based assignment, conditional routing (approve/reject), and use of APIs, services, and fluent builders.

## Scenario: Document Review & Approval
- States:
  - `draft` (initial)
  - `appoint_reviewers` (normal)
  - `reviewing` (normal)
  - `approval` (normal)
  - `approved` (final)
  - `rejected` (final)
- Roles:
  - `Reviewer` (id: 3)
  - `Approver` (id: 5)

## Transitions JSON (saved in `workflow_definitions.transitions`)
```json
[
  {
    "name": "Start Review Setup",
    "from": "draft",
    "to": "appoint_reviewers",
    "trigger": { "type": "automatic" },
    "guard_provider": null,
    "decision_options": {
      "assignment_mode": "single",
      "assignment_method": "role_claim"
    },
    "assignment": { "type": "role", "ref": 3 }
  },
  {
    "name": "Assign Reviewers",
    "from": "appoint_reviewers",
    "to": "reviewing",
    "trigger": { "type": "automatic" },
    "guard_provider": "isHighPriority",
    "decision_options": {
      "assignment_mode": "parallel",
      "parallel": {
        "slots": [
          { "assignment_method": "role_least_busy", "role_id": 3 },
          { "assignment_method": "role_round_robin", "role_id": 3 }
        ]
      }
    }
  },
  {
    "name": "Submit Individual Review",
    "from": "reviewing",
    "to": "approval",
    "trigger": { "type": "manual" },
    "guard_provider": null,
    "decision_options": { "assignment_mode": "single", "assignment_method": "role_claim" },
    "assignment": { "type": "role", "ref": 3 }
  },
  {
    "name": "Consolidate Reviews",
    "from": "reviewing",
    "to": "approval",
    "trigger": { "type": "automatic" },
    "guard_provider": "allParallelSlotsCompleted",
    "decision_options": { "assignment_mode": "single", "assignment_method": "role_claim" },
    "assignment": { "type": "role", "ref": 3 },
    "action_provider": "notify"
  },
  {
    "name": "Approval Decision",
    "from": "approval",
    "to": ["approved", "rejected"],
    "trigger": { "type": "manual" },
    "guard_provider": null,
    "conditional": {
      "key": "decision",
      "routes": [
        { "value": "approve", "to": "approved" },
        { "value": "reject",  "to": "rejected" }
      ]
    },
    "decision_options": { "assignment_mode": "single", "assignment_method": "role_direct_user", "role_id": 5 }
  }
]
```
Notes:
- Uses automatic transitions originating from `draft` and `appoint_reviewers`.
- Parallel assignment creates multiple tasks concurrently in `Assign Reviewers`.
- `Consolidate Reviews` runs automatically when guard `allParallelSlotsCompleted` is satisfied.
- `Approval Decision` requires runtime user selection (`role_direct_user`) and conditional routing.

## Admin UI Steps
- Create workflow in `/admin/workflows`:
  - Name: `doc_review`
  - Marking Store: `single_state`
  - Places: `draft`, `appoint_reviewers`, `reviewing`, `approval`, `approved`, `rejected`
- Transitions:
  - Add the five transitions above using the UI’s trigger, decision mode, and assignment sections.
  - For parallel slots, configure 2 slots and set methods as shown.

## Instance Lifecycle (API)
1. Create instance
```
POST /api/workflow/instances
{
  "definition_id": 1,
  "model_type": "App\\Models\\Document",
  "model_id": 77,
  "variables": { "priority": 5 }
}
```
Response includes `current_state_id` pointing to `draft`.

2. Run automatic from `draft` (moves to `appoint_reviewers` and potentially creates tasks)
```
POST /api/workflow/instances/{id}/run-automatic
```
Engine executes transitions with `trigger.type = "automatic"` from `draft`.

3. Run automatic from `appoint_reviewers` (creates parallel reviewer tasks)
```
POST /api/workflow/instances/{id}/run-automatic
```
One task assigned by `least_busy`, another by `round_robin`.

4. Reviewers check inbox/claimables
```
GET /api/workflow/tasks/inbox?user_id=42
GET /api/workflow/tasks/claimables?user_id=42
POST /api/workflow/tasks/{taskId}/claim { "user_id": 42 }
```

5. Each reviewer completes their task
```
POST /api/workflow/tasks/{taskId}/complete
{
  "user_id": 42,
  "context": { "notes": "LGTM" }
}
```

6. Consolidation automatic transition (guard ensures all parallel tasks done)
```
POST /api/workflow/instances/{id}/run-automatic
```
Moves to `approval`.

7. Approval task requires runtime user selection
- Automatic/Manual creation with `role_direct_user` yields `requiresUserSelection`.
- Finalize selection:
```
POST /api/workflow/tasks/finalize
{
  "instance_id": {id},
  "state_id": {approval_state_id},
  "task_name": "Approval Decision",
  "user_id": 91,
  "role_id": 5,
  "decision_options": { "assignment_method": "role_direct_user" }
}
```

8. Approver completes with decision
```
POST /api/workflow/tasks/{taskId}/complete
{
  "user_id": 91,
  "context": { "decision": "approve" }
}
```
Instance advances to `approved`.

## Fluent Usage (Facade)
```php
use Zojaji\Workflow\Facades\Workflow;

// 1) Kick off automatic from current place
$auto1 = Workflow::instance($instanceId)->autoRun();

// 2) Reviewer completes and routes forward
$routed = Workflow::completeAndRoute($taskId, $userId, ['notes' => 'LGTM']);

// 3) Approver selection (role_direct_user)
$final = Workflow::finalizeSelection($instanceId, $stateId, 'Approval Decision', $chosenUserId, $roleId, [
  'assignment_method' => 'role_direct_user'
]);

// 4) Advanced chaining for manual decision
$items = Workflow::onTask($taskId, $approverId)
  ->complete(['decision' => 'approve'])
  ->advance(['decision' => 'approve'])
  ->autoAssign();

// 5) Queries
$inbox = Workflow::tasks()->forUserInbox($userId)->get();
$history = Workflow::history($instanceId)->get();
```
References:
- Task chaining: `src/Builders/TaskBuilder.php:196`
- Instance auto run: `src/Builders/InstanceBuilder.php:20`
- Facade methods: `src/Facades/Workflow.php:8`

## Services
### WorkflowEngine
```php
$engine = app(\Zojaji\Workflow\Contracts\WorkflowEngineInterface::class);
$res = $engine->decideAndAssign([
  'instance_id' => $instanceId,
  'state_id' => $stateId,
  'task_name' => 'Assign Reviewer',
  'assignmentType' => 'strategy',
  'assignmentRef' => 3,
  'strategyKey' => 'least_busy',
  'guard' => null,
]);
```
- Implementation: `src/Services/WorkflowEngine.php:17`

### AutomaticTriggerRunner
```php
$runner = app(\Zojaji\Workflow\Services\AutomaticTriggerRunner::class);
$r = $runner->runForDefinition($definition, 'appoint_reviewers', [
  'instance_id' => $instanceId,
  'state_id' => $stateId,
  'user_id' => $userId,
  'role_id' => 3,
  'variables' => ['priority' => 5],
]);
```
- Implementation: `src/Services/AutomaticTriggerRunner.php:18`

### DecisionEngine (providers)
```php
$this->app->extend(\Zojaji\Workflow\Contracts\DecisionEngineInterface::class, function ($engine, $app) {
  $base = array_merge((array) config('workflow_registry.condition_providers', []), (array) config('workflow_registry.actions', []));
  $custom = [
    'isHighPriority' => fn(array $ctx) => (int)($ctx['variables']['priority'] ?? $ctx['priority'] ?? 0) >= 5,
    'allParallelSlotsCompleted' => function (array $ctx): bool {
      $instId = (int) ($ctx['instance_id'] ?? 0);
      $place = (string) ($ctx['current_place'] ?? '');
      // Project-specific check: ensure no open/in_progress tasks remain for this instance/place
      return true;
    },
    'notify' => function (array $ctx) { \Log::info('workflow.notify', $ctx); return true; },
  ];
  return new \Zojaji\Workflow\Services\DecisionEngine(array_merge($base, $custom));
});
```
- Implementation: `src/Services/DecisionEngine.php:11`

### TaskAssigner (strategies)
```php
$this->app->extend(\Zojaji\Workflow\Contracts\TaskAssignerInterface::class, function ($assigner, $app) {
  return new \Zojaji\Workflow\Services\TaskAssigner([
    'least_busy' => \Zojaji\Workflow\Assignment\LeastBusyStrategy::class,
    'round_robin' => \Zojaji\Workflow\Assignment\RoundRobinStrategy::class,
    'random' => \Zojaji\Workflow\Assignment\RandomStrategy::class,
  ]);
});
```
- Implementation: `src/Services/TaskAssigner.php:12`

## Claiming vs Direct Assignment
- Role claim: tasks visible in `claimables` to role members; first to claim becomes assignee (`src/Models/WorkflowTask.php:62`).
- Direct user: task is assigned to a specific user immediately.
- Strategy: task assigned to a user chosen from role members.

## Data Model Touchpoints
- Definitions: `src/Models/WorkflowDefinition.php:11`
- States: `src/Models/WorkflowState.php:12`
- Instances: `src/Models/WorkflowInstance.php:12`
- Tasks: `src/Models/WorkflowTask.php:14`
- History: `src/Models/WorkflowHistory.php:12`

## End-to-End Trace
1. Instance created at `draft`.
2. Automatic to `appoint_reviewers` with role-claim task.
3. Automatic parallel tasks from `appoint_reviewers` to `reviewing` using strategies.
4. Manual reviewer completions; `Consolidate Reviews` auto fires by guard.
5. At `approval`, approver selection is finalized (`finalizeSelection`).
6. Approver completes with `decision=approve|reject` routing to final state.

---

For developer API references, see also `docs/developer-tools.en.md`.
