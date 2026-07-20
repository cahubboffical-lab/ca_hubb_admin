<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3 mandatory">
            <label for="code">{{ __('Stable Code') }}</label>
            <input type="text" class="form-control" id="code" name="code" maxlength="50"
                   value="{{ old('code', $carFinanceBank->code) }}" placeholder="e.g. faysal" required>
            <small class="text-muted">{{ __('Lowercase letters, numbers, hyphens, and underscores only.') }}</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group mb-3 mandatory">
            <label for="name">{{ __('Bank / Plan Name') }}</label>
            <input type="text" class="form-control" id="name" name="name" maxlength="150"
                   value="{{ old('name', $carFinanceBank->name) }}" required>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group mb-3 mandatory">
            <label for="finance_rate">{{ __('Finance Rate (%)') }}</label>
            <input type="number" class="form-control" id="finance_rate" name="finance_rate" min="0" max="999.9999"
                   step="0.0001" value="{{ old('finance_rate', $carFinanceBank->finance_rate) }}" required>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group mb-3 mandatory">
            <label for="insurance_rate">{{ __('Insurance Rate (%)') }}</label>
            <input type="number" class="form-control" id="insurance_rate" name="insurance_rate" min="0" max="999.9999"
                   step="0.0001" value="{{ old('insurance_rate', $carFinanceBank->insurance_rate) }}" required>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group mb-3 mandatory">
            <label for="processing_fee">{{ __('Processing Fee (PKR)') }}</label>
            <input type="number" class="form-control" id="processing_fee" name="processing_fee" min="0" step="1"
                   value="{{ old('processing_fee', $carFinanceBank->processing_fee) }}" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="logo_url">{{ __('Logo URL') }}</label>
            <input type="url" class="form-control" id="logo_url" name="logo_url" maxlength="2048"
                   value="{{ old('logo_url', $carFinanceBank->logo_url) }}" placeholder="https://...">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group mb-3">
            <label for="accent_color">{{ __('Accent Color') }}</label>
            <input type="text" class="form-control" id="accent_color" name="accent_color" maxlength="7"
                   value="{{ old('accent_color', $carFinanceBank->accent_color) }}" placeholder="#1B6B9A">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group mb-3 mandatory">
            <label for="display_order">{{ __('Display Order') }}</label>
            <input type="number" class="form-control" id="display_order" name="display_order" min="0" step="1"
                   value="{{ old('display_order', $carFinanceBank->display_order ?? 0) }}" required>
        </div>
    </div>
    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch mb-3">
            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                   @checked(old('is_active', $carFinanceBank->exists ? $carFinanceBank->is_active : true))>
            <label class="form-check-label" for="is_active">{{ __('Active and visible in the mobile app') }}</label>
        </div>
    </div>
</div>
