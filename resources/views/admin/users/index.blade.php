@extends('layouts.admin')
@section('title','Người dùng · Posh Boutique')
@section('page-title','Quản lý người dùng')
@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: #0f172a; letter-spacing: -0.03em;">Con người &amp; Quyền truy cập</h1>
        <p class="text-muted small mb-0" style="color: #64748b;">Quản lý khách hàng, lễ tân và quản trị viên trong một danh sách rõ ràng.</p>
    </div>
    <button type="button" class="btn btn-dark d-inline-flex align-items-center gap-2 px-3.5 py-2 fw-medium" data-bs-toggle="modal" data-bs-target="#createUserModal" style="border-radius: 999px; background: #0f172a; font-size: 0.82rem; border: none; box-shadow: 0 2px 8px rgba(15,23,42,0.18);">
        <i class="bi bi-plus-lg"></i>
        <span>Thêm tài khoản</span>
    </button>
</div>

<form class="report-filter-bar mb-3" method="GET" action="{{ route('admin.users.index') }}">
    <div class="report-filter-inputs">
        <label class="report-filter-field position-relative" style="min-width: 240px; flex: 1;">
            <i class="bi bi-search position-absolute text-muted" style="left: 12px; top: 50%; transform: translateY(-50%); font-size: 0.8rem; pointer-events: none;"></i>
            <input name="search" value="{{ request('search') }}" class="report-input-pill w-100 ps-4" placeholder="Tìm tên, email hoặc tài khoản…" style="padding-left: 32px !important;">
        </label>
        <label class="report-filter-field">
            <span>Vai trò</span>
            <select name="role" class="report-select-pill">
                <option value="">Mọi vai trò</option>
                <option value="admin" @selected(request('role')==='admin')>Quản trị viên</option>
                <option value="receptionist" @selected(request('role')==='receptionist')>Lễ tân</option>
                <option value="customer" @selected(request('role')==='customer')>Khách hàng</option>
            </select>
        </label>
        <label class="report-filter-field">
            <span>Trạng thái</span>
            <select name="verified" class="report-select-pill">
                <option value="">Mọi trạng thái</option>
                <option value="1" @selected(request('verified')==='1')>Đang hoạt động</option>
                <option value="0" @selected(request('verified')==='0')>Đã khóa</option>
            </select>
        </label>
    </div>
    <div class="report-filter-actions">
        <button type="submit" class="btn-report-apply">
            <i class="bi bi-funnel"></i>
            <span>Lọc</span>
        </button>
        <a href="{{ route('admin.users.index') }}" class="btn-report-reset" aria-label="Xóa bộ lọc" title="Xóa bộ lọc">
            <i class="bi bi-arrow-counterclockwise"></i>
        </a>
    </div>
</form>

