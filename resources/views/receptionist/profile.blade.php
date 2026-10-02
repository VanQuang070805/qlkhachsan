@extends('layouts.dashboard')

@section('title', 'Hồ Sơ Cá Nhân · Posh Boutique')
@section('page-title', 'Hồ sơ cá nhân')

@section('content')
<div class="macos-profile-wrapper">

    {{-- ===== ALERTS FLASH ===== --}}
    @if(session('success_info'))
        <div class="macos-alert-banner alert-success mb-3.5 w-100" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                <span class="fw-semibold text-slate-800" style="font-size: 0.85rem;">{{ session('success_info') }}</span>
            </div>
            <button type="button" class="btn-close" onclick="this.parentElement.remove()" aria-label="Close"></button>
        </div>
    @endif

    @if(session('success_password'))
        <div class="macos-alert-banner alert-success mb-3.5 w-100" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-shield-fill-check text-success fs-5"></i>
                <span class="fw-semibold text-slate-800" style="font-size: 0.85rem;">{{ session('success_password') }}</span>
            </div>
            <button type="button" class="btn-close" onclick="this.parentElement.remove()" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="macos-alert-banner alert-danger mb-3.5 w-100" role="alert">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5 mt-0.5"></i>
                <div>
                    <div class="fw-bold text-danger mb-1" style="font-size: 0.85rem;">Đã có lỗi xảy ra:</div>
                    <ul class="mb-0 ps-3 text-slate-700" style="font-size: 0.82rem;">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" onclick="this.parentElement.remove()" aria-label="Close"></button>
        </div>
    @endif

    {{-- ===== BỐ CỤC 2 CARD CĂN CHÍNH GIỮA GIAO DIỆN (RA GIỮA, KHOẢNG CÁCH CÂN ĐỐI) ===== --}}
    <div class="macos-cards-container d-flex flex-column flex-lg-row gap-4 align-items-stretch">
        
        {{-- ===== THẺ 1 (TRÁI): IDENTITY CARD (340PX, 2 NÚT THON GỌN, KHUNG RA GIỮA) ===== --}}
        <div class="macos-card-col-left">
            <div class="macos-vibrancy-card macos-identity-card w-100 h-100 d-flex flex-column justify-content-between p-4">
                
                {{-- A. Thanh Tiêu Đề Đèn Giao Thông (Cao Ngang Bằng Với Header Card Phải) --}}
                <div class="macos-card-header-row d-flex align-items-center justify-content-between w-100 mb-4">
                    {{-- Đèn giao thông thuần trang trí, không tương tác --}}
                    <div class="macos-traffic-lights" aria-hidden="true">
                        <span class="traffic-dot dot-close"></span>
                        <span class="traffic-dot dot-minimize"></span>
                        <span class="traffic-dot dot-zoom"></span>
                    </div>
                    <span class="macos-card-tag tracking-[0.18em]">IDENTITY CARD</span>
                </div>

                {{-- B. Đại Diện & Huy Hiệu Định Danh (Căn Giữa, Tách Rõ Khỏi Avatar) --}}
                <div class="w-100 d-flex flex-column align-items-center">
                    
                    {{-- Avatar Monogram với Vòng Hào Quang Phát Sáng --}}
                    <div class="macos-avatar-container mb-3" id="avatarContainer">
                        <div class="avatar-halo-ring"></div>
                        <div class="avatar-inner-box">
                            <span class="avatar-monogram" id="cardAvatarMonogram">
                                {{ mb_strtoupper(mb_substr($user->fullname ?? $user->name, 0, 1)) }}
                            </span>
                        </div>
                        <span class="status-pip-online" title="Đang trực tuyến"></span>
                    </div>

                    {{-- Tên Người Dùng --}}
                    <h2 class="macos-user-fullname mt-2 mb-2 text-center" id="cardFullname">
                        {{ $user->fullname ?? $user->name }}
                    </h2>

                    {{-- Huy Hiệu Mã Số --}}
                    <div class="macos-id-pill font-monospace mb-4">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1.5 text-sky-500">
                            <path d="M3 5v14M7 5v14M11 5v14M15 5v14M19 5v14M21 5v14"/>
                        </svg>
                        <span>{{ $user->role === 'admin' ? 'AETH-EXEC-001' : 'AETH-STAFF-' . str_pad($user->id, 3, '0', STR_PAD_LEFT) }}</span>
                    </div>

                    {{-- C. Khung Thông Số Inset (Căn Ra Giữa Card Identity, Chấm Xanh Không Bị Đè Chữ) --}}
                    <div class="macos-status-box">
                        <div class="status-row">
                            <span class="status-label">
                                <span class="status-dot-emerald"></span>
                                <span>Trạng thái:</span>
                            </span>
                            <span class="status-val-active">Đang trực tuyến</span>
                        </div>
                        <div class="status-row">
                            <span class="status-label">
                                <i class="bi bi-shield-check text-sky-500"></i>
                                <span>Bảo mật:</span>
                            </span>
                            <span class="badge-security-enclave">Apple Enclave 2FA</span>
                        </div>
                        <div class="status-row">
                            <span class="status-label">
                                <i class="bi bi-person text-slate-500"></i>
                                <span>Phân quyền:</span>
                            </span>
                            <span class="text-slate-900 fw-bold" style="font-size: 0.82rem;">
                                {{ $user->role === 'admin' ? 'Quản trị viên' : 'Nhân viên lễ tân' }}
                            </span>
                        </div>
                    </div>

                </div>

                {{-- D. 2 Nút Tác Vụ Thu Gọn, Căn Giữa, Không Bị Kéo Dài Fit Card --}}
                <div class="w-100 d-flex flex-column align-items-center gap-3 mt-auto pt-2">
                    <button type="button" class="btn btn-macos-action-primary" onclick="openModal('editProfileModal')">
                        <i class="bi bi-pencil-fill me-2 fs-7"></i>
                        <span>Chỉnh Sửa Hồ Sơ</span>
                    </button>
                    <button type="button" class="btn btn-macos-action-secondary" onclick="openModal('passwordModal')">
                        <i class="bi bi-key-fill me-2 text-amber-500 fs-6"></i>
                        <span>Đổi Mật Khẩu</span>
                    </button>
                </div>

            </div>
        </div>

        {{-- ===== THẺ 2 (PHẢI): BẢNG CHI TIẾT THÔNG TIN HỒ SƠ & TÀI KHOẢN ===== --}}
        <div class="macos-card-col-right flex-grow-1">
            <div class="macos-vibrancy-card macos-detail-card w-100 h-100 d-flex flex-column justify-content-between p-4">
                
                <div>
                    {{-- A. Thanh Trạng Thái Xác Thực (Cao Ngang Bằng Với Traffic Light Card Trái) --}}
                    <div class="macos-card-header-row d-flex align-items-center justify-content-between w-100 mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-vcard text-primary fs-5" style="line-height: 1; display: inline-flex; align-items: center;"></i>
                            <span class="fw-bold text-slate-800" style="font-size: 0.92rem; letter-spacing: -0.01em; line-height: 1;">
                                Thông Tin Cá Nhân Chi Tiết
                            </span>
                        </div>
                        <span class="badge-apple-status">
                            <span class="status-dot-emerald animate-pulse"></span>
                            <span>Hệ Thống Đã Xác Thực</span>
                        </span>
                    </div>

                    {{-- B. Tiêu Đề Phân Khu (Đã Xóa Dòng Chữ Màu Xám) --}}
                    <div class="detail-section-intro mb-4">
                        <h3 class="detail-title mb-0">Hồ Sơ Định Danh &amp; Tài Khoản</h3>
                    </div>

                    {{-- C. Bảng 5 Dòng (Icon Chính Thống Không Viền Bao Quanh, Xóa Chữ Xám Dưới Title) --}}
                    <div class="macos-grouped-list rounded-2xl overflow-hidden mb-4">
                        
                        {{-- 1. Họ và tên đầy đủ --}}
                        <div class="macos-list-row d-flex align-items-center justify-content-between border-bottom border-slate-100">
                            <div class="row-meta d-flex align-items-center gap-3">
                                <i class="bi bi-person-fill fs-5 text-primary"></i>
                                <span class="row-field-name">Họ và tên đầy đủ</span>
                            </div>
                            <div class="row-field-val text-end d-flex align-items-center gap-2">
                                <span class="text-slate-800" id="detailFullname" style="font-size: 0.85rem; font-weight: 500;">{{ $user->fullname ?? $user->name }}</span>
                                <button type="button" class="btn btn-sm btn-macos-icon-clean" onclick="openModal('editProfileModal')" title="Chỉnh sửa họ tên">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </div>
                        </div>

                        {{-- 2. Tên đăng nhập hệ thống --}}
                        <div class="macos-list-row d-flex align-items-center justify-content-between border-bottom border-slate-100">
                            <div class="row-meta d-flex align-items-center gap-3">
                                <i class="bi bi-person-badge-fill fs-5 text-amber-500"></i>
                                <span class="row-field-name">Tên đăng nhập hệ thống</span>
                            </div>
                            <div class="row-field-val text-end d-flex align-items-center gap-2">
                                <code class="macos-code-badge font-monospace">{{ $user->username }}</code>
                                <span class="badge-fixed-tag font-monospace">
                                    <i class="bi bi-lock-fill me-1 text-slate-400"></i>Cố định
                                </span>
                            </div>
                        </div>

                        {{-- 3. Vai trò & Phân quyền (Badge trung tính, phông chữ monospace giống letan) --}}
                        <div class="macos-list-row d-flex align-items-center justify-content-between border-bottom border-slate-100">
                            <div class="row-meta d-flex align-items-center gap-3">
                                <i class="bi bi-shield-lock-fill fs-5 text-slate-600"></i>
                                <span class="row-field-name">Vai trò &amp; Phân quyền</span>
                            </div>
                            <div class="row-field-val text-end">
                                <span class="badge-role-neutral font-monospace">
                                    {{ $user->role === 'admin' ? 'Quản trị viên (Admin)' : 'Nhân viên lễ tân (Staff)' }}
                                </span>
                            </div>
                        </div>

                        {{-- 4. Địa chỉ Email (Phông chữ monospace giống letan) --}}
                        <div class="macos-list-row d-flex align-items-center justify-content-between border-bottom border-slate-100">
                            <div class="row-meta d-flex align-items-center gap-3">
                                <i class="bi bi-envelope-fill fs-5 text-emerald-500"></i>
                                <span class="row-field-name">Địa chỉ Email</span>
                            </div>
                            <div class="row-field-val text-end d-flex align-items-center gap-2">
                                <span class="font-monospace text-slate-800" id="detailEmail" style="font-size: 0.84rem;">{{ $user->email }}</span>
                                <button type="button" class="btn btn-sm btn-macos-icon-clean" onclick="copyToClipboard('{{ $user->email }}', 'Đã sao chép Email!')" title="Sao chép Email">
                                    <i class="bi bi-copy"></i>
                                </button>
                            </div>
                        </div>

                        {{-- 5. Số điện thoại Hotline (Phông chữ monospace giống letan) --}}
                        <div class="macos-list-row d-flex align-items-center justify-content-between">
                            <div class="row-meta d-flex align-items-center gap-3">
                                <i class="bi bi-telephone-fill fs-5 text-teal-600"></i>
                                <span class="row-field-name">Số điện thoại Hotline</span>
                            </div>
                            <div class="row-field-val text-end d-flex align-items-center gap-2">
                                <span class="font-monospace text-slate-800" id="detailPhone" style="font-size: 0.84rem;">{{ $user->phone ?? '0900000001' }}</span>
                                <a href="tel:{{ $user->phone ?? '0900000001' }}" class="btn btn-sm btn-macos-icon-clean" title="Gọi trực tiếp">
                                    <i class="bi bi-arrow-up-right"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-macos-icon-clean" onclick="copyToClipboard('{{ $user->phone ?? '0900000001' }}', 'Đã sao chép số điện thoại!')" title="Sao chép SĐT">
                                    <i class="bi bi-copy"></i>
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- D. Chân Trang Hệ Thống --}}
                <div class="pt-2 mt-4 d-flex flex-wrap justify-content-between align-items-center text-muted gap-2" style="font-size: 0.78rem;">
                    <div class="d-flex align-items-center gap-1.5 text-slate-600">
                        <i class="bi bi-shield-check text-success fs-6"></i>
                        <span>Hệ thống quản trị Aetheria OS v4.2</span>
                    </div>
                    <div class="text-slate-400">
                        Cập nhật gần nhất: <span class="fw-semibold text-slate-600" id="liveTimestamp">{{ $user->updated_at ? \Carbon\Carbon::parse($user->updated_at)->format('d/m/Y H:i') : '05/06/2026 18:27' }}</span>
                    </div>
                </div>

            </div>
        </div>

    </div>

