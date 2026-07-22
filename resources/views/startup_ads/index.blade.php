@extends('layouts.main')

@section('title')
    {{ __($config['label']) }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6"><h4 class="mb-0">@yield('title')</h4></div>
            <div class="col-12 col-md-6 d-flex justify-content-end">
                @can($config['permission_prefix'].'-create')
                    <a class="btn btn-primary" href="{{ route('startup-ads.create', compact('section')) }}">
                        <i class="fas fa-plus me-1"></i>{{ __('Add Ad') }}
                    </a>
                @endcan
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless table-striped" id="startup-ads-table" data-toggle="table"
                           data-url="{{ route('startup-ads.table', compact('section')) }}"
                           data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100]"
                           data-search="true" data-show-columns="true" data-show-refresh="true"
                           data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true">
                        <thead class="thead-dark"><tr>
                            <th data-field="id" data-sortable="true">{{ __('ID') }}</th>
                            <th data-field="name" data-sortable="true">{{ __('Name') }}</th>
                            <th data-field="image" data-formatter="imageFormatter">{{ __('Image') }}</th>
                            <th data-field="url_link" data-escape="false">{{ __('URL') }}</th>
                            <th data-field="type">{{ __('Type') }}</th>
                            <th data-field="is_active"
                                @can($config['permission_prefix'].'-update') data-formatter="startupAdStatusFormatter" @else data-formatter="startupAdStatusBadgeFormatter" @endcan>
                                {{ __('Active') }}
                            </th>
                            <th data-field="created_at" data-sortable="true">{{ __('Created At') }}</th>
                            <th data-field="created_by">{{ __('Created By') }}</th>
                            <th data-field="updated_at" data-sortable="true">{{ __('Updated At') }}</th>
                            <th data-field="updated_by">{{ __('Updated By') }}</th>
                            @canany([$config['permission_prefix'].'-update', $config['permission_prefix'].'-delete'])
                                <th data-field="operate" data-escape="false">{{ __('Actions') }}</th>
                            @endcanany
                        </tr></thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
    <script>
        function startupAdStatusFormatter(value, row) {
            return `<div class="form-check form-switch d-flex justify-content-center">
                <input class="form-check-input startup-ad-status" type="checkbox" data-url="${row.toggle_url}" ${value ? 'checked' : ''}>
            </div>`;
        }

        function startupAdStatusBadgeFormatter(value) {
            return value
                ? `<span class="badge bg-success">${@json(__('Active'))}</span>`
                : `<span class="badge bg-secondary">${@json(__('Inactive'))}</span>`;
        }

        $(document).on('change', '.startup-ad-status', async function () {
            const toggle = this;
            const isActive = toggle.checked;
            toggle.disabled = true;

            try {
                const response = await fetch(toggle.dataset.url, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({is_active: isActive}),
                });
                const payload = await response.json();
                if (!response.ok || payload.error) {
                    throw new Error(payload.message || @json(__('Unable to update ad status.')));
                }
                showSuccessToast(payload.message);
            } catch (error) {
                toggle.checked = !isActive;
                showErrorToast(error.message);
            } finally {
                toggle.disabled = false;
            }
        });
    </script>
@endsection