<section class="card border-0 mb-4 people-panel" style="border-radius: 18px; border: 1px solid rgba(0,0,0,0.06) !important; box-shadow: 0 1px 3px rgba(0,0,0,0.01); background: #ffffff; overflow: hidden;">
    <header class="d-flex align-items-center justify-content-between px-4 py-3" style="border-bottom: 1px solid rgba(0,0,0,0.06); background: #ffffff;">
        <h2 class="h6 fw-bold m-0" style="color: #0f172a;">Danh sách tài khoản</h2>
        <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 600; font-size: 0.72rem; padding: 5px 10px; border-radius: 999px;">{{ $users->total() }} tài khoản</span>
    </header>
    <div class="table-responsive">
        <table class="table align-middle m-0" style="border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(0,0,0,0.06); background: #f8fafc; font-size: 0.68rem; letter-spacing: 0.05em; text-transform: uppercase; color: #64748b;">
                    <th>Tài khoản</th>
                    <th>Liên hệ</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th class="pe-4 text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr style="border-bottom: 1px solid rgba(0,0,0,0.04); transition: background-color 0.15s ease;">
                    <td class="ps-4 py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; background: #e0f2fe; color: #0284c7; font-size: 0.76rem; flex-shrink: 0;">
                                {{ mb_strtoupper(mb_substr($user->username, 0, 1)) }}
                            </span>
                            <div>
                                <strong class="d-block text-dark fw-bold" style="font-size: 0.85rem; color: #0f172a;">{{ $user->username }}</strong>
                                <small class="text-muted" style="font-size: 0.72rem;">{{ $user->fullname }}</small>
                            </div>
                        </div>
                    </td>
                    <td class="py-3">
                        <div style="font-size: 0.82rem; color: #334155;">{{ $user->email }}</div>
                        <small class="text-muted" style="font-size: 0.72rem;">{{ $user->phone ?? 'Chưa có số điện thoại' }}</small>
                    </td>
                    <td class="py-3">
                        <span class="badge" style="background: #f1f5f9; color: #334155; font-size: 0.72rem; font-weight: 600; padding: 4px 8px; border-radius: 6px;">{{ $user->role_label }}</span>
                    </td>
                    <td class="py-3">
                        @if($user->id === auth()->id())
                            <span class="status-pill status-confirmed">Đang hoạt động</span>
                        @elseif($user->verified)
                            <button type="button" class="status-pill status-confirmed btn-toggle-status border-0" data-id="{{ $user->id }}" style="cursor: pointer; background: transparent; padding: 0;">
                                Đang hoạt động
                            </button>
                        @else
                            <button type="button" class="status-pill status-cancelled btn-toggle-status border-0" data-id="{{ $user->id }}" style="cursor: pointer; background: transparent; padding: 0;">
                                Đã khóa
                            </button>
                        @endif
                    </td>
                    <td class="pe-4 py-3 text-end">
                        <div class="d-inline-flex align-items-center gap-1">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-light d-inline-flex align-items-center justify-content-center p-0" title="Chỉnh sửa" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.06); background: #ffffff; color: #475569;">
                                <i class="bi bi-pencil" style="font-size: 0.8rem;"></i>
                            </a>
                            @if($user->id !== auth()->id())
                                <button type="button" class="btn btn-sm btn-light btn-delete d-inline-flex align-items-center justify-content-center p-0" data-id="{{ $user->id }}" data-name="{{ $user->fullname }}" title="Xóa" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.06); background: #ffffff; color: #ef4444;">
                                    <i class="bi bi-trash3" style="font-size: 0.8rem;"></i>
                                </button>
                                <form id="delete-form-{{ $user->id }}" action="{{ route('admin.users.destroy', $user) }}" method="POST" hidden>
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted" style="font-size: 0.88rem;">Không tìm thấy tài khoản phù hợp.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <footer class="px-4 py-3 d-flex align-items-center justify-content-between" style="border-top: 1px solid rgba(0,0,0,0.06); background: #ffffff;">
            {{ $users->links('pagination.royal') }}
        </footer>
    @endif
</section>