</div>

{{-- ===== HỘP THOẠI 1: MACOS SHEET ĐỔI MẬT KHẨU ===== --}}
<div class="macos-modal-backdrop" id="passwordModal" style="display: none;" onclick="closeModalOnBackdrop(event, 'passwordModal')">
    <div class="macos-modal-dialog modal-dialog-password">
        
        {{-- Header: Orange Squircle + Tiêu đề (Căn giữa popup) + Nút Đóng '✕' --}}
        <div class="macos-modal-header position-relative d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center justify-content-center gap-2">
                <div class="modal-header-squircle bg-apple-amber">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.5 2v6h-6"></path>
                        <path d="M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                        <rect x="9.5" y="11" width="5" height="4.5" rx="1" fill="#ffffff"></rect>
                        <path d="M10.5 11V9.5a1.5 1.5 0 0 1 3 0V11"></path>
                    </svg>
                </div>
                <h3 class="modal-header-title text-center mb-0">
                    {{ $user->role === 'admin' ? 'Đổi Mật Khẩu Quản Trị' : 'Đổi Mật Khẩu Nhân Viên' }}
                </h3>
            </div>
            <button type="button" class="modal-circle-close-btn" onclick="closeModal('passwordModal')" aria-label="Đóng popup">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        {{-- Form Đổi Mật Khẩu --}}
        <form id="formPwd" method="POST" action="{{ route('receptionist.profile.update-password') }}" class="macos-modal-body">
            @csrf

            {{-- 1. Mật khẩu hiện tại --}}
            <div class="modal-form-group mb-2.5">
                <label class="modal-field-label">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                <div class="modal-input-wrap">
                    <input type="password" name="current_password" id="popup_p1" class="modal-field-input @error('current_password') is-invalid @enderror" placeholder="Nhập mật khẩu hiện tại..." required>
                    <button type="button" class="modal-input-eye-btn" onclick="togglePopupPwd('popup_p1', this)" aria-label="Hiện/ẩn mật khẩu">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                @error('current_password')<div class="modal-field-error">{{ $message }}</div>@enderror
            </div>

            {{-- 2. Mật khẩu mới --}}
            <div class="modal-form-group mb-2">
                <label class="modal-field-label">Mật khẩu mới <span class="text-danger">*</span></label>
                <div class="modal-input-wrap">
                    <input type="password" name="new_password" id="popup_p2" class="modal-field-input @error('new_password') is-invalid @enderror" placeholder="Tối thiểu 8 ký tự..." required oninput="calculateAppleStrength(this.value)">
                    <button type="button" class="modal-input-eye-btn" onclick="togglePopupPwd('popup_p2', this)" aria-label="Hiện/ẩn mật khẩu">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                @error('new_password')<div class="modal-field-error">{{ $message }}</div>@enderror
            </div>

            {{-- Thanh Đo Độ An Toàn 4 Vạch Chuẩn Apple --}}
            <div class="apple-strength-wrap mb-2.5 px-1">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="apple-meter-label text-muted" style="font-size: 0.7rem;">Độ an toàn mật khẩu:</span>
                    <span class="apple-meter-status fw-semibold" id="appleStrengthText" style="font-size: 0.7rem;">Chưa nhập</span>
                </div>
                <div class="apple-meter-track d-flex gap-1.5">
                    <div class="meter-bar flex-grow-1" id="mBar1"></div>
                    <div class="meter-bar flex-grow-1" id="mBar2"></div>
                    <div class="meter-bar flex-grow-1" id="mBar3"></div>
                    <div class="meter-bar flex-grow-1" id="mBar4"></div>
                </div>
            </div>

            {{-- 3. Xác nhận mật khẩu mới --}}
            <div class="modal-form-group mb-2.5">
                <label class="modal-field-label">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                <div class="modal-input-wrap">
                    <input type="password" name="new_password_confirmation" id="popup_p3" class="modal-field-input" placeholder="Nhập lại mật khẩu mới..." required>
                    <button type="button" class="modal-input-eye-btn" onclick="togglePopupPwd('popup_p3', this)" aria-label="Hiện/ẩn mật khẩu">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            {{-- Footer: 2 Buttons Chuẩn Dạng Pill --}}
            <div class="modal-footer-actions d-flex align-items-center justify-content-end gap-2.5 pt-2">
                <button type="button" class="btn btn-modal-cancel" onclick="closeModal('passwordModal')">Hủy Bỏ</button>
                <button type="submit" class="btn btn-modal-submit-blue">Lưu Mật Khẩu</button>
            </div>
        </form>

    </div>
