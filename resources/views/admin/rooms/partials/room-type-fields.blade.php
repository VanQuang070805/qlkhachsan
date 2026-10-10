@php
    $selectedAmenities = old('amenities_present')
        ? old('amenities', [])
        : ($type?->amenities->pluck('id')->all() ?? []);
@endphp

<div class="room-type-form">
    <div class="room-form-grid room-type-form__grid">
        <div class="room-field">
            <label for="{{ $fieldPrefix }}-name">Tên hạng <span class="text-danger">*</span></label>
            <input id="{{ $fieldPrefix }}-name" name="type_name" value="{{ old('type_name', $type?->type_name) }}" maxlength="100" required @if($errors->getBag($errorBag)->has('type_name')) aria-invalid="true" aria-describedby="{{ $fieldPrefix }}-name-error" @endif>
            @error('type_name', $errorBag)<span class="room-field-error" id="{{ $fieldPrefix }}-name-error" role="alert">{{ $message }}</span>@enderror
        </div>
        <div class="room-field">
            <label for="{{ $fieldPrefix }}-price">Giá nền / đêm <span class="text-danger">*</span></label>
            <input id="{{ $fieldPrefix }}-price" name="price" type="number" min="1" step="1" value="{{ old('price', $type?->price) }}" required @if($errors->getBag($errorBag)->has('price')) aria-invalid="true" aria-describedby="{{ $fieldPrefix }}-price-error" @endif>
            @error('price', $errorBag)<span class="room-field-error" id="{{ $fieldPrefix }}-price-error" role="alert">{{ $message }}</span>@enderror
        </div>
        <div class="room-field">
            <label for="{{ $fieldPrefix }}-adults">Người lớn tối đa <span class="text-danger">*</span></label>
            <input id="{{ $fieldPrefix }}-adults" name="max_adults" type="number" min="1" max="100" value="{{ old('max_adults', $type?->max_adults ?? 2) }}" required @if($errors->getBag($errorBag)->has('max_adults')) aria-invalid="true" aria-describedby="{{ $fieldPrefix }}-adults-error" @endif>
            @error('max_adults', $errorBag)<span class="room-field-error" id="{{ $fieldPrefix }}-adults-error" role="alert">{{ $message }}</span>@enderror
        </div>
        <div class="room-field">
            <label for="{{ $fieldPrefix }}-children">Trẻ em tối đa <span class="text-danger">*</span></label>
            <input id="{{ $fieldPrefix }}-children" name="max_children" type="number" min="0" max="100" value="{{ old('max_children', $type?->max_children ?? 0) }}" required @if($errors->getBag($errorBag)->has('max_children')) aria-invalid="true" aria-describedby="{{ $fieldPrefix }}-children-error" @endif>
            @error('max_children', $errorBag)<span class="room-field-error" id="{{ $fieldPrefix }}-children-error" role="alert">{{ $message }}</span>@enderror
        </div>
        <div class="room-field">
            <label for="{{ $fieldPrefix }}-guests">Tổng sức chứa <span class="text-danger">*</span></label>
            <input id="{{ $fieldPrefix }}-guests" name="max_guests" type="number" min="1" max="100" value="{{ old('max_guests', $type?->max_guests ?? 2) }}" required @if($errors->getBag($errorBag)->has('max_guests')) aria-invalid="true" aria-describedby="{{ $fieldPrefix }}-guests-error" @endif>
            @error('max_guests', $errorBag)<span class="room-field-error" id="{{ $fieldPrefix }}-guests-error" role="alert">{{ $message }}</span>@enderror
        </div>
        <div class="room-field">
            @php($currentImage = $type?->image ? $type->image_url : '')
            <label for="{{ $fieldPrefix }}-image">Ảnh đại diện</label>
            <div class="room-image-upload room-type-image-upload" data-room-image-upload>
                <div class="room-image-upload__preview">
                    <img @if($currentImage) src="{{ $currentImage }}" @endif data-original-src="{{ $currentImage }}" data-room-image-preview alt="Ảnh xem trước hạng phòng" @if(!$currentImage) hidden @endif>
                    <span data-room-image-empty @if($currentImage) hidden @endif>Chưa chọn ảnh</span>
                </div>
                <input id="{{ $fieldPrefix }}-image" name="image_file" type="file" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp" data-room-image-input aria-describedby="{{ $fieldPrefix }}-image-hint @if($errors->getBag($errorBag)->has('image_file')) {{ $fieldPrefix }}-image-error @endif" @if($errors->getBag($errorBag)->has('image_file')) aria-invalid="true" @endif>
                <small class="room-hint" id="{{ $fieldPrefix }}-image-hint">JPG, PNG hoặc WebP · Tối đa 5 MB</small>
                @error('image_file', $errorBag)<span class="room-field-error" id="{{ $fieldPrefix }}-image-error" role="alert">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="room-field" style="grid-column:1/-1">
            <label for="{{ $fieldPrefix }}-description">Mô tả</label>
            <textarea id="{{ $fieldPrefix }}-description" name="description" maxlength="2000" @if($errors->getBag($errorBag)->has('description')) aria-invalid="true" aria-describedby="{{ $fieldPrefix }}-description-error" @endif>{{ old('description', $type?->description) }}</textarea>
            @error('description', $errorBag)<span class="room-field-error" id="{{ $fieldPrefix }}-description-error" role="alert">{{ $message }}</span>@enderror
        </div>
    </div>

    <section aria-labelledby="{{ $fieldPrefix }}-amenities-title">
        <h3 id="{{ $fieldPrefix }}-amenities-title" class="room-hint fw-semibold mb-2">Tiện nghi áp dụng</h3>
        <input type="hidden" name="amenities_present" value="1">
        <div class="room-amenities">
            @forelse($amenities as $amenity)
                <label class="room-amenity"><input type="checkbox" name="amenities[]" value="{{ $amenity->id }}" @checked(in_array($amenity->id, $selectedAmenities))> {{ $amenity->amenity_name }}</label>
            @empty
                <span class="room-hint">Chưa có tiện nghi lưu trong hệ thống.</span>
            @endforelse
        </div>
        @if($errors->getBag($errorBag)->has('amenities.*'))
            <span class="room-field-error" role="alert">{{ $errors->getBag($errorBag)->first('amenities.*') }}</span>
        @endif
    </section>

    <div class="room-field">
        <label for="{{ $fieldPrefix }}-new-amenities">Thêm tiện nghi (mỗi dòng hoặc dấu phẩy)</label>
        <textarea id="{{ $fieldPrefix }}-new-amenities" name="new_amenities" maxlength="2000" placeholder="Ví dụ: Ban công, Ấm đun nước" @if($errors->getBag($errorBag)->has('new_amenities')) aria-invalid="true" aria-describedby="{{ $fieldPrefix }}-new-amenities-error" @endif>{{ old('new_amenities') }}</textarea>
        @error('new_amenities', $errorBag)<span class="room-field-error" id="{{ $fieldPrefix }}-new-amenities-error" role="alert">{{ $message }}</span>@enderror
    </div>
</div>
