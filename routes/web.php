<?php

use Illuminate\Support\Facades\Route;
use Zojaji\Workflow\Http\Controllers\Admin\WorkflowAdminController;
use Illuminate\Support\Facades\View;

Route::middleware(['web'])
    ->prefix('admin/workflows')
    ->group(function () {
        Route::get('/', [WorkflowAdminController::class, 'index'])->name('workflow.admin.index');
        Route::post('/', [WorkflowAdminController::class, 'store'])->name('workflow.admin.store');
        // Read-only details for config-defined workflows
        Route::get('/config/{key}', [WorkflowAdminController::class, 'showConfig'])
            ->name('workflow.admin.config.show');
        Route::get('/{id}/edit', [WorkflowAdminController::class, 'edit'])->name('workflow.admin.edit');
        Route::put('/{id}', [WorkflowAdminController::class, 'update'])->name('workflow.admin.update');
        Route::delete('/{id}', [WorkflowAdminController::class, 'destroy'])->name('workflow.admin.destroy');
    });