</div>

{{-- ===== HỘP THOẠI 2: MACOS SHEET CHỈNH SỬA HỒ SƠ ===== --}}
<div class="macos-modal-backdrop" id="editProfileModal" style="display: none;" onclick="closeModalOnBackdrop(event, 'editProfileModal')">
    <div class="macos-modal-dialog modal-dialog-edit">
        
        {{-- Header: Blue Squircle + Tiêu đề (Căn giữa popup) + Nút Đóng '✕' --}}
        <div class="macos-modal-header position-relative d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center justify-content-center gap-2">
                <div class="modal-header-squircle bg-apple-blue">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                    </svg>
                </div>
                <h3 class="modal-header-title text-center mb-0">
                    {{ $user->role === 'admin' ? 'Chỉnh Sửa Hồ Sơ Quản Trị' : 'Chỉnh Sửa Hồ Sơ Nhân Viên' }}
                </h3>
            </div>
            <button type="button" class="modal-circle-close-btn" onclick="closeModal('editProfileModal')" aria-label="Đóng popup">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        {{-- Form Chỉnh Sửa Hồ Sơ --}}
        <form id="formInfo" method="POST" action="{{ route('receptionist.profile.update-info') }}" class="macos-modal-body">
            @csrf

            {{-- 1. Họ và tên đầy đủ --}}
            <div class="modal-form-group mb-2.5">
                <label class="modal-field-label">Họ và tên đầy đủ <span class="text-danger">*</span></label>
                <div class="modal-input-wrap">
                    <input type="text" name="fullname" id="inputPopupFullname" class="modal-field-input @error('fullname') is-invalid @enderror" value="{{ old('fullname', $user->fullname ?? $user->name) }}" required oninput="syncLiveName(this.value)">
                </div>
                @error('fullname')<div class="modal-field-error">{{ $message }}</div>@enderror
            </div>

            {{-- 2. Tên đăng nhập hệ thống (Cố định định danh) --}}
            <div class="modal-form-group mb-2.5">
                <label class="modal-field-label">
                    Tên đăng nhập hệ thống <span class="text-muted fw-normal">(Cố định định danh)</span>
                </label>
                <div class="modal-input-wrap">
                    <input type="text" class="modal-field-input input-readonly font-monospace" value="{{ $user->username }}" readonly disabled>
                </div>
            </div>

            {{-- 3. Vai trò & Phân quyền (Dropdown) --}}
            <div class="modal-form-group mb-2.5">
                <label class="modal-field-label">Vai trò &amp; Phân quyền <span class="text-danger">*</span></label>
                <div class="modal-input-wrap position-relative">
                    <input type="text" class="modal-field-input input-readonly pe-5 font-monospace" value="{{ $user->role === 'admin' ? 'Quản trị viên (Admin)' : 'Nhân viên lễ tân (Staff)' }}" readonly disabled>
                    <i class="bi bi-chevron-down position-absolute end-0 top-50 translate-middle-y me-3 text-slate-400" style="font-size: 0.75rem;"></i>
                </div>
            </div>

            {{-- 4. Địa chỉ Email --}}
            <div class="modal-form-group mb-2.5">
                <label class="modal-field-label">Địa chỉ Email <span class="text-danger">*</span></label>
                <div class="modal-input-wrap">
                    <input type="email" name="email" id="inputPopupEmail" class="modal-field-input font-monospace @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                </div>
                @error('email')<div class="modal-field-error">{{ $message }}</div>@enderror
            </div>

            {{-- 5. Số điện thoại Hotline --}}
            <div class="modal-form-group mb-2.5">
                <label class="modal-field-label">Số điện thoại Hotline <span class="text-danger">*</span></label>
                <div class="modal-input-wrap">
                    <input type="text" name="phone" id="inputPopupPhone" class="modal-field-input font-monospace @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone ?? '0900000001') }}" required>
                </div>
                @error('phone')<div class="modal-field-error">{{ $message }}</div>@enderror
            </div>

            {{-- Footer: 2 Buttons Chuẩn Dạng Pill --}}
            <div class="modal-footer-actions d-flex align-items-center justify-content-end gap-2.5 pt-2">
                <button type="button" class="btn btn-modal-cancel" onclick="closeModal('editProfileModal')">Hủy Bỏ</button>
                <button type="submit" class="btn btn-modal-submit-blue">Lưu Thay Đổi</button>
            </div>
        </form>

    </div>
