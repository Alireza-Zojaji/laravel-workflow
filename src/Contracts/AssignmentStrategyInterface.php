<?php

namespace Amir\Workflow\Contracts;

interface AssignmentStrategyInterface
{
    public function assign(array $pool, array $context): ?string;
}
