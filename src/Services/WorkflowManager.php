<?php

namespace Zojaji\Workflow\Services;

use Zojaji\Workflow\Models\WorkflowInstance;
use Zojaji\Workflow\Models\WorkflowTask;
use Zojaji\Workflow\Models\WorkflowHistory;
use Zojaji\Workflow\Contracts\DecisionEngineInterface;
use Zojaji\Workflow\Contracts\WorkflowEngineInterface;
use Zojaji\Workflow\Builders\InstanceBuilder;
use Zojaji\Workflow\Builders\TaskBuilder;
use Zojaji\Workflow\Support\Models;

class WorkflowManager
{
    public function __construct(
        private readonly AutomaticTriggerRunner $runner,
        private readonly DecisionEngineInterface $decision,
        private readonly WorkflowEngineInterface $engine,
    ) {
    }

    /**
     * Entry point for fluent operations on a workflow instance.
     */
    public function instance(int $instanceId): InstanceBuilder
    {
        $instance = WorkflowInstance::query()->with(['definition', 'currentState'])->findOrFail($instanceId);
        return new InstanceBuilder($instance, $this->runner);
    }

    /**
     * Entry point for fluent operations targeting a specific task.
     */
    public function onTask(int $taskId, ?int $userId = null): TaskBuilder
    {
        $task = WorkflowTask::query()->with(['instance.definition', 'instance.currentState'])->findOrFail($taskId);
        $builder = new TaskBuilder($task, $this->runner, $this->decision);
        if ($userId !== null) {
            $builder->withUser($userId);
        }
        return $builder;
    }

    /**
     * Convenience to query tasks similar to Eloquent entrypoint.
     */
    public function tasks()
    {
        return WorkflowTask::query();
    }

    /**
     * Convenience to query history records for an instance.
     */
    public function history(int $instanceId)
    {
        return WorkflowHistory::query()->where('instance_id', $instanceId)->orderByDesc('id');
    }

    /**
     * One-shot helper: complete task and route automatically.
     */
    public function completeAndRoute(int $taskId, int $userId, array $context = []): array
    {
        return $this->onTask($taskId, $userId)->completeAndAutoRoute($context);
    }

    /**
     * Finalize selection for role_direct_user by assigning task directly to a chosen user.
     * Optionally validates membership if roleId is provided.
     */
    public function finalizeSelection(
        int $instanceId,
        ?int $stateId,
        string $taskName,
        int $userId,
        ?int $roleId = null,
        array $decisionOptions = []
    ): array {
        // Optional membership check
        if ($roleId) {
            $roleClass = Models::roleModel();
            $isMember = $roleClass::query()
                ->whereKey($roleId)
                ->whereHas('users', function ($q) use ($userId) {
                    $q->whereKey($userId);
                })
                ->exists();

            if (!$isMember) {
                return [
                    'canProceed' => false,
                    'createdTaskId' => null,
                    'assigneeId' => null,
                    'error' => 'user_not_in_role',
                ];
            }
        }

        $ctx = [
            'task_name' => $taskName,
            'instance_id' => $instanceId,
            'state_id' => $stateId,
            'assignmentType' => 'user',
            'assignmentRef' => $userId,
            'strategyKey' => null,
            'decision_options' => array_merge($decisionOptions, [
                'requires_user_selection' => false,
                'role_id' => $roleId,
            ]),
        ];

        return $this->engine->decideAndAssign($ctx);
    }
}