</div>

{{-- ===== TOAST NOTIFICATION CHUẨN MACOS ===== --}}
<div class="macos-floating-toast" id="macosToast" style="display: none;">
    <i class="bi bi-check2-circle text-success fs-5"></i>
    <span id="toastMsg">Đã sao chép vào bộ nhớ tạm!</span>
</div>

<style>
/* ========================================================
   MACOS SEQUOIA 2-CARD DESIGN SYSTEM TOKENS & VIBRANCY
   CĂN CHÍNH GIỮA GIAO DIỆN HOÀN HẢO
   ======================================================== */
.macos-profile-wrapper {
    max-width: 1040px; /* Độ rộng tối ưu để căn chính giữa giao diện */
    margin: 36px auto 44px; /* Lùi xuống dưới để căn ra chính giữa không gian giao diện web */
    padding: 0 16px 40px;
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "SF Pro Display", "Inter", "Segoe UI", Roboto, sans-serif !important;
    -webkit-font-smoothing: antialiased;
}
.macos-profile-wrapper * {
    box-sizing: border-box;
}

/* Container chia 2 card căn giữa */
.macos-cards-container {
    width: 100%;
    display: flex;
    justify-content: center;
    gap: 24px;
    align-items: stretch;
}

.macos-card-col-left {
    width: 340px;
    flex: 0 0 340px;
}
@media (max-width: 991.98px) {
    .macos-card-col-left {
        width: 100%;
        flex: 1 1 100%;
    }
}

