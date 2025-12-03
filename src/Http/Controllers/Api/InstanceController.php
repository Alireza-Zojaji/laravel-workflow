<?php

namespace Zojaji\Workflow\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Zojaji\Workflow\Models\WorkflowInstance;
use Zojaji\Workflow\Models\WorkflowDefinition;
use Zojaji\Workflow\Models\WorkflowState;
use Zojaji\Workflow\Services\AutomaticTriggerRunner;

class InstanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WorkflowInstance::query()->orderByDesc('id');

        if ($request->filled('definition_id')) {
            $query->where('definition_id', (int) $request->get('definition_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', (string) $request->get('status'));
        }
        if ($request->filled('model_type')) {
            $query->where('model_type', (string) $request->get('model_type'));
        }
        if ($request->filled('model_id')) {
            $query->where('model_id', (int) $request->get('model_id'));
        }

        $items = $query->limit(200)->get();
        return response()->json(['success' => true, 'data' => $items]);
    }

    public function show(int $id): JsonResponse
    {
        $instance = WorkflowInstance::query()
            ->with(['definition', 'currentState'])
            ->find($id);
        if (!$instance) {
            return response()->json(['success' => false, 'message' => 'Workflow instance not found'], 404);
        }
        $tasks = $instance->tasks()->orderByDesc('id')->limit(200)->get();
        return response()->json(['success' => true, 'data' => [
            'instance' => $instance,
            'tasks' => $tasks,
        ]]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'definition_id' => 'required|integer|exists:workflow_definitions,id',
            'model_type' => 'nullable|string|max:190',
            'model_id' => 'nullable|integer',
            'variables' => 'nullable|array',
        ]);

        $definition = WorkflowDefinition::findOrFail($data['definition_id']);
        $initialState = WorkflowState::query()
            ->where('definition_id', $definition->getKey())
            ->where('type', 'initial')
            ->orderBy('id')
            ->first();

        if (!$initialState) {
            return response()->json(['success' => false, 'message' => 'Initial state not defined for workflow'], 422);
        }

        $instance = new WorkflowInstance();
        $instance->definition_id = $definition->getKey();
        $instance->current_state_id = $initialState->getKey();
        $instance->status = 'running';
        $instance->model_type = $data['model_type'] ?? null;
        $instance->model_id = $data['model_id'] ?? null;
        $instance->variables = $data['variables'] ?? null;
        $instance->started_at = now();
        $instance->save();

        return response()->json(['success' => true, 'data' => $instance], 201);
    }

    public function runAutomatic(Request $request, int $id, AutomaticTriggerRunner $runner): JsonResponse
    {
        $instance = WorkflowInstance::query()->with(['definition', 'currentState'])->find($id);
        if (!$instance) {
            return response()->json(['success' => false, 'message' => 'Workflow instance not found'], 404);
        }

        $definition = $instance->definition;
        $currentState = $instance->currentState;
        if (!$definition || !$currentState) {
            return response()->json(['success' => false, 'message' => 'Instance missing definition or state'], 422);
        }

        // build context for runner
        $context = [
            'instance_id' => $instance->getKey(),
            'state_id' => $currentState->getKey(),
            'user_id' => (string) ($request->get('user_id') ?? Auth::id()),
            'role_id' => $request->get('role_id'),
            'variables' => $instance->variables ?? [],
        ];

        $result = $runner->runForDefinition($definition, (string) $currentState->key, $context);

        return response()->json(['success' => true, 'data' => $result]);
    }
}

