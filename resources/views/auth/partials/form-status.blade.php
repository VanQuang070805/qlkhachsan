@if(session('success'))
    <p class="auth-form-status" role="status"><i class="bi bi-check-circle" aria-hidden="true"></i><span>{{ session('success') }}</span></p>
@endif
@if(session('error'))
    <p class="auth-form-status is-error" role="alert"><i class="bi bi-exclamation-circle" aria-hidden="true"></i><span>{{ session('error') }}</span></p>
@endif
