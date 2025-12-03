<?php

namespace Zojaji\Workflow\Contracts;

interface TaskAssignerInterface
{
    public function assign(
        ?string $assignmentType = null,
        ?string $assignmentRef = null,
        ?string $strategyKey = null,
        array $context = []
    ): ?string;
}