{{-- POPUP THÊM TÀI KHOẢN MỚI (MACOS CUPERTINO MODAL) --}}
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 20px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 25px 60px rgba(0,0,0,0.18); overflow: hidden; background: #ffffff;">
            {{-- macOS Window Header --}}
            <div class="modal-header d-flex align-items-center justify-content-between px-4 py-3" style="background: #f8fafc; border-bottom: 1px solid rgba(0,0,0,0.06);">
                <div class="window-controls d-flex align-items-center gap-1.5" style="pointer-events: none; margin: 0; padding: 0;" aria-hidden="true">
                    <span class="ctrl-dot ctrl-red"></span>
                    <span class="ctrl-dot ctrl-yellow"></span>
                    <span class="ctrl-dot ctrl-green"></span>
                </div>
                <h5 class="modal-title fw-bold text-dark m-0 fs-6 text-center flex-grow-1" id="createUserModalLabel" style="color: #0f172a; letter-spacing: -0.01em;">
                    Thêm tài khoản hệ thống mới
                </h5>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Đóng" style="font-size: 0.75rem;"></button>
            </div>

            <form action="{{ route('admin.users.store') }}" method="POST" novalidate id="formCreateUser">
                @csrf
                <div class="modal-body p-4" style="background: #ffffff;">
                    {{-- Subtitle / description --}}
                    <div class="mb-3.5 pb-2 border-bottom" style="border-color: rgba(0,0,0,0.05) !important;">
                        <p class="text-muted small mb-0" style="color: #64748b; font-size: 0.84rem;">
                            Tạo tài khoản phân quyền quản trị hoặc lễ tân vận hành khách sạn Posh Boutique.
                        </p>
                    </div>

                    <div class="row g-3">
                        {{-- Họ tên --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label text-dark small fw-semibold mb-1" style="font-size: 0.8rem; color: #1e293b;">
                                Họ và tên <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="fullname"
                                   class="form-control @error('fullname') is-invalid @enderror"
                                   value="{{ old('fullname') }}" placeholder="Ví dụ: Nguyễn Văn An" required
                                   style="border-radius: 10px; font-size: 0.86rem; border: 1px solid rgba(0,0,0,0.12); height: 42px; background: #ffffff; color: #0f172a;">
                            @error('fullname')
                                <div class="invalid-feedback d-block small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Username --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label text-dark small fw-semibold mb-1" style="font-size: 0.8rem; color: #1e293b;">
                                Tên đăng nhập <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="username"
                                   class="form-control @error('username') is-invalid @enderror"
                                   value="{{ old('username') }}" placeholder="nguyen_van_an" required
                                   style="border-radius: 10px; font-size: 0.86rem; border: 1px solid rgba(0,0,0,0.12); height: 42px; background: #ffffff; color: #0f172a;">
                            @error('username')
                                <div class="invalid-feedback d-block small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label text-dark small fw-semibold mb-1" style="font-size: 0.8rem; color: #1e293b;">
                                Email liên hệ <span class="text-danger">*</span>
                            </label>
                            <input type="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" placeholder="an.nguyen@royalhotel.vn" required
                                   style="border-radius: 10px; font-size: 0.86rem; border: 1px solid rgba(0,0,0,0.12); height: 42px; background: #ffffff; color: #0f172a;">
                            @error('email')
                                <div class="invalid-feedback d-block small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Phone --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label text-dark small fw-semibold mb-1" style="font-size: 0.8rem; color: #1e293b;">
                                Số điện thoại
                            </label>
                            <input type="text" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone') }}" placeholder="09xxxxxxxx"
                                   style="border-radius: 10px; font-size: 0.86rem; border: 1px solid rgba(0,0,0,0.12); height: 42px; background: #ffffff; color: #0f172a;">
                            @error('phone')
                                <div class="invalid-feedback d-block small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label text-dark small fw-semibold mb-1" style="font-size: 0.8rem; color: #1e293b;">
                                Mật khẩu khởi tạo <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" name="password" id="modalPassword"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Tối thiểu 8 ký tự" minlength="8" required
                                       style="border-radius: 10px 0 0 10px; font-size: 0.86rem; border: 1px solid rgba(0,0,0,0.12); border-right: none; height: 42px; background: #ffffff; color: #0f172a;">
                                <button class="btn btn-light border eye-btn" type="button" onclick="toggleModalPwd('modalPassword', this)" style="border-radius: 0 10px 10px 0; border-color: rgba(0,0,0,0.12) !important; color: #64748b; padding: 0 14px;">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Confirm Password --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label text-dark small fw-semibold mb-1" style="font-size: 0.8rem; color: #1e293b;">
                                Xác nhận mật khẩu <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" name="password_confirmation" id="modalPasswordConfirm"
                                       class="form-control" placeholder="Nhập lại mật khẩu" required
                                       style="border-radius: 10px 0 0 10px; font-size: 0.86rem; border: 1px solid rgba(0,0,0,0.12); border-right: none; height: 42px; background: #ffffff; color: #0f172a;">
                                <button class="btn btn-light border eye-btn" type="button" onclick="toggleModalPwd('modalPasswordConfirm', this)" style="border-radius: 0 10px 10px 0; border-color: rgba(0,0,0,0.12) !important; color: #64748b; padding: 0 14px;">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Role + Verified --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label text-dark small fw-semibold mb-1" style="font-size: 0.8rem; color: #1e293b;">
                                Phân quyền vai trò <span class="text-danger">*</span>
                            </label>
                            <select name="role" class="form-select @error('role') is-invalid @enderror" style="border-radius: 10px; font-size: 0.86rem; border: 1px solid rgba(0,0,0,0.12); height: 42px; background: #ffffff; color: #0f172a;">
                                <option value="receptionist" {{ old('role', 'receptionist') === 'receptionist' ? 'selected' : '' }}>Lễ tân vận hành</option>
                                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Quản trị viên toàn quyền</option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback d-block small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 d-flex align-items-center">
                            <div class="form-check form-switch pt-md-3">
                                <input class="form-check-input" type="checkbox" name="verified" id="modalVerified" value="1" {{ old('verified', 1) ? 'checked' : '' }} style="cursor: pointer; width: 38px; height: 20px;">
                                <label class="form-check-label fw-semibold text-dark ms-2" for="modalVerified" style="font-size: 0.84rem; cursor: pointer; color: #1e293b;">
                                    Kích hoạt tài khoản ngay
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer actions --}}
                <div class="modal-footer px-4 py-3 d-flex align-items-center justify-content-end gap-2" style="background: #f8fafc; border-top: 1px solid rgba(0,0,0,0.06);">
                    <button type="button" class="btn btn-light px-3.5 py-2 fw-medium" data-bs-dismiss="modal" style="border-radius: 999px; border: 1px solid rgba(0,0,0,0.14); color: #334155; font-size: 0.82rem; background: #ffffff;">
                        Hủy
                    </button>
                    <button type="submit" class="btn btn-dark px-4 py-2 fw-bold d-inline-flex align-items-center gap-1.5" style="border-radius: 999px; background: #0f172a; color: #ffffff; font-size: 0.82rem; border: none; box-shadow: 0 2px 8px rgba(15,23,42,0.18);">
                        <i class="bi bi-person-plus-fill"></i>
                        <span>Tạo tài khoản</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 18px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 20px 50px rgba(0,0,0,0.15); overflow: hidden; background: #ffffff;">
            <div class="modal-header d-flex align-items-center justify-content-between px-4 py-3" style="background: #ffffff; border-bottom: 1px solid rgba(0,0,0,0.06);">
                <div class="window-controls window-controls--modal d-flex align-items-center gap-1" style="pointer-events: none; margin: 0; padding: 0;" aria-hidden="true">
                    <span class="ctrl-dot ctrl-red"></span>
                    <span class="ctrl-dot ctrl-yellow"></span>
                    <span class="ctrl-dot ctrl-green"></span>
                </div>
                <h5 class="modal-title fw-bold text-dark m-0 fs-6 text-center flex-grow-1" id="deleteModalLabel" style="color: #0f172a;">Xác nhận xóa tài khoản</h5>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Đóng" style="font-size: 0.75rem;"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <i class="bi bi-trash3 text-danger fs-1 mb-3 d-inline-block"></i>
                <h5 class="fw-bold mb-2" style="color: #0f172a; font-size: 1.1rem;">Xóa tài khoản này?</h5>
                <p class="mb-4" style="color: #475569; font-size: 0.9rem;">Tài khoản <strong id="delete-name" class="fw-bold" style="color: #0f172a;"></strong> sẽ bị xóa vĩnh viễn khỏi hệ thống.</p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light px-4 fw-medium" data-bs-dismiss="modal" style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.1); color: #334155; font-size: 0.88rem;">Giữ lại</button>
                    <button type="button" class="btn btn-danger px-4 fw-medium" id="confirm-delete" style="border-radius: 10px; background: #ef4444; border-color: #ef4444; font-size: 0.88rem;">Xóa vĩnh viễn</button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3"><div id="statusToast" class="toast border-0"><div class="d-flex"><div class="toast-body" id="toastMessage"></div><button class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button></div></div></div>
