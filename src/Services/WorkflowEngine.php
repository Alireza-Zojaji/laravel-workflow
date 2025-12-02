<?php

namespace Amir\Workflow\Services;

use Amir\Workflow\Contracts\WorkflowEngineInterface;
use Amir\Workflow\Contracts\DecisionEngineInterface;
use Amir\Workflow\Contracts\TaskAssignerInterface;
use Amir\Workflow\Models\WorkflowTask;

class WorkflowEngine implements WorkflowEngineInterface
{
    public function __construct(
        protected DecisionEngineInterface $decision,
        protected TaskAssignerInterface $assigner
    ) {}

    public function decideAndAssign(array $context = []): array
    {
        // Evaluate guard
        $canProceed = $this->decision->evaluate($context['guard'] ?? null, $context);
        if (!$canProceed) {
            return ['canProceed' => false, 'createdTaskId' => null, 'assigneeId' => null];
        }

        // Determine assignee
        $assignmentType = $context['assignmentType'] ?? null;            // user|role|strategy
        $assignmentRef  = $context['assignmentRef'] ?? null;             // userId or roleId
        $strategyKey    = $context['strategyKey'] ?? null;
        $decisionOpts   = $context['decision_options'] ?? null;          // optional UI options

        $assigneeId = $this->assigner->assign($assignmentType, $assignmentRef, $strategyKey, $context);

        // Prepare record fields
        $effectiveAssignmentType = $assigneeId ? 'user' : ($assignmentType ?: 'user');
        $effectiveAssignedTo = $assigneeId ?: $assignmentRef; // For role mode, assignmentRef should be Role ID

        // Handle runtime user selection requirement: if user not provided
        if ($effectiveAssignmentType === 'user' && empty($effectiveAssignedTo)) {
            // When requires user selection, we do not create a task automatically
            $requiresSel = (bool) ($decisionOpts['requires_user_selection'] ?? false);
            return [
                'canProceed' => true,
                'createdTaskId' => null,
                'assigneeId' => null,
                'requiresUserSelection' => $requiresSel,
            ];
        }

        // Create task
        $task = new WorkflowTask();
        $task->instance_id = (int) ($context['instance_id'] ?? 0);
        $task->state_id = $context['state_id'] ?? null;
        $task->name = (string) ($context['task_name'] ?? 'Task');
        $task->assigned_to = $effectiveAssignedTo ? (string) $effectiveAssignedTo : null;
        $task->assignment_type = $effectiveAssignmentType;
        $task->assignment_ref = $assignmentRef ? (string) $assignmentRef : null;
        $task->strategy_key = $strategyKey ?: null;
        $task->status = 'open';
        $task->decision_options = is_array($decisionOpts) ? $decisionOpts : null;
        $task->save();

        return ['canProceed' => true, 'createdTaskId' => $task->id, 'assigneeId' => $assigneeId];
    }
}
