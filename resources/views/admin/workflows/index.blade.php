<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workflow Management</title>
    <script>
        (function() {
            var orig = console.warn;
            console.warn = function() {
                if (arguments[0] && typeof arguments[0] === 'string' && arguments[0].includes('cdn.tailwindcss.com')) {
                    return;
                }
                orig.apply(console, arguments);
            };
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom scrollbar for tables if needed */
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>
<body class="h-full font-sans text-gray-900 antialiased">

    <div class="min-h-full">
        <!-- Navigation / Header -->
        <nav class="bg-indigo-600 shadow-lg">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-indigo-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </div>
                        <div class="ml-4 text-xl font-bold text-white tracking-tight">
                            Workflow Manager
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <main class="py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-10">

                <!-- Status Message -->
                @if(session('status'))
                    <div class="rounded-md bg-green-50 p-4 shadow-sm border border-green-200">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ session('status') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Section 1: Saved Workflows -->
                <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Saved Workflows (DB)</h3>
                        <p class="mt-1 text-sm text-gray-500">Workflows currently stored in the database.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Label</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active?</th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Edit</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($definitions as $def)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $def->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $def->label }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $def->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $def->is_active ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('workflow.admin.edit', $def->id) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">Edit</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">No definitions found in database.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Section 2: Create New Workflow -->
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Create New Workflow</h3>
                        <p class="mt-1 text-sm text-gray-500">Define a new workflow with places and transitions.</p>
                    </div>

                    <form id="createWorkflowForm" method="post" action="{{ route('workflow.admin.store') }}" class="p-6">
                        @csrf

                        <div class="grid grid-cols-1 gap-y-8 gap-x-8 lg:grid-cols-2">

                            <!-- Left Column: Basic Info -->
                            <div class="space-y-6">
                                <div class="bg-gray-50 p-4 rounded-md border border-gray-200">
                                    <h4 class="text-md font-semibold text-gray-800 mb-4">Basic Information</h4>
                                    <div class="space-y-4">
                                        <div>
                                            <label for="name" class="block text-sm font-medium text-gray-700">Name *</label>
                                            <div class="mt-1">
                                                <input type="text" name="name" id="name" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                                            </div>
                                        </div>

                                        <div>
                                            <label for="label" class="block text-sm font-medium text-gray-700">Label</label>
                                            <div class="mt-1">
                                                <input type="text" name="label" id="label" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                                            </div>
                                        </div>

                                        <div>
                                            <label for="markingStoreSelect" class="block text-sm font-medium text-gray-700">Marking Store</label>
                                            <div class="mt-1">
                                                <select name="marking_store" id="markingStoreSelect" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                                                    <option value="single_state" selected>single_state</option>
                                                    <option value="multiple_state">multiple_state</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="flex items-start">
                                            <div class="flex h-5 items-center">
                                                <input type="hidden" name="is_active" value="0" />
                                                <input id="is_active" name="is_active" type="checkbox" value="1" checked class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="is_active" class="font-medium text-gray-700">Active</label>
                                                <p class="text-gray-500">Enable this workflow immediately.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Places -->
                            <div class="space-y-6">
                                <div class="bg-gray-50 p-4 rounded-md border border-gray-200">
                                    <h4 class="text-md font-semibold text-gray-800 mb-4">Places</h4>

                                    <div class="flex gap-2 mb-4">
                                        <input id="placeInput" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border" placeholder="e.g. draft" />
                                        <button id="addPlaceBtn" type="button" class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                            Add
                                        </button>
                                    </div>

                                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg bg-white">
                                        <table id="placesTable" class="min-w-full divide-y divide-gray-300">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="py-2 pl-4 pr-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 sm:pl-6">#</th>
                                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Name</th>
                                                    <th scope="col" class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 bg-white">
                                                <!-- Dynamic places will be added here -->
                                            </tbody>
                                        </table>
                                        <div id="placesEmptyState" class="p-4 text-center text-sm text-gray-500 italic">No places added yet.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transitions Section (Full Width) -->
                        <div class="mt-8 bg-gray-50 p-4 rounded-md border border-gray-200">
                            <h4 class="text-md font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Transitions Management</h4>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 mb-6">
                                <div class="col-span-1 md:col-span-2 lg:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700">Transition Name</label>
                                    <input id="transitionNameInput" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border" placeholder="e.g. publish" />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">From (Hold Ctrl to select multiple)</label>
                                    <select id="fromSelect" multiple class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm h-32 border"></select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">To (Hold Ctrl to select multiple)</label>
                                    <select id="toSelect" multiple class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm h-32 border"></select>
                                </div>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Guard Provider (Optional)</label>
                                        <input id="guardProviderInput" type="text" placeholder="e.g. isHighPriority" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Trigger Type</label>
                                        <select id="triggerTypeSelect" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                                            <option value="">- Select -</option>
                                            <option value="manual">manual</option>
                                            <option value="automatic">automatic</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Decision Mode</label>
                                        <select id="decisionModeSelect" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                                            <option value="none" selected>none</option>
                                            <option value="approve">approve</option>
                                            <option value="reject">reject</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white p-4 rounded-md border border-gray-200 mb-4">
                                <h5 class="text-sm font-medium text-gray-700 mb-3">Assignment (Destination)</h5>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                                    <div id="assignmentModeGroup" style="display:block;">
                                        <label class="block text-xs font-medium text-gray-500 uppercase">Assignment Mode</label>
                                        <select id="assignmentModeSelect" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                                            <option value="single">single</option>
                                            <option value="parallel">parallel</option>
                                        </select>
                                    </div>
                                    <div id="assignmentTypeGroup" style="display:none;">
                                        <label class="block text-xs font-medium text-gray-500 uppercase">Type</label>
                                        <select id="assignmentTypeSelect" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                                            <option value="">- None -</option>
                                            <option value="user">user</option>
                                            <option value="role">role</option>
                                        </select>
                                    </div>
                                    <div id="assignmentUserGroup" style="display:none;">
                                        <label class="block text-xs font-medium text-gray-500 uppercase">Role for runtime user selection</label>
                                        <select id="userSelectionRoleSelect" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                                            <option value="">- Select Role -</option>
                                        </select>
                                    </div>
                                    <div id="assignmentRoleGroup" style="display:none;">
                                        <label class="block text-xs font-medium text-gray-500 uppercase">Assign to Role</label>
                                        <select id="roleSelect" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                                            <option value="">- Select Role -</option>
                                        </select>
                                    </div>
                                    <div id="assignmentMethodGroup" style="display:none;">
                                        <label class="block text-xs font-medium text-gray-500 uppercase">Assignment Method</label>
                                        <select id="assignmentMethodSelect" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                                            <option value="role_claim">Claim by role members (first-come)</option>
                                            <option value="role_direct_user">Direct user chosen by referrer</option>
                                            <option value="role_least_busy">Least busy member</option>
                                            <option value="role_round_robin">Round robin</option>
                                            <option value="role_random">Random assignment</option>
                                        </select>
                                    </div>
                                    <div id="parallelConfigGroup" style="display:none;" class="md:col-span-4">
                                        <label class="block text-xs font-medium text-gray-500 uppercase">Parallel Count (n)</label>
                                        <input id="parallelCountInput" type="number" min="1" value="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border" />
                                        <div id="parallelSlotsContainer" class="mt-3 space-y-3"></div>
                                    </div>
                                    <div id="singleConfigGroup" style="display:block;" class="md:col-span-4">
                                        <label class="block text-xs font-medium text-gray-500 uppercase">Single Assignment Options</label>
                                        <div id="singleSlotContainer" class="mt-3 space-y-3"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button id="addTransitionBtn" type="button" class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    <svg class="mr-2 -ml-1 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                    Add Transition
                                </button>
                            </div>

                            <div class="mt-6 overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg bg-white">
                                <table id="transitionsTable" class="min-w-full divide-y divide-gray-300">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="py-2 pl-4 pr-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 sm:pl-6">#</th>
                                            <th scope="col" class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Name</th>
                                            <th scope="col" class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">From</th>
                                            <th scope="col" class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">To</th>
                                            <th scope="col" class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Guard</th>
                                            <th scope="col" class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Trigger</th>
                                            <th scope="col" class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Assignment</th>
                                            <th scope="col" class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        <!-- Dynamic transitions will be added here -->
                                    </tbody>
                                </table>
                                <div id="transitionsEmptyState" class="p-4 text-center text-sm text-gray-500 italic">No transitions added yet.</div>
                            </div>
                        </div>

                        <!-- Hidden Inputs -->
                        <div class="mt-6 bg-gray-100 p-4 rounded-md border border-gray-200 hidden"> <!-- Kept hidden visually unless debugging needed, but user script hides it anyway via css or just structure. Let's make them visible but small for debugging or hidden input type. The original code had them as textareas. I'll keep them as textareas but maybe hide them or put them in a "Debug" details tag. -->
                             <details>
                                <summary class="text-xs text-gray-500 cursor-pointer">Show JSON Data (Debug)</summary>
                                <div class="mt-2 space-y-2">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">places (JSON)</label>
                                        <textarea name="places_json" id="placesJson" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs border font-mono" readonly></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">transitions (JSON)</label>
                                        <textarea name="transitions_json" id="transitionsJson" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs border font-mono" readonly></textarea>
                                    </div>
                                </div>
                             </details>
                        </div>

                        <div class="mt-8 flex justify-end border-t border-gray-200 pt-6">
                            <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-green-600 py-3 px-6 text-base font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                Save Workflow
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Section 3: Config Definitions -->
                <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Definitions from Config (read-only)</h3>
                        <p class="mt-1 text-sm text-gray-500">Workflows defined in configuration files.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marking Store</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">States Count</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transitions Count</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($configDefs as $key => $conf)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $key }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ is_array($conf['marking_store'] ?? null)
                                                ? ($conf['marking_store']['type'] ?? '-')
                                                : ($conf['marking_store'] ?? '-') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ is_array($conf['places'] ?? null) ? count($conf['places']) : '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ is_array($conf['transitions'] ?? null) ? count($conf['transitions']) : '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('workflow.admin.config.show', $key) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">Workflow config is empty.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', () => {
        const placeInput = document.getElementById('placeInput');
        const addPlaceBtn = document.getElementById('addPlaceBtn');
        const placesTableBody = document.querySelector('#placesTable tbody');
        const placesEmptyState = document.getElementById('placesEmptyState');
        const placesTextarea = document.querySelector('textarea[name="places_json"]');

        const transitionNameInput = document.getElementById('transitionNameInput');
        const fromSelect = document.getElementById('fromSelect');
        const toSelect = document.getElementById('toSelect');
        const guardProviderInput = document.getElementById('guardProviderInput');
        const triggerTypeSelect = document.getElementById('triggerTypeSelect');
        const decisionModeSelect = document.getElementById('decisionModeSelect');
        const assignmentTypeSelect = document.getElementById('assignmentTypeSelect');
        const assignmentModeSelect = document.getElementById('assignmentModeSelect');
        const userSelectionRoleSelect = document.getElementById('userSelectionRoleSelect');
        const roleSelect = document.getElementById('roleSelect');
        const assignmentMethodSelect = document.getElementById('assignmentMethodSelect');
        const assignmentUserGroup = document.getElementById('assignmentUserGroup');
        const assignmentModeGroup = document.getElementById('assignmentModeGroup');
        const assignmentRoleGroup = document.getElementById('assignmentRoleGroup');
        const parallelConfigGroup = document.getElementById('parallelConfigGroup');
        const parallelCountInput = document.getElementById('parallelCountInput');
        const parallelSlotsContainer = document.getElementById('parallelSlotsContainer');
        const addTransitionBtn = document.getElementById('addTransitionBtn');
        const transitionsTableBody = document.querySelector('#transitionsTable tbody');
        const transitionsEmptyState = document.getElementById('transitionsEmptyState');
        const transitionsTextarea = document.querySelector('textarea[name="transitions_json"]');

        let transitions = [];
        let places = [];
        // Keep lookups and slot state global for shared access across functions

        function getSelectedValues(selectEl) {
            return Array.from(selectEl.selectedOptions || []).map(o => o.value);
        }

        // --- Places Logic ---
        function rebuildPlacesJson() {
            placesTextarea.value = JSON.stringify(places);
            updatePlaceSelects();
        }

        function updatePlaceSelects() {
            // Save current selections
            const currentFrom = getSelectedValues(fromSelect);
            const currentTo = getSelectedValues(toSelect);

            fromSelect.innerHTML = '';
            toSelect.innerHTML = '';

            places.forEach(p => {
                const opt1 = new Option(p, p);
                const opt2 = new Option(p, p);
                fromSelect.add(opt1);
                toSelect.add(opt2);
            });

            // Restore selections if possible
            Array.from(fromSelect.options).forEach(o => {
                if(currentFrom.includes(o.value)) o.selected = true;
            });
            Array.from(toSelect.options).forEach(o => {
                if(currentTo.includes(o.value)) o.selected = true;
            });
        }

        // Legacy conditional UI removed in favor of three-state Decision Mode

        function renderPlaces() {
            placesTableBody.innerHTML = '';
            if (places.length === 0) {
                placesEmptyState.style.display = 'block';
            } else {
                placesEmptyState.style.display = 'none';
                places.forEach((p, idx) => {
                    const tr = document.createElement('tr');
                    tr.className = "hover:bg-gray-50";
                    tr.innerHTML = `
                        <td class="whitespace-nowrap py-2 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">${idx + 1}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-500">${p}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-right text-sm font-medium">
                            <button data-idx="${idx}" type="button" class="text-red-600 hover:text-red-900 hover:underline remove-place-btn">Remove</button>
                        </td>
                    `;
                    placesTableBody.appendChild(tr);
                });
            }
            rebuildPlacesJson();
        }

        addPlaceBtn.addEventListener('click', () => {
            const val = (placeInput.value || '').trim();
            if (!val) return;
            if (places.includes(val)) {
                alert('Place already exists!');
                return;
            }
            places.push(val);
            placeInput.value = '';
            renderPlaces();
        });

        placesTableBody.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-place-btn')) {
                const idx = parseInt(e.target.dataset.idx, 10);
                const placeName = places[idx];

                // Remove transitions that use this place
                transitions = transitions.filter(t => {
                    const fromArr = Array.isArray(t.from) ? t.from : [t.from];
                    const toArr = Array.isArray(t.to) ? t.to : [t.to];
                    // If place is involved in from or to, remove the transition
                    if (fromArr.includes(placeName) || toArr.includes(placeName)) {
                        return false;
                    }
                    return true;
                });
                renderTransitions();

                places.splice(idx, 1);
                renderPlaces();
            }
        });

        // --- Transitions Logic ---

        function rebuildTransitionsJson() {
            transitionsTextarea.value = JSON.stringify(transitions.map(t => ({
                name: t.name || '',
                from: Array.isArray(t.from) ? t.from : (t.from ? [t.from] : []),
                to: Array.isArray(t.to) ? t.to : (t.to ? [t.to] : []),
                guard_provider: t.guard_provider || undefined,
                trigger: t.trigger || undefined,
                assignment: t.assignment || undefined,
                decision_options: t.decision_options || undefined,
                strategy_key: t.strategy_key || undefined,
                conditional: t.conditional || undefined
            })));
        }

        function renderTransitions() {
            transitionsTableBody.innerHTML = '';
            if (transitions.length === 0) {
                transitionsEmptyState.style.display = 'block';
            } else {
                transitionsEmptyState.style.display = 'none';
                transitions.forEach((t, idx) => {
                    const fromArr = Array.isArray(t.from) ? t.from : (t.from ? [t.from] : []);
                    const toArr = Array.isArray(t.to) ? t.to : (t.to ? [t.to] : []);
                    const assignmentText = t.assignment
                        ? (t.assignment.type || '-') +
                        (t.assignment.ref ? `:${t.assignment.ref}` : '')
                        : '-';

                    const tr = document.createElement('tr');
                    tr.className = "hover:bg-gray-50";
                    tr.innerHTML = `
                        <td class="whitespace-nowrap py-2 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">${idx + 1}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-900 font-semibold">${t.name || ''}</td>
                        <td class="px-3 py-2 text-sm text-gray-500"><span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">${fromArr.join(', ')}</span></td>
                        <td class="px-3 py-2 text-sm text-gray-500"><span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10">${toArr.join(', ')}</span></td>
                        <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-500">${t.guard_provider || '-'}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-500">${t.trigger?.type || '-'}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-500">${assignmentText}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-right text-sm font-medium">
                            <button data-idx="${idx}" type="button" class="text-red-600 hover:text-red-900 hover:underline remove-transition-btn">Remove</button>
                        </td>
                    `;
                    transitionsTableBody.appendChild(tr);
                });
            }
            rebuildTransitionsJson();
        }

        addTransitionBtn.addEventListener('click', () => {
            const name = (transitionNameInput.value || '').trim();
            const fromVals = getSelectedValues(fromSelect);
            const toVals = getSelectedValues(toSelect);
            const guardProvider = (guardProviderInput.value || '').trim();
            const triggerType = triggerTypeSelect.value || '';
            const assignmentType = assignmentTypeSelect.value || '';

            if (!name || fromVals.length === 0 || toVals.length === 0) {
                alert('Please fill in Name, From, and To fields.');
                return;
            }

            // Build assignment configuration per requirements (no user selection at design-time)
            let assignmentObj = undefined;
            let decisionOptions = undefined;
            const mode = assignmentModeSelect.value || 'single';
            if (mode === 'parallel') {
                // Parallel: each slot can have its own method and params
                const count = Math.max(1, parseInt(parallelCountInput.value || '1', 10));
                const slots = parallelSlots.map(s => {
                    const roleId = s.role_id || undefined;
                    if (s.method === 'user') {
                        return {
                            method: 'user',
                            user_id: s.user_id || undefined
                        };
                    }
                    const am = s.assignment_method || 'role_claim';
                    const slot = {
                        method: 'role',
                        role_id: roleId,
                        assignment_method: am
                    };
                    if (am === 'role_least_busy') slot.strategy_key = 'least_busy';
                    else if (am === 'role_round_robin') slot.strategy_key = 'round_robin';
                    else if (am === 'role_random') slot.strategy_key = 'random';
                    return slot;
                });
                decisionOptions = {
                    assignment_mode: 'parallel',
                    parallel: { count, slots }
                };
                assignmentObj = undefined; // summarized by decision_options
            } else {
                // Single via unified singleSlot UI
                const s = singleSlot || { method: 'user', role_id: '' };
                const roleId = s.role_id || undefined;
                if (s.method === 'user') {
                    assignmentObj = s.user_id ? { type: 'user', ref: s.user_id } : { type: 'user' };
                    decisionOptions = { assignment_mode: 'single' };
                } else {
                    const am = s.assignment_method || 'role_claim';
                    if (am === 'role_claim') {
                        assignmentObj = roleId ? { type: 'role', ref: roleId } : { type: 'role' };
                        decisionOptions = { assignment_mode: 'single', assignment_method: 'role_claim' };
                    } else if (am === 'role_direct_user') {
                        // Direct user chosen by referrer at runtime
                        assignmentObj = { type: 'user' };
                        decisionOptions = { assignment_mode: 'single', assignment_method: 'role_direct_user', requires_user_selection: true, role_id: roleId };
                    } else if (am === 'role_least_busy') {
                        assignmentObj = roleId ? { type: 'strategy', ref: roleId } : { type: 'strategy' };
                        decisionOptions = { assignment_mode: 'single', assignment_method: 'role_least_busy' };
                        var strategyKey = 'least_busy';
                    } else if (am === 'role_round_robin') {
                        assignmentObj = roleId ? { type: 'strategy', ref: roleId } : { type: 'strategy' };
                        decisionOptions = { assignment_mode: 'single', assignment_method: 'role_round_robin' };
                        var strategyKey = 'round_robin';
                    } else if (am === 'role_random') {
                        assignmentObj = roleId ? { type: 'strategy', ref: roleId } : { type: 'strategy' };
                        decisionOptions = { assignment_mode: 'single', assignment_method: 'role_random' };
                        var strategyKey = 'random';
                    } else {
                        assignmentObj = roleId ? { type: 'role', ref: roleId } : { type: 'role' };
                        decisionOptions = { assignment_mode: 'single' };
                    }
                }
            }

            const newTransition = {
                name,
                from: fromVals,
                to: toVals,
                guard_provider: guardProvider || undefined,
                trigger: triggerType ? { type: triggerType } : undefined,
                assignment: assignmentObj,
                decision_options: decisionOptions
            };
            if (typeof strategyKey !== 'undefined') {
                newTransition.strategy_key = strategyKey;
            }
            const decisionMode = decisionModeSelect?.value || 'none';
            if (decisionMode === 'approve' || decisionMode === 'reject') {
                const pickedTo = toVals[0] || '';
                newTransition.conditional = { key: 'decision', routes: [{ value: decisionMode, to: pickedTo }] };
                const unionTo = Array.from(new Set([ ...toVals, pickedTo ].filter(Boolean)));
                newTransition.to = unionTo;
            }
            transitions.push(newTransition);

            transitionNameInput.value = '';
            fromSelect.selectedIndex = -1;
            toSelect.selectedIndex = -1;
            guardProviderInput.value = '';
            triggerTypeSelect.value = '';
            if (decisionModeSelect) decisionModeSelect.value = 'none';
            assignmentTypeSelect.value = '';
            userSelectionRoleSelect.value = '';
            roleSelect.value = '';
            // strategy UI removed
            updateAssignmentUI();
            renderTransitions();
        });

        transitionsTableBody.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-transition-btn')) {
                const idx = parseInt(e.target.dataset.idx, 10);
                transitions.splice(idx, 1);
                renderTransitions();
            }
        });
    });

    // Global shared state for lookups and assignment configs
    let rolesLookup = [];
    let usersLookup = [];
    let parallelSlots = [];
