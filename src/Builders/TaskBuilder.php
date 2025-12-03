<?php

namespace Amir\Workflow\Builders;

use Amir\Workflow\Models\WorkflowTask;
use Amir\Workflow\Models\WorkflowState;
use Amir\Workflow\Models\WorkflowHistory;
use Amir\Workflow\Contracts\DecisionEngineInterface;
use Amir\Workflow\Services\AutomaticTriggerRunner;

class TaskBuilder
{
    private ?int $userId = null;
    private ?string $advancedToKey = null;
    private ?int $advancedToStateId = null;

    public function __construct(
        private readonly WorkflowTask $task,
        private readonly AutomaticTriggerRunner $runner,
        private readonly DecisionEngineInterface $decision,
    ) {
    }

    /**
     * Set the acting user for audit and assignment context.
     */
    public function withUser(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Mark the task as completed.
     */
    public function complete(array $metadata = []): self
    {
        $meta = is_array($this->task->metadata) ? $this->task->metadata : [];
        $meta = array_merge($meta, $metadata, [
            'completed_by' => $this->userId,
            'completed_at' => now()->toISOString(),
        ]);
        $this->task->status = 'completed';
        $this->task->metadata = $meta;
        $this->task->save();
        return $this;
    }

    /**
     * Advance the instance from its current place to the next valid place
     * based on guards. Records workflow history.
     */
    public function advance(array $context = []): self
    {
        $instance = $this->task->instance;
        $definition = $instance->definition;
        $currentState = $instance->currentState;
        $currentPlace = $currentState?->key ?? '';

        $transitions = is_array($definition->transitions) ? $definition->transitions : [];

        $toKey = null;
        $pickedTransition = null;

        foreach ($transitions as $t) {
            $from = $t['from'] ?? [];
            $fromArr = is_array($from) ? $from : (empty($from) ? [] : [$from]);
            if (!in_array($currentPlace, $fromArr, true)) {
                continue;
            }

            $conditional = is_array($t['conditional'] ?? null) ? $t['conditional'] : null;
            if ($conditional) {
                $decisionKey = (string) ($conditional['key'] ?? ($conditional['decision_key'] ?? 'decision'));
                $decisionVal = (string) ($context[$decisionKey] ?? '');
                if ($decisionVal !== '') {
                    $routes = is_array($conditional['routes'] ?? null) ? $conditional['routes'] : null;
                    if ($routes) {
                        foreach ($routes as $r) {
                            $rv = (string) ($r['value'] ?? '');
                            if ($rv === $decisionVal) {
                                $toKey = $r['to'] ?? null;
                                $pickedTransition = $t;
                                break;
                            }
                        }
                        if ($toKey) break;
                    } else {
                        if ($decisionVal === 'approve') {
                            $toKey = $conditional['approve_to'] ?? null;
                            $pickedTransition = $t;
                            if ($toKey) break;
                        } elseif ($decisionVal === 'reject') {
                            $toKey = $conditional['reject_to'] ?? null;
                            $pickedTransition = $t;
                            if ($toKey) break;
                        }
                    }
                }
            }

            $guardProvider = $t['guard_provider'] ?? null;
            $canProceed = $this->decision->evaluate($guardProvider, array_merge($context, [
                'transition' => $t,
                'definition_id' => $definition->getKey(),
                'current_place' => $currentPlace,
                'instance_id' => $instance->getKey(),
                'user_id' => $this->userId,
            ]));

            if (!$canProceed) {
                continue;
            }

            $to = $t['to'] ?? [];
            $toArr = is_array($to) ? $to : (empty($to) ? [] : [$to]);
            $toKey = $toArr[0] ?? null;
            $pickedTransition = $t;
            break;
        }

        if (!$toKey) {
            // No valid transition found; do not change state
            return $this;
        }

        $toState = WorkflowState::query()
            ->where('definition_id', $definition->getKey())
            ->where('key', $toKey)
            ->first();

        if (!$toState) {
            // Target state not found; abort gracefully
            return $this;
        }

        $fromStateId = $instance->current_state_id;

        $instance->current_state_id = $toState->getKey();

        // If final state, mark instance complete
        if (($toState->type ?? null) === 'final') {
            $instance->status = 'completed';
            $instance->completed_at = now();
        }

        $instance->save();

        // Record history
        WorkflowHistory::query()->create([
            'instance_id' => $instance->getKey(),
            'transition_id' => null,
            'from_state_id' => $fromStateId,
            'to_state_id' => $toState->getKey(),
            'performed_by' => $this->userId,
            'metadata' => [
                'transition' => $pickedTransition,
            ],
        ]);

        $this->advancedToKey = $toKey;
        $this->advancedToStateId = $toState->getKey();

        return $this;
    }

    /**
     * After advancing, assign tasks for the new place using automatic triggers.
     */
    public function autoAssign(array $context = []): array
    {
        $instance = $this->task->instance->refresh()->load(['definition', 'currentState']);
        $definition = $instance->definition;
        $placeKey = $this->advancedToKey ?? $instance->currentState?->key ?? '';

        $ctx = array_merge($context, [
            'instance_id' => $instance->getKey(),
            'state_id' => $this->advancedToStateId ?? $instance->current_state_id,
            'user_id' => $this->userId,
        ]);

        return $this->runner->runForDefinition($definition, $placeKey, $ctx);
    }

    /**
     * Expose underlying task if needed in chains.
     */
    public function getTask(): WorkflowTask
    {
        return $this->task;
    }

    /**
     * Convenience: complete task, advance instance, and auto-assign next tasks in one call.
     */
    public function completeAndAutoRoute(array $context = []): array
    {
        $this->complete();
        $this->advance($context);
        if ($this->advancedToKey !== null) {
            return $this->autoAssign($context);
        }

        $instance = $this->task->instance->refresh()->load(['definition', 'currentState']);
        return [
            'definition_id' => $instance->definition->getKey(),
            'current_place' => $instance->currentState?->key ?? '',
            'items' => [],
        ];
    }
}
