@php($controls = $controls ?? ['close', 'minimize', 'zoom'])
@php($dismiss = $dismiss ?? null)
@php($class = $class ?? '')
<div class="window-controls {{ $class }}" role="group" aria-label="Điều khiển {{ $label ?? 'cửa sổ' }}">
    @if(in_array('close', $controls, true))<button class="window-control window-control--close" type="button" @if($dismiss) data-bs-dismiss="{{ $dismiss }}" @else data-window-action="close" @endif aria-label="Đóng {{ $label ?? 'cửa sổ' }}"></button>@endif
    @if(in_array('minimize', $controls, true))<button class="window-control window-control--minimize" type="button" data-window-action="minimize" aria-label="Thu gọn {{ $label ?? 'cửa sổ' }}" aria-expanded="true"></button>@endif
    @if(in_array('zoom', $controls, true))<button class="window-control window-control--zoom" type="button" data-window-action="zoom" aria-label="Toàn màn hình {{ $label ?? 'cửa sổ' }}"></button>@endif
</div>
