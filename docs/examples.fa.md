# مثال‌های ورکفلو — پوشش کامل

این سند یک سناریوی end-to-end را ارائه می‌کند که تمام قابلیت‌های موتور را پوشش می‌دهد: وضعیت‌های initial/normal/final، تریگرهای automatic و manual، تخصیص وظیفه به‌صورت single و parallel، Claim توسط نقش، انتخاب کاربر در زمان اجرا، تخصیص مبتنی بر استراتژی، مسیریابی شرطی (approve/reject)، و استفاده از APIها، سرویس‌ها و فلوئنت‌ها.

## سناریو: بازبینی و تأیید سند
- وضعیت‌ها:
  - `draft` (initial)
  - `appoint_reviewers` (normal)
  - `reviewing` (normal)
  - `approval` (normal)
  - `approved` (final)
  - `rejected` (final)
- نقش‌ها:
  - `Reviewer` (id: 3)
  - `Approver` (id: 5)

## JSON ترنزیشن‌ها (ذخیره در `workflow_definitions.transitions`)
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
نکات:
- از ترنزیشن‌های خودکار originating از `draft` و `appoint_reviewers` استفاده می‌کند.
- تخصیص موازی در «Assign Reviewers» چند وظیفه هم‌زمان ایجاد می‌کند.
- «Consolidate Reviews» وقتی گارد `allParallelSlotsCompleted` برقرار باشد، خودکار اجرا می‌شود.
- «Approval Decision» نیازمند انتخاب کاربر در زمان اجرا (`role_direct_user`) و مسیریابی شرطی است.

## مراحل UI مدیریت
- ایجاد ورکفلو در `/admin/workflows`:
  - Name: `doc_review`
  - Marking Store: `single_state`
  - Places: `draft`, `appoint_reviewers`, `reviewing`, `approval`, `approved`, `rejected`
- ترنزیشن‌ها:
  - پنج ترنزیشن بالا را با فیلدهای Trigger، Decision Mode و Assignment تنظیم کنید.
  - برای Parallel دو Slot تعریف و مطابق نمونه، روش هر Slot را تعیین کنید.

## چرخه عمر Instance (API)
1. ایجاد instance
```
POST /api/workflow/instances
{
  "definition_id": 1,
  "model_type": "App\\Models\\Document",
  "model_id": 77,
  "variables": { "priority": 5 }
}
```
پاسخ شامل `current_state_id` مربوط به `draft` است.

2. اجرای automatic از `draft` (حرکت به `appoint_reviewers` و احتمال ایجاد وظیفه)
```
POST /api/workflow/instances/{id}/run-automatic
```
موتور ترنزیشن‌های با `trigger.type = "automatic"` از `draft` را اجرا می‌کند.

3. اجرای automatic از `appoint_reviewers` (ایجاد وظایف موازی برای بازبین‌ها)
```
POST /api/workflow/instances/{id}/run-automatic
```
یک وظیفه با `least_busy` و دیگری با `round_robin` تخصیص داده می‌شود.

4. بازبین‌ها Inbox و Claimables را بررسی می‌کنند
```
GET /api/workflow/tasks/inbox?user_id=42
GET /api/workflow/tasks/claimables?user_id=42
POST /api/workflow/tasks/{taskId}/claim { "user_id": 42 }
```

5. هر بازبین وظیفه خود را تکمیل می‌کند
```
POST /api/workflow/tasks/{taskId}/complete
{
  "user_id": 42,
  "context": { "notes": "LGTM" }
}
```

6. ترنزیشن خودکار تجمیع بازبینی‌ها (گارد اطمینان می‌دهد که همه وظایف موازی انجام شده‌اند)
```
POST /api/workflow/instances/{id}/run-automatic
```
انتقال به `approval` انجام می‌شود.

7. وظیفه تأیید نیازمند انتخاب کاربر در زمان اجرا است
- ایجاد خودکار/دستی با `role_direct_user` مقدار `requiresUserSelection` را می‌دهد.
- نهایی‌سازی انتخاب:
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

8. Approver با تصمیم وظیفه را تکمیل می‌کند
```
POST /api/workflow/tasks/{taskId}/complete
{
  "user_id": 91,
  "context": { "decision": "approve" }
}
```
Instance به `approved` منتقل می‌شود.

