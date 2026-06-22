@php($category = $category ?? null)
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{translate('name')}} *</label>
        <input type="text" name="name" class="form-control" required value="{{ old('name', $category->name ?? '') }}" maxlength="100">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{translate('sort_order')}}</label>
        <input type="number" name="sort_order" min="0" class="form-control" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{translate('image')}}</label>
        <input type="file" name="image" class="form-control" accept="image/*">
        @if($category && $category->image)
            <img src="{{ asset('storage/'.$category->image) }}" class="mt-2" style="max-width:90px;border-radius:6px;border:1px solid #dee2e6">
        @endif
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check form-switch">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="catActive" @checked(old('is_active', $category->is_active ?? true))>
            <label class="form-check-label" for="catActive">{{translate('active')}}</label>
        </div>
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-primary">{{ translate('save') }}</button>
    </div>
</div>
