<?php

namespace Amir\Workflow\Builders;

use Amir\Workflow\Models\WorkflowInstance;
use Amir\Workflow\Services\AutomaticTriggerRunner;

class InstanceBuilder
{
    public function __construct(
        private readonly WorkflowInstance $instance,
        private readonly AutomaticTriggerRunner $runner,
    ) {
    }

    /**
     * Execute all automatic transitions originating from the instance's current place.
     * Returns detailed results from the runner.
     */
    public function autoRun(array $context = []): array
    {
        $definition = $this->instance->definition;
        $currentState = $this->instance->currentState;
        $currentPlace = $currentState?->key ?? '';

        $ctx = array_merge($context, [
            'instance_id' => $this->instance->getKey(),
            'state_id' => $this->instance->current_state_id,
        ]);

        return $this->runner->runForDefinition($definition, $currentPlace, $ctx);
    }

    /**
     * Access the underlying instance if needed in chains.
     */
    public function getInstance(): WorkflowInstance
    {
        return $this->instance;
    }
}

