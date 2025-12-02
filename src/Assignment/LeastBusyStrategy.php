<?php

namespace Amir\Workflow\Assignment;

use Amir\Workflow\Contracts\AssignmentStrategyInterface;

class LeastBusyStrategy implements AssignmentStrategyInterface
{
    public function assign(array $pool, array $context): ?string
    {
        // Real implementation requires workload metrics; using a simple fallback for now
        return $pool[0] ?? null;
    }
}
