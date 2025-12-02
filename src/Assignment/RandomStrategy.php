<?php

namespace Amir\Workflow\Assignment;

use Amir\Workflow\Contracts\AssignmentStrategyInterface;

class RandomStrategy implements AssignmentStrategyInterface
{
    public function assign(array $pool, array $context): ?string
    {
        if (count($pool) === 0) {
            return null;
        }
        $index = random_int(0, count($pool) - 1);
        return (string) $pool[$index];
    }
}

