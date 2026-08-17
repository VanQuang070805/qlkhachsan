<?php
// ============================================================
// app/Models/User.php
// ============================================================
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
    const UPDATED_AT = null;

    protected $fillable = [
        'username', 'password', 'fullname', 'email',
        'phone', 'role', 'verified', 'otp_code', 'otp_expires_at',
    ];

    protected $hidden = ['password', 'remember_token', 'otp_code'];

    protected $casts = [
        'verified'       => 'boolean',
        'otp_expires_at' => 'datetime',
    ];

    // Relations
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Helpers
    public function isAdmin(): bool        { return $this->role === 'admin'; }
    public function isReceptionist(): bool { return $this->role === 'receptionist'; }
    public function isCustomer(): bool     { return $this->role === 'customer'; }
    public function isVerified(): bool     { return $this->verified === true; }

    public function countCustomers(): int
    {
        return self::where('role', 'customer')->orWhereNull('role')->count();
    }
}