let singleSlot = { method: 'user', role_id: '', user_id: '' };

    // Assignment UI helpers
    function updateAssignmentUI() {
        const mode = document.getElementById('assignmentModeSelect').value || 'single';
        // Mode is selected first, always visible
        document.getElementById('assignmentModeGroup').style.display = 'block';
        // New single-mode UI replaces legacy single controls
        const showSingle = mode === 'single';
        const typeGroup = document.getElementById('assignmentTypeGroup');
        if (typeGroup) typeGroup.style.display = 'none';
        document.getElementById('assignmentUserGroup').style.display = 'none';
        document.getElementById('assignmentRoleGroup').style.display = 'none';
        document.getElementById('assignmentMethodGroup').style.display = 'none';
        document.getElementById('singleConfigGroup').style.display = showSingle ? 'block' : 'none';
        document.getElementById('parallelConfigGroup').style.display = mode === 'parallel' ? 'block' : 'none';
    }

    function fillSelectOptions(selectEl, items, placeholder) {
        selectEl.innerHTML = '';
        const ph = document.createElement('option');
        ph.value = '';
        ph.textContent = placeholder;
        selectEl.appendChild(ph);
        items.forEach(it => {
            const opt = document.createElement('option');
            opt.value = String(it.id);
            opt.textContent = it.name;
            selectEl.appendChild(opt);
        });
    }

    async function loadLookups() {
        try {
            const rolesRes = await fetch('/api/workflow/lookups/roles');
            rolesLookup = rolesRes.ok ? await rolesRes.json() : [];
            fillSelectOptions(document.getElementById('roleSelect'), rolesLookup, '- Select Role -');
            fillSelectOptions(document.getElementById('userSelectionRoleSelect'), rolesLookup, '- Select Role -');

            const usersRes = await fetch('/api/workflow/lookups/users');
            usersLookup = usersRes.ok ? await usersRes.json() : [];

            // Strategies UI removed
        } catch (e) {
            console.error('Failed to load lookups', e);
        }
    }

    function renderParallelSlots() {
        const count = Math.max(1, parseInt(document.getElementById('parallelCountInput').value || '1', 10));
        // Resize parallelSlots preserving existing
        const current = parallelSlots.slice();
        parallelSlots = Array.from({ length: count }, (_, i) => {
            const s = current[i] || { method: 'user', role_id: '' };
            if (!s.assignment_method) {
                s.assignment_method = (s.method === 'role') ? 'role_claim' : 'role_direct_user';
            }
            return s;
        });
        const parallelSlotsContainer = document.getElementById('parallelSlotsContainer');
        parallelSlotsContainer.innerHTML = '';
        parallelSlots.forEach((slot, idx) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'grid grid-cols-1 md:grid-cols-4 gap-3';
            wrapper.innerHTML = `
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Slot ${idx + 1} Method</label>
                    <select class="parallel-slot-type mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                        <option value="user" ${slot.method === 'user' ? 'selected' : ''}>user</option>
                        <option value="role" ${slot.method === 'role' ? 'selected' : ''}>role</option>

                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">User</label>
                    <select class="parallel-slot-user mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                        <option value="">- Select User -</option>
                        ${usersLookup.map(u => `<option value="${String(u.id)}" ${String(slot.user_id||'')===String(u.id)?'selected':''}>${u.name}</option>`).join('')}
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Role</label>
                    <select class="parallel-slot-role mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                        <option value="">- Select Role -</option>
                        ${rolesLookup.map(r => `<option value="${String(r.id)}" ${String(slot.role_id||'')===String(r.id)?'selected':''}>${r.name}</option>`).join('')}
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Slot ${idx + 1} Assignment Method</label>
                    <select class="parallel-slot-assignment-method mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                        <option value="role_claim" ${slot.assignment_method === 'role_claim' ? 'selected' : ''}>Claim by role members (first-come)</option>
                        <option value="role_direct_user" ${slot.assignment_method === 'role_direct_user' ? 'selected' : ''}>Direct user chosen by referrer</option>
                        <option value="role_least_busy" ${slot.assignment_method === 'role_least_busy' ? 'selected' : ''}>Least busy member</option>
                        <option value="role_round_robin" ${slot.assignment_method === 'role_round_robin' ? 'selected' : ''}>Round robin</option>
                        <option value="role_random" ${slot.assignment_method === 'role_random' ? 'selected' : ''}>Random assignment</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <div class="text-xs text-gray-500">Configure each slot independently</div>
                </div>
            `;
            parallelSlotsContainer.appendChild(wrapper);

            const typeSel = wrapper.querySelector('.parallel-slot-type');
            const userSel = wrapper.querySelector('.parallel-slot-user');
            const roleSel = wrapper.querySelector('.parallel-slot-role');
            const methodSel = wrapper.querySelector('.parallel-slot-assignment-method');


            function syncVisibility() {
                // Show user selector for user method
                userSel.parentElement.style.display = (typeSel.value === 'user') ? 'block' : 'none';
                // Show role selector for role method
                roleSel.parentElement.style.display = (typeSel.value === 'role') ? 'block' : 'none';
                // Assignment method shown only for role method in UI
                methodSel.parentElement.style.display = (typeSel.value === 'role') ? 'block' : 'none';
            }
            syncVisibility();

            typeSel.addEventListener('change', () => {
                parallelSlots[idx].method = typeSel.value;
                // Set a sensible default assignment method based on type
                if (typeSel.value === 'role') {
                    if (!parallelSlots[idx].assignment_method) {
                        parallelSlots[idx].assignment_method = 'role_claim';
                    }
                } else {
                    // user method doesn't rely on role-based assignment methods
                    parallelSlots[idx].assignment_method = undefined;
                }
                syncVisibility();
            });
            userSel.addEventListener('change', () => {
                parallelSlots[idx].user_id = userSel.value || '';
            });
            roleSel.addEventListener('change', () => {
                parallelSlots[idx].role_id = roleSel.value || '';
            });
            methodSel.addEventListener('change', () => {
                parallelSlots[idx].assignment_method = methodSel.value || 'role_claim';
            });

        });
    }

    function renderSingleSlot() {
        const mode = document.getElementById('assignmentModeSelect').value || 'single';
        const singleContainer = document.getElementById('singleSlotContainer');
        if (!singleContainer) return;
        if (mode !== 'single') {
            singleContainer.innerHTML = '';
            return;
        }
    // Ensure defaults
    if (!singleSlot.method) singleSlot.method = 'user';
    if (singleSlot.method === 'role' && !singleSlot.assignment_method) singleSlot.assignment_method = 'role_claim';

        singleContainer.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Method</label>
                    <select class="single-slot-type mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                        <option value="user" ${singleSlot.method === 'user' ? 'selected' : ''}>user</option>
                        <option value="role" ${singleSlot.method === 'role' ? 'selected' : ''}>role</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">User</label>
                    <select class="single-slot-user mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                        <option value="">- Select User -</option>
                        ${usersLookup.map(u => `<option value="${String(u.id)}" ${String(singleSlot.user_id||'')===String(u.id)?'selected':''}>${u.name}</option>`).join('')}
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Role</label>
                    <select class="single-slot-role mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                        <option value="">- Select Role -</option>
                        ${rolesLookup.map(r => `<option value="${String(r.id)}" ${String(singleSlot.role_id||'')===String(r.id)?'selected':''}>${r.name}</option>`).join('')}
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Assignment Method</label>
                    <select class="single-slot-assignment-method mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                        <option value="role_claim" ${singleSlot.assignment_method === 'role_claim' ? 'selected' : ''}>Claim by role members (first-come)</option>
                        <option value="role_direct_user" ${singleSlot.assignment_method === 'role_direct_user' ? 'selected' : ''}>Direct user chosen by referrer</option>
                        <option value="role_least_busy" ${singleSlot.assignment_method === 'role_least_busy' ? 'selected' : ''}>Least busy member</option>
                        <option value="role_round_robin" ${singleSlot.assignment_method === 'role_round_robin' ? 'selected' : ''}>Round robin</option>
                        <option value="role_random" ${singleSlot.assignment_method === 'role_random' ? 'selected' : ''}>Random assignment</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <div class="text-xs text-gray-500">Single assignment configuration</div>
                </div>
            </div>
        `;

        const typeSel = singleContainer.querySelector('.single-slot-type');
        const userSel = singleContainer.querySelector('.single-slot-user');
        const roleSel = singleContainer.querySelector('.single-slot-role');
        const methodSel = singleContainer.querySelector('.single-slot-assignment-method');

        function syncVisibility() {
            userSel.parentElement.style.display = (typeSel.value === 'user') ? 'block' : 'none';
            roleSel.parentElement.style.display = (typeSel.value === 'role') ? 'block' : 'none';
            methodSel.parentElement.style.display = (typeSel.value === 'role') ? 'block' : 'none';
        }
        syncVisibility();

        typeSel.addEventListener('change', () => {
            singleSlot.method = typeSel.value;
            if (typeSel.value === 'role') {
                if (!singleSlot.assignment_method) {
                    singleSlot.assignment_method = 'role_claim';
                }
            } else {
                singleSlot.assignment_method = undefined;
            }
            syncVisibility();
        });
        userSel.addEventListener('change', () => {
            singleSlot.user_id = userSel.value || '';
        });
        roleSel.addEventListener('change', () => {
            singleSlot.role_id = roleSel.value || '';
        });
        methodSel.addEventListener('change', () => {
            singleSlot.assignment_method = methodSel.value || 'role_claim';
        });
    }

    document.getElementById('assignmentTypeSelect').addEventListener('change', () => { updateAssignmentUI(); renderParallelSlots(); renderSingleSlot(); });
    document.getElementById('assignmentModeSelect').addEventListener('change', () => { updateAssignmentUI(); renderParallelSlots(); renderSingleSlot(); });
    document.getElementById('parallelCountInput').addEventListener('input', renderParallelSlots);
    document.addEventListener('DOMContentLoaded', () => {
        updateAssignmentUI();
        loadLookups().then(() => { renderParallelSlots(); renderSingleSlot(); });
    });
    </script>
</body>
</html>
