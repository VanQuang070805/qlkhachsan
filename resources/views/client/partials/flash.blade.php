@php
    $flashError = session('error');
    $flashSuccess = session('success');
    $flashWarning = session('warning');
    $flashInfo = session('info') ?? session('status');
    $flashMsg = $flashError ?? $flashSuccess ?? $flashWarning ?? $flashInfo;
    $flashType = $flashError ? 'error' : ($flashWarning ? 'warning' : ($flashInfo ? 'info' : 'success'));
@endphp

@if($flashMsg)
    <div class="flash-wrap" id="globalFlashWrap" aria-live="polite" role="status" style="position: fixed; z-index: 99999; top: 92px; left: 50%; transform: translateX(-50%); width: min(540px, calc(100% - 32px)); pointer-events: none; transition: opacity 0.45s cubic-bezier(0.16, 1, 0.3, 1), transform 0.45s cubic-bezier(0.16, 1, 0.3, 1);">
        <div class="flash-message flash-message--{{ $flashType }}" style="pointer-events: auto; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 14px 18px 16px 18px; border-radius: 18px; background: {{ $flashType === 'error' ? 'rgba(255, 245, 245, 0.97)' : ($flashType === 'warning' ? 'rgba(255, 251, 235, 0.97)' : ($flashType === 'info' ? 'rgba(240, 249, 255, 0.97)' : 'rgba(240, 253, 244, 0.97)')) }}; border: 1.5px solid {{ $flashType === 'error' ? 'rgba(239, 68, 68, 0.28)' : ($flashType === 'warning' ? 'rgba(245, 158, 11, 0.28)' : ($flashType === 'info' ? 'rgba(14, 165, 233, 0.28)' : 'rgba(34, 197, 94, 0.28)')) }}; color: {{ $flashType === 'error' ? '#991b1b' : ($flashType === 'warning' ? '#92400e' : ($flashType === 'info' ? '#075985' : '#14532d')) }}; box-shadow: 0 16px 36px rgba(0,0,0,0.12), 0 3px 10px rgba(0,0,0,0.05); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);">
            <div style="display: flex; align-items: center; gap: 12px; font-size: 13.5px; font-weight: 600; line-height: 1.45;">
                <div style="width: 32px; height: 32px; border-radius: 50%; background: {{ $flashType === 'error' ? '#fee2e2' : ($flashType === 'warning' ? '#fef3c7' : ($flashType === 'info' ? '#e0f2fe' : '#dcfce7')) }}; color: {{ $flashType === 'error' ? '#dc2626' : ($flashType === 'warning' ? '#d97706' : ($flashType === 'info' ? '#0284c7' : '#16a34a')) }}; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 16px;">
                    <i class="bi {{ $flashType === 'error' ? 'bi-exclamation-triangle-fill' : ($flashType === 'warning' ? 'bi-exclamation-circle-fill' : ($flashType === 'info' ? 'bi-info-circle-fill' : 'bi-check-circle-fill')) }}"></i>
                </div>
                <span>{{ $flashMsg }}</span>
            </div>
            <button type="button" onclick="dismissFlashNotice(this.closest('.flash-wrap'))" aria-label="Đóng thông báo" style="background: none; border: none; padding: 4px 6px; color: currentColor; cursor: pointer; display: flex; align-items: center; justify-content: center; border-radius: 8px; opacity: 0.65; transition: opacity 0.2s ease;">
                <i class="bi bi-x-lg" style="font-size: 13px;"></i>
            </button>
            {{-- 5s animated countdown progress indicator line at the bottom --}}
            <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: rgba(0,0,0,0.06);">
                <div id="flashProgressBar" style="height: 100%; width: 100%; background: {{ $flashType === 'error' ? '#ef4444' : ($flashType === 'warning' ? '#f59e0b' : ($flashType === 'info' ? '#0ea5e9' : '#22c55e')) }}; transform-origin: left; animation: flashCountdown 5s linear forwards;"></div>
            </div>
        </div>
    </div>
    <style>
        @keyframes flashCountdown {
            from { transform: scaleX(1); }
            to { transform: scaleX(0); }
        }
    </style>
    <script>
        function dismissFlashNotice(el) {
            if (!el || el.dataset.dismissed) return;
            el.dataset.dismissed = 'true';
            el.style.opacity = '0';
            el.style.transform = 'translate(-50%, -16px)';
            setTimeout(function() {
                if (el && el.parentNode) el.remove();
            }, 450);
        }
        document.addEventListener('DOMContentLoaded', function() {
            var flashEl = document.getElementById('globalFlashWrap');
            if (flashEl) {
                setTimeout(function() {
                    dismissFlashNotice(flashEl);
                }, 5000);
            }
        });
    </script>
@endif

