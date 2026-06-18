<script>
    (function () {
        const wrapper = document.getElementById('features-wrapper');
        const addButton = document.getElementById('add-feature');

        if (!wrapper || !addButton) {
            return;
        }

        const createRow = (value = '') => {
            const row = document.createElement('div');
            row.className = 'input-group mb-2 feature-row';
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'features[]';
            input.className = 'form-control';
            input.placeholder = "{{ __('Enter feature') }}";
            input.value = value;

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-outline-danger remove-feature';
            button.textContent = "{{ __('Remove') }}";

            row.appendChild(input);
            row.appendChild(button);
            return row;
        };

        addButton.addEventListener('click', () => {
            wrapper.appendChild(createRow());
        });

        wrapper.addEventListener('click', (event) => {
            if (!event.target.classList.contains('remove-feature')) {
                return;
            }

            const rows = wrapper.querySelectorAll('.feature-row');
            if (rows.length <= 1) {
                rows[0].querySelector('input').value = '';
                return;
            }

            event.target.closest('.feature-row').remove();
        });
    })();
</script>
