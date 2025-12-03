<?php

namespace Zojaji\Workflow\Contracts;

interface AssignmentStrategyInterface
{
    public function assign(array $pool, array $context): ?string;
}
