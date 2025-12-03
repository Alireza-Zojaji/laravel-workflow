# راهنمای توسعه‌دهندگان پکیج Laravel Workflow

این سند ابزارها و APIهایی را که برای توسعه‌دهندگان در پکیج `laravel-workflow` فراهم شده توضیح می‌دهد: نقش هر جزء، ورودی‌ها و خروجی‌ها، و مثال‌های استفاده. هدف این است که بتوانید گردش‌کارها را بسازید، تسک‌ها را مدیریت کنید، و به‌صورت مطمئن آن‌ها را پیش ببرید و انتساب کنید.

## Facade: `Workflow`

نقطه‌ی ورود ساده و Fluent برای کار با نمونه‌ها و تسک‌ها.

- متدها:
  - `Workflow::instance(int $instanceId): InstanceBuilder`
  - `Workflow::onTask(int $taskId, ?int $userId = null): TaskBuilder`
  - `Workflow::tasks(): \Illuminate\Database\Eloquent\Builder`
  - `Workflow::history(int $instanceId): \Illuminate\Database\Eloquent\Builder`
  - `Workflow::completeAndRoute(int $taskId, int $userId, array $context = []): array`
  - `Workflow::finalizeSelection(int $instanceId, ?int $stateId, string $taskName, int $userId, ?int $roleId = null, array $decisionOptions = []): array`

- خروجی‌ها:
  - متدهای کوئری‌گر (`tasks`, `history`) یک Builder برمی‌گردانند.
  - `completeAndRoute` نتیجه‌ی پیشروی و انتساب خودکار را به‌صورت آرایه برمی‌گرداند.
  - `finalizeSelection` نتیجه‌ی ایجاد تسک با انتساب مستقیم به کاربر انتخاب‌شده را برمی‌گرداند.

- مثال استفاده:

```php
use Zojaji\Workflow\Facades\Workflow;

// تکمیل یک تسک و مسیریابی خودکار
$result = Workflow::completeAndRoute($taskId, $userId, [
    'priority' => 5,
]);

// نهایی‌سازی انتخاب کاربر مستقیم (role_direct_user)
$res2 = Workflow::finalizeSelection($instanceId, $stateId, 'Approve Request', $chosenUserId, $roleId, [
    'assignment_method' => 'role_direct_user',
]);
```

## Builder: `InstanceBuilder`

کتاب‌ساز برای عملیات روی یک نمونه‌ی گردش‌کار.

- متدها:
  - `autoRun(array $context = []): array` اجرای تریگرهای خودکار برای مرحله‌ی فعلی نمونه.
  - `getInstance(): WorkflowInstance` دسترسی به نمونه‌ی زیرین.

- ورودی `autoRun`:
  - کانتکست اختیاری (کلیدهای رایج: `instance_id`, `state_id`, ... به‌صورت خودکار پر می‌شوند).

- خروجی `autoRun`:
  - آرایه‌ای با ساختار:
    - `definition_id`, `current_place`
    - `items`: لیست نتایج هر انتقال خودکار شامل فیلدهای `transition`, `status`, `mode`, `createdTaskId|createdTaskIds`, `requiresUserSelection`.

- مثال:

```php
use Zojaji\Workflow\Facades\Workflow;

$r = Workflow::instance($instanceId)->autoRun();
```

## Builder: `TaskBuilder`

کتاب‌ساز برای عملیات روی تسک.

- متدها:
  - `withUser(int $userId): self` تعیین کاربر عامل برای ثبت و کانتکست.
  - `complete(array $metadata = []): self` علامت‌گذاری تسک به‌صورت کامل شده.
  - `advance(array $context = []): self` پیشبرد نمونه از مرحله فعلی بر اساس گاردها و ثبت تاریخچه.
  - `autoAssign(array $context = []): array` پس از پیشروی، انتساب خودکار تسک‌های مرحله جدید.
  - `completeAndAutoRoute(array $context = []): array` ترکیب سه مرحله‌ی بالا در یک فراخوانی.
  - `getTask(): WorkflowTask` دسترسی به خود تسک.

- ورودی‌ها:
  - `withUser`: شناسه کاربر.
  - `advance/autoAssign/completeAndAutoRoute`: کانتکست اختیاری برای گاردها و اکشن‌ها.

- خروجی‌ها:
  - `autoAssign/completeAndAutoRoute`: نتایج `AutomaticTriggerRunner` با آرایه‌ی آیتم‌ها.

- مثال:

```php
use Zojaji\Workflow\Facades\Workflow;

$result = Workflow::onTask($taskId, $userId)
    ->completeAndAutoRoute(['priority' => 3]);
```

نکته: اگر گاردها اجازه‌ی پیشروی ندهند، `completeAndAutoRoute` وظایف خودکار مرحله‌ی فعلی را دوباره ایجاد نمی‌کند و خلاصه‌ی وضعیت را برمی‌گرداند.

## Service: `WorkflowEngine`

مسئول تصمیم‌گیری و ایجاد/انتساب تسک.

