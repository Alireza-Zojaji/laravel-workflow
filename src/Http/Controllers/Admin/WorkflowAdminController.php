<?php

namespace Amir\Workflow\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Amir\Workflow\Models\WorkflowDefinition;

class WorkflowAdminController extends Controller
{
    public function index()
    {
        $definitions = WorkflowDefinition::orderBy('name')->get();

        // If database is empty, read definitions from config (display only)
        $configDefs = config('workflow', []);

        return view('workflow::admin.workflows.index', compact('definitions', 'configDefs'));
    }

    public function showConfig(string $key)
    {
        $configDefs = config('workflow', []);
        $conf = $configDefs[$key] ?? null;
        if (!$conf || !is_array($conf)) {
            abort(404, 'Workflow config definition not found');
        }

        // Normalize values for view convenience
        $type = $conf['type'] ?? null;
        $markingStore = $conf['marking_store'] ?? null;
        $places = is_array($conf['places'] ?? null) ? $conf['places'] : [];
        $supports = is_array($conf['supports'] ?? null) ? $conf['supports'] : [];

        // Transitions: associative name => {from, to}
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
                ];
            }
        }

        return view('workflow::admin.workflows.show_config', [
            'key' => $key,
            'type' => $type,
            'markingStore' => $markingStore,
            'places' => $places,
            'supports' => $supports,
            'transitions' => $transitions,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:190|unique:workflow_definitions,name',
            'label' => 'nullable|string|max:190',
            'marking_store' => 'nullable|string|max:190',
            'places_json' => 'nullable|string',
            'transitions_json' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $places = $this->decodeJson($data['places_json'] ?? null);
        $transitions = $this->decodeJson($data['transitions_json'] ?? null);

        WorkflowDefinition::create([
            'name' => $data['name'],
            'label' => $data['label'] ?? null,
            'marking_store' => $data['marking_store'] ?? null,
            'places' => $places,
            'transitions' => $transitions,
            'is_active' => $data['is_active'] ?? true,
            'version' => 1,
        ]);

        return redirect()->route('workflow.admin.index')->with('status', 'Workflow created.');
    }

    public function edit(int $id)
    {
        $definition = WorkflowDefinition::findOrFail($id);
        return view('workflow::admin.workflows.edit', compact('definition'));
    }

    public function update(Request $request, int $id)
    {
        $definition = WorkflowDefinition::findOrFail($id);

        $data = $request->validate([
            'label' => 'nullable|string|max:190',
            'marking_store' => 'nullable|string|max:190',
            'places_json' => 'nullable|string',
            'transitions_json' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $definition->update([
            'label' => $data['label'] ?? $definition->label,
            'marking_store' => $data['marking_store'] ?? $definition->marking_store,
            'places' => $this->decodeJson($data['places_json'] ?? null) ?? $definition->places,
            'transitions' => $this->decodeJson($data['transitions_json'] ?? null) ?? $definition->transitions,
            'is_active' => $data['is_active'] ?? $definition->is_active,
        ]);

        return redirect()->route('workflow.admin.index')->with('status', 'Workflow updated.');
    }

    public function destroy(int $id)
    {
        $definition = WorkflowDefinition::findOrFail($id);
        $definition->delete();

        return redirect()->route('workflow.admin.index')->with('status', 'Workflow deleted.');
    }

    private function decodeJson(?string $json): ?array
    {
        if (!$json) return null;
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }
}
