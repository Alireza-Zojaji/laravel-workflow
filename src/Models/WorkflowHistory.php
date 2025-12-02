<?php

namespace Amir\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowHistory extends Model
{
    protected $table = 'workflow_history';

    protected $fillable = [
        'instance_id',
        'transition_id',
        'from_state_id',
        'to_state_id',
        'performed_by',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}

