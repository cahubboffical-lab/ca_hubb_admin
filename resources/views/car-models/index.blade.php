@extends('layouts.main')

@section('title', __('Car Models'))

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6"><h4 class="mb-0">@yield('title')</h4></div>
            <div class="col-12 col-md-8">
                @include('car-models.partials.csv-actions')
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-body">
                <table class="table table-borderless table-striped" aria-describedby="car-models-table"
                    id="table_list" data-toggle="table" data-url="{{ route('car-models.show', 0) }}"
                    data-click-to-select="true" data-side-pagination="server" data-pagination="true"
                    data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true"
                    data-show-refresh="true" data-trim-on-search="false" data-responsive="true"
                    data-sort-name="id" data-sort-order="desc" data-query-params="queryParams"
                    data-pagination-successively-size="3" data-table="car_models" data-mobile-responsive="true"
                    data-show-export="true"
                    data-export-options='{"fileName":"car-models-list","ignoreColumn":["operate"]}'
                    data-export-types="['json', 'xml', 'csv', 'txt', 'sql', 'doc', 'excel']">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col" data-field="id" data-sortable="true">{{ __('ID') }}</th>
                            <th scope="col" data-field="name" data-sortable="true">{{ __('Model Name') }}</th>
                            <th scope="col" data-field="brand_name" data-sortable="true">{{ __('Brand Name') }}</th>
                            <th scope="col" data-field="price" data-sortable="true">{{ __('Price') }}</th>
                            <th scope="col" data-field="created_at" data-sortable="true">{{ __('Created At') }}</th>
                            <th scope="col" data-field="created_by_name" data-sortable="true">{{ __('Created By') }}</th>
                            <th scope="col" data-field="updated_at" data-sortable="true">{{ __('Updated At') }}</th>
                            <th scope="col" data-field="updated_by_name" data-sortable="true">{{ __('Updated By') }}</th>
                            @canany(['car-model-update', 'car-model-delete'])
                                <th scope="col" data-field="operate" data-escape="false">{{ __('Action') }}</th>
                            @endcanany
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </section>
@endsection
