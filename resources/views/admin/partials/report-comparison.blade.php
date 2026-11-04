@php($item = $comparison[$metric] ?? null)
<div class="ds-kpi-foot">
  @if(!$item)
    <span class="ds-trend-desc">Chọn đủ hai ngày để so sánh kỳ trước</span>
  @elseif($item['percent'] === null && $item['current'] > 0)
    <span class="ds-trend-desc">Mới phát sinh · kỳ trước: 0</span>
  @elseif($item['percent'] === null || abs($item['percent']) < 0.00001)
    <span class="ds-trend-desc">Không đổi so với kỳ trước</span>
  @else
    <span class="{{ $item['percent'] > 0 ? 'ds-trend-up' : 'ds-trend-down' }}">
      <i class="bi {{ $item['percent'] > 0 ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow' }}" aria-hidden="true"></i>
      {{ number_format(abs($item['percent']), 1, ',', '.') }}%
    </span>
    <span class="ds-trend-desc">{{ $item['percent'] > 0 ? 'Tăng' : 'Giảm' }} so với kỳ trước ({{ number_format($item['previous'], 0, ',', '.') }}{{ !empty($currency) ? ' đ' : '' }})</span>
  @endif
</div>
