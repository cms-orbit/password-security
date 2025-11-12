<?php

namespace CmsOrbit\PasswordSecurity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PasswordHistory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'securable_type',
        'securable_id',
        'password_hash',
        'changed_at',
        'changed_by',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'changed_at' => 'datetime',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('password-security.tables.password_histories', 'password_histories');
    }

    /**
     * Get the owning securable model.
     */
    public function securable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who changed the password.
     */
    public function changedBy()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'changed_by');
    }
}

