<div class="sidebar-foot">
    <div class="sidebar-account-actions">
        <form action="{{ route('internalauth.logout') }}" method="POST">@csrf<button class="sidebar-logout" type="submit" title="Đăng xuất"><i class="bi bi-box-arrow-right"></i><span>Đăng xuất</span></button></form>
    </div>
</div>
