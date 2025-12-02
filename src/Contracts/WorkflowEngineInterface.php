<?php

namespace Amir\Workflow\Contracts;

interface WorkflowEngineInterface
{
    public function decideAndAssign(array $context = []): array;
}
