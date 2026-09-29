@php
    $currentStep = max(1, min(3, (int) ($currentStep ?? 1)));
    $stepLinks = $stepLinks ?? [];
    $steps = [
        1 => 'Chọn phòng',
        2 => 'Thông tin',
        3 => 'Thanh toán',
    ];
@endphp

<nav class="royal-steps-nav" aria-label="Tiến trình đặt phòng">
    <ol class="booking-steps royal-steps" data-booking-steps data-current-step="{{ $currentStep }}" role="list">
        @foreach($steps as $step => $label)
            @php
                $isComplete = $step < $currentStep;
                $isCurrent = $step === $currentStep;
                $isUpcoming = $step > $currentStep;
                $state = $isComplete ? 'is-complete' : ($isCurrent ? 'is-current' : 'is-upcoming');
                $href = $stepLinks[$step] ?? null;
                $statusTooltip = $isComplete 
                    ? "Đã xong · Bấm để xem lại bước {$step}: {$label}" 
                    : ($isCurrent ? "Đang thực hiện · Bước {$step}: {$label}" : "Chưa mở · Bước {$step}: {$label}");
            @endphp
            <li class="royal-step {{ $state }}" 
                @if($isCurrent) aria-current="step" @endif
                data-step="{{ $step }}"
                data-step-title="{{ $label }}">
                
                @if($href && $isComplete)
                    <a class="booking-step__content royal-step__content royal-step__content--interactive" 
                       href="{{ $href }}" 
                       aria-label="Quay lại bước {{ $step }}: {{ $label }}"
                       title="{{ $statusTooltip }}">
                @else
                    <div class="booking-step__content royal-step__content" 
                         @if($isComplete) data-step-nav="{{ $step }}" role="button" tabindex="0" @endif
                         aria-label="Bước {{ $step }}: {{ $label }} - {{ $statusTooltip }}"
                         title="{{ $statusTooltip }}">
                @endif

                        <span class="booking-step__indicator royal-step__indicator" aria-hidden="true">
                            <span class="royal-step__indicator-badge">
                                @if($isComplete)
                                    <i class="bi bi-check2"></i>
                                @else
                                    <span class="royal-step__num">{{ $step }}</span>
                                @endif
                            </span>
                        </span>

                        <span class="booking-step__copy royal-step__copy">
                            <strong class="royal-step__title">{{ $label }}</strong>
                        </span>

                @if($href && $isComplete)
                    </a>
                @else
                    </div>
                @endif

                @if(!$loop->last)
                    <span class="royal-step__connector {{ $isComplete ? 'is-complete' : '' }}" aria-hidden="true">
                        <span class="royal-step__connector-rail"></span>
                        <span class="royal-step__connector-fill" style="width: {{ $isComplete ? '100%' : '0%' }}"></span>
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
