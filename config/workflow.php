<?php

return [
    'models' => [
        'role' => env('WORKFLOW_ROLE_MODEL', \Spatie\Permission\Models\Role::class),
        'user' => env('WORKFLOW_USER_MODEL', config('auth.providers.users.model')),
    ],
    'database' => [
        'connection' => env('DB_WORKFLOW_CONNECTION', env('DB_CONNECTION', 'mysql')),
        'tables' => [
            'definitions'   => 'workflow_definitions',
            'states'        => 'workflow_states',
            'transitions'   => 'workflow_transitions',
            'versions'      => 'workflow_versions',
            'instances'     => 'workflow_instances',
            'tasks'         => 'workflow_tasks',
            'history'       => 'workflow_history',
            'timers'        => 'workflow_timers',
            'messages'      => 'workflow_messages',
            'locks'         => 'workflow_locks',
            'subworkflows'  => 'workflow_subworkflows',
        ],
    ],
    'events' => [
        'enabled' => true,
        'queue' => env('WORKFLOW_EVENTS_QUEUE', 'default'),
    ],
];
