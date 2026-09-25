<div class="sidebar-foot">
    @if($isAdmin)
        <a class="sidebar-guest-link" href="{{ route('home') }}" title="Trang khách"><i class="bi bi-box-arrow-up-right"></i><span>Trang khách</span></a>
    @endif
    <div class="sidebar-account">
        <span class="internal-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(session('user.fullname','R'),0,1)) }}</span>
        <div class="sidebar-account__name"><strong>{{ session('user.fullname', $isAdmin ? 'Quản trị viên' : 'Nhân viên') }}</strong><small>{{ $isAdmin ? 'Quản trị viên' : 'Lễ tân' }}</small></div>
    </div>
    <div class="sidebar-account-actions">
        <form action="{{ route('internalauth.logout') }}" method="POST">@csrf<button class="sidebar-logout" type="submit" title="Đăng xuất"><i class="bi bi-box-arrow-right"></i><span>Đăng xuất</span></button></form>
    </div>
</div>
