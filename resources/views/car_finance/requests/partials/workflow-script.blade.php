<script>
    @include('shared._request-table-toolbar-script')

    let activeFinanceRequestStatus = 'pending';

    function financeRequestQueryParams(params) {
        params.status = activeFinanceRequestStatus;
        return params;
    }

    $(function () {
        const table = $('#car-finance-request-table');
        const modal = new bootstrap.Modal(document.getElementById('finance-request-detail-modal'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        placeRequestFiltersInTableToolbar('#car-finance-request-table', '#finance-status-tabs');

        $('#finance-status-tabs').on('click', '[data-status]', function () {
            activeFinanceRequestStatus = this.dataset.status;
            $('#finance-status-tabs .nav-link').removeClass('active');
            $(this).addClass('active');
            table.bootstrapTable('refresh', {pageNumber: 1});
        });

        $(document).on('click', '.view-finance-request', async function () {
            setLoading(true);
            modal.show();
            try {
                const response = await fetch(this.dataset.url, {headers: {'Accept': 'application/json'}});
                const payload = await response.json();
                if (!response.ok || payload.error) throw new Error(payload.message || @json(__('Unable to load request details.')));
                renderDetails(payload.data);
            } catch (error) {
                modal.hide();
                showError(error.message);
            }
        });

        $(document).on('click', '.update-finance-status', async function () {
            const button = this;
            const isCanceled = button.dataset.status === 'canceled';
            const isCompleted = button.dataset.status === 'completed';
            const result = await Swal.fire({
                title: isCanceled ? @json(__('Cancel this request?')) : `${@json(__('Mark as'))} ${button.dataset.label}?`,
                text: isCanceled
                    ? @json(__('The request will move to Canceled and cannot be resumed.'))
                    : (isCompleted
                        ? @json(__('The request will move to Completed and cannot be changed again.'))
                        : @json(__('This confirms that the customer has been contacted and moves the request to In Process.'))),
                icon: isCanceled ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: isCanceled ? @json(__('Yes, cancel request')) : `${@json(__('Yes, mark as'))} ${button.dataset.label}`,
                cancelButtonText: @json(__('Keep Request')),
                confirmButtonColor: isCanceled ? '#dc3545' : (isCompleted ? '#198754' : '#435ebe'),
                reverseButtons: true,
                focusCancel: true,
            });
            if (!result.isConfirmed) return;

            button.disabled = true;
            try {
                const response = await fetch(button.dataset.url, {
                    method: 'PATCH',
                    headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                    body: JSON.stringify({status: button.dataset.status}),
                });
                const payload = await response.json();
                if (!response.ok || payload.error) throw new Error(payload.message || @json(__('Unable to update request status.')));
                await Swal.fire({title: @json(__('Updated')), text: payload.message, icon: 'success'});
                table.bootstrapTable('refresh');
            } catch (error) {
                showError(error.message);
            } finally {
                button.disabled = false;
            }
        });

        function renderDetails(request) {
            const user = request.user ? `${request.user.name || '-'}${request.user.email ? ` (${request.user.email})` : ''}` : '-';
            const bank = request.bank ? `${request.bank.name} (${request.bank.code})` : '-';
            const fields = [
                [@json(__('Request ID')), request.id], [@json(__('Status')), request.status_label],
                [@json(__('Customer')), user], [@json(__('Phone')), request.user?.phone || '-'],
                [@json(__('Bank')), bank], [@json(__('City')), request.city || '-'], [@json(__('Car')), request.car || '-'],
                [@json(__('Finance Type')), formatValue(request.finance_type)], [@json(__('Model Year')), request.model_year || '-'],
                [@json(__('Variant')), request.car_variant || '-'], [@json(__('Used Car Price')), money(request.used_car_price)],
                [@json(__('Vehicle Price')), money(request.vehicle_price)], [@json(__('Price Source')), formatValue(request.price_source)],
                [@json(__('Tenure')), `${request.tenure_years} ${@json(__('years'))}`],
                [@json(__('Down Payment')), `${request.down_payment_percent}%`], [@json(__('Finance Rate')), `${request.finance_rate}%`],
                [@json(__('Insurance Rate')), `${request.insurance_rate}%`], [@json(__('Processing Fee')), money(request.processing_fee)],
                [@json(__('Down Payment Amount')), money(request.down_payment_amount)], [@json(__('Bank Loan')), money(request.bank_loan)],
                [@json(__('First Year Insurance')), money(request.first_year_insurance)], [@json(__('Monthly Installment')), money(request.monthly_installment)],
                [@json(__('Total Initial Deposit')), money(request.total_initial_deposit)], [@json(__('Admin Notes')), request.admin_notes || '-'],
                [@json(__('Submitted At')), request.created_at], [@json(__('Completed At')), request.completed_at || '-'],
                [@json(__('Canceled At')), request.canceled_at || '-'],
            ];
            document.getElementById('finance-request-detail-content').replaceChildren(...fields.map(([label, value]) => detailField(label, value)));
            setLoading(false);
        }

        function detailField(label, value) {
            const wrapper = document.createElement('div'); wrapper.className = 'col-md-6 col-xl-4';
            const card = document.createElement('div'); card.className = 'border rounded p-3 h-100';
            const title = document.createElement('div'); title.className = 'text-muted small mb-1'; title.textContent = label;
            const text = document.createElement('div'); text.className = 'fw-semibold text-break'; text.textContent = value ?? '-';
            card.append(title, text); wrapper.append(card); return wrapper;
        }

        function money(value) { return value === null || value === undefined ? '-' : `PKR ${Number(value).toLocaleString()}`; }
        function formatValue(value) { return value ? value.replaceAll('_', ' ').replace(/\b\w/g, letter => letter.toUpperCase()) : '-'; }
        function setLoading(loading) {
            document.getElementById('finance-request-detail-loading').classList.toggle('d-none', !loading);
            document.getElementById('finance-request-detail-content').classList.toggle('d-none', loading);
        }
        function showError(message) { Swal.fire({title: @json(__('Error')), text: message, icon: 'error'}); }
    });
</script>
