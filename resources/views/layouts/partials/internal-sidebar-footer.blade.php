@php
    $logoutRoute = ($isAdmin ?? (session('staff_user.role') === 'admin' || session('user.role') === 'admin'))
        ? route('admin.logout')
        : route('staff.logout');
@endphp
<div class="sidebar-foot">
    <div class="sidebar-account-actions">
        <form action="{{ $logoutRoute }}" method="POST">@csrf<button class="sidebar-logout" type="submit" title="Đăng xuất"><i class="bi bi-box-arrow-right"></i><span>Đăng xuất</span></button></form>
    </div>
</div>
