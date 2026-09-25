@php
    $currentStep = max(1, min(3, (int) ($currentStep ?? 1)));
    $stepLinks = $stepLinks ?? [];
    $steps = [1 => 'Chọn phòng', 2 => 'Thông tin', 3 => 'Thanh toán'];
@endphp

<ol class="booking-steps" aria-label="Tiến trình đặt phòng" data-booking-steps>
    @foreach($steps as $step => $label)
        @php
            $state = $step < $currentStep ? 'is-complete' : ($step === $currentStep ? 'is-current' : 'is-upcoming');
            $href = $stepLinks[$step] ?? null;
        @endphp
        <li class="{{ $state }}" @if($step === $currentStep) aria-current="step" @endif>
            @if($href && $step < $currentStep)
                <a class="booking-step__content" href="{{ $href }}" aria-label="Quay lại bước {{ $step }}: {{ $label }}">
            @else
                <span class="booking-step__content">
            @endif
                <span class="booking-step__indicator" aria-hidden="true">
                    @if($step < $currentStep)<i class="bi bi-check2"></i>@else{{ str_pad($step, 2, '0', STR_PAD_LEFT) }}@endif
                </span>
                <span class="booking-step__copy"><small>Bước {{ str_pad($step, 2, '0', STR_PAD_LEFT) }}</small><strong>{{ $label }}</strong></span>
            @if($href && $step < $currentStep)</a>@else</span>@endif
        </li>
    @endforeach
</ol>
