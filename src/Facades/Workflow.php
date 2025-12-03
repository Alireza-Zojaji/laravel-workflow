<?php

namespace Zojaji\Workflow\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \\Zojaji\\Workflow\\Builders\\InstanceBuilder instance(int $instanceId)
 * @method static \\Zojaji\\Workflow\\Builders\\TaskBuilder onTask(int $taskId, ?int $userId = null)
 * @method static \\Illuminate\\Database\\Eloquent\\Builder tasks()
 * @method static \\Illuminate\\Database\\Eloquent\\Builder history(int $instanceId)
 * @method static array completeAndRoute(int $taskId, int $userId, array $context = [])
 * @method static array finalizeSelection(int $instanceId, ?int $stateId, string $taskName, int $userId, ?int $roleId = null, array $decisionOptions = [])
 */
class Workflow extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'workflow';
    }
}
