<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'state_id',
        'phc_id',
        'phone',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    // Relationships
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function phc(): BelongsTo
    {
        return $this->belongsTo(Phc::class);
    }

    // Role checking methods
    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    public function isUpdater(): bool
    {
        return $this->role === 'updater';
    }

    public function isCoordinator(): bool
    {
        return $this->role === 'coordinator';
    }

    public function isAdministrator(): bool
    {
        return $this->role === 'administrator';
    }

    public function canUpdate(): bool
    {
        return in_array($this->role, ['updater', 'coordinator', 'administrator']);
    }

    public function canDelete(): bool
    {
        return $this->role === 'administrator';
    }
}
