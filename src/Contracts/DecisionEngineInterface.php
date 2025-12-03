<?php

namespace Zojaji\Workflow\Contracts;

interface DecisionEngineInterface
{
    public function evaluate(?string $guardProvider = null, array $context = []): bool;

    public function executeAction(?string $actionProvider = null, array $context = []): void;
}
