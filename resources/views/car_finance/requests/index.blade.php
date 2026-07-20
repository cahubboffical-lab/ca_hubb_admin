@extends('layouts.main')

@section('title') {{ __('Car Finance Requests') }} @endsection

@section('css')
    <style>
        @include('shared._request-table-toolbar-styles')
        #car-finance-request-table td:last-child, #car-finance-request-table th:last-child { min-width: 600px; text-align: center; }
        .car-finance-request-actions { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; white-space: nowrap; }
        .car-finance-request-actions .btn { border-radius: 7px; font-weight: 600; }
    </style>
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header"><h3 class="page-title">{{ __('Car Finance Requests') }}</h3></div>
        <div class="card"><div class="card-body">
            <ul class="nav nav-pills request-status-toolbar" id="finance-status-tabs">
                <li class="nav-item"><button class="nav-link active" type="button" data-status="pending">{{ __('Pending') }}</button></li>
                <li class="nav-item"><button class="nav-link" type="button" data-status="in_progress">{{ __('In Process') }}</button></li>
                <li class="nav-item"><button class="nav-link" type="button" data-status="canceled">{{ __('Canceled') }}</button></li>
                <li class="nav-item"><button class="nav-link" type="button" data-status="completed">{{ __('Completed') }}</button></li>
            </ul>
            <div class="table-responsive">
                <table id="car-finance-request-table" class="table table-striped" data-toggle="table"
                       data-url="{{ route('car-finance-requests.table') }}" data-query-params="financeRequestQueryParams"
                       data-pagination="true" data-search="true" data-side-pagination="server" data-show-columns="true"
                       data-show-refresh="true" data-sort-name="id" data-sort-order="desc">
                    <thead><tr>
                        <th data-field="id" data-sortable="true">{{ __('ID') }}</th>
                        <th data-field="customer">{{ __('Customer') }}</th>
                        <th data-field="phone">{{ __('Phone') }}</th>
                        <th data-field="bank">{{ __('Bank') }}</th>
                        <th data-field="city">{{ __('City') }}</th>
                        <th data-field="car">{{ __('Car') }}</th>
                        <th data-field="finance_type" data-sortable="true">{{ __('Type') }}</th>
                        <th data-field="vehicle_price" data-sortable="true">{{ __('Vehicle Price') }}</th>
                        <th data-field="tenure_years" data-sortable="true">{{ __('Tenure') }}</th>
                        <th data-field="down_payment_percent" data-sortable="true">{{ __('Down Payment') }}</th>
                        <th data-field="monthly_installment">{{ __('Monthly Installment') }}</th>
                        <th data-field="created_at" data-sortable="true">{{ __('Created At') }}</th>
                        <th data-field="operate">{{ __('Actions') }}</th>
                    </tr></thead>
                </table>
            </div>
        </div></div>
    </div>
    @include('car_finance.requests.partials.detail-modal')
@endsection

@section('js') @include('car_finance.requests.partials.workflow-script') @endsection
