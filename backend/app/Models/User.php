<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = ['name', 'email', 'password', 'phone', 'avatar', 'is_active'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['is_active' => 'boolean', 'email_verified_at' => 'datetime'];

    public function inbounds(): HasMany { return $this->hasMany(Inbound::class); }
    public function outbounds(): HasMany { return $this->hasMany(Outbound::class); }
    public function transfers(): HasMany { return $this->hasMany(Transfer::class); }
    public function stockOpnames(): HasMany { return $this->hasMany(StockOpname::class); }
    public function auditLogs(): HasMany { return $this->hasMany(AuditLog::class); }
}
