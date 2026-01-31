<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'department_id',
        'position',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relations
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class, 'requester_id');
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class, 'approver_id');
    }

    public function managedDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'manager_id');
    }

    // Role Helpers
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isApprover(): bool
    {
        return in_array($this->role, ['approver', 'admin']);
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    // Approval Helpers
    public function pendingApprovals(): HasMany
    {
        return $this->approvalSteps()->pending();
    }

    public function getPendingApprovalsCountAttribute(): int
    {
        return $this->pendingApprovals()->count();
    }

    // Team Helpers
    public function isTeamLeaderOf(User $user): bool
    {
        if (!$user->department_id) {
            return false;
        }

        return $this->managedDepartments()
                    ->where('id', $user->department_id)
                    ->exists();
    }

    public function isDepartmentHeadOf(User $user): bool
    {
        if (!$user->department || !$user->department->parent_id) {
            return false;
        }

        return $this->managedDepartments()
                    ->where('id', $user->department->parent_id)
                    ->exists();
    }

    // Display Helpers
    public function getFullTitleAttribute(): string
    {
        $parts = [];

        if ($this->department) {
            $parts[] = $this->department->name;
        }

        if ($this->position) {
            $parts[] = $this->position;
        }

        return implode(' / ', $parts);
    }
}
