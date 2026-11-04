@foreach (['error', 'success', 'warning', 'info', 'status'] as $flashType)
    @if (session($flashType))
        <div data-animated-flash="{{ $flashType === 'status' ? 'info' : $flashType }}" role="{{ $flashType === 'error' ? 'alert' : 'status' }}">{{ session($flashType) }}</div>
    @endif
@endforeach
