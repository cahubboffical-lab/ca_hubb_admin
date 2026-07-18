@php
    $priceFields = [
        'petrol_super' => __('Petrol (Super)'),
        'high_octane' => __('High Octane'),
        'high_speed_diesel' => __('High Speed Diesel'),
        'lpg' => __('LPG'),
        'kerosene_oil' => __('Kerosene Oil'),
    ];
@endphp

<div class="row">
    @foreach ($priceFields as $field => $label)
        <div class="col-md-6 col-xl-4">
            <div class="form-group mb-3 mandatory">
                <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                <div class="input-group">
                    <span class="input-group-text">PKR</span>
                    <input type="number" name="{{ $field }}" id="{{ $field }}" class="form-control"
                           value="{{ old($field, $fuelPrice->getAttribute($field)) }}" min="0" max="99999999.99"
                           step="0.01" inputmode="decimal" placeholder="0.00" required>
                </div>
                @error($field)
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endforeach
</div>

<p class="text-muted mb-0">{{ __('The creation date is saved automatically. The latest created entry is returned by the mobile API.') }}</p>
