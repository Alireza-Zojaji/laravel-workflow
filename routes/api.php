<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Collection;
use Zojaji\Workflow\Http\Controllers\TaskController;
use Zojaji\Workflow\Http\Controllers\Api\DefinitionController;
use Zojaji\Workflow\Http\Controllers\Api\InstanceController;
use Zojaji\Workflow\Support\Models;

Route::group([
    'prefix' => 'api/workflow',
    'middleware' => 'api',
    'as' => 'workflow.api.',
], function () {
    Route::get('/admin/workflows', function () {
        // Redirect to the web route that provides $definitions and $configDefs
        return Redirect::route('workflow.admin.index');
    })->name('admin.index');

    // Lookups for Users and Roles
    Route::get('/lookups/users', function (Request $request) {
        $userClass = Models::userModel();
        $roleClass = Models::roleModel();
        $roleName = trim((string) $request->query('role', ''));
        $roleId = trim((string) $request->query('role_id', ''));

        // If role filter provided, return users of that role
        if ($roleName !== '' || $roleId !== '') {
            try {
                $role = null;
                if ($roleId !== '') {
                    $role = $roleClass::query()->whereKey($roleId)->first();
                } elseif ($roleName !== '') {
                    try {
                        if (method_exists($roleClass, 'findByName')) {
                            $role = $roleClass::findByName($roleName, 'api');
                        }
                    } catch (\Throwable $e) {
                    }
                    if (!$role) {
                        $role = $roleClass::query()->where('name', $roleName)->first();
                    }
                }

                if ($role) {
                    return $role->users()
                        ->select('id', 'name')
                        ->orderBy('name')
                        ->limit(200)
                        ->get();
                }
            } catch (\Throwable $e) {
                // Fallback to all users below
            }
        }

        return $userClass::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->limit(200)
            ->get();
    })->name('lookups.users');

    Route::get('/lookups/roles', function () {
        $roleClass = Models::roleModel();
        return $roleClass::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->limit(200)
            ->get();
    })->name('lookups.roles');

    // Lookups for Assignment Strategies
    Route::get('/lookups/strategies', function () {
        $strategies = Config::get('workflow_registry.assignment_strategies', []);
        $list = Collection::make($strategies)->map(function ($handler, $key) {
            $name = Str::of($key)->replace('_', ' ')->title()->toString();
            return ['id' => (string) $key, 'name' => $name];
        })->values();
        return $list;
    })->name('lookups.strategies');

    // Definitions CRUD + config read-only
    Route::get('/definitions', [DefinitionController::class, 'index'])->name('definitions.index');
    Route::get('/definitions/{id}', [DefinitionController::class, 'show'])->name('definitions.show');
    Route::post('/definitions', [DefinitionController::class, 'store'])->name('definitions.store');
    Route::put('/definitions/{id}', [DefinitionController::class, 'update'])->name('definitions.update');
    Route::delete('/definitions/{id}', [DefinitionController::class, 'destroy'])->name('definitions.destroy');
    Route::get('/config-definitions', [DefinitionController::class, 'configIndex'])->name('definitions.config.index');
    Route::get('/config-definitions/{key}', [DefinitionController::class, 'configShow'])->name('definitions.config.show');

    // Tasks API: inbox, claimables, claim, store
    Route::get('/tasks/inbox', [TaskController::class, 'inbox'])->name('tasks.inbox');
    Route::get('/tasks/claimables', [TaskController::class, 'claimables'])->name('tasks.claimables');
    Route::post('/tasks/{taskId}/claim', [TaskController::class, 'claim'])->name('tasks.claim');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('/tasks/{taskId}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
    Route::post('/tasks/finalize', [TaskController::class, 'finalize'])->name('tasks.finalize');

    // Instances API: list, show, create, run automatic transitions
    Route::get('/instances', [InstanceController::class, 'index'])->name('instances.index');
    Route::get('/instances/{id}', [InstanceController::class, 'show'])->name('instances.show');
    Route::post('/instances', [InstanceController::class, 'store'])->name('instances.store');
    Route::post('/instances/{id}/run-automatic', [InstanceController::class, 'runAutomatic'])->name('instances.run_automatic');
});