.macos-card-col-right {
    flex: 1 1 0%;
    min-width: 0;
}

/* Chất liệu kính mờ Apple (macOS Vibrancy) */
.macos-vibrancy-card {
    border-radius: 26px;
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.95);
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
    transition: transform 0.18s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.18s cubic-bezier(0.16, 1, 0.3, 1);
}
.macos-vibrancy-card:hover {
    box-shadow: 0 20px 48px rgba(15, 23, 42, 0.09);
}

.macos-identity-card,
.macos-detail-card {
    padding: 24px 28px !important;
}

/* Thanh Header 2 Card (Căn ngang bằng nhau tuyệt đối) */
.macos-card-header-row {
    height: 28px;
    min-height: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px !important;
}

.macos-traffic-lights {
    display: flex;
    align-items: center;
    gap: 7.5px;
    pointer-events: none;
    user-select: none;
}
.traffic-dot {
    position: relative;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: none;
    padding: 0;
    margin: 0;
    display: inline-block;
    cursor: default;
    pointer-events: none;
    user-select: none;
    box-shadow: inset 0 1px 1px rgba(255,255,255,0.45), 0 1px 2px rgba(0,0,0,0.12);
}
.dot-close    { background: #ff5f56; border: 1px solid #e0443e; }
.dot-minimize { background: #ffbd2e; border: 1px solid #dea123; }
.dot-zoom     { background: #27c93f; border: 1px solid #1aab29; }

.macos-card-tag {
    font-size: 0.68rem;
    font-weight: 700;
    color: #94a3b8;
    letter-spacing: 0.18em;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

/* Avatar Styling với Vòng Hào Quang Phát Sáng */
.macos-avatar-container {
    position: relative;
    width: 96px;
    height: 96px;
}
.avatar-halo-ring {
    position: absolute;
    inset: -3px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0071e3 0%, #38bdf8 50%, #3b82f6 100%);
    filter: blur(2px);
    opacity: 0.9;
}
.avatar-inner-box {
    position: relative;
    width: 96px;
    height: 96px;
    border-radius: 50%;
    background: #020617; /* bg-slate-950 */
    border: 3px solid #ffffff;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.avatar-monogram {
    color: #ffffff;
    font-size: 2.25rem;
    font-weight: 700;
}
.status-pip-online {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 15px;
    height: 15px;
    background: #10b981;
    border: 2px solid #ffffff;
    border-radius: 50%;
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
    animation: statusPulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes statusPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.75; transform: scale(1.08); }
}

.macos-user-fullname, h2.macos-user-fullname {
    font-size: 1.25rem !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    letter-spacing: -0.015em;
    line-height: 1.35;
}

.macos-id-pill {
    display: inline-flex;
    align-items: center;
    background: #f0f7ff;
    color: #0284c7;
    padding: 3.5px 12px;
    border-radius: 999px;
    font-size: 0.74rem;
    font-weight: 600;
    border: 1px solid #bae0fd;
}

/* Khung Thông Số Inset (Căn Ra Giữa Card Identity, Chấm Xanh Không Bị Đè Chữ) */
.macos-status-box {
    background: rgba(248, 250, 252, 0.85);
    border: 1px solid rgba(226, 232, 240, 0.7);
    border-radius: 16px;
    padding: 14px 18px;
    width: 100%;
    max-width: 290px; /* Ra giữa card */
    margin: 26px auto 26px; /* Tách xa mã định danh và căn ra giữa card */
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.status-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.82rem;
    white-space: nowrap;
}
.status-label {
    color: #64748b;
    display: inline-flex;
    align-items: center;
    gap: 8px; /* Tách rõ ràng 8px, không bao giờ đè chữ */
    font-weight: 500;
    white-space: nowrap;
}
.status-dot-emerald {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #10b981;
    display: inline-block;
    flex-shrink: 0;
    margin: 0 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
}
.status-val-active {
    color: #059669;
    font-weight: 700;
    font-size: 0.8rem;
}
.badge-security-enclave {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
    padding: 2.5px 9px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 600;
}

/* 2 Nút Tác Vụ Thu Gọn, Căn Giữa, Không Kéo Dài Fit Card */
.btn-macos-action-primary {
    background: #0f172a !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: 12px !important;
    width: 210px !important;
    min-width: 210px !important;
    max-width: 210px !important;
    height: 42px !important;
    font-size: 0.84rem !important;
    font-weight: 600 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.12) !important;
    transition: all 0.15s ease !important;
    margin: 0 auto !important;
}
.btn-macos-action-primary:hover {
    background: #1e293b !important;
    color: #ffffff !important;
}
.btn-macos-action-primary:active {
    transform: scale(0.98) !important;
}

.btn-macos-action-secondary {
    background: #ffffff !important;
    color: #1e293b !important;
    border: 1px solid rgba(226, 232, 240, 0.95) !important;
    border-radius: 12px !important;
    width: 210px !important;
    min-width: 210px !important;
    max-width: 210px !important;
    height: 42px !important;
    font-size: 0.84rem !important;
    font-weight: 600 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03) !important;
    transition: all 0.15s ease !important;
    margin: 0 auto !important;
}
.btn-macos-action-secondary:hover {
    background: #f8fafc !important;
    border-color: #cbd5e1 !important;
    color: #0f172a !important;
}
.btn-macos-action-secondary:active {
    transform: scale(0.98) !important;
}

/* Thẻ 2 Chi Tiết (Right Card) */
.badge-apple-status {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 3.5px 12px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    white-space: nowrap;
}

.detail-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.02em;
}

