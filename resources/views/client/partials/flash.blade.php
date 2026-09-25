@if(session('success') || session('error'))
    <div class="flash-wrap" aria-live="polite"><div class="flash-message {{ session('error') ? 'flash-message--error' : '' }}" role="status">{{ session('error') ?? session('success') }}</div></div>
@endif
