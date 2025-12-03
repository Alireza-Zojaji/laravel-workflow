<?php

namespace Zojaji\Workflow\Assignment;

use Zojaji\Workflow\Contracts\AssignmentStrategyInterface;

class RoundRobinStrategy implements AssignmentStrategyInterface
{
    public function assign(array $pool, array $context): ?string
    {
        $count = count($pool);
        if ($count === 0) {
            return null;
        }
        $cursor = $context['round_robin_cursor'] ?? 0;
        $index = $cursor % $count;
        return (string) $pool[$index];
    }
}
