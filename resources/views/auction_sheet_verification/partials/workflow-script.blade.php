<script>
    @include('shared._request-table-toolbar-script')

    let activeAuctionVerificationStatus = 'pending';

    function auctionVerificationQueryParams(params) {
        params.status = activeAuctionVerificationStatus;
        return params;
    }

    $(function () {
        const table = $('#auction-verification-table');
        const detailModal = new bootstrap.Modal(document.getElementById('auction-request-detail-modal'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        placeRequestFiltersInTableToolbar('#auction-verification-table', '#auction-status-tabs');

        $('#auction-status-tabs').on('click', '[data-status]', function () {
            activeAuctionVerificationStatus = this.dataset.status;
            $('#auction-status-tabs .nav-link').removeClass('active');
            $(this).addClass('active');
            table.bootstrapTable('refresh', {pageNumber: 1});
        });

        $('#auction-price-form').on('submit', async function (event) {
            event.preventDefault();
            const amount = document.getElementById('auction-price-amount').value;
            const result = await Swal.fire({
                title: @json(__('Update verification price?')),
                text: `${@json(__('The mobile application will receive the new price:'))} PKR ${amount}`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: @json(__('Yes, save price')),
                cancelButtonText: @json(__('Cancel')),
                confirmButtonColor: '#435ebe',
                reverseButtons: true,
            });

            if (!result.isConfirmed) return;

            try {
                const payload = await sendJson(@json(route('auction-sheet-verification.price.update')), 'PUT', {price_amount: amount});
                document.getElementById('auction-price-amount').value = payload.data.price_amount;
                await showSuccess(payload.message);
            } catch (error) {
                showError(error.message);
            }
        });

        $(document).on('click', '.view-auction-request', async function () {
            setDetailLoading(true);
            detailModal.show();

            try {
                const response = await fetch(this.dataset.url, {headers: {'Accept': 'application/json'}});
                const payload = await response.json();
                if (!response.ok || payload.error) throw new Error(payload.message || @json(__('Unable to load request details.')));
                renderRequest(payload.data);
            } catch (error) {
                detailModal.hide();
                showError(error.message);
            }
        });

        $(document).on('click', '.complete-auction-request', async function () {
            const button = this;
            const result = await Swal.fire({
                title: @json(__('Mark as Completed?')),
                text: @json(__('This request will move to Completed and cannot be moved back to Pending.')),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: @json(__('Yes, mark as Completed')),
                cancelButtonText: @json(__('Cancel')),
                confirmButtonColor: '#198754',
                reverseButtons: true,
            });

            if (!result.isConfirmed) return;

            button.disabled = true;
            try {
                const payload = await sendJson(button.dataset.url, 'PATCH', {});
                await showSuccess(payload.message);
                table.bootstrapTable('refresh');
            } catch (error) {
                showError(error.message);
            } finally {
                button.disabled = false;
            }
        });

        $(document).on('click', '.cancel-auction-request', async function () {
            const button = this;
            const result = await Swal.fire({
                title: @json(__('Cancel this request?')),
                text: @json(__('This request will move to Canceled and cannot be resumed.')),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: @json(__('Yes, cancel request')),
                cancelButtonText: @json(__('Keep Request')),
                confirmButtonColor: '#dc3545',
                reverseButtons: true,
                focusCancel: true,
            });

            if (!result.isConfirmed) return;

            button.disabled = true;
            try {
                const payload = await sendJson(button.dataset.url, 'PATCH', {});
                await showSuccess(payload.message);
                table.bootstrapTable('refresh');
            } catch (error) {
                showError(error.message);
            } finally {
                button.disabled = false;
            }
        });

        async function sendJson(url, method, body) {
            const response = await fetch(url, {
                method,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(body),
            });
            const payload = await response.json();
            if (!response.ok || payload.error) {
                const validationMessage = payload.errors ? Object.values(payload.errors).flat()[0] : null;
                throw new Error(validationMessage || payload.message || @json(__('The action could not be completed.')));
            }
            return payload;
        }

        function renderRequest(request) {
            const account = request.user
                ? `${request.user.name || '-'}${request.user.email ? ` (${request.user.email})` : ''}`
                : @json(__('Guest request'));
            const fields = [
                [@json(__('Request ID')), request.id],
                [@json(__('Status')), request.status_label],
                [@json(__('Chassis Number')), request.chassis_number],
                [@json(__('Phone')), request.phone_number],
                [@json(__('Account')), account],
                [@json(__('Price')), `${request.currency_code || 'PKR'} ${request.price_amount || '-'}`],
                [@json(__('Notification')), formatValue(request.notification_status)],
                [@json(__('Report URL')), request.report_url || '-'],
                [@json(__('Admin Notes')), request.admin_notes || '-'],
                [@json(__('Notified At')), request.notified_at || '-'],
                [@json(__('Submitted At')), request.created_at],
                [@json(__('Completed At')), request.completed_at || '-'],
            ];

            const content = document.getElementById('auction-request-detail-content');
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
            document.getElementById('auction-request-detail-loading').classList.toggle('d-none', !loading);
            document.getElementById('auction-request-detail-content').classList.toggle('d-none', loading);
        }

        function showSuccess(message) {
            return Swal.fire({title: @json(__('Updated')), text: message, icon: 'success', confirmButtonText: @json(__('OK'))});
        }

        function showError(message) {
            Swal.fire({title: @json(__('Error')), text: message, icon: 'error', confirmButtonText: @json(__('OK'))});
        }
    });
</script>
