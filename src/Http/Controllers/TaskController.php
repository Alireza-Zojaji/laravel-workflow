<?php

namespace Zojaji\Workflow\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Zojaji\Workflow\Models\WorkflowTask;
use Zojaji\Workflow\Contracts\WorkflowEngineInterface;
use Zojaji\Workflow\Facades\Workflow;

class TaskController extends Controller
{
    public function inbox(Request $request): JsonResponse
    {
        $userId = (string) ($request->query('user_id') ?? Auth::id());
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized', 'data' => []], 401);
        }

        $tasks = WorkflowTask::query()
            ->forUserInbox($userId)
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'name', 'status', 'instance_id', 'state_id', 'assigned_to', 'assignment_type']);

        return response()->json(['success' => true, 'data' => $tasks]);
    }

    public function claimables(Request $request): JsonResponse
    {
        $userId = (string) ($request->query('user_id') ?? Auth::id());
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized', 'data' => []], 401);
        }

        $tasks = WorkflowTask::query()
            ->claimableByUser($userId)
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'name', 'status', 'instance_id', 'state_id', 'assigned_to', 'assignment_type']);

        return response()->json(['success' => true, 'data' => $tasks]);
    }

    public function claim(Request $request, int $taskId): JsonResponse
    {
        $userId = (string) ($request->input('user_id') ?? Auth::id());
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $task = WorkflowTask::findOrFail($taskId);
        if ($task->assignment_type !== 'role') {
            return response()->json(['success' => false, 'message' => 'Task is not claimable'], 422);
        }

        $ok = $task->claimBy($userId);
        return response()->json(['success' => $ok, 'data' => $task->only(['id', 'assigned_to', 'assignment_type'])]);
    }

    public function store(Request $request, WorkflowEngineInterface $engine): JsonResponse
    {
        $data = $request->validate([
            'task_name' => 'required|string|max:190',
            'instance_id' => 'required|integer',
            'state_id' => 'nullable|integer',
            'assignment_type' => 'nullable|string|in:user,role,strategy',
            'assignment_ref' => 'nullable|string',
            'strategy_key' => 'nullable|string',
            'decision_options' => 'nullable|array',
        ]);

        $result = $engine->decideAndAssign([
            'task_name' => $data['task_name'],
            'instance_id' => $data['instance_id'],
            'state_id' => $data['state_id'] ?? null,
            'assignmentType' => $data['assignment_type'] ?? null,
            'assignmentRef' => $data['assignment_ref'] ?? null,
            'strategyKey' => $data['strategy_key'] ?? null,
            'decision_options' => $data['decision_options'] ?? null,
        ]);

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Complete a task and automatically route the workflow to next steps.
     */
    public function complete(Request $request, int $taskId): JsonResponse
    {
        $userId = (int) ($request->input('user_id') ?? Auth::id());
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $context = $request->input('context', []);
        $context = is_array($context) ? $context : [];

        $result = Workflow::completeAndRoute($taskId, $userId, $context);

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Finalize selection for role_direct_user by assigning task directly to chosen user.
     */
    public function finalize(Request $request): JsonResponse
    {
        $data = $request->validate([
            'instance_id' => 'required|integer',
            'state_id' => 'nullable|integer',
            'task_name' => 'required|string|max:190',
            'user_id' => 'required|integer',
            'role_id' => 'nullable|integer',
            'decision_options' => 'nullable|array',
        ]);

        $result = Workflow::finalizeSelection(
            (int) $data['instance_id'],
            isset($data['state_id']) ? (int) $data['state_id'] : null,
            (string) $data['task_name'],
            (int) $data['user_id'],
            isset($data['role_id']) ? (int) $data['role_id'] : null,
            $data['decision_options'] ?? []
        );

        $statusCode = isset($result['error']) ? 422 : 200;
        return response()->json(['success' => !isset($result['error']), 'data' => $result], $statusCode);
    }
}
