# Laravel Workflow

General-purpose workflow engine for Laravel, with default integration to Spatie's `laravel-permission`.

Full developer documentation: `docs/guide.en.md`
Examples: `docs/examples.en.md`
Persian documentation: `docs/guide.fa.md`, `docs/examples.fa.md`

## Installation
- Require the package:
  - `composer require zojaji/laravel-workflow`
- Publish configs, migrations, and views:
  - `php artisan vendor:publish --tag=workflow-config`
  - `php artisan vendor:publish --tag=workflow-migrations`
  - `php artisan vendor:publish --tag=workflow-views`
- Run migrations:
  - `php artisan migrate`

## Dependencies
- `spatie/laravel-permission` as a primary dependency
  - Add the `Spatie\Permission\Traits\HasRoles` trait to your User model

## Model Configuration
- In `config/workflow.php`:
  - `workflow.models.role`: Role model class (default: `Spatie\Permission\Models\Role`)
  - `workflow.models.user`: User model class (default: `config('auth.providers.users.model')`)

## Quick Usage
- Create a task with decision & assignment:
  - `\Zojaji\Workflow\Facades\Workflow::completeAndRoute($taskId, $userId, $context)`
- Finalize user selection for the `role_direct_user` method:
  - `\Zojaji\Workflow\Facades\Workflow::finalizeSelection($instanceId, $stateId, $taskName, $userId, $roleId, $decisionOptions)`

## Routes
- API is loaded under `api/workflow/*` and admin pages under `admin/workflows/*`.

## License
- MIT
