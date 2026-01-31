<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStep extends Model
{
    protected $fillable = [
        'request_id',
        'approver_id',
        'step_order',
        'type',
        'status',
        'comment',
        'processed_at',
        'due_date',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'due_date' => 'datetime',
    ];

    // Relations
    public function request(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'request_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    public function scopeProcessed($query)
    {
        return $query->whereIn('status', ['approved', 'rejected', 'skipped']);
    }

    public function scopeByApprover($query, int $userId)
    {
        return $query->where('approver_id', $userId);
    }

    // State checks
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isWaiting(): bool
    {
        return $this->status === 'waiting';
    }

    public function isProcessed(): bool
    {
        return in_array($this->status, ['approved', 'rejected', 'skipped']);
    }

    public function canProcess(): bool
    {
        return $this->isPending() && $this->request->status === 'pending';
    }

    // Helpers
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'waiting' => '대기',
            'pending' => '결재대기',
            'approved' => '승인',
            'rejected' => '반려',
            'skipped' => '건너뜀',
            default => $this->status,
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'approve' => '승인',
            'review' => '검토',
            'notify' => '참조',
            default => $this->type,
        };
    }
}
