<?php

namespace Amir\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowInstance extends Model
{
    protected $table = 'workflow_instances';

    protected $fillable = [
        'definition_id',
        'current_state_id',
        'status',
        'model_type',
        'model_id',
        'variables',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'variables' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function definition()
    {
        return $this->belongsTo(WorkflowDefinition::class, 'definition_id');
    }

    public function currentState()
    {
        return $this->belongsTo(WorkflowState::class, 'current_state_id');
    }

    public function tasks()
    {
        return $this->hasMany(WorkflowTask::class, 'instance_id');
    }

    public function history()
    {
        return $this->hasMany(WorkflowHistory::class, 'instance_id');
    }
}
