@php
    $portal = $portal ?? 'customer';
    $signedIn = $portal === 'customer'
        ? (bool) session('customer_user_id')
        : ($portal === 'admin' ? (bool) session('admin_user_id') : (bool) session('staff_user_id'));
    $logoutUrl = $portal === 'customer' ? route('logout') : ($portal === 'admin' ? route('admin.logout') : route('staff.logout'));
    $loginUrl = $portal === 'customer' ? route('login') : ($portal === 'admin' ? route('admin.login') : route('staff.login'));
@endphp
@if($signedIn)
<script>
(() => {
    const key = 'rosaliza:{{ $portal }}:tab-session';
    try {
        if (sessionStorage.getItem(key) === 'active') return;
        // A new tab has no tab-scoped proof of the previous sign-in.
        document.documentElement.style.visibility = 'hidden';
        fetch(@json($logoutUrl), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'text/html' },
            keepalive: true,
        }).finally(() => window.location.replace(@json($loginUrl)));
    } catch (_) {
        // Storage-disabled browsers continue using the normal server session.
        document.documentElement.style.visibility = '';
    }
})();
</script>
@endif
