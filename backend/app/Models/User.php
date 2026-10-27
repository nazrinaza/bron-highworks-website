<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password', 'is_admin', 'role', 'status', 'pilot_licence_no', 'pilot_licence_expires_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'pilot_licence_expires_at' => 'date',
        ];
    }

    public function assignedOperations(): HasMany
    {
        return $this->hasMany(DroneOperation::class, 'assigned_pilot_id');
    }

    public function flightLogs(): HasMany
    {
        return $this->hasMany(FlightLogEntry::class, 'pilot_id');
    }

    public function isOperationsAdmin(): bool
    {
        return $this->is_admin || $this->role === 'admin';
    }

    public function isPilot(): bool
    {
        return $this->role === 'pilot';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
