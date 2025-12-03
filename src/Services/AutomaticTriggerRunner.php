<?php

namespace Zojaji\Workflow\Services;

use Zojaji\Workflow\Models\WorkflowDefinition;

class AutomaticTriggerRunner
{
    public function __construct(
        protected DecisionEngine $decision,
        protected WorkflowEngine $engine,
    ) {}

    /**
     * Run all automatic transitions that originate from the given place.
     * Returns an array of execution results per transition.
     */
    public function runForDefinition(WorkflowDefinition $definition, string $currentPlace, array $context = []): array
    {
        $results = [];
        $transitions = is_array($definition->transitions) ? $definition->transitions : [];

        foreach ($transitions as $t) {
            $trigger = $t['trigger']['type'] ?? $t['trigger_type'] ?? null;
            if ($trigger !== 'automatic') {
                continue; // only handle automatic triggers
            }

            $from = $t['from'] ?? [];
            $fromArr = is_array($from) ? $from : (empty($from) ? [] : [$from]);
            if (!in_array($currentPlace, $fromArr, true)) {
                continue; // not originating from current place
            }

            $guardProvider = $t['guard_provider'] ?? null;
            $canProceed = $this->decision->evaluate($guardProvider, array_merge($context, [
                'transition' => $t,
                'definition_id' => $definition->getKey(),
                'current_place' => $currentPlace,
            ]));

            if (!$canProceed) {
                $results[] = [
                    'transition' => ($t['name'] ?? $t['key'] ?? 'unknown'),
                    'status' => 'skipped',
                    'reason' => 'guard_failed',
                ];
                continue;
            }

            $decisionOptions = $t['decision_options'] ?? null;
            $strategyKey = $t['strategy_key'] ?? null;
            $assignment = $t['assignment'] ?? null; // single-mode only

            // Execute based on decision options
            $mode = $decisionOptions['assignment_mode'] ?? ($assignment ? 'single' : null);
            if ($mode === 'parallel' && isset($decisionOptions['parallel']['slots']) && is_array($decisionOptions['parallel']['slots'])) {
                $slotResults = [];
                $slotIdx = 0;
                foreach ($decisionOptions['parallel']['slots'] as $slot) {
                    $slotIdx++;
                    $am = $slot['assignment_method'] ?? $slot['method'] ?? 'role_claim';
                    $roleId = $slot['role_id'] ?? null;
                    $userId = $slot['user_id'] ?? null;

                    $slotCtx = array_merge($context, [
                        'guard' => $guardProvider,
                        'task_name' => ($t['name'] ?? $t['key'] ?? 'Task') . " #{$slotIdx}",
                        'decision_options' => $decisionOptions,
                        // common workflow fields
                        'state_id' => $context['state_id'] ?? null,
                        'instance_id' => $context['instance_id'] ?? null,
                    ]);

                    if ($am === 'user') {
                        $slotCtx['assignmentType'] = 'user';
                        $slotCtx['assignmentRef'] = $userId ?: ($context['user_id'] ?? null);
                        $slotCtx['strategyKey'] = null;
                    } elseif ($am === 'role_direct_user') {
                        // Require user selection at runtime, optionally restricted by role
                        $slotCtx['assignmentType'] = 'user';
                        $slotCtx['assignmentRef'] = null;
                        $slotCtx['strategyKey'] = null;
                        $slotCtx['decision_options'] = array_merge($decisionOptions ?? [], [
                            'requires_user_selection' => true,
                            'role_id' => $roleId,
                        ]);
                    } elseif ($am === 'role_least_busy') {
                        $slotCtx['assignmentType'] = 'strategy';
                        $slotCtx['assignmentRef'] = $roleId ?: ($context['role_id'] ?? null);
                        $slotCtx['strategyKey'] = 'least_busy';
                    } elseif ($am === 'role_round_robin') {
                        $slotCtx['assignmentType'] = 'strategy';
                        $slotCtx['assignmentRef'] = $roleId ?: ($context['role_id'] ?? null);
                        $slotCtx['strategyKey'] = 'round_robin';
                    } elseif ($am === 'role_random') {
                        $slotCtx['assignmentType'] = 'strategy';
                        $slotCtx['assignmentRef'] = $roleId ?: ($context['role_id'] ?? null);
                        $slotCtx['strategyKey'] = 'random';
                    } else { // role_claim and default
                        $slotCtx['assignmentType'] = 'role';
                        $slotCtx['assignmentRef'] = $roleId ?: ($context['role_id'] ?? null);
                        $slotCtx['strategyKey'] = null;
                    }

                    $slotResults[] = $this->engine->decideAndAssign($slotCtx);
                }

                $results[] = [
                    'transition' => ($t['name'] ?? $t['key'] ?? 'unknown'),
                    'status' => 'completed',
                    'mode' => 'parallel',
                    'createdTaskIds' => array_values(array_filter(array_map(fn($r) => $r['createdTaskId'] ?? null, $slotResults))),
                ];
                continue;
            }

            // Single mode: use explicit assignment object if present, else fall back to decision options
            $assignmentType = $assignment['type'] ?? null; // user|role|strategy
            $assignmentRef = $assignment['ref'] ?? null;
            if (!$assignmentType && is_array($decisionOptions)) {
                $am = $decisionOptions['assignment_method'] ?? null;
                if ($am === 'role_least_busy') {
                    $assignmentType = 'strategy';
                    $strategyKey = 'least_busy';
                } elseif ($am === 'role_round_robin') {
                    $assignmentType = 'strategy';
                    $strategyKey = 'round_robin';
                } elseif ($am === 'role_random') {
                    $assignmentType = 'strategy';
                    $strategyKey = 'random';
                } elseif ($am === 'role_direct_user') {
                    $assignmentType = 'user';
                    $assignmentRef = null;
                } elseif ($am === 'role_claim' || $am === null) {
                    $assignmentType = 'role';
                } else {
                    $assignmentType = 'user';
                }
                // attempt to resolve role/user ref from context in fallback mode
                $assignmentRef = $assignmentRef ?: ($context['role_id'] ?? $context['user_id'] ?? null);
            }

            $engineCtx = array_merge($context, [
                'guard' => $guardProvider,
                'assignmentType' => $assignmentType,
                'assignmentRef' => $assignmentRef,
                'strategyKey' => $strategyKey,
                'decision_options' => (isset($decisionOptions['assignment_method']) && $decisionOptions['assignment_method'] === 'role_direct_user')
                    ? array_merge($decisionOptions, ['requires_user_selection' => true])
                    : $decisionOptions,
                'task_name' => $t['name'] ?? $t['key'] ?? 'Task',
            ]);

            $res = $this->engine->decideAndAssign($engineCtx);
            $results[] = [
                'transition' => ($t['name'] ?? $t['key'] ?? 'unknown'),
                'status' => $res['createdTaskId'] ? 'completed' : 'skipped',
                'mode' => 'single',
                'createdTaskId' => $res['createdTaskId'] ?? null,
                'requiresUserSelection' => $res['requiresUserSelection'] ?? false,
            ];

            // If an action provider is defined, execute it after task creation decision
            $actionProvider = $t['action_provider'] ?? null;
            if ($actionProvider) {
                $this->decision->executeAction($actionProvider, array_merge($engineCtx, [
                    'result' => $res,
                ]));
            }
        }

        return [
            'definition_id' => $definition->getKey(),
            'current_place' => $currentPlace,
            'items' => $results,
        ];
    }
}
