# راهنمای توسعه‌دهندگان Laravel Workflow

یک موتور ورکفلو عمومی برای لاراول با قابلیت ساخت وظایف، تخصیص، ترنزیشن‌های خودکار، گاردها/اکشن‌ها و UI مدیریتی. این راهنما نصب، پیکربندی، UI، APIها، فلوئنت‌ها، سرویس‌ها، مدل‌ها، استراتژی‌ها و نقاط توسعه‌پذیری را پوشش می‌دهد.

## مرور کلی
- قابلیت‌ها: تعریف‌ها، وضعیت‌ها، ترنزیشن‌ها، وظایف، تریگرهای خودکار، گارد/اکشن، استراتژی‌های تخصیص، تاریخچه.
- UI مدیریت: ایجاد/ویرایش ورکفلو، تعریف places و transitions، تنظیم assignment (تکی/Parallel)، تریگرها، decision mode.
- APIهای JSON: مدیریت definitions، instances، tasks و lookups.
- فلوئنت: Facade `Workflow` با `InstanceBuilder` و `TaskBuilder`.
- توسعه‌پذیری: ثبت گارد/اکشن و استراتژی‌های تخصیص از طریق تنظیمات.

## نصب
- نصب پکیج: `composer require zojaji/laravel-workflow`
- انتشار فایل‌ها:
  - `php artisan vendor:publish --tag=workflow-config`
  - `php artisan vendor:publish --tag=workflow-migrations`
  - `php artisan vendor:publish --tag=workflow-views`
- اجرای مایگریشن‌ها: `php artisan migrate`

## وابستگی‌ها
- استفاده از `spatie/laravel-permission` برای نقش‌ها و رابطه کاربر-نقش.
- افزودن `Spatie\Permission\Traits\HasRoles` به مدل `User`.

## پیکربندی
- فایل: `config/workflow.php`
  - `workflow.models.role`: کلاس مدل نقش
  - `workflow.models.user`: کلاس مدل کاربر
  - `database.connection`: نام کانکشن
  - `database.tables.*`: نام جدول‌ها
  - `events.enabled`, `events.queue`
- فایل: `config/workflow_registry.php`
  - `assignment_strategies`: نگاشت کلید استراتژی به هندلر
  - `condition_providers`: ارائه‌دهنده‌های گارد
  - `actions`: ارائه‌دهنده‌های اکشن
  - `calendars`, `message_channels`: کلیدهای رزرو برای قابلیت‌های پیشرفته

## مسیرها و UI
- پیشوند API: `/api/workflow/*`
- پیشوند UI مدیریت: `/admin/workflows/*`
- صفحات مدیریت:
  - Index: لیست تعریف‌های دیتابیس و تعریف‌های config به صورت Read-only
  - Create: نام، label، marking store، places، transitions
  - Edit: ویرایش places/transitions و وضعیت فعال بودن
- امکانات UI:
  - Places: افزودن/حذف
  - Transitions: نام، from/to، ارائه‌دهنده گارد، تریگر (`manual|automatic`)، Decision Mode (`none|approve|reject`)
  - Assignment:
    - Mode: `single` یا `parallel`
    - Single: کاربر مستقیم، Claim توسط نقش، انتخاب کاربر در زمان اجرا (`role_direct_user`)، `least_busy`، `round_robin`، `random`
    - Parallel: N اسلات مستقل (user یا role با روش مشخص)
  - Lookups: دریافت نقش‌ها و کاربران از API

## APIهای JSON
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

### نمونه‌های API
- ایجاد وظیفه
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
- تکمیل و مسیردهی خودکار
```
POST /api/workflow/tasks/123/complete
Content-Type: application/json
{
  "user_id": 42,
  "context": { "priority": 5 }
}
```
- نهایی‌سازی انتخاب کاربر (`role_direct_user`)
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

## Facade و Fluent Builders
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
- اجرای ترنزیشن‌های خودکار از Place فعلی و بازگشت نتیجه هر ترنزیشن.

### TaskBuilder
- `withUser(int $userId): self`
- `complete(array $metadata = []): self`
- `advance(array $context = []): self`
- `autoAssign(array $context = []): array`
- `completeAndAutoRoute(array $context = []): array`
- `getTask(): WorkflowTask`

