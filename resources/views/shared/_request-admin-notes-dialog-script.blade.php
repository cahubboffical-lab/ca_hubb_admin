async function confirmRequestStatusWithNotes(options) {
    return Swal.fire({
        title: options.title,
        text: options.text,
        icon: options.icon || 'question',
        input: 'textarea',
        inputLabel: @json(__('Admin Notes')),
        inputPlaceholder: @json(__('Enter a note explaining this action...')),
        inputAttributes: {
            maxlength: 2000,
            'aria-label': @json(__('Admin Notes')),
        },
        inputValidator: value => {
            if (!value || !value.trim()) {
                return @json(__('Admin notes are required.'));
            }

            return null;
        },
        showCancelButton: true,
        confirmButtonText: options.confirmButtonText,
        cancelButtonText: options.cancelButtonText || @json(__('Keep Request')),
        confirmButtonColor: options.confirmButtonColor || '#435ebe',
        cancelButtonColor: '#6c757d',
        reverseButtons: true,
        focusCancel: true,
    });
}
