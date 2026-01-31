<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ApprovalRequest extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'template_id',
        'requester_id',
        'title',
        'content',
        'status',
        'current_step',
        'total_steps',
        'urgency',
        'submitted_at',
        'completed_at',
    ];

    protected $casts = [
        'content' => 'array',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'current_step'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relations
    public function template(): BelongsTo
    {
        return $this->belongsTo(RequestTemplate::class, 'template_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class, 'request_id')->orderBy('step_order');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'request_id');
    }

    public function currentStep(): HasOne
    {
        return $this->hasOne(ApprovalStep::class, 'request_id')
                    ->where('status', 'pending');
    }

    // State Machine
    public function canSubmit(): bool
    {
        return $this->status === 'draft';
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['draft', 'submitted', 'pending']);
    }

    public function canEdit(): bool
    {
        return $this->status === 'draft';
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['approved', 'rejected', 'canceled']);
    }

    // Scopes
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['approved', 'rejected', 'canceled']);
    }

    public function scopeByRequester($query, int $userId)
    {
        return $query->where('requester_id', $userId);
    }

    // Helpers
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => '임시저장',
            'submitted' => '제출됨',
            'pending' => '결재중',
            'approved' => '승인',
            'rejected' => '반려',
            'canceled' => '취소',
            default => $this->status,
        };
    }

    public function getUrgencyLabelAttribute(): string
    {
        return match($this->urgency) {
            'urgent' => '긴급',
            'critical' => '최긴급',
            default => '일반',
        };
    }

    public function getProgressAttribute(): string
    {
        if ($this->total_steps === 0) {
            return '0/0';
        }

        return "{$this->current_step}/{$this->total_steps}";
    }
}
