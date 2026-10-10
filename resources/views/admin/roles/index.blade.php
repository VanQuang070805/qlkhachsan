@extends('layouts.admin')

@section('title', 'Phân quyền nội bộ · Rosaliza Hotel')
@section('page-title', 'Phân quyền nội bộ')

@push('styles')
<style>
.rbac-shell{display:grid;grid-template-columns:minmax(260px,320px) minmax(0,1fr);min-height:calc(100vh - 150px);background:#fff;border:1px solid #e5e7eb;border-radius:18px;overflow:hidden}
.rbac-side{padding:20px;border-right:1px solid #e5e7eb;background:#fafafa}
.rbac-main{padding:24px;min-width:0}
.rbac-titlebar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px}
.rbac-titlebar h1{font-size:1.05rem;font-weight:700;color:#111827;margin:0}
.rbac-search{width:100%;height:42px;padding:0 13px;border:1px solid #d1d5db;border-radius:10px;background:#fff;color:#111827}
.rbac-search:focus{outline:2px solid #9abfda;outline-offset:1px}
.rbac-filters{display:flex;flex-wrap:wrap;gap:7px;margin:14px 0}
.rbac-filter{border:1px solid #d1d5db;border-radius:999px;padding:6px 10px;background:#fff;color:#4b5563;font-size:.76rem}
.rbac-filter.is-active{background:#111827;color:#fff;border-color:#111827}
.rbac-account-list{display:grid;gap:7px;max-height:calc(100vh - 310px);overflow:auto}
.rbac-account{display:block;padding:11px 12px;border:1px solid transparent;border-radius:11px;color:#111827;text-decoration:none;background:transparent}
.rbac-account:hover{background:#f3f4f6;color:#111827}
.rbac-account.is-active{background:#fff;border-color:#d1d5db;box-shadow:0 1px 3px #0000000a}
.rbac-account strong,.rbac-account small{display:block;overflow-wrap:anywhere}
.rbac-account small{color:#6b7280;margin-top:3px;font-size:.75rem}
.rbac-badge{display:inline-flex;margin-top:7px;padding:3px 8px;border-radius:999px;background:#eef2f7;color:#475569;font-size:.68rem}
.rbac-profile{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;padding-bottom:18px;margin-bottom:18px;border-bottom:1px solid #e5e7eb}
.rbac-profile h2{font-size:1.1rem;margin:0 0 5px;font-weight:700;color:#111827}
.rbac-profile p,.rbac-note{color:#64748b;font-size:.82rem;margin:0}
.rbac-group{border:1px solid #e5e7eb;border-radius:13px;margin-bottom:12px;overflow:hidden}
.rbac-group h3{font-size:.82rem;font-weight:700;background:#f8fafc;color:#334155;padding:11px 14px;margin:0;border-bottom:1px solid #e5e7eb}
.rbac-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 14px;border-bottom:1px solid #f1f5f9;color:#1f2937;font-size:.86rem;cursor:pointer}
.rbac-row:last-child{border-bottom:0}
.rbac-row input{width:17px;height:17px;accent-color:#111827;flex:none}
.rbac-row.is-inherited{background:#f8fafc}
.rbac-row.is-inherited input{accent-color:#64748b}
.rbac-save{border:0;border-radius:9px;background:#111827;color:#fff;padding:10px 16px;font-size:.84rem;font-weight:600}
.rbac-save:disabled{opacity:.5;cursor:not-allowed}
.rbac-empty{padding:40px 20px;text-align:center;color:#64748b}
.rbac-tools{display:flex;align-items:center;gap:8px}.rbac-profile-form{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:end;gap:10px;padding:14px;margin:0 0 18px;border:1px solid #e5e7eb;border-radius:13px;background:#fafafa}.rbac-profile-form label{display:block;margin-bottom:5px;color:#475569;font-size:.75rem;font-weight:600}.rbac-profile-form select,.rbac-modal input[type=text]{width:100%;min-height:40px;padding:8px 11px;border:1px solid #d1d5db;border-radius:9px;background:#fff;color:#111827}.rbac-subrole{display:block;margin-top:4px;color:#64748b;font-size:.72rem}.rbac-modal .modal-content{overflow:hidden;border:1px solid #dfe5eb;border-radius:16px;box-shadow:0 24px 72px rgba(15,23,42,.2)}.rbac-modal__head{display:grid;grid-template-columns:62px 1fr 28px;align-items:center;gap:12px;padding:16px 20px;border-bottom:1px solid #edf0f3}.rbac-modal__head h2{margin:0;text-align:center;color:#17212b;font-size:.98rem;font-weight:700}.rbac-modal__lights{display:flex;gap:7px}.rbac-modal__lights .ctrl-dot{width:12px;height:12px;flex:0 0 12px;border:1px solid rgba(0,0,0,.12);border-radius:50%}.rbac-modal__close{display:grid;width:26px;height:26px;padding:0;place-items:center;justify-self:end;border:0;border-radius:50%;background:#f1f5f9;color:#526171;font-size:1.15rem;line-height:1}.rbac-modal__body{padding:20px}.rbac-modal__footer{display:flex;justify-content:flex-end;gap:8px;padding:14px 20px;border-top:1px solid #edf0f3;background:#f8fafc}.rbac-modal .room-field-error{display:block;margin-top:5px;color:#b42318;font-size:.75rem}
@media(max-width:850px){.rbac-shell{grid-template-columns:1fr}.rbac-side{border-right:0;border-bottom:1px solid #e5e7eb}.rbac-account-list{max-height:270px}.rbac-main{padding:18px}}
@media(max-width:560px){.rbac-tools{flex-wrap:wrap;justify-content:flex-end}.rbac-profile-form{grid-template-columns:1fr}.rbac-modal__body{padding:16px}.rbac-modal__footer{padding:12px 16px}}
</style>
@endpush

@section('content')
@php
    $roleName = fn ($slug) => $roles->firstWhere('slug', $slug)?->name ?? ucfirst(str_replace('_', ' ', (string) $slug));
    $isAdminAccount = $selected?->isAdmin() ?? false;
@endphp
<div class="rbac-titlebar">
    <h1>Quyền truy cập tài khoản nội bộ</h1>
    <div class="rbac-tools">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.roles.permissions.index', ['user_id' => $selected?->id]) }}">Role</a>
        <button class="btn btn-outline-dark btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#addRoleProfileModal">Thêm vai trò</button>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.users.index') }}">Quản lý tài khoản</a>
    </div>
</div>

<div class="rbac-shell">
    <aside class="rbac-side" aria-label="Tài khoản nội bộ">
        <label class="visually-hidden" for="rbacSearch">Tìm tài khoản</label>
        <input id="rbacSearch" class="rbac-search" type="search" placeholder="Tìm tài khoản..." autocomplete="off">

        <div class="rbac-filters" aria-label="Lọc theo vai trò">
            <button type="button" class="rbac-filter is-active" data-role="all">Tất cả</button>
            @foreach($roles as $role)
                <button type="button" class="rbac-filter" data-role="{{ $role->slug }}">{{ $role->name }}</button>
            @endforeach
        </div>

        <nav class="rbac-account-list" id="rbacAccounts" aria-label="Danh sách tài khoản">
            @forelse($accounts as $account)
                <a class="rbac-account {{ $selected?->id === $account->id ? 'is-active' : '' }}"
                   href="{{ route('admin.roles.index', ['user_id' => $account->id]) }}"
                   data-role="{{ $account->assignedRole?->slug ?? $account->role }}"
                   data-search="{{ mb_strtolower($account->username.' '.$account->fullname.' '.$account->role_label) }}">
                    <strong>{{ $account->username }}</strong>
                    <small>{{ $account->fullname }}</small>
                    <span class="rbac-badge">{{ $account->role_label }}</span>
                </a>
            @empty
                <div class="rbac-empty">Chưa có tài khoản nội bộ.</div>
            @endforelse
        </nav>
    </aside>

    <main class="rbac-main">
        @if($selected)
            <div class="rbac-profile">
                <div>
                    <h2>{{ $selected->fullname }}</h2>
                    <p>{{ $selected->username }} · {{ $selectedRole?->name ?? $roleName($selected->role) }}</p>
                </div>
                @if($isAdminAccount)
                <span class="rbac-badge">Toàn quyền</span>
                @endif
            </div>

            @if($isAdminAccount)
                <p class="rbac-note">Tài khoản quản trị viên luôn có toàn quyền.</p>
            @else
                @if($hasUserPermissionOverride)
                    <p class="rbac-note mb-3">Bộ quyền riêng đang áp dụng cho tài khoản này. Các thay đổi tại đây không tác động đến tài khoản khác. <span class="rbac-badge">Quyền riêng</span></p>
                @else
                    <p class="rbac-note mb-3">Các quyền được chọn ban đầu theo vai trò <strong>{{ $selectedRole?->name ?? $selected->role_label }}</strong>. Lưu tại đây sẽ tạo bộ quyền riêng cho tài khoản này.</p>
                @endif
                <form method="POST" action="{{ route('admin.roles.user-permissions.update', $selected) }}">
                    @csrf
                    @method('PATCH')
                    @foreach($permissionGroups as $group => $items)
                        <section class="rbac-group" aria-label="Quyền {{ $group }} cho {{ $selected->username }}">
                            <h3>{{ $group }}</h3>
                            @foreach($items as $key => $item)
                                <label class="rbac-row {{ in_array($key, $selectedRolePermissions, true) ? 'is-inherited' : '' }}">
                                    <span>{{ $item['label'] }}@if(in_array($key, $selectedRolePermissions, true)) <small class="rbac-note">· theo role</small>@endif</span>
                                    <input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, $selectedPermissions, true))>
                                </label>
                            @endforeach
                        </section>
                    @endforeach
                    @error('permissions.*')<span class="room-field-error" role="alert">{{ $message }}</span>@enderror
                    <div class="d-flex justify-content-end">
                        <button class="rbac-save" type="submit">Lưu quyền tài khoản</button>
                    </div>
                </form>
            @endif
        @else
            <div class="rbac-empty">Chọn một tài khoản nội bộ để xem và cập nhật quyền.</div>
        @endif
    </main>
</div>

<div class="modal fade rbac-modal" id="addRoleProfileModal" tabindex="-1" aria-labelledby="addRoleProfileModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.roles.profiles.store') }}">
                @csrf
                <input type="hidden" name="user_id" value="{{ $selected?->id }}">
                <div class="rbac-modal__head">
                    <div class="rbac-modal__lights macos-traffic-lights" aria-hidden="true"><span class="ctrl-dot ctrl-red"></span><span class="ctrl-dot ctrl-yellow"></span><span class="ctrl-dot ctrl-green"></span></div>
                    <h2 id="addRoleProfileModalTitle">Thêm vai trò quyền hạn</h2>
                    <button class="rbac-modal__close" type="button" data-bs-dismiss="modal" aria-label="Đóng popup thêm vai trò"><span aria-hidden="true">×</span></button>
                </div>
                <div class="rbac-modal__body">
                    <label class="form-label fw-semibold" for="new-role-profile-name">Tên vai trò <span class="text-danger">*</span></label>
                    <input class="form-control" id="new-role-profile-name" name="name" type="text" maxlength="60" value="{{ old('name') }}" placeholder="Ví dụ: Ca đêm" required>
                    @error('name', 'createRoleProfile')<span class="room-field-error" role="alert">{{ $message }}</span>@enderror
                    <p class="rbac-note mt-2 mb-3">Chọn quyền áp dụng cho mọi tài khoản được gán vai trò này. Vai trò mới sẽ có trong bộ lọc và danh sách chọn vai trò của tài khoản.</p>
                    @foreach($permissionGroups as $group => $items)
                        <section class="rbac-group" aria-label="Quyền cho {{ $group }}">
                            <h3>{{ $group }}</h3>
                            @foreach($items as $key => $item)
                                <label class="rbac-row">
                                    <span>{{ $item['label'] }}</span>
                                    <input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(collect(old('permissions', []))->contains($key))>
                                </label>
                            @endforeach
                        </section>
                    @endforeach
                    @error('permissions.*', 'createRoleProfile')<span class="room-field-error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="rbac-modal__footer">
                    <button class="btn btn-light border" type="button" data-bs-dismiss="modal">Hủy</button>
                    <button class="rbac-save" type="submit">Lưu vai trò</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('rbacSearch');
    const accounts = [...document.querySelectorAll('.rbac-account')];
    const filters = [...document.querySelectorAll('.rbac-filter')];
    let role = 'all';
    const filter = () => {
        const term = (search?.value || '').trim().toLocaleLowerCase();
        accounts.forEach(account => {
            account.hidden = (role !== 'all' && account.dataset.role !== role)
                || !account.dataset.search.includes(term);
        });
    };
    search?.addEventListener('input', filter);
    filters.forEach(button => button.addEventListener('click', () => {
        filters.forEach(item => item.classList.toggle('is-active', item === button));
        role = button.dataset.role;
        filter();
    }));

    @if($errors->getBag('createRoleProfile')->any())
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addRoleProfileModal')).show();
    @endif
});
</script>
@endsection
