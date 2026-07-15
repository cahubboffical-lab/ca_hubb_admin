<div class="d-flex flex-wrap justify-content-end align-items-center gap-2">
    @can('car-model-list')
        <a class="btn btn-outline-primary" href="{{ route('car-models.export') }}">
            <i class="fas fa-file-export me-1"></i>{{ __('Export CSV') }}
        </a>
    @endcan

    @canany(['car-model-create', 'car-model-update'])
        <form action="{{ route('car-models.import') }}" method="POST" enctype="multipart/form-data"
            class="d-flex flex-wrap align-items-center gap-2">
            @csrf
            <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required
                aria-label="{{ __('Select Car Models CSV') }}">
            <button type="submit" class="btn btn-outline-success">
                <i class="fas fa-file-import me-1"></i>{{ __('Import CSV') }}
            </button>
        </form>
    @endcanany

    @can('car-model-create')
        <a class="btn btn-primary" href="{{ route('car-models.create') }}">+ {{ __('Add Car Model') }}</a>
    @endcan
</div>
