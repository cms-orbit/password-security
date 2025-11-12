<?php

namespace CmsOrbit\PasswordSecurity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PasswordSecurity extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'securable_type',
        'securable_id',
        'password_changed_at',
        'password_expires_at',
        'password_must_change',
        'is_active',
        'deactivated_at',
        'deactivation_reason',
        'last_login_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password_changed_at' => 'datetime',
        'password_expires_at' => 'datetime',
        'password_must_change' => 'boolean',
        'is_active' => 'boolean',
        'deactivated_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('password-security.tables.password_securities', 'password_securities');
    }

    /**
     * Get the owning securable model.
     */
    public function securable(): MorphTo
    {
        return $this->morphTo();
    }
}

