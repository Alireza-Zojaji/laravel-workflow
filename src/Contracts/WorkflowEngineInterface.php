<?php

namespace Zojaji\Workflow\Contracts;

interface WorkflowEngineInterface
{
    public function decideAndAssign(array $context = []): array;
}
