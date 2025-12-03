<?php

namespace Zojaji\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Zojaji\Workflow\Support\Models;

class WorkflowTask extends Model
{
    protected $table = 'workflow_tasks';

    protected $fillable = [
        'instance_id',
        'state_id',
        'name',
        'assigned_to',
        'assignment_type',
        'assignment_ref',
        'strategy_key',
        'due_at',
        'status',
        'metadata',
        'decision_options',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'metadata' => 'array',
        'decision_options' => 'array',
    ];

    public function scopeForUserInbox(Builder $query, int|string $userId): Builder
    {
        return $query->where('assignment_type', 'user')
            ->where('assigned_to', $userId)
            ->whereIn('status', ['open', 'in_progress']);
    }

    public function scopeClaimableByUser(Builder $query, int|string $userId): Builder
    {
        // Find roles that include this user
        $roleClass = Models::roleModel();
        $roleIds = $roleClass::query()
            ->whereHas('users', function ($q) use ($userId) {
                $q->whereKey($userId);
            })
            ->pluck('id')
            ->all();

        if (empty($roleIds)) {
            // Return none
            return $query->whereRaw('1 = 0');
        }

        return $query->where('assignment_type', 'role')
            ->whereIn('assigned_to', $roleIds)
            ->where('status', 'open');
    }

    public function claimBy(int|string $userId): bool
    {
        if ($this->assignment_type !== 'role') {
            return false;
        }

        // Update assignment to be a direct user
        $meta = is_array($this->metadata) ? $this->metadata : (array) json_decode((string) $this->metadata, true);
        $meta['claimed_by'] = (string) $userId;
        $meta['claimed_at'] = now()->toISOString();

        $this->assignment_type = 'user';
        $this->assigned_to = (string) $userId;
        $this->metadata = $meta;
        return $this->save();
    }

    /**
     * Instance relation for fluent builders and chaining.
     */
    public function instance()
    {
        return $this->belongsTo(WorkflowInstance::class, 'instance_id');
    }

    /**
     * State relation to access the task's state record.
     */
    public function state()
    {
        return $this->belongsTo(WorkflowState::class, 'state_id');
    }
}