### نمونه‌ها
```php
use Zojaji\Workflow\Facades\Workflow;
// تکمیل وظیفه و مسیردهی خودکار
$result = Workflow::completeAndRoute($taskId, $userId, ['priority' => 3]);
// زنجیره صریح
$items = Workflow::onTask($taskId, $userId)
  ->complete(['note' => 'done'])
  ->advance(['decision' => 'approve'])
  ->autoAssign();
// اجرای خودکار برای یک instance
$auto = Workflow::instance($instanceId)->autoRun();
```

## سرویس‌ها و قراردادها
### WorkflowEngine
- `decideAndAssign(array $context = []): array`
- زمینه:
  - `guard`، `instance_id`، `state_id`، `task_name`
  - `assignmentType` (`user|role|strategy`)، `assignmentRef` (شناسه کاربر/نقش)، `strategyKey`
  - `decision_options` (گزینه‌های UI)
- خروجی: `canProceed`، `createdTaskId`، `assigneeId`، `requiresUserSelection`

### AutomaticTriggerRunner
- `runForDefinition(WorkflowDefinition $definition, string $currentPlace, array $context = []): array`
- اجرای ترنزیشن‌های با `trigger.type = "automatic"` از Place فعلی
- پشتیبانی از حالت‌های single و parallel

### DecisionEngine
- `evaluate(?string $guardProvider, array $context = []): bool`
- `executeAction(?string $actionProvider, array $context = []): void`
- Providerها از `config/workflow_registry.php` بارگذاری و در اپلیکیشن قابل توسعه هستند

### TaskAssigner
- `assign(?string $assignmentType, ?string $assignmentRef, ?string $strategyKey, array $context = []): ?string`
- حالت‌ها: کاربر مستقیم، Claim توسط نقش، استراتژی‌محور
- استراتژی‌ها: `least_busy`، `round_robin`، `random`

## مدل‌ها
- `WorkflowDefinition`: `name`, `label`, `description`, `marking_store`, `places[]`, `transitions[]`, `schema`, `version`, `is_active`
- `WorkflowState`: `definition_id`, `key`, `label`, `type (initial|normal|final)`, `metadata`
- `WorkflowInstance`: `definition_id`, `current_state_id`, `status`, `model_type`, `model_id`, `variables`, `started_at`, `completed_at`
- `WorkflowTask`: `instance_id`, `state_id`, `name`, `assigned_to`, `assignment_type`, `assignment_ref`, `strategy_key`, `due_at`, `status`, `metadata`, `decision_options`
- `WorkflowHistory`: `instance_id`, `transition_id`, `from_state_id`, `to_state_id`, `performed_by`, `metadata`

## استراتژی‌های تخصیص
- کلیدهای داخلی:
  - `least_busy`: کم‌مشغله‌ترین عضو
  - `round_robin`: گردش چرخشی
  - `random`: تصادفی
- توسعه با افزودن به `workflow_registry.assignment_strategies` با کلاس پیاده‌ساز `AssignmentStrategyInterface` یا callable.

## مسیریابی شرطی
- نمونه ساختار `conditional` در ترنزیشن:
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
- مقدار `context.decision` را هنگام تکمیل وظیفه ارسال کنید.

## شِمای پایگاه‌داده
- جدول‌ها: `workflow_definitions`، `workflow_states`، `workflow_transitions`، `workflow_instances`، `workflow_tasks`، `workflow_history`
- مایگریشن‌ها با تگ `workflow-migrations` منتشر می‌شوند.

## نقاط توسعه‌پذیری
- گاردها: قواعد کسب‌وکار با زمینه کامل (`instance_id`، `state_id`، `user_id`، `transition`، `definition_id`، `current_place`، ...)
- اکشن‌ها: Side-effect ها پس از تصمیم ساخت وظیفه
- استراتژی‌ها: منطق انتخاب سفارشی؛ امکان استفاده از pool مبتنی بر نقش

## نکات امنیتی
- از آشکارسازی Secrets در گارد/اکشن جلوگیری کنید
- احراز مجوز برای مسیرهای مدیریت و APIها مطابق سیاست اپلیکیشن
- اعتبارسنجی عضویت نقش هنگام `finalizeSelection`

## مثال‌ها
- ثبت Provider و Strategy سفارشی در Service Provider اپلیکیشن:
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

برای مراجعه سریع به APIهای توسعه‌دهندگان، فایل `docs/developer-tools.fa.md` را نیز ببینید.