- متد:
  - `decideAndAssign(array $context = []): array`

- ورودی `context`:
  - `guard`: کلید گارد برای ارزیابی (اختیاری).
  - `assignmentType`: یکی از `user|role|strategy`.
  - `assignmentRef`: شناسه‌ی کاربر یا شناسه/نام نقش (بسته به حالت).
  - `strategyKey`: کلید استراتژی (`least_busy|round_robin|random|...`).
  - `decision_options`: آبجکت اختیاری برای گزینه‌های تصمیم‌گیری و UI.
  - `instance_id`, `state_id`, `task_name`.

- خروجی:
  - `canProceed`: آیا گارد اجازه داد.
  - `createdTaskId`: شناسه‌ی تسکِ ایجاد شده (یا `null`).
  - `assigneeId`: شناسه‌ی کاربر منتخب (در حالت استراتژی/نقش)، یا `null`.
  - `requiresUserSelection`: در حالت `role_direct_user` وقتی کاربر مشخص نیست، `true` می‌شود.

- مثال:

```php
use Zojaji\Workflow\Contracts\WorkflowEngineInterface;

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

اجرای انتقال‌های خودکار و ایجاد تسک‌ها (حالت تک‌تایی یا موازی).

- متد:
  - `runForDefinition(WorkflowDefinition $definition, string $currentPlace, array $context = []): array`

- ورودی‌ها:
  - `definition`: تعریف گردش‌کار.
  - `currentPlace`: کلید مرحله‌ی فعلی.
  - `context`: شامل `instance_id`, `state_id`, و کانتکست‌های مرتبط.
  - هر انتقال با `trigger.type = "automatic"` بررسی می‌شود.

- گزینه‌های تصمیم (`decision_options`):
  - حالت تک‌تایی (`single`):
    - `assignment_method`: یکی از `role_claim`, `role_direct_user`, `role_least_busy`, `role_round_robin`, `role_random`, `user`.
  - حالت موازی (`parallel`):
    - `parallel.slots[]`: آرایه‌ی اسلات‌ها با فیلدهای:
      - `assignment_method`: مانند بالا.
      - `role_id` یا `user_id`.
    - برای هر اسلات یک تسک جداگانه ایجاد می‌شود.

- خروجی:
  - آرایه شامل آیتم‌های اجرای هر انتقال با فیلدهای `transition`, `status` (`completed|skipped`), `mode` (`single|parallel`), و شناسه‌های تسک ایجاد شده.

- مثال موازی:

```php
$ctx = ['instance_id' => $instanceId, 'state_id' => $stateId];
$res = app(\Zojaji\Workflow\Services\AutomaticTriggerRunner::class)
    ->runForDefinition($definition, 'reviewing', $ctx);
```

## Service: `DecisionEngine`

ارزیابی گاردها و اجرای اکشن‌ها با استفاده از Providerهای رجیستری.

- متدها:
  - `evaluate(?string $guardProvider, array $context = []): bool`
  - `executeAction(?string $actionProvider, array $context = []): void`

- ثبت Providerها:
  - در `WorkflowServiceProvider`، کلیدهای `condition_providers` و `actions` از `config/workflow_registry.php` بارگذاری می‌شوند.
  - می‌توانید در `AppServiceProvider` آن‌ها را توسعه دهید.

- گارد آماده: `allParallelSlotsCompleted`
  - اجازه‌ی پیشروی تنها وقتی می‌دهد که هیچ تسکِ `open` یا `in_progress` در مرحله‌ی فعلی نمونه باقی نمانده باشد.
  - استفاده: در انتقال، `guard_provider` را روی `allParallelSlotsCompleted` بگذارید.

- مثال توسعه‌ی Providerها:

```php
// App\Providers\AppServiceProvider::register()
$this->app->extend(DecisionEngineInterface::class, function ($engine, $app) {
    return new \Zojaji\Workflow\Services\DecisionEngine(array_merge(
        config('workflow_registry.condition_providers', []),
        config('workflow_registry.actions', []),
        [
            'isHighPriority' => fn(array $ctx) => (int)($ctx['priority'] ?? 0) >= 5,
            'allParallelSlotsCompleted' => function (array $ctx): bool {
                // پیاده‌سازی آماده در پروژه فعلی وجود دارد
                return true; // نمونه، پیاده‌سازی واقعی وضعیت تسک‌ها را چک می‌کند
            },
        ]
    ));
});
```

## Service: `TaskAssigner`

انتخاب کاربر مقصد برای تسک.

- امضا (درونی):
  - `assign(string $assignmentType, $assignmentRef, ?string $strategyKey, array $context = []): ?string`

- حالت‌ها:
  - `assignmentType = 'user'`: انتساب مستقیم به کاربر.
  - `assignmentType = 'role'`: تسک قابل مطالبه توسط اعضای نقش.
  - `assignmentType = 'strategy'`: انتخاب کاربر از اعضای نقش با استراتژی (`least_busy|round_robin|random`).

- توسعه‌ی استراتژی‌ها:

```php
// App\Providers\AppServiceProvider::register()
$this->app->extend(TaskAssignerInterface::class, function ($assigner, $app) {
    return new \Zojaji\Workflow\Services\TaskAssigner([
        'least_busy' => function (array $ctx) { /*...*/ },
        'round_robin' => function (array $ctx) { /*...*/ },
        'random' => function (array $ctx) { /*...*/ },
    ]);
});
```

## API‌ها (JSON)

پیشوند: `/api/workflow`

- Lookups:
  - `GET /lookups/users?role_id=...` فهرست کاربران (با امکان فیلتر نقش).
  - `GET /lookups/roles` فهرست نقش‌ها.
  - `GET /lookups/strategies` فهرست استراتژی‌های انتساب.

- Definitions:
  - `GET /definitions`, `GET /definitions/{id}`، `POST /definitions`، `PUT /definitions/{id}`، `DELETE /definitions/{id}`.
  - `GET /config-definitions`, `GET /config-definitions/{key}`.

- Tasks:
  - `GET /tasks/inbox`، `GET /tasks/claimables`.
  - `POST /tasks/{taskId}/claim` ادعای تسک در حالت نقش.
  - `POST /tasks` ایجاد تسک جدید با `WorkflowEngine`.
  - `POST /tasks/{taskId}/complete` تکمیل و مسیریابی خودکار.
  - `POST /tasks/finalize` نهایی‌سازی انتخاب کاربر مستقیم.

- Instances:
  - `GET /instances`، `GET /instances/{id}`.
  - `POST /instances` ایجاد نمونه‌ی جدید.
  - `POST /instances/{id}/run-automatic` اجرای تریگرهای خودکار مرحله‌ی فعلی.

### مثال‌های API

ایجاد تسک:

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

تکمیل و مسیریابی خودکار:

```http
POST /api/workflow/tasks/123/complete
Content-Type: application/json

