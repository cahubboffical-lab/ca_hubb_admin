<script>
    @include('shared._request-table-toolbar-script')
    @include('shared._request-admin-notes-dialog-script')

    let activeVehicleRequestStatus = 'pending';

    function vehicleServiceRequestQueryParams(params) {
        params.status = activeVehicleRequestStatus;
        return params;
    }

    $(function () {
        const table = $('#vehicle-service-request-table');
        const detailModal = new bootstrap.Modal(document.getElementById('vehicle-request-detail-modal'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        placeRequestFiltersInTableToolbar('#vehicle-service-request-table', '#vehicle-request-status-tabs');

        $('#vehicle-request-status-tabs').on('click', '[data-status]', function () {
            activeVehicleRequestStatus = this.dataset.status;
            $('#vehicle-request-status-tabs .nav-link').removeClass('active');
            $(this).addClass('active');
            table.bootstrapTable('refresh', {pageNumber: 1});
        });

        $(document).on('click', '.view-request', async function () {
            setDetailLoading(true);
            detailModal.show();

            try {
                const response = await fetch(this.dataset.url, {headers: {'Accept': 'application/json'}});
                const payload = await response.json();
                if (!response.ok || payload.error) {
                    throw new Error(payload.message || @json(__('Unable to load request details.')));
                }
                renderRequest(payload.data);
            } catch (error) {
                detailModal.hide();
                showRequestError(error.message);
            }
        });

        $(document).on('click', '.update-request-status', async function () {
            const button = this;
            const statusLabel = button.dataset.label;
            const isCompleted = button.dataset.status === 'completed';
            const isCanceled = button.dataset.status === 'canceled';
            const result = await confirmRequestStatusWithNotes({
                title: isCanceled ? @json(__('Cancel this request?')) : `${@json(__('Mark as'))} ${statusLabel}?`,
                text: isCanceled
                    ? @json(__('This request will move to Canceled and cannot be resumed.'))
                    : (isCompleted
                        ? @json(__('This request will be marked as completed. This action cannot be reversed.'))
                        : @json(__('This confirms that the customer has been contacted and moves the request to In Process.'))),
                icon: isCanceled ? 'warning' : (isCompleted ? 'success' : 'question'),
                confirmButtonText: isCanceled ? @json(__('Yes, cancel request')) : `${@json(__('Yes, mark as'))} ${statusLabel}`,
                cancelButtonText: @json(__('Keep Request')),
                confirmButtonColor: isCanceled ? '#dc3545' : (isCompleted ? '#198754' : '#435ebe'),
            });

            if (!result.isConfirmed) {
                return;
            }

            button.disabled = true;
            try {
                const response = await fetch(button.dataset.url, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({status: button.dataset.status, admin_notes: result.value.trim()}),
                });
                const payload = await response.json();
                if (!response.ok || payload.error) {
                    throw new Error(payload.message || @json(__('Unable to update request status.')));
                }

                await Swal.fire({
                    title: @json(__('Updated')),
                    text: payload.message,
                    icon: 'success',
                    confirmButtonText: @json(__('OK')),
                });
                table.bootstrapTable('refresh');
            } catch (error) {
                showRequestError(error.message);
            } finally {
                button.disabled = false;
            }
        });

        function renderRequest(serviceRequest) {
            const car = serviceRequest.car_model
                ? `${serviceRequest.car_model.brand_name} ${serviceRequest.car_model.name}`
                : '-';
            const user = serviceRequest.user
                ? `${serviceRequest.user.name || '-'}${serviceRequest.user.email ? ` (${serviceRequest.user.email})` : ''}`
                : @json(__('Guest request'));
            const fields = [
                [@json(__('Request ID')), serviceRequest.id],
                [@json(__('Status')), serviceRequest.status_label],
                [@json(__('Customer')), serviceRequest.full_name],
                [@json(__('Phone')), serviceRequest.phone_number],
                [@json(__('Account')), user],
                [@json(__('Filer')), serviceRequest.is_filer ? @json(__('Yes')) : @json(__('No'))],
                [@json(__('Car')), car],
                [@json(__('Model Year')), serviceRequest.model_year],
                [@json(__('Variant')), serviceRequest.car_variant],
                [@json(__('Registration Place')), serviceRequest.registration_place],
                [@json(__('Admin Notes')), serviceRequest.admin_notes || '-'],
                [@json(__('Submitted At')), serviceRequest.created_at],
                [@json(__('Last Updated')), serviceRequest.updated_at],
                [@json(__('Completed At')), serviceRequest.completed_at || '-'],
            ];

            document.getElementById('vehicle-request-detail-content')
                .replaceChildren(...fields.map(([label, value]) => createDetailField(label, value)));
            setDetailLoading(false);
        }

        function createDetailField(label, value) {
            const wrapper = document.createElement('div');
            wrapper.className = 'col-md-6';
            const card = document.createElement('div');
            card.className = 'border rounded p-3 h-100';
            const title = document.createElement('div');
            title.className = 'text-muted small mb-1';
            title.textContent = label;
            const text = document.createElement('div');
            text.className = 'fw-semibold text-break';
            text.textContent = value ?? '-';
            card.append(title, text);
            wrapper.append(card);
            return wrapper;
        }

        function setDetailLoading(loading) {
            document.getElementById('vehicle-request-detail-loading').classList.toggle('d-none', !loading);
            document.getElementById('vehicle-request-detail-content').classList.toggle('d-none', loading);
        }

        function showRequestError(message) {
            Swal.fire({
                title: @json(__('Error')),
                text: message,
                icon: 'error',
                confirmButtonText: @json(__('OK')),
            });
        }
    });
</script>
