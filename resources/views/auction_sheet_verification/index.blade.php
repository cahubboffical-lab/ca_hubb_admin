@extends('layouts.main')

@section('title')
    {{ __('Auction Sheet Verification') }}
@endsection

@section('css')
    <style>
        @include('shared._request-table-toolbar-styles')

        .auction-price-card {
            border: 1px solid #e8ebf1;
            border-radius: 12px;
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
        }

        .auction-price-input {
            max-width: 230px;
        }

        #auction-verification-table td:last-child,
        #auction-verification-table th:last-child {
            min-width: 485px;
            text-align: center;
        }

        .auction-verification-actions {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            white-space: nowrap;
        }

        .auction-verification-actions .btn {
            border-radius: 7px;
            font-weight: 600;
        }
    </style>
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">{{ __('Auction Sheet Verification') }}</h3>
        </div>

        <div class="card auction-price-card mb-4">
            <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <div>
                    <h5 class="mb-1">{{ __('Verification Price') }}</h5>
                    <p class="text-muted mb-0">{{ __('This single price is returned to the mobile application and saved with each new request.') }}</p>
                </div>
                <form id="auction-price-form" class="d-flex align-items-center gap-2">
                    <div class="input-group auction-price-input">
                        <span class="input-group-text">PKR</span>
                        <input type="number" class="form-control" id="auction-price-amount" name="price_amount"
                               min="0" step="0.01" value="{{ $price->price_amount }}" required
                               @cannot('auction-sheet-verification-request-update') disabled @endcannot>
                    </div>
                    @can('auction-sheet-verification-request-update')
                        <button type="submit" class="btn btn-primary text-nowrap">
                            <i class="fas fa-save me-1"></i>{{ __('Save Price') }}
                        </button>
                    @endcan
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <ul class="nav nav-pills request-status-toolbar" id="auction-status-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" type="button" data-status="pending">{{ __('Pending') }}</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" type="button" data-status="completed">{{ __('Completed') }}</button>
                    </li>
                </ul>

                <div class="table-responsive">
                    <table id="auction-verification-table" class="table table-striped" data-toggle="table"
                           data-url="{{ route('auction-sheet-verification.table') }}"
                           data-query-params="auctionVerificationQueryParams"
                           data-pagination="true" data-search="true" data-side-pagination="server"
                           data-show-columns="true" data-show-refresh="true" data-sort-name="id"
                           data-sort-order="DESC">
                        <thead>
                        <tr>
                            <th data-field="id" data-sortable="true">{{ __('ID') }}</th>
                            <th data-field="chassis_number" data-sortable="true">{{ __('Chassis Number') }}</th>
                            <th data-field="phone_number" data-sortable="true">{{ __('Phone') }}</th>
                            <th data-field="price">{{ __('Price') }}</th>
                            <th data-field="notification_status">{{ __('Notification') }}</th>
                            <th data-field="created_at" data-sortable="true">{{ __('Created At') }}</th>
                            <th data-field="completed_at" data-sortable="true">{{ __('Completed At') }}</th>
                            <th data-field="operate">{{ __('Actions') }}</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('auction_sheet_verification.partials.detail-modal')
@endsection

@section('js')
    @include('auction_sheet_verification.partials.workflow-script')
@endsection
