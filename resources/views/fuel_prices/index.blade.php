@extends('layouts.main')

@section('title')
    {{ __('Fuel Prices') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6">
                <h4 class="mb-0">@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 d-flex justify-content-end">
                @can('fuel-price-create')
                    <a class="btn btn-primary" href="{{ route('fuel-prices.create') }}">
                        <i class="fas fa-plus me-1"></i>{{ __('Add Fuel Prices') }}
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
                    <table class="table table-borderless table-striped" id="table_list" data-toggle="table"
                           data-url="{{ route('fuel-prices.table') }}" data-side-pagination="server"
                           data-pagination="true" data-page-list="[5, 10, 20, 50, 100]" data-search="true"
                           data-show-columns="true" data-show-refresh="true" data-sort-name="created_at"
                           data-sort-order="desc" data-escape="true" data-mobile-responsive="true">
                        <thead class="thead-dark">
                        <tr>
                            <th data-field="id" data-sortable="true">{{ __('ID') }}</th>
                            <th data-field="petrol_super" data-sortable="true">{{ __('Petrol (Super)') }}</th>
                            <th data-field="high_octane" data-sortable="true">{{ __('High Octane') }}</th>
                            <th data-field="high_speed_diesel" data-sortable="true">{{ __('High Speed Diesel') }}</th>
                            <th data-field="lpg" data-sortable="true">{{ __('LPG') }}</th>
                            <th data-field="kerosene_oil" data-sortable="true">{{ __('Kerosene Oil') }}</th>
                            <th data-field="created_at" data-sortable="true">{{ __('Created At') }}</th>
                            @canany(['fuel-price-update', 'fuel-price-delete'])
                                <th data-field="operate" data-escape="false">{{ __('Actions') }}</th>
                            @endcanany
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
