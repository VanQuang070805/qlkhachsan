@extends('layouts.receiption')

@section('title', 'Cài đặt tài khoản')

@section('content')
<div class="settings-page">

    {{-- ===== HEADER ===== --}}
    <div class="settings-header">
        <h1 class="settings-title">Cài đặt tài khoản</h1>
        <p class="settings-sub">Quản lý thông tin cá nhân và bảo mật tài khoản của bạn</p>
    </div>

    <div class="settings-layout">

        {{-- ===== SIDEBAR ===== --}}
        <aside class="settings-sidebar">
            <div class="user-card">
                <div class="user-avatar">
                    {{ mb_strtoupper(mb_substr($user->fullname, 0, 1)) }}
                </div>
                <div class="user-meta">
                    <p class="user-name">{{ $user->fullname }}</p>
                    <p class="user-role">Lễ tân</p>
                </div>
            </div>

            <nav class="sidebar-nav">
                <button class="nav-item active" onclick="switchTab('profile', this)">
                    <i class="bi bi-person-lines-fill"></i>
                    <span>Thông tin cá nhân</span>
                </button>
                <button class="nav-item" onclick="switchTab('security', this)">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span>Bảo mật</span>
                </button>
            </nav>
        </aside>

        {{-- ===== NỘI DUNG CHÍNH ===== --}}
        <main class="settings-main">

            {{-- ========== TAB: THÔNG TIN CÁ NHÂN ========== --}}
            <div id="tab-profile" class="settings-section">

                <div class="section-head">
                    <div>
                        <h2 class="section-title">Thông tin cá nhân</h2>
                        <p class="section-desc">Tên, email và số điện thoại của bạn</p>
                    </div>
                    <div class="section-actions">
                        <button class="btn-secondary" id="btnEdit" onclick="startEdit()">
                            <i class="bi bi-pencil"></i> Chỉnh sửa
                        </button>
                        <div id="editActions" style="display:none; display:flex; gap:8px; display:none">
                            <button class="btn-ghost" onclick="cancelEdit()">Hủy</button>
                            <button class="btn-primary" onclick="document.getElementById('formInfo').submit()">
                                Lưu thay đổi
                            </button>
                        </div>
                    </div>
                </div>

                @if(session('success_info'))
                    <div class="alert-ok">
                        <i class="bi bi-check-circle-fill"></i> {{ session('success_info') }}
                    </div>
                @endif

                {{-- ---- CHẾ ĐỘ XEM ---- --}}
                <div id="viewMode" class="info-list">
                    <div class="info-item">
                        <span class="info-key">Tên đăng nhập</span>
                        <span class="info-val muted">{{ $user->username }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-key">Họ và tên</span>
                        <span class="info-val">{{ $user->fullname }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-key">Địa chỉ email</span>
                        <span class="info-val">{{ $user->email }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-key">Số điện thoại</span>
                        <span class="info-val">{{ $user->phone ?: '—' }}</span>
                    </div>
                    <div class="info-item" style="border-bottom:none">
                        <span class="info-key">Ngày tạo tài khoản</span>
                        <span class="info-val">{{ $user->created_at ? $user->created_at->format('d/m/Y') : '—' }}</span>
                    </div>
                </div>

                {{-- ---- CHẾ ĐỘ SỬA ---- --}}
                <form id="formInfo" method="POST"
                      action="{{ route('receptionist.profile.update-info') }}"
                      style="display:none">
                    @csrf
                    <div class="form-list">
                        <div class="form-row">
                            <label class="form-label">Tên đăng nhập</label>
                            <div class="form-field">
                                <input type="text" class="form-input locked"
                                       value="{{ $user->username }}" disabled>
                                <span class="field-hint">Tên đăng nhập không thể thay đổi</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <label class="form-label">Họ và tên <span class="req">*</span></label>
                            <div class="form-field">
                                <input type="text" name="fullname"
                                       class="form-input @error('fullname') is-err @enderror"
                                       value="{{ old('fullname', $user->fullname) }}">
                                @error('fullname')<span class="err-msg">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="form-row">
                            <label class="form-label">Địa chỉ email <span class="req">*</span></label>
                            <div class="form-field">
                                <input type="email" name="email"
                                       class="form-input @error('email') is-err @enderror"
                                       value="{{ old('email', $user->email) }}">
                                @error('email')<span class="err-msg">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="form-row" style="border-bottom:none">
                            <label class="form-label">Số điện thoại</label>
                            <div class="form-field">
                                <input type="text" name="phone"
                                       class="form-input @error('phone') is-err @enderror"
                                       value="{{ old('phone', $user->phone) }}">
                                @error('phone')<span class="err-msg">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            {{-- ========== TAB: BẢO MẬT ========== --}}
            <div id="tab-security" class="settings-section" style="display:none">

                <div class="section-head">
                    <div>
                        <h2 class="section-title">Bảo mật</h2>
                        <p class="section-desc">Cập nhật mật khẩu để bảo vệ tài khoản</p>
                    </div>
                </div>

                @if(session('success_password'))
                    <div class="alert-ok">
                        <i class="bi bi-check-circle-fill"></i> {{ session('success_password') }}
                    </div>
                @endif

                {{-- Trạng thái mật khẩu --}}
                <div id="pwdStatus">
                    <div class="info-list">
                        <div class="info-item" style="border-bottom:none">
                            <span class="info-key">Mật khẩu</span>
                            <div style="display:flex; align-items:center; gap:12px">
                                <span class="info-val">••••••••</span>
                                <button class="btn-link" onclick="showPwdForm()">Đổi mật khẩu →</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form đổi mật khẩu --}}
                <div id="pwdFormWrap" style="display:none">
                    <form id="formPwd" method="POST"
                          action="{{ route('receptionist.profile.update-password') }}">
                        @csrf
                        <div class="form-list">
                            <div class="form-row">
                                <label class="form-label">Mật khẩu hiện tại <span class="req">*</span></label>
                                <div class="form-field">
                                    <div class="pwd-wrap">
                                        <input type="password" name="current_password" id="p1"
                                               class="form-input @error('current_password') is-err @enderror"
                                               placeholder="Nhập mật khẩu hiện tại">
                                        <button type="button" class="eye-btn" data-t="p1"><i class="bi bi-eye"></i></button>
                                    </div>
                                    @error('current_password')<span class="err-msg">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="form-row">
                                <label class="form-label">Mật khẩu mới <span class="req">*</span></label>
                                <div class="form-field">
                                    <div class="pwd-wrap">
                                        <input type="password" name="password" id="p2"
                                               class="form-input @error('password') is-err @enderror"
                                               placeholder="Tối thiểu 6 ký tự">
                                        <button type="button" class="eye-btn" data-t="p2"><i class="bi bi-eye"></i></button>
                                    </div>
                                    @error('password')<span class="err-msg">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="form-row" style="border-bottom:none">
                                <label class="form-label">Xác nhận mật khẩu <span class="req">*</span></label>
                                <div class="form-field">
                                    <div class="pwd-wrap">
                                        <input type="password" name="password_confirmation" id="p3"
                                               class="form-input"
                                               placeholder="Nhập lại mật khẩu mới">
                                        <button type="button" class="eye-btn" data-t="p3"><i class="bi bi-eye"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-footer">
                            <button type="button" class="btn-ghost" onclick="hidePwdForm()">Hủy</button>
                            <button type="submit" class="btn-primary">Cập nhật mật khẩu</button>
                        </div>
                    </form>
                </div>

            </div>
        </main>
    </div>
</div>
@endsection

@push('styles')
<style>
/* ===== RESET & PAGE ===== */
*, *::before, *::after { box-sizing: border-box; }

.settings-page {
    max-width: 960px;
    margin: 0 auto;
    padding: 40px 28px 60px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* ===== PAGE HEADER ===== */
.settings-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 4px;
}
.settings-sub {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0 0 36px;
}

/* ===== LAYOUT ===== */
.settings-layout {
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 32px;
    align-items: start;
}
@media (max-width: 700px) {
    .settings-layout { grid-template-columns: 1fr; }
}

/* ===== SIDEBAR ===== */
.settings-sidebar {
    position: sticky;
    top: 24px;
}
.user-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 8px;
}
.user-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #f59e0b;
    color: #fff;
    font-size: 1.1rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.user-name {
    font-weight: 600;
    font-size: 0.88rem;
    color: #0f172a;
    margin: 0 0 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.user-role {
    font-size: 0.75rem;
    color: #94a3b8;
    margin: 0;
}

/* ===== NAV ===== */
.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: 8px;
    border: none;
    background: transparent;
    font-size: 0.875rem;
    font-weight: 500;
    color: #475569;
    cursor: pointer;
    text-align: left;
    width: 100%;
    transition: background .15s, color .15s;
}
.nav-item i { font-size: 1rem; flex-shrink: 0; }
.nav-item:hover { background: #f1f5f9; color: #0f172a; }
.nav-item.active { background: #f1f5f9; color: #0f172a; font-weight: 600; }

/* ===== MAIN CONTENT ===== */
.settings-main {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}
.settings-section { padding: 0; }

/* ===== SECTION HEAD ===== */
.section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px 28px 20px;
    border-bottom: 1px solid #f1f5f9;
}
.section-title {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 3px;
}
.section-desc {
    font-size: 0.8rem;
    color: #94a3b8;
    margin: 0;
}
.section-actions { display: flex; gap: 8px; align-items: center; }

/* ===== INFO LIST (view mode) ===== */
.info-list {}
.info-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 28px;
    border-bottom: 1px solid #f8fafc;
}
.info-key {
    font-size: 0.82rem;
    font-weight: 500;
    color: #64748b;
    min-width: 160px;
}
.info-val {
    font-size: 0.875rem;
    color: #0f172a;
    font-weight: 400;
}
.info-val.muted { color: #94a3b8; }

/* ===== FORM LIST (edit mode) ===== */
.form-list {}
.form-row {
    display: flex;
    align-items: flex-start;
    padding: 18px 28px;
    border-bottom: 1px solid #f8fafc;
    gap: 24px;
}
.form-label {
    font-size: 0.82rem;
    font-weight: 500;
    color: #64748b;
    min-width: 160px;
    padding-top: 9px;
    flex-shrink: 0;
}
.req { color: #ef4444; }
.form-field { flex: 1; }
.form-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    color: #0f172a;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
    outline: none;
}
.form-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,.12);
}
.form-input.locked {
    background: #f8fafc;
    color: #94a3b8;
    cursor: not-allowed;
}
.form-input.is-err { border-color: #ef4444; }
.field-hint {
    display: block;
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 5px;
}
.err-msg {
    display: block;
    font-size: 0.75rem;
    color: #ef4444;
    margin-top: 5px;
}

/* ===== PASSWORD WRAP ===== */
.pwd-wrap { position: relative; display: flex; }
.pwd-wrap .form-input { padding-right: 40px; }
.eye-btn {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 2px;
    font-size: 0.9rem;
    transition: color .15s;
}
.eye-btn:hover { color: #475569; }

/* ===== FORM FOOTER ===== */
.form-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 16px 28px;
    border-top: 1px solid #f1f5f9;
    background: #fafafa;
}

/* ===== BUTTONS ===== */
.btn-primary {
    padding: 8px 18px;
    background: #0f172a;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.825rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s, transform .1s;
}
.btn-primary:hover { background: #1e293b; }
.btn-primary:active { transform: scale(.98); }

.btn-secondary {
    padding: 7px 14px;
    background: #fff;
    color: #374151;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.825rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background .15s, border-color .15s;
}
.btn-secondary:hover { background: #f8fafc; border-color: #cbd5e1; }

.btn-ghost {
    padding: 7px 14px;
    background: transparent;
    color: #64748b;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.825rem;
    font-weight: 500;
    cursor: pointer;
    transition: background .15s;
}
.btn-ghost:hover { background: #f1f5f9; }

.btn-link {
    background: none;
    border: none;
    color: #3b82f6;
    font-size: 0.82rem;
    font-weight: 500;
    cursor: pointer;
    padding: 0;
    transition: color .15s;
}
.btn-link:hover { color: #2563eb; text-decoration: underline; }

/* ===== ALERT ===== */
.alert-ok {
    margin: 0 28px;
    padding: 10px 14px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 8px;
    color: #166534;
    font-size: 0.82rem;
    display: flex;
    align-items: center;
    gap: 7px;
    margin-top: 16px;
}

/* ===== ANIMATION ===== */
.fade-in {
    animation: fadein .2s ease;
}
@keyframes fadein {
    from { opacity: 0; transform: translateY(-4px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
@endpush

@push('scripts')
<script>
// ---- Chuyển tab sidebar ----
function switchTab(tab, el) {
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    el.classList.add('active');
    document.querySelectorAll('.settings-section').forEach(s => s.style.display = 'none');
    document.getElementById('tab-' + tab).style.display = 'block';
}

// ---- Toggle chỉnh sửa thông tin ----
function startEdit() {
    document.getElementById('viewMode').style.display  = 'none';
    document.getElementById('formInfo').style.display  = 'block';
    document.getElementById('btnEdit').style.display   = 'none';
    document.getElementById('editActions').style.display = 'flex';
    document.getElementById('formInfo').classList.add('fade-in');
}
function cancelEdit() {
    document.getElementById('viewMode').style.display  = 'block';
    document.getElementById('formInfo').style.display  = 'none';
    document.getElementById('btnEdit').style.display   = 'inline-flex';
    document.getElementById('editActions').style.display = 'none';
}

// ---- Toggle form đổi mật khẩu ----
function showPwdForm() {
    document.getElementById('pwdStatus').style.display  = 'none';
    document.getElementById('pwdFormWrap').style.display = 'block';
    document.getElementById('pwdFormWrap').classList.add('fade-in');
}
function hidePwdForm() {
    document.getElementById('pwdStatus').style.display  = 'block';
    document.getElementById('pwdFormWrap').style.display = 'none';
}

// ---- Show/hide password ----
document.querySelectorAll('.eye-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const inp  = document.getElementById(this.dataset.t);
        const icon = this.querySelector('i');
        if (inp.type === 'password') {
            inp.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            inp.type = 'password';
            icon.className = 'bi bi-eye';
        }
    });
});

// ---- Nếu có lỗi validation → mở đúng form ----
@if($errors->has('fullname') || $errors->has('email') || $errors->has('phone'))
    startEdit();
@endif
@if($errors->has('current_password') || $errors->has('password'))
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.querySelector('[onclick*="security"]').classList.add('active');
    document.getElementById('tab-profile').style.display  = 'none';
    document.getElementById('tab-security').style.display = 'block';
    showPwdForm();
@endif
</script>
@endpush