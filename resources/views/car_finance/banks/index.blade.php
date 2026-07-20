@extends('layouts.main')

@section('title')
    {{ __('Car Finance Banks') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6"><h4 class="mb-0">@yield('title')</h4></div>
            <div class="col-12 col-md-6 d-flex justify-content-end">
                @can('car-finance-bank-create')
                    <a class="btn btn-primary" href="{{ route('car-finance-banks.create') }}"><i class="fas fa-plus me-1"></i>{{ __('Add Bank') }}</a>
                @endcan
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-body">
                <div class="alert alert-light border mb-3">
                    {{ __('Rate changes affect new applications only. Existing requests keep their original calculation snapshot.') }}
                </div>
                <div class="table-responsive">
                    <table class="table table-borderless table-striped" id="table_list" data-toggle="table"
                           data-url="{{ route('car-finance-banks.table') }}" data-side-pagination="server" data-pagination="true"
                           data-page-list="[5, 10, 20, 50, 100]" data-search="true" data-show-columns="true"
                           data-show-refresh="true" data-sort-name="display_order" data-sort-order="asc" data-escape="true">
                        <thead>
                        <tr>
                            <th data-field="id" data-sortable="true">{{ __('ID') }}</th>
                            <th data-field="code" data-sortable="true">{{ __('Code') }}</th>
                            <th data-field="name" data-sortable="true">{{ __('Bank / Plan') }}</th>
                            <th data-field="finance_rate" data-sortable="true">{{ __('Finance Rate') }}</th>
                            <th data-field="insurance_rate" data-sortable="true">{{ __('Insurance Rate') }}</th>
                            <th data-field="processing_fee" data-sortable="true">{{ __('Processing Fee') }}</th>
                            <th data-field="accent_color">{{ __('Color') }}</th>
                            <th data-field="is_active" data-sortable="true">{{ __('Status') }}</th>
                            <th data-field="display_order" data-sortable="true">{{ __('Order') }}</th>
                            <th data-field="created_at" data-sortable="true">{{ __('Created At') }}</th>
                            @canany(['car-finance-bank-update', 'car-finance-bank-delete'])
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
