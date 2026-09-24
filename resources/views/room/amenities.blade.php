@extends('layouts.main')

@section('content')
<?php $pageTitle = 'Tiện Nghi Khách Sạn'; ?>

<h2 class="fw-bold mb-4">Tiện Nghi Theo Loại Phòng</h2>

<div class="row g-4">
    <?php foreach ($roomTypes as $type): ?>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-dark text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= htmlspecialchars($type['type_name']) ?></h5>
                    <span class="badge bg-warning text-dark">
                        <?= number_format($type['price'], 0, ',', '.') ?> VNĐ/đêm
                    </span>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3"><?= htmlspecialchars($type['description'] ?? '') ?></p>

                <h6 class="fw-semibold mb-2">
                    <i class="bi bi-star-fill text-warning me-1"></i>Tiện nghi bao gồm:
                </h6>
                <?php if (!empty($type['amenities'])): ?>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($type['amenities'] as $a): ?>
                    <span class="badge rounded-pill bg-light text-dark border py-2 px-3">
                        <i class="bi bi-check-lg text-success me-1"></i>
                        <?= htmlspecialchars($a['amenity_name']) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted small">Chưa có thông tin tiện nghi.</p>
                <?php endif; ?>

                <p class="mt-3 mb-0 text-muted small">
                    <i class="bi bi-people me-1"></i>Tối đa <?= $type['max_guests'] ?> khách
                </p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
@endsection
