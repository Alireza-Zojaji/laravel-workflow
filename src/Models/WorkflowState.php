<?php

namespace Amir\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowState extends Model
{
    protected $table = 'workflow_states';

    protected $fillable = [
        'definition_id',
        'key',
        'label',
        'type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}