/* Bảng 5 Dòng Chuẩn Apple (Không Viền Ngoài, Không Viền Icon) */
.macos-grouped-list {
    background: #ffffff;
    border: none !important;
    box-shadow: none !important;
}
.macos-list-row {
    padding: 15px 12px !important;
    transition: background 0.12s ease;
    min-height: 56px;
    border-bottom: 1px solid #f1f5f9;
}
.macos-list-row:last-child {
    border-bottom: none !important;
}
.macos-list-row:hover {
    background: #f8fafc;
    border-radius: 12px;
}

.row-field-name {
    font-size: 0.86rem;
    font-weight: 500; /* Bỏ in đậm theo yêu cầu */
    color: #334155;
    line-height: 1.3;
}

.macos-code-badge {
    background: #f1f5f9;
    color: #0f172a;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.8rem;
    border: 1px solid #e2e8f0;
}
.badge-fixed-tag {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 500;
}

/* Badge phân quyền trung tính - phông chữ monospace giống letan */
.badge-role-neutral {
    background: #f1f5f9;
    color: #0f172a;
    border: 1px solid #e2e8f0;
    padding: 3.5px 12px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 500;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
    display: inline-flex;
    align-items: center;
}

/* Phông chữ monospace giống letan cho Email & SĐT */
.detail-clean-font {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
    font-size: 0.84rem;
    color: #1e293b;
    letter-spacing: -0.01em;
}

