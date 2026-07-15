@canany(['car-model-create', 'car-model-update'])
    <div class="modal fade" id="carModelImportModal" tabindex="-1" role="dialog"
        aria-labelledby="carModelImportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="{{ route('car-models.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="carModelImportModalLabel">{{ __('Import Car Models') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-3 mandatory">
                            <label for="car_model_csv_file" class="form-label">{{ __('CSV File') }}</label>
                            <input type="file" name="csv_file" id="car_model_csv_file" class="form-control"
                                accept=".csv,text/csv" required>
                        </div>
                        <div class="alert alert-light border mb-0">
                            <small>
                                {{ __('Required columns: name and brand_name. The optional price must be a whole number of zero or greater.') }}
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-file-import me-1"></i>{{ __('Import CSV') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcanany
