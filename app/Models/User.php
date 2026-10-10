<?php
// ============================================================
// app/Models/User.php
// ============================================================
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    use Notifiable;
    const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (! $user->role_id && Schema::hasTable('roles')) {
                $user->role_id = DB::table('roles')->where('slug', $user->role)->value('id');
            }
        });
    }

    public const INTERNAL_PERMISSION_CATALOG = [
        'bp_view_schedule' => ['group' => 'Phòng', 'label' => 'Xem sơ đồ phòng và lịch đặt'],
        'bp_create_booking' => ['group' => 'Đặt phòng', 'label' => 'Tạo đặt phòng tại quầy'],
        'bp_checkin_checkout' => ['group' => 'Đặt phòng', 'label' => 'Thực hiện nhận phòng và trả phòng'],
        'bp_extend_stay' => ['group' => 'Đặt phòng', 'label' => 'Gia hạn lưu trú'],
        'bp_clean_status' => ['group' => 'Phòng', 'label' => 'Cập nhật trạng thái phòng và dọn phòng'],
        'tn_collect_payment' => ['group' => 'Thanh toán', 'label' => 'Thu và ghi nhận thanh toán tại quầy'],
        'tn_refund' => ['group' => 'Hủy phòng & hoàn tiền', 'label' => 'Xử lý hủy phòng và hoàn tiền'],
        'rp_view_reports' => ['group' => 'Báo cáo & doanh thu', 'label' => 'Xem báo cáo, doanh thu'],
    ];

    public const PERMISSIONS_CONFIGURED_MARKER = '_internal_permissions_configured';
    public const INTERNAL_ROLE_PROFILE_PREFIX = '_internal_role_profile:';

    protected $fillable = [
        'username', 'password', 'fullname', 'email',
        'phone', 'role', 'role_id', 'verified', 'otp_code', 'otp_expires_at',
        'google_id', 'avatar_url',
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

    public function assignedRole()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // Helpers
    public function isAdmin(): bool        { return $this->role === 'admin'; }
    public function isReceptionist(): bool { return $this->role === 'receptionist'; }
    public function isCustomer(): bool     { return $this->role === 'customer'; }
    public function isVerified(): bool     { return $this->verified === true; }

    public function hasInternalPermission(string $permissionKey): bool
    {
        if (! array_key_exists($permissionKey, self::INTERNAL_PERMISSION_CATALOG)) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        $assigned = Schema::hasTable('user_permissions')
            ? DB::table('user_permissions')->where('user_id', $this->id)->pluck('permission_key')
            : collect();

        // An explicit per-account profile takes precedence over the shared role.
        if ($assigned->contains(self::PERMISSIONS_CONFIGURED_MARKER)) {
            return $assigned->contains($permissionKey);
        }

        $roleAllows = false;
        if ($this->role_id && Schema::hasTable('role_permissions')) {
            $roleAllows = DB::table('role_permissions')
                ->where('role_id', $this->role_id)
                ->where('permission_key', $permissionKey)
                ->exists();
        } elseif (! $this->role_id || ! Schema::hasTable('role_permissions')) {
            // Preserve the legacy fallback on installations without the RBAC tables.
            $roleAllows = true;
        }

        // Legacy direct grants without the marker are additive to the role profile.
        return $roleAllows || $assigned->contains($permissionKey);
    }

    public function countCustomers(): int
    {
        return self::where('role', 'customer')->orWhereNull('role')->count();
    }

    public function getRoleLabelAttribute(): string
    {
        if ($this->role_id && Schema::hasColumn('users', 'role_id')) {
            $name = $this->assignedRole?->name;
            if (filled($name)) {
                return $name;
            }
        }

        return match ($this->role) {
            'admin'        => 'Quản trị viên',
            'receptionist' => 'Lễ tân',
            'customer'     => 'Khách hàng',
            default        => (string) $this->role,
        };
    }

    public function getRoleBadgeAttribute(): string
    {
        if ($this->role_id && Schema::hasColumn('users', 'role_id')) {
            return match ($this->assignedRole?->slug) {
                'admin' => 'danger',
                'receptionist' => 'warning',
                'customer' => 'primary',
                null => 'secondary',
                default => 'info',
            };
        }

        return match ($this->role) {
            'admin'        => 'danger',
            'receptionist' => 'warning',
            'customer'     => 'primary',
            default        => 'secondary',
        };
    }
}
