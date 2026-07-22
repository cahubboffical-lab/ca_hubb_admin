<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label class="form-label" for="name">{{ __('Name') }} <span class="text-muted">({{ __('Optional') }})</span></label>
            <input type="text" class="form-control" id="name" name="name" maxlength="255"
                   value="{{ old('name', $startupAd->name) }}" placeholder="{{ __('Ad name') }}">
            @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label class="form-label" for="url">{{ __('URL') }} <span class="text-muted">({{ __('Optional') }})</span></label>
            <input type="url" class="form-control" id="url" name="url" maxlength="2048"
                   value="{{ old('url', $startupAd->url) }}" placeholder="https://example.com">
            @error('url') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group mb-3 mandatory">
            <label class="form-label" for="image">{{ __('Image') }}</label>
            <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/webp"
                   @if (! $startupAd->exists) required @endif>
            <small class="text-muted">{{ __('JPG, PNG or WebP. Maximum size 7 MB.') }}</small>
            @error('image') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
        @if ($startupAd->exists && $startupAd->image)
            <div class="mb-3">
                <img src="{{ $startupAd->image }}" alt="{{ $startupAd->name ?? __('Ad image') }}"
                     class="img-thumbnail" style="max-width: 260px; max-height: 180px; object-fit: contain;">
            </div>
        @endif
    </div>
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label class="form-label d-block">{{ __('Type') }}</label>
            <div class="form-control bg-light">{{ $startupAd->type ?? __('Startup / General') }}</div>
            <small class="text-muted">{{ __('The type is controlled by the selected admin section.') }}</small>
        </div>
        <div class="form-group mb-3">
            <input type="hidden" name="is_active" value="0">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"
                       @checked((bool) old('is_active', $startupAd->is_active ?? true))>
                <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
            </div>
            @error('is_active') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
