<div class="row">
    <div class="col-md-6">
        <div class="form-group mandatory">
            <label for="brand_name">{{ __('Brand Name') }}</label>
            <input type="text" name="brand_name" id="brand_name" class="form-control"
                value="{{ old('brand_name', $carModel?->brand_name) }}"
                placeholder="{{ __('e.g. Toyota') }}" maxlength="255" data-parsley-required="true" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group mandatory">
            <label for="name">{{ __('Model Name') }}</label>
            <input type="text" name="name" id="name" class="form-control"
                value="{{ old('name', $carModel?->name) }}"
                placeholder="{{ __('e.g. Corolla') }}" maxlength="255" data-parsley-required="true" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="price">{{ __('Price') }}</label>
            <input type="number" name="price" id="price" class="form-control"
                value="{{ old('price', $carModel?->price) }}" placeholder="{{ __('Optional') }}"
                min="0" step="1" data-parsley-type="integer" data-parsley-min="0">
        </div>
    </div>
</div>
