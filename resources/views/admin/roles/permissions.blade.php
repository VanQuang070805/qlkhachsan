@extends('layouts.admin')

@section('title', 'Quyền theo role · Rosaliza Hotel')
@section('page-title', 'Quyền theo role')

@push('styles')
<style>
.rbac-shell{display:grid;grid-template-columns:minmax(260px,320px) minmax(0,1fr);min-height:calc(100vh - 150px);background:#fff;border:1px solid #e5e7eb;border-radius:18px;overflow:hidden}.rbac-side{padding:20px;border-right:1px solid #e5e7eb;background:#fafafa}.rbac-main{padding:24px;min-width:0}.rbac-titlebar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px}.rbac-titlebar h1{font-size:1.05rem;font-weight:700;color:#111827;margin:0}.rbac-titlebar p{margin:5px 0 0;color:#64748b;font-size:.82rem}.rbac-search{width:100%;height:42px;padding:0 13px;border:1px solid #d1d5db;border-radius:10px;background:#fff;color:#111827}.rbac-search:focus{outline:2px solid #9abfda;outline-offset:1px}.rbac-account-list{display:grid;gap:7px;max-height:calc(100vh - 260px);overflow:auto;margin-top:14px}.rbac-account{display:block;padding:11px 12px;border:1px solid transparent;border-radius:11px;color:#111827;text-decoration:none;background:transparent}.rbac-account:hover{background:#f3f4f6;color:#111827}.rbac-account.is-active{background:#fff;border-color:#d1d5db;box-shadow:0 1px 3px #0000000a}.rbac-account strong,.rbac-account small{display:block;overflow-wrap:anywhere}.rbac-account small{color:#6b7280;margin-top:3px;font-size:.75rem}.rbac-badge{display:inline-flex;margin-top:7px;padding:3px 8px;border-radius:999px;background:#eef2f7;color:#475569;font-size:.68rem}.rbac-profile{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;padding-bottom:18px;margin-bottom:18px;border-bottom:1px solid #e5e7eb}.rbac-profile h2{font-size:1.1rem;margin:0 0 5px;font-weight:700;color:#111827}.rbac-profile p,.rbac-note{color:#64748b;font-size:.82rem;margin:0}.rbac-group{border:1px solid #e5e7eb;border-radius:13px;margin-bottom:12px;overflow:hidden}.rbac-group h3{font-size:.82rem;font-weight:700;background:#f8fafc;color:#334155;padding:11px 14px;margin:0;border-bottom:1px solid #e5e7eb}.rbac-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 14px;border-bottom:1px solid #f1f5f9;color:#1f2937;font-size:.86rem;cursor:pointer}.rbac-row:last-child{border-bottom:0}.rbac-row input{width:17px;height:17px;accent-color:#111827;flex:none}.rbac-save{border:0;border-radius:9px;background:#111827;color:#fff;padding:10px 16px;font-size:.84rem;font-weight:600}.rbac-save:disabled{opacity:.5;cursor:not-allowed}.rbac-empty{padding:40px 20px;text-align:center;color:#64748b}.rbac-tools{display:flex;align-items:center;gap:8px}
@media(max-width:850px){.rbac-shell{grid-template-columns:1fr}.rbac-side{border-right:0;border-bottom:1px solid #e5e7eb}.rbac-account-list{max-height:270px}.rbac-main{padding:18px}}
@media(max-width:560px){.rbac-titlebar{align-items:flex-start;flex-direction:column}.rbac-main{padding:16px}}
</style>
@endpush

@section('content')
<div class="rbac-titlebar">
    <div>
        <h1>Quyền theo role</h1>
        <p>Chỉnh sửa quyền dùng chung cho các tài khoản thuộc role đã chọn.</p>
    </div>
    <div class="rbac-tools">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.roles.index', $backUserId ? ['user_id' => $backUserId] : []) }}">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Quay lại phân quyền
        </a>
    </div>
</div>

<div class="rbac-shell">
    <aside class="rbac-side" aria-label="Danh sách role">
        <label class="visually-hidden" for="roleSearch">Tìm role</label>
        <input id="roleSearch" class="rbac-search" type="search" placeholder="Tìm role..." autocomplete="off">
        <nav class="rbac-account-list" id="roleList" aria-label="Các role nội bộ">
            @forelse($roles as $role)
                @php($permissions = $rolePermissions[$role->id] ?? [])
                <a class="rbac-account {{ $selectedRole?->id === $role->id ? 'is-active' : '' }}"
                   href="{{ route('admin.roles.permissions.index', array_filter(['role_id' => $role->id, 'user_id' => $backUserId])) }}"
                   data-search="{{ mb_strtolower($role->name.' '.$role->slug) }}">
                    <strong>{{ $role->name }}</strong>
                    <small>{{ $role->users_count }} tài khoản · {{ $role->slug === 'admin' ? 'Toàn quyền' : count($permissions).' quyền' }}</small>
                    <span class="rbac-badge">{{ $role->is_system ? 'Hệ thống' : 'Tùy chỉnh' }}</span>
                </a>
            @empty
                <div class="rbac-empty">Chưa có role nội bộ.</div>
            @endforelse
        </nav>
    </aside>

    <main class="rbac-main">
        @if($selectedRole)
            @php($isAdminRole = $selectedRole->slug === 'admin')
            @php($selectedPermissions = $rolePermissions[$selectedRole->id] ?? [])
            <div class="rbac-profile">
                <div>
                    <h2>{{ $selectedRole->name }}</h2>
                    <p>{{ $selectedRole->users_count }} tài khoản · {{ $selectedRole->slug }}</p>
                </div>
                @if($isAdminRole)<span class="rbac-badge">Toàn quyền</span>@endif
            </div>

            @if($isAdminRole)
                <p class="rbac-note mb-3">Role quản trị viên có toàn quyền và không thể tắt quyền tại đây.</p>
            @else
                <p class="rbac-note mb-3">Các thay đổi sẽ áp dụng cho mọi tài khoản được gán role này.</p>
            @endif

            <form method="POST" action="{{ route('admin.roles.role-permissions.update', $selectedRole) }}">
                @csrf @method('PATCH')
                @if($backUserId)<input type="hidden" name="user_id" value="{{ $backUserId }}">@endif
                @foreach($permissionGroups as $group => $items)
                    <section class="rbac-group" aria-label="{{ $group }}">
                        <h3>{{ $group }}</h3>
                        @foreach($items as $key => $item)
                            <label class="rbac-row">
                                <span>{{ $item['label'] }}</span>
                                <input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, $selectedPermissions, true)) @disabled($isAdminRole)>
                            </label>
                        @endforeach
                    </section>
                @endforeach
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mt-3">
                    <span class="rbac-note">Quyền được kiểm tra ở máy chủ khi thực hiện thao tác.</span>
                    <button class="rbac-save" type="submit" @disabled($isAdminRole)>Lưu quyền cho role</button>
                </div>
            </form>
        @else
            <div class="rbac-empty">Chọn một role ở danh sách bên trái.</div>
        @endif
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('roleSearch');
    const roles = [...document.querySelectorAll('#roleList .rbac-account')];
    search?.addEventListener('input', () => {
        const term = search.value.trim().toLocaleLowerCase();
        roles.forEach(role => { role.hidden = !role.dataset.search.includes(term); });
    });
});
</script>
@endsection