/* Icon Nút Bấm Không Viền (Clean, Borderless) */
.btn-macos-icon-clean {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none !important;
    background: transparent !important;
    color: #94a3b8;
    font-size: 0.92rem;
    padding: 0;
    transition: all 0.12s ease;
    cursor: pointer;
}
.btn-macos-icon-clean:hover {
    background: rgba(0, 0, 0, 0.05) !important;
    color: #0071e3 !important;
}

/* ========================================================
   HỘP THOẠI MACOS SHEET TƯƠNG TÁC
   ======================================================== */
.macos-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    z-index: 10000;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}
.macos-modal-dialog {
    width: 100%;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 25px 60px rgba(15, 23, 42, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.9);
    overflow: hidden;
    padding: 20px 24px;
    animation: sheetSlideDown 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}
.modal-dialog-password {
    max-width: 390px;
}
.modal-dialog-edit {
    max-width: 420px;
}

@keyframes sheetSlideDown {
    from { opacity: 0; transform: translateY(-12px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.macos-modal-header {
    position: relative;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 13px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
}
.modal-header-squircle {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    flex-shrink: 0;
    margin-right: 6px;
}
.modal-header-squircle.bg-apple-amber {
    background: linear-gradient(135deg, #ff9f0a 0%, #f57c00 100%) !important;
    box-shadow: 0 3px 8px rgba(245, 124, 0, 0.3) !important;
}
.modal-header-squircle.bg-apple-blue {
    background: linear-gradient(135deg, #0077ed 0%, #0062cc 100%) !important;
    box-shadow: 0 3px 8px rgba(0, 119, 237, 0.3) !important;
}
.modal-header-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.015em;
}
.macos-modal-header .modal-circle-close-btn {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
}
.modal-circle-close-btn {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #f1f5f9;
    border: none;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    cursor: pointer;
    transition: all 0.12s ease;
}
.modal-circle-close-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
}

/* Modal Form Fields (Nhỏ gọn, chuẩn macOS) */
.modal-form-group {
    margin-bottom: 11px;
}
.modal-field-label {
    display: block;
    font-size: 0.74rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 4px;
}
.modal-input-wrap {
    position: relative;
    width: 100%;
}
.modal-field-input {
    width: 100%;
    height: 36px;
    padding: 6px 12px;
    border-radius: 9px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    font-size: 0.8rem;
    color: #0f172a;
    outline: none;
    transition: all 0.15s ease;
}
.modal-field-input:focus {
    background: #ffffff;
    border-color: #0071e3;
    box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.12);
}
.modal-field-input.input-readonly {
    background: #f8fafc;
    color: #475569;
    font-weight: 500;
    cursor: not-allowed;
    border-color: #e2e8f0;
}
.modal-field-input::placeholder {
    color: #94a3b8;
    font-family: inherit;
    font-size: 0.78rem;
}
.modal-input-eye-btn {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #94a3b8;
    font-size: 0.86rem;
    cursor: pointer;
    padding: 3px;
}
.modal-input-eye-btn:hover {
    color: #475569;
}
.modal-field-error {
    font-size: 0.72rem;
    color: #ef4444;
    margin-top: 3px;
}

/* Apple 4-segment Strength Meter */
.apple-meter-track {
    height: 3.5px;
    background: transparent;
}
.meter-bar {
    height: 3.5px;
    border-radius: 2px;
    background: #e2e8f0;
    transition: background 0.2s ease;
}
.meter-bar.active-red    { background: #ef4444; }
.meter-bar.active-orange { background: #f97316; }
.meter-bar.active-yellow { background: #eab308; }
.meter-bar.active-green  { background: #10b981; }

/* Modal Footer Actions (Push Button Chuẩn macOS) */
.modal-footer-actions {
    border-top: 1px solid #f1f5f9;
    margin-top: 8px;
    padding-top: 12px;
}
.btn-modal-cancel {
    background: #f1f5f9 !important;
    color: #334155 !important;
    border: none !important;
    border-radius: 8px !important;
    padding: 6px 16px !important;
    height: 32px !important;
    font-size: 0.79rem !important;
    font-weight: 600 !important;
    transition: all 0.15s ease !important;
}
.btn-modal-cancel:hover {
    background: #e2e8f0 !important;
    color: #0f172a !important;
}

.btn-modal-submit-blue {
    background: #0071e3 !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: 8px !important;
    padding: 6px 20px !important;
    height: 32px !important;
    font-size: 0.79rem !important;
    font-weight: 600 !important;
    box-shadow: 0 2px 6px rgba(0, 113, 227, 0.25) !important;
    transition: all 0.15s ease !important;
}
.btn-modal-submit-blue:hover {
    background: #0077ed !important;
    filter: brightness(1.05);
}
.btn-modal-submit-blue:active {
    transform: scale(0.98);
}

/* Alert Banners & Floating Toast */
.macos-alert-banner {
    padding: 12px 18px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; }
.alert-danger  { background: #fef2f2; border: 1px solid #fecaca; }

.macos-floating-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 99999;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: #18181b;
    color: #ffffff;
    border-radius: 999px;
    font-size: 0.82rem;
    font-weight: 500;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    animation: toastPop 0.22s ease;
}
@keyframes toastPop {
    from { transform: translateY(10px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>

<script>
/* ========================================================
   MACOS INTERACTION & REALTIME SYNC CONTROLLERS
   ======================================================== */
function openModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) {
        el.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) {
        el.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function closeModalOnBackdrop(event, modalId) {
    if (event.target && event.target.id === modalId) {
        closeModal(modalId);
    }
}

// Close on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal('passwordModal');
        closeModal('editProfileModal');
    }
});

// Fullscreen toggle for green traffic light
function toggleFullScreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(() => {});
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen().catch(() => {});
        }
    }
}

// Minimize card subtle bounce animation for yellow traffic light
function minimizeCardAnimation() {
    const card = document.querySelector('.macos-identity-card');
    if (card) {
        card.style.transition = 'transform 0.15s ease';
        card.style.transform = 'scale(0.96)';
        setTimeout(() => {
            card.style.transform = 'scale(1)';
        }, 180);
    }
}

// Realtime live reflection while typing name in Edit Modal
function syncLiveName(val) {
    const trimmed = val.trim();
    if (trimmed.length > 0) {
        const leftName = document.getElementById('cardFullname');
        const rightName = document.getElementById('detailFullname');
        const monogram = document.getElementById('cardAvatarMonogram');

        if (leftName) leftName.textContent = trimmed;
        if (rightName) rightName.textContent = trimmed;
        if (monogram) monogram.textContent = trimmed.charAt(0).toUpperCase();
    }
}

// Apple 4-segment Password Strength Calculation
function calculateAppleStrength(pwd) {
    const len = pwd.length >= 8;
    const hasCase = /[a-z]/.test(pwd) && /[A-Z]/.test(pwd);
    const hasNum = /\d/.test(pwd);
    const hasSpec = /[^a-zA-Z0-9]/.test(pwd);

    const score = [len, hasCase, hasNum, hasSpec].filter(Boolean).length;
    const b1 = document.getElementById('mBar1');
    const b2 = document.getElementById('mBar2');
    const b3 = document.getElementById('mBar3');
    const b4 = document.getElementById('mBar4');
    const txt = document.getElementById('appleStrengthText');

    [b1, b2, b3, b4].forEach(b => {
        if (b) b.className = 'meter-bar flex-grow-1';
    });

    if (pwd.length === 0) {
        if (txt) { txt.textContent = 'Chưa nhập'; txt.className = 'apple-meter-status text-muted'; }
        return;
    }

    if (score === 1) {
        if (b1) b1.className = 'meter-bar flex-grow-1 active-red';
        if (txt) { txt.textContent = 'Rất yếu'; txt.className = 'apple-meter-status text-danger'; }
    } else if (score === 2) {
        if (b1) b1.className = 'meter-bar flex-grow-1 active-orange';
        if (b2) b2.className = 'meter-bar flex-grow-1 active-orange';
        if (txt) { txt.textContent = 'Trung bình'; txt.className = 'apple-meter-status text-warning'; }
    } else if (score === 3) {
        if (b1) b1.className = 'meter-bar flex-grow-1 active-yellow';
        if (b2) b2.className = 'meter-bar flex-grow-1 active-yellow';
        if (b3) b3.className = 'meter-bar flex-grow-1 active-yellow';
        if (txt) { txt.textContent = 'Khá mạnh'; txt.className = 'apple-meter-status text-primary'; }
    } else if (score === 4) {
        if (b1) b1.className = 'meter-bar flex-grow-1 active-green';
        if (b2) b2.className = 'meter-bar flex-grow-1 active-green';
        if (b3) b3.className = 'meter-bar flex-grow-1 active-green';
        if (b4) b4.className = 'meter-bar flex-grow-1 active-green';
        if (txt) { txt.textContent = 'Rất an toàn (Apple Enclave)'; txt.className = 'apple-meter-status text-success'; }
    }
}

// Eye Toggle for Password Fields
function togglePopupPwd(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
}

// Copy to Clipboard with macOS Toast Notification
function copyToClipboard(text, message) {
    navigator.clipboard.writeText(text).then(() => {
        showToast(message || 'Đã sao chép vào bộ nhớ tạm!');
    }).catch(() => {
        const temp = document.createElement('textarea');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        showToast(message || 'Đã sao chép vào bộ nhớ tạm!');
    });
}

function showToast(message) {
    const toast = document.getElementById('macosToast');
    const msg = document.getElementById('toastMsg');
    if (toast && msg) {
        msg.textContent = message;
        toast.style.display = 'flex';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 2200);
    }
}
</script>
@endsection