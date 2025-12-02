<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <title>View Workflow (Config): {{ $key }}</title>
    <script>
        (function() {
            const originalWarn = console.warn;
            console.warn = function(...args) {
                if (args[0] && typeof args[0] === 'string' && args[0].includes('cdn.tailwindcss.com')) {
                    return;
                }
                originalWarn.apply(console, args);
            };
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body class="h-full font-sans text-gray-900 antialiased">
    <div class="min-h-full">
        <nav class="bg-indigo-600 shadow-lg">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-indigo-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div class="ml-4 text-xl font-bold text-white tracking-tight">
                            View Workflow (Config): {{ $key }}
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('workflow.admin.index') }}" class="text-indigo-100 hover:text-white text-sm font-medium">&larr; Back to List</a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-10">

                <!-- Basic Information -->
                <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Workflow Configuration (Read-only)</h3>
                        <p class="mt-1 text-sm text-gray-500">Details loaded from `config/workflow.php`.</p>
                    </div>

                    <div class="p-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" value="{{ $key }}" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-gray-700 shadow-sm sm:text-sm border px-3 py-2 mono" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type</label>
                            <input type="text" value="{{ $type ?? '-' }}" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-gray-700 shadow-sm sm:text-sm border px-3 py-2 mono" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Marking Store</label>
                            @php
                                $markingStoreText = is_array($markingStore ?? null) ? ($markingStore['type'] ?? '-') : ($markingStore ?? '-');
                            @endphp
                            <input type="text" value="{{ $markingStoreText }}" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-gray-700 shadow-sm sm:text-sm border px-3 py-2 mono" />
                        </div>
                    </div>

                    <div class="px-6 pb-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Supports</label>
                        @if(!empty($supports))
                            <div class="flex flex-wrap gap-2">
                                @foreach($supports as $cls)
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-300 mono">{{ $cls }}</span>
                                @endforeach
                            </div>
                        @else
                            <div class="text-sm text-gray-500">-</div>
                        @endif
                    </div>
                </div>

                <!-- Places -->
                <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Places</h3>
                        <p class="mt-1 text-sm text-gray-500">Workflow states as defined in config.</p>
                    </div>

                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg m-6">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">#</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Name</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($places as $p)
                                    <tr class="hover:bg-gray-50">
                                        <td class="whitespace-nowrap py-2 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ $loop->iteration }}</td>
                                        <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-700 mono">{{ $p }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-6 py-6 text-center text-sm text-gray-500">No places defined.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Transitions -->
                <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Transitions</h3>
                        <p class="mt-1 text-sm text-gray-500">From/to mappings as defined in config.</p>
                    </div>

                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg m-6">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">#</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Name</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">From</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">To</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($transitions as $t)
                                    <tr class="hover:bg-gray-50">
                                        <td class="whitespace-nowrap py-2 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ $loop->iteration }}</td>
                                        <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-900 font-semibold mono">{{ $t['name'] }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-700">
                                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 mono">{{ implode(', ', $t['from']) }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-sm text-gray-700">
                                            <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10 mono">{{ implode(', ', $t['to']) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-6 text-center text-sm text-gray-500">No transitions defined.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>
