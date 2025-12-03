<?php

namespace Zojaji\Workflow\Services;

use Zojaji\Workflow\Contracts\TaskAssignerInterface;
use Zojaji\Workflow\Support\Models;

class TaskAssigner implements TaskAssignerInterface
{
    protected array $strategies;

    public function __construct(array $strategies = [])
    {
        // Normalize strategy registry entries into instances implementing AssignmentStrategyInterface
        $this->strategies = [];
        foreach ($strategies as $key => $handler) {
            try {
                if (is_object($handler)) {
                    $this->strategies[$key] = $handler;
                } elseif (is_string($handler) && class_exists($handler)) {
                    $this->strategies[$key] = new $handler();
                } elseif (is_callable($handler)) {
                    $this->strategies[$key] = $handler; // allow raw callable for advanced cases
                }
            } catch (\Throwable $e) {
                // Skip invalid handlers silently
            }
        }
    }

    public function assign(?string $assignmentType = null, ?string $assignmentRef = null, ?string $strategyKey = null, array $context = []): ?string
    {
        // Direct user assignment
        if ($assignmentType === 'user' && !empty($assignmentRef)) {
            return (string) $assignmentRef;
        }

        // Role assignment: return null to signal role-level assignment (to be claimed later)
        if ($assignmentType === 'role') {
            return null;
        }

        // Strategy-based assignment: build pool from role ref if provided
        if ($assignmentType === 'strategy' && $strategyKey) {
            $strategy = $this->strategies[$strategyKey] ?? null;
            if (!$strategy) {
                return null;
            }

            // Resolve pool of candidate users based on role reference if available
            $pool = [];
            try {
                if (!empty($assignmentRef)) {
                    // assignmentRef is expected to be Role ID
                    $roleModelClass = Models::roleModel();
                    if (class_exists($roleModelClass)) {
                        $role = $roleModelClass::query()->whereKey($assignmentRef)->first();
                        if ($role) {
                            $pool = $role->users()->pluck('id')->map(fn($id) => (string) $id)->all();
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Fallback to empty pool
            }

            // If no pool resolved, allow strategy to decide (may return null)
            $ctx = $context;
            $ctx['assignmentType'] = $assignmentType;
            $ctx['assignmentRef'] = $assignmentRef;
            if (is_callable($strategy)) {
                $result = call_user_func($strategy, $pool, $ctx);
            } else {
                // Object strategy implementing AssignmentStrategyInterface::assign(array $pool, array $context)
                $result = method_exists($strategy, 'assign') ? $strategy->assign($pool, $ctx) : null;
            }

            return $result ? (string) $result : null;
        }

        return null;
    }
}
