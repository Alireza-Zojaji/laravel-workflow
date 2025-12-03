<?php

namespace Zojaji\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowDefinition extends Model
{
    protected $table = 'workflow_definitions';

    protected $fillable = [
        'name', 'label', 'description', 'marking_store',
        'places', 'transitions', 'schema', 'version', 'is_active',
    ];

    protected $casts = [
        'places' => 'array',
        'transitions' => 'array',
        'schema' => 'array',
        'is_active' => 'boolean',
        'version' => 'integer',
    ];
}
