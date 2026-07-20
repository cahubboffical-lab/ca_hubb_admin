<div class="modal fade" id="finance-request-detail-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Car Finance Request Details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <div id="finance-request-detail-loading" class="text-center py-5"><div class="spinner-border text-primary"></div></div>
                <div id="finance-request-detail-content" class="row g-3 d-none"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button></div>
        </div>
    </div>
</div>
