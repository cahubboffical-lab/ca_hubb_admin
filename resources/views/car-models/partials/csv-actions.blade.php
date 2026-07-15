<div class="d-flex flex-wrap justify-content-start justify-content-lg-end align-items-center gap-2">
    @can('car-model-list')
        <a class="btn btn-outline-primary" href="{{ route('car-models.export') }}">
            <i class="fas fa-file-export me-1"></i>{{ __('Export CSV') }}
        </a>
    @endcan

    @canany(['car-model-create', 'car-model-update'])
        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal"
            data-bs-target="#carModelImportModal">
            <i class="fas fa-file-import me-1"></i>{{ __('Import CSV') }}
        </button>
    @endcanany

    @can('car-model-create')
        <a class="btn btn-primary" href="{{ route('car-models.create') }}">+ {{ __('Add Car Model') }}</a>
    @endcan
</div>
