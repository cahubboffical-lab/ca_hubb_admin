<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>{{ __('City') }}</label>
            <select name="city_id" class="form-select" data-parsley-required="true">
                <option value="">{{ __('Select City') }}</option>
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}"
                        {{ (string) old('city_id', $news?->city_id) === (string) $city->id ? 'selected' : '' }}>
                        {{ $city->translated_name ?? $city->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label>{{ __('Cover Image') }}</label>
            @if ($news?->cover_image)
                <div class="mb-2">
                    <img src="{{ $news->cover_image }}" alt="{{ __('Cover Image') }}" class="img-fluid rounded"
                        style="max-height: 120px;">
                </div>
            @endif
            <input type="file" name="cover_image" class="form-control" accept=".jpg,.jpeg,.png,.webp"
                @if (!$news) data-parsley-required="true" @endif>
        </div>
    </div>

    <div class="col-md-12">
        <div class="form-group">
            <label>{{ __('English HTML') }}</label>
            <textarea name="english_html" id="english_html_editor" class="tinymce_editor form-control" rows="8">{{ old('english_html', $news?->english_html) }}</textarea>
        </div>
    </div>

    <div class="col-md-12">
        <div class="form-group">
            <label>{{ __('Urdu HTML') }}</label>
            <textarea name="urdu_html" id="urdu_html_editor" class="tinymce_editor form-control" rows="8">{{ old('urdu_html', $news?->urdu_html) }}</textarea>
        </div>
    </div>
</div>
