<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Role extends Model
{
    protected $fillable = ['slug', 'name', 'is_system'];

    protected $casts = ['is_system' => 'boolean'];

    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function permissionKeys(): array
    {
        return DB::table('role_permissions')
            ->where('role_id', $this->id)
            ->orderBy('permission_key')
            ->pluck('permission_key')
            ->all();
    }
}
