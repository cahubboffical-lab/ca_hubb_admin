@php
    $featureValues = old('features', $servicePackage->features ?? []);
    if (!is_array($featureValues)) {
        $featureValues = [$featureValues];
    }
    $featureValues = array_values(array_filter($featureValues, static fn ($value) => $value !== null && $value !== ''));
    if (empty($featureValues)) {
        $featureValues = [''];
    }
@endphp

<input type="hidden" name="type" value="{{ $servicePackage->type ?? $config['type'] }}">

<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="name">{{ __('Name') }} <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $servicePackage->name) }}" required>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="price">{{ __('Price') }} <span class="text-danger">*</span></label>
            <input type="number" min="0" step="0.01" name="price" id="price" class="form-control" value="{{ old('price', $servicePackage->price) }}" required>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="icon">{{ __('Icon') }} {{ empty($servicePackage->id) ? '*' : '' }}</label>
            <input type="file" name="icon" id="icon" class="form-control" {{ empty($servicePackage->id) ? 'required' : '' }} accept=".jpg,.jpeg,.png,.webp">
            @if(!empty($servicePackage->icon))
                <div class="mt-2">
                    <img src="{{ $servicePackage->icon }}" alt="{{ $servicePackage->name }}" class="rounded border" style="width: 72px; height: 72px; object-fit: cover;">
                </div>
            @endif
        </div>
    </div>

    <div class="col-12">
        <div class="form-group mb-3">
            <label class="d-block">{{ __('Features') }} <span class="text-danger">*</span></label>
            <div id="features-wrapper">
                @foreach($featureValues as $feature)
                    <div class="input-group mb-2 feature-row">
                        <input type="text" name="features[]" class="form-control" placeholder="{{ __('Enter feature') }}" value="{{ $feature }}">
                        <button class="btn btn-outline-danger remove-feature" type="button">{{ __('Remove') }}</button>
                    </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="add-feature">{{ __('Add Feature') }}</button>
        </div>
    </div>

    <div class="col-12">
        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
    </div>
</div>
