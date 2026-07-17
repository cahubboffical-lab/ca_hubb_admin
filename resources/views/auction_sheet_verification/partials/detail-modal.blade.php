<div class="modal fade" id="auction-request-detail-modal" tabindex="-1" aria-labelledby="auction-request-detail-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="auction-request-detail-title">{{ __('Auction Sheet Verification Details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <div id="auction-request-detail-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">{{ __('Loading') }}</span></div>
                </div>
                <div id="auction-request-detail-content" class="row g-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>