@endsection
@push('scripts')
<script>
let deleteTargetId=null;const deleteModal=new bootstrap.Modal(document.getElementById('deleteModal'));document.querySelectorAll('.btn-delete').forEach(btn=>btn.addEventListener('click',()=>{deleteTargetId=btn.dataset.id;document.getElementById('delete-name').textContent=btn.dataset.name;deleteModal.show()}));document.getElementById('confirm-delete').addEventListener('click',()=>deleteTargetId&&document.getElementById('delete-form-'+deleteTargetId).submit());
const toast=new bootstrap.Toast(document.getElementById('statusToast'));document.querySelectorAll('.btn-toggle-status').forEach(btn=>btn.addEventListener('click',async function(){if(this.disabled)return;this.disabled=true;try{const res=await fetch('/admin/users/'+this.dataset.id+'/toggle-verified',{method:'PATCH',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});const json=await res.json();if(!res.ok||!json.success)throw new Error(json.message||'Không thể cập nhật');this.className='status-pill '+(json.verified?'status-confirmed':'status-cancelled')+' btn-toggle-status';this.textContent=json.verified?'Đang hoạt động':'Đã khóa';document.getElementById('toastMessage').textContent=json.message;toast.show()}catch(error){document.getElementById('toastMessage').textContent=error.message;toast.show()}finally{this.disabled=false}}));
function toggleModalPwd(id, btn) {
    const input = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

@if($errors->has('fullname') || $errors->has('username') || $errors->has('email') || $errors->has('password') || $errors->has('role'))
    const createModal = new bootstrap.Modal(document.getElementById('createUserModal'));
    createModal.show();
@endif
</script>
@endpush
