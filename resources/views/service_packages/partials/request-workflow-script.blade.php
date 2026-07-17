<script>
    let activeServiceRequestStatus = 'pending';

    function serviceRequestQueryParams(params) {
        params.status = activeServiceRequestStatus;
        return params;
    }

    $(function () {
        const table = $('#service-request-table');
        const modalElement = document.getElementById('service-request-detail-modal');
        const detailModal = new bootstrap.Modal(modalElement);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        $('#request-status-tabs').on('click', '[data-status]', function () {
            activeServiceRequestStatus = this.dataset.status;
            $('#request-status-tabs .nav-link').removeClass('active');
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
                renderServiceRequest(payload.data);
            } catch (error) {
                detailModal.hide();
                showRequestError(error.message);
            }
        });

        $(document).on('click', '.update-request-status', async function () {
            const button = this;
            const statusLabel = button.dataset.label;
            const isCompleted = button.dataset.status === 'completed';
            const result = await Swal.fire({
                title: `${@json(__('Mark as'))} ${statusLabel}?`,
                text: isCompleted
                    ? @json(__('This request will be marked as completed. This action cannot be reversed.'))
                    : @json(__('This confirms that the customer has been contacted and moves the request to In Process.')),
                icon: isCompleted ? 'success' : 'question',
                showCancelButton: true,
                confirmButtonText: `${@json(__('Yes, mark as'))} ${statusLabel}`,
                cancelButtonText: @json(__('Cancel')),
                confirmButtonColor: isCompleted ? '#198754' : '#435ebe',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                focusCancel: true,
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
                    body: JSON.stringify({status: button.dataset.status}),
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

        function renderServiceRequest(serviceRequest) {
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
                [@json(__('Package')), serviceRequest.package ? serviceRequest.package.name : '-'],
                [@json(__('Package Price')), serviceRequest.package ? serviceRequest.package.price : '-'],
                [@json(__('City')), serviceRequest.city || '-'],
                [@json(__('Car')), car],
                [@json(__('Model Year')), serviceRequest.model_year],
                [@json(__('Variant')), serviceRequest.car_variant],
                [@json(__('Condition')), formatValue(serviceRequest.car_condition)],
                [@json(__('Registration Area')), serviceRequest.registration_area || '-'],
                [@json(__('Visit Area')), serviceRequest.visit_area],
                [@json(__('Visit Date')), serviceRequest.visit_date],
                [@json(__('Visit Time')), `${serviceRequest.visit_start_time} - ${serviceRequest.visit_end_time}`],
                [@json(__('Submitted At')), serviceRequest.created_at],
                [@json(__('Last Updated')), serviceRequest.updated_at],
            ];

            const content = document.getElementById('service-request-detail-content');
            content.replaceChildren(...fields.map(([label, value]) => createDetailField(label, value)));
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

        function formatValue(value) {
            return value ? value.replaceAll('_', ' ').replace(/\b\w/g, character => character.toUpperCase()) : '-';
        }

        function setDetailLoading(loading) {
            document.getElementById('service-request-detail-loading').classList.toggle('d-none', !loading);
            document.getElementById('service-request-detail-content').classList.toggle('d-none', loading);
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