{
  "user_id": 42,
  "context": { "priority": 5 }
}
```

نهایی‌سازی انتخاب کاربر مستقیم:

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

## Configuration و DSL

- `config/workflow_registry.php`: ثبت Providerها، استراتژی‌ها و تنظیمات رجیستری.
  - نمونه:

```php
return [
  'assignment_strategies' => [
    'role.round_robin' => Zojaji\Workflow\Assignment\RoundRobinStrategy::class,
    'role.least_busy' => Zojaji\Workflow\Assignment\LeastBusyStrategy::class,
    'role.random' => Zojaji\Workflow\Assignment\RandomStrategy::class,
  ],
  'condition_providers' => [
    'isHighPriority' => fn(array $ctx) => (int)($ctx['priority'] ?? 0) >= 5,
    'notify' => function (array $ctx) { \Log::info('workflow.notify', $ctx); return true; },
  ],
];
```

- تعریف انتقال با تریگر خودکار و موازی (در مدل/DB تعریف گردش‌کار):

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

در حالت `role_direct_user`، اگر کاربر انتخاب نشود، `WorkflowEngine` پیام `requiresUserSelection` برمی‌گرداند و باید از اندپوینت `POST /api/workflow/tasks/finalize` برای نهایی‌سازی استفاده کنید.


### مسیریابی شرطی (approve/reject)

- در UI ادمین (صفحات `index` و `edit`) یک فیلد سه‌حالته با عنوان `Decision Mode` برای هر ترنزیشن وجود دارد: `none|approve|reject`.
- اگر `approve` یا `reject` انتخاب شود، ترنزیشن به‌صورت شرطی ذخیره می‌شود و مسیر تصمیم با اولین مقصد انتخاب‌شده در فیلد `To` نگاشت می‌گردد.
- ساختار ذخیره‌سازی در JSONِ ترنزیشن‌ها به شکل زیر است:

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

- در زمان اجرا، تصمیم کاربر با کلید `context.decision` ارسال می‌شود:

```http
POST /api/workflow/tasks/{taskId}/complete
Content-Type: application/json

{
  "user_id": 42,
  "context": { "decision": "approve" }
}
```

- اگر `context.decision` ارسال نشود یا خالی باشد، موتور از مقصد عادی `to[0]` استفاده می‌کند.

## نکات پیشرفته

- گاردها (`guard_provider`) کانتکست‌های زیر را دریافت می‌کنند: `instance_id`, `state_id`, `user_id`, `transition`, `definition_id`, `current_place`.
- اکشن‌ها (`action_provider`) پس از تصمیم ایجاد تسک، با کانتکست فراخوانی می‌شوند (`AutomaticTriggerRunner`).
- برای سیاست‌های متفاوت مانند «N از M تکمیل شود»، می‌توانید Provider سفارشی اضافه کنید که تعداد تسک‌های `completed` را شمارش کند و شرط آستانه را اعمال نماید.

---

برای سؤالات و سناریوهای خاص‌تر، می‌توانید از `Workflow` Facade و سرویس‌ها به‌صورت ترکیبی استفاده کنید تا کنترل کامل بر پیشروی، انتساب و اکشن‌ها داشته باشید.