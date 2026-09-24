<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    public $timestamps = true;
    const UPDATED_AT = null;

    protected $fillable = [
        'username',
        'password',
        'fullname',
        'email',
        'phone',
        'role',
        'verified',
        'otp_code',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'verified' => 'boolean',
    ];

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin'        => 'Quản trị viên',
            'receptionist' => 'Lễ tân',
            'customer'     => 'Khách hàng',
            default        => $this->role,
        };
    }

    public function getRoleBadgeAttribute(): string
    {
        return match ($this->role) {
            'admin'        => 'danger',
            'receptionist' => 'warning',
            'customer'     => 'primary',
            default        => 'secondary',
        };
    }
}