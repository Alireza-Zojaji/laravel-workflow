# Laravel Workflow

پکیج موتور گردش‌کار عمومی برای لاراول با ادغام پیش‌فرض با Spatie (laravel-permission).

## نصب
- افزودن پکیج به پروژه:
  - `composer require amir/laravel-workflow`
- انتشار کانفیگ‌ها و مایگریشن‌ها و ویوها:
  - `php artisan vendor:publish --tag=workflow-config`
  - `php artisan vendor:publish --tag=workflow-migrations`
  - `php artisan vendor:publish --tag=workflow-views`
- اجرای مایگریشن‌ها:
  - `php artisan migrate`

## وابستگی‌ها
- `spatie/laravel-permission` به‌عنوان وابستگی اصلی
  - افزودن trait `Spatie\Permission\Traits\HasRoles` به مدل کاربر

## پیکربندی مدل‌ها
- فایل `config/workflow.php`:
  - `workflow.models.role`: کلاس مدل نقش (پیش‌فرض: `Spatie\Permission\Models\Role`)
  - `workflow.models.user`: کلاس مدل کاربر (پیش‌فرض: `config('auth.providers.users.model')`)

## استفاده سریع
- ایجاد تسک و تخصیص:
  - `\Amir\Workflow\Facades\Workflow::completeAndRoute($taskId, $userId, $context)`
- نهایی‌سازی انتخاب کاربر برای روش `role_direct_user`:
  - `\Amir\Workflow\Facades\Workflow::finalizeSelection($instanceId, $stateId, $taskName, $userId, $roleId, $decisionOptions)`

## مسیرها
- API تحت `api/workflow/*` و صفحات مدیریت تحت `admin/workflows/*` بارگذاری می‌شوند.

## مجوز
- MIT
