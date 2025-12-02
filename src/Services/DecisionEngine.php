<?php

namespace Amir\Workflow\Services;

use Amir\Workflow\Contracts\DecisionEngineInterface;

class DecisionEngine implements DecisionEngineInterface
{
    protected array $providers;

    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
    }

    public function evaluate(?string $guardProvider = null, array $context = []): bool
    {
        if ($guardProvider && isset($this->providers[$guardProvider]) && is_callable($this->providers[$guardProvider])) {
            return (bool) call_user_func($this->providers[$guardProvider], $context);
        }

        return true;
    }

    public function executeAction(?string $actionProvider = null, array $context = []): void
    {
        if ($actionProvider && isset($this->providers[$actionProvider]) && is_callable($this->providers[$actionProvider])) {
            call_user_func($this->providers[$actionProvider], $context);
        }
    }
}
