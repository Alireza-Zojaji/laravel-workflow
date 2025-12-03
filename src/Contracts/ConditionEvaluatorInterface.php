<?php

namespace Zojaji\Workflow\Contracts;

interface ConditionEvaluatorInterface
{
    public function evaluate(array $condition, array $context): bool;
}
