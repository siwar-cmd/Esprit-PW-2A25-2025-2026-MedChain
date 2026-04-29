function confirmSwal(event, element, title, text) {
    event.preventDefault();
    Swal.fire({
        title: title || 'Êtes-vous sûr ?',
        text: text || '',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#1D9E75',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Oui',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            if (element.tagName === 'FORM') {
                element.submit();
            } else if (element.tagName === 'A') {
                window.location.href = element.href;
            }
        }
    });
}
