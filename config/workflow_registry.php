<?php

return [
    // Action and strategy identifiers are resolved at runtime.
    'actions' => [
        // 'send_email' => \Vendor\Package\Actions\SendEmail::class,
    ],
    'assignment_strategies' => [
        'round_robin' => \Zojaji\Workflow\Assignment\RoundRobinStrategy::class,
        'least_busy'  => \Zojaji\Workflow\Assignment\LeastBusyStrategy::class,
    ],
    'condition_providers' => [
        // Example: 'expr' => \Vendor\Package\Conditions\ExpressionEvaluator::class,
    ],
    'calendars' => [
        // Working calendars for SLA
    ],
    'message_channels' => [
        // Message/Signal channels
    ],
];
