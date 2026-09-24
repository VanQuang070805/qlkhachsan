@extends('layouts.admin')

@section('title', 'Quản lý tài khoản')

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">
            <i class="bi bi-people-fill me-2 text-primary"></i>Quản lý người dùng
        </h4>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i> Thêm tài khoản
        </a>
    </div>

    {{-- Thông báo --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Bộ lọc --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Tìm kiếm</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control"
                               placeholder="Tên, email, username..."
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Vai trò</label>
                    <select name="role" class="form-select">
                        <option value="">-- Tất cả --</option>
                        <option value="admin"        {{ request('role') === 'admin'        ? 'selected' : '' }}>Quản trị viên</option>
                        <option value="receptionist" {{ request('role') === 'receptionist' ? 'selected' : '' }}>Lễ tân</option>
                        <option value="customer"     {{ request('role') === 'customer'     ? 'selected' : '' }}>Khách hàng</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Trạng thái</label>
                    <select name="verified" class="form-select">
                        <option value="">-- Tất cả --</option>
                        <option value="1" {{ request('verified') === '1' ? 'selected' : '' }}>Đang hoạt động</option>
                        <option value="0" {{ request('verified') === '0' ? 'selected' : '' }}>Đã khóa</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Lọc
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Bảng --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Họ tên</th>
                            <th>Tài khoản</th>
                            <th>Email</th>
                            <th>SĐT</th>
                            <th class="text-center">Vai trò</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                        <tr>
                            <td class="ps-3 text-muted">{{ $user->id }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle bg-{{ $user->role_badge }} bg-opacity-10 text-{{ $user->role_badge }}">
                                        {{ mb_strtoupper(mb_substr($user->fullname, 0, 1)) }}
                                    </div>
                                    <span class="fw-semibold">{{ $user->fullname }}</span>
                                </div>
                            </td>
                            <td class="text-muted">{{ $user->username }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?? '—' }}</td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-{{ $user->role_badge }}-subtle text-{{ $user->role_badge }} border border-{{ $user->role_badge }}-subtle px-2">
                                    {{ $user->role_label }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if ($user->id === auth()->id())
                                    {{-- Không cho khóa chính mình --}}
                                    <span class="badge bg-success">
                                        <i class="bi bi-unlock-fill me-1"></i>Hoạt động
                                    </span>
                                @elseif ($user->verified)
                                    <button class="btn btn-sm btn-success btn-toggle-status"
                                            data-id="{{ $user->id }}"
                                            data-status="1"
                                            title="Bấm để khóa tài khoản">
                                        <i class="bi bi-unlock-fill me-1"></i>Hoạt động
                                    </button>
                                @else
                                    <button class="btn btn-sm btn-danger btn-toggle-status"
                                            data-id="{{ $user->id }}"
                                            data-status="0"
                                            title="Bấm để mở khóa tài khoản">
                                        <i class="bi bi-lock-fill me-1"></i>Đã khóa
                                    </button>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.users.edit', $user) }}"
                                   class="btn btn-sm btn-outline-primary me-1"
                                   title="Chỉnh sửa">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                @if ($user->id !== auth()->id())
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger btn-delete"
                                        data-id="{{ $user->id }}"
                                        data-name="{{ $user->fullname }}"
                                        title="Xóa">
                                    <i class="bi bi-trash3"></i>
                                </button>
                                <form id="delete-form-{{ $user->id }}"
                                      action="{{ route('admin.users.destroy', $user) }}"
                                      method="POST" class="d-none">
                                    @csrf @method('DELETE')
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                Không tìm thấy tài khoản nào
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($users->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Hiển thị {{ $users->firstItem() }}–{{ $users->lastItem() }}
                trong tổng {{ $users->total() }} tài khoản
            </small>
            {{ $users->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

{{-- Modal xác nhận xóa --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Xác nhận xóa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Bạn có chắc muốn xóa tài khoản <strong id="delete-name"></strong>?
                <br><small class="text-muted">Hành động này không thể hoàn tác.</small>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="confirm-delete">
                    <i class="bi bi-trash3 me-1"></i>Xóa
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Toast thông báo --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="statusToast" class="toast align-items-center border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage"></div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .avatar-circle {
        width: 36px; height: 36px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 15px; flex-shrink: 0;
    }
</style>
@endpush

@push('scripts')
<script>
// Xóa tài khoản
let deleteTargetId = null;
const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', () => {
        deleteTargetId = btn.dataset.id;
        document.getElementById('delete-name').textContent = btn.dataset.name;
        deleteModal.show();
    });
});

document.getElementById('confirm-delete').addEventListener('click', () => {
    if (deleteTargetId) {
        document.getElementById('delete-form-' + deleteTargetId).submit();
    }
});

// Khóa / Mở tài khoản
const toast = new bootstrap.Toast(document.getElementById('statusToast'));

document.querySelectorAll('.btn-toggle-status').forEach(btn => {
    btn.addEventListener('click', async function () {
        const id = this.dataset.id;
        try {
            const res = await fetch(`/admin/users/${id}/toggle-verified`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            });
            const json = await res.json();

            if (json.success) {
                // Đổi nút ngay không cần reload
                if (json.verified) {
                    this.className = 'btn btn-sm btn-success btn-toggle-status';
                    this.innerHTML = '<i class="bi bi-unlock-fill me-1"></i>Hoạt động';
                    this.dataset.status = '1';
                    this.title = 'Bấm để khóa tài khoản';
                } else {
                    this.className = 'btn btn-sm btn-danger btn-toggle-status';
                    this.innerHTML = '<i class="bi bi-lock-fill me-1"></i>Đã khóa';
                    this.dataset.status = '0';
                    this.title = 'Bấm để mở khóa tài khoản';
                }

                // Hiện toast
                const toastEl = document.getElementById('statusToast');
                toastEl.classList.remove('bg-success', 'bg-danger', 'text-white');
                toastEl.classList.add(json.verified ? 'bg-success' : 'bg-danger', 'text-white');
                document.getElementById('toastMessage').textContent = json.message;
                toast.show();
            }
        } catch (e) {
            console.error(e);
        }
    });
});
</script>
@endpush