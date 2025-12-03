<?php

namespace Zojaji\Workflow\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Zojaji\Workflow\Models\WorkflowDefinition;

class DefinitionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WorkflowDefinition::query()->orderBy('name');
        if ($request->boolean('active')) {
            $query->where('is_active', true);
        }
        $items = $query->get();
        return response()->json(['success' => true, 'data' => $items]);
    }

    public function show(int $id): JsonResponse
    {
        $def = WorkflowDefinition::find($id);
        if (!$def) {
            return response()->json(['success' => false, 'message' => 'Workflow definition not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $def]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:190|unique:workflow_definitions,name',
            'label' => 'nullable|string|max:190',
            'marking_store' => 'nullable|string|max:190',
            'places' => 'nullable|array',
            'transitions' => 'nullable|array',
            'places_json' => 'nullable|string',
            'transitions_json' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $places = $data['places'] ?? $this->decodeJson($data['places_json'] ?? null) ?? [];
        $transitions = $data['transitions'] ?? $this->decodeJson($data['transitions_json'] ?? null) ?? [];

        $def = WorkflowDefinition::create([
            'name' => $data['name'],
            'label' => $data['label'] ?? null,
            'marking_store' => $data['marking_store'] ?? null,
            'places' => $places,
            'transitions' => $transitions,
            'is_active' => $data['is_active'] ?? true,
            'version' => 1,
        ]);

        return response()->json(['success' => true, 'data' => $def], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $def = WorkflowDefinition::find($id);
        if (!$def) {
            return response()->json(['success' => false, 'message' => 'Workflow definition not found'], 404);
        }

        $data = $request->validate([
            'label' => 'nullable|string|max:190',
            'marking_store' => 'nullable|string|max:190',
            'places' => 'nullable|array',
            'transitions' => 'nullable|array',
            'places_json' => 'nullable|string',
            'transitions_json' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $def->update([
            'label' => $data['label'] ?? $def->label,
            'marking_store' => $data['marking_store'] ?? $def->marking_store,
            'places' => $data['places'] ?? $this->decodeJson($data['places_json'] ?? null) ?? $def->places,
            'transitions' => $data['transitions'] ?? $this->decodeJson($data['transitions_json'] ?? null) ?? $def->transitions,
            'is_active' => $data['is_active'] ?? $def->is_active,
        ]);

        return response()->json(['success' => true, 'data' => $def]);
    }

    public function destroy(int $id): JsonResponse
    {
        $def = WorkflowDefinition::find($id);
        if (!$def) {
            return response()->json(['success' => false, 'message' => 'Workflow definition not found'], 404);
        }
        $def->delete();
        return response()->json(['success' => true]);
    }

    public function configIndex(): JsonResponse
    {
        $configDefs = config('workflow', []);
        $list = collect($configDefs)->map(function ($conf, $key) {
            return [
                'key' => (string) $key,
                'type' => $conf['type'] ?? null,
                'label' => $conf['label'] ?? null,
            ];
        })->values();
        return response()->json(['success' => true, 'data' => $list]);
    }

    public function configShow(string $key): JsonResponse
    {
        $conf = config('workflow.' . $key);
        if (!$conf || !is_array($conf)) {
            return response()->json(['success' => false, 'message' => 'Workflow config definition not found'], 404);
        }

        $type = $conf['type'] ?? null;
        $markingStore = $conf['marking_store'] ?? null;
        $places = is_array($conf['places'] ?? null) ? $conf['places'] : [];
        $supports = is_array($conf['supports'] ?? null) ? $conf['supports'] : [];
        $transitions = [];
        if (is_array($conf['transitions'] ?? null)) {
            foreach ($conf['transitions'] as $tName => $tConf) {
                $fromVal = $tConf['from'] ?? null;
                $toVal = $tConf['to'] ?? null;
                $transitions[] = [
                    'name' => $tName,
                    'from' => is_array($fromVal) ? $fromVal : ($fromVal ? [$fromVal] : []),
                    'to' => is_array($toVal) ? $toVal : ($toVal ? [$toVal] : []),
                    'guard_provider' => $tConf['guard_provider'] ?? null,
                    'trigger' => $tConf['trigger'] ?? null,
                    'assignment' => $tConf['assignment'] ?? null,
                    'conditional' => $tConf['conditional'] ?? null,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'key' => $key,
                'type' => $type,
                'marking_store' => $markingStore,
                'places' => $places,
                'supports' => $supports,
                'transitions' => $transitions,
            ],
        ]);
    }

    private function decodeJson(?string $json): ?array
    {
        if (!$json) return null;
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }
}