## استفاده فلوئنت (Facade)
```php
use Zojaji\Workflow\Facades\Workflow;
// اجرای خودکار از Place فعلی
$auto1 = Workflow::instance($instanceId)->autoRun();
// تکمیل بازبین و مسیردهی
$routed = Workflow::completeAndRoute($taskId, $userId, ['notes' => 'LGTM']);
// انتخاب Approver (role_direct_user)
$final = Workflow::finalizeSelection($instanceId, $stateId, 'Approval Decision', $chosenUserId, $roleId, [
  'assignment_method' => 'role_direct_user'
]);
// زنجیره پیشرفته برای تصمیم‌گیری دستی
$items = Workflow::onTask($taskId, $approverId)
  ->complete(['decision' => 'approve'])
  ->advance(['decision' => 'approve'])
  ->autoAssign();
// کوئری‌ها
$inbox = Workflow::tasks()->forUserInbox($userId)->get();
$history = Workflow::history($instanceId)->get();
```
ارجاعات:
- زنجیره وظیفه: `src/Builders/TaskBuilder.php:196`
- اجرای خودکار Instance: `src/Builders/InstanceBuilder.php:20`
- متدهای Facade: `src/Facades/Workflow.php:8`

## سرویس‌ها
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
امکان اجرا: `src/Services/WorkflowEngine.php:17`

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
امکان اجرا: `src/Services/AutomaticTriggerRunner.php:18`

### DecisionEngine (providers)
```php
$this->app->extend(\Zojaji\Workflow\Contracts\DecisionEngineInterface::class, function ($engine, $app) {
  $base = array_merge((array) config('workflow_registry.condition_providers', []), (array) config('workflow_registry.actions', []));
  $custom = [
    'isHighPriority' => fn(array $ctx) => (int)($ctx['variables']['priority'] ?? $ctx['priority'] ?? 0) >= 5,
    'allParallelSlotsCompleted' => function (array $ctx): bool {
      $instId = (int) ($ctx['instance_id'] ?? 0);
      $place = (string) ($ctx['current_place'] ?? '');
      // بررسی پروژه‌محور: عدم وجود وظیفه open/in_progress برای این instance/place
      return true;
    },
    'notify' => function (array $ctx) { \Log::info('workflow.notify', $ctx); return true; },
  ];
  return new \Zojaji\Workflow\Services\DecisionEngine(array_merge($base, $custom));
});
```
امکان اجرا: `src/Services/DecisionEngine.php:11`

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
امکان اجرا: `src/Services/TaskAssigner.php:12`

## Claim در برابر تخصیص مستقیم
- Claim نقش: وظایف در `claimables` برای اعضای نقش قابل مشاهده؛ اولین فرد که Claim کند، assignee می‌شود (`src/Models/WorkflowTask.php:62`).
- کاربر مستقیم: وظیفه فوراً به کاربر مشخص تخصیص می‌یابد.
- استراتژی: وظیفه به کاربری از اعضای نقش براساس استراتژی داده می‌شود.

## نقاط تماس مدل‌ها
- تعریف‌ها: `src/Models/WorkflowDefinition.php:11`
- وضعیت‌ها: `src/Models/WorkflowState.php:12`
- instanceها: `src/Models/WorkflowInstance.php:12`
- وظایف: `src/Models/WorkflowTask.php:14`
- تاریخچه: `src/Models/WorkflowHistory.php:12`

## ردگیری End-to-End
1. Instance در `draft` ایجاد می‌شود.
2. انتقال خودکار به `appoint_reviewers` با وظیفه نقش-claim.
3. انتقال خودکار موازی از `appoint_reviewers` به `reviewing` با استراتژی‌ها.
4. تکمیل دستی بازبین‌ها؛ «Consolidate Reviews» با گارد اجرا می‌شود.
5. در `approval`، انتخاب Approver نهایی می‌شود (`finalizeSelection`).
6. Approver با `decision=approve|reject` به وضعیت نهایی مسیردهی می‌شود.

---

برای مرجع API توسعه‌دهندگان، `docs/developer-tools.fa.md` را نیز ببینید.
