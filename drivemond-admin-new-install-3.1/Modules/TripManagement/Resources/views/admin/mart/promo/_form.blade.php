@php($promo = $promo ?? null)
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{translate('code')}} *</label>
        <input type="text" name="code" class="form-control text-uppercase" required value="{{ old('code', $promo->code ?? '') }}" maxlength="50">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{translate('discount_type')}} *</label>
        <select name="discount_type" class="form-control" required>
            <option value="fixed" @selected(old('discount_type', $promo->discount_type ?? '') === 'fixed')>{{translate('fixed')}}</option>
            <option value="percent" @selected(old('discount_type', $promo->discount_type ?? '') === 'percent')>{{translate('percent')}}</option>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{translate('discount_value')}} *</label>
        <input type="number" name="discount_value" step="0.01" min="0.01" class="form-control" required value="{{ old('discount_value', $promo->discount_value ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{translate('min_order_amount')}}</label>
        <input type="number" name="min_order_amount" step="0.01" min="0" class="form-control" value="{{ old('min_order_amount', $promo->min_order_amount ?? 0) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{translate('max_discount')}}</label>
        <input type="number" name="max_discount" step="0.01" min="0" class="form-control" value="{{ old('max_discount', $promo->max_discount ?? '') }}" placeholder="{{translate('optional')}}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{translate('expires_at')}}</label>
        <input type="date" name="expires_at" class="form-control" value="{{ old('expires_at', isset($promo->expires_at) ? date('Y-m-d', strtotime($promo->expires_at)) : '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{translate('usage_limit')}}</label>
        <input type="number" name="usage_limit" min="1" class="form-control" value="{{ old('usage_limit', $promo->usage_limit ?? '') }}" placeholder="{{translate('unlimited')}}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{translate('per_user_limit')}}</label>
        <input type="number" name="per_user_limit" min="1" class="form-control" value="{{ old('per_user_limit', $promo->per_user_limit ?? '') }}" placeholder="{{translate('unlimited')}}">
    </div>
    <div class="col-12">
        <div class="form-check form-switch">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="promoActive" @checked(old('is_active', $promo->is_active ?? true))>
            <label class="form-check-label" for="promoActive">{{translate('active')}}</label>
        </div>
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-primary">{{ translate('save') }}</button>
    </div>
</div>
