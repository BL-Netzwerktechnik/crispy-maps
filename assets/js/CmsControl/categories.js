
$(document).ready(function () {
    $('[role="delete"]').on('click', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Kategorie löschen',
            text: "Möchten Sie diese Kategorie wirklich löschen?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ja, löschen!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/lp/categories/' + id,
                    type: 'DELETE',
                    success: function (response) {
                        Swal.fire(
                            'Gelöscht!',
                            'Die Kategorie wurde gelöscht.',
                            'success'
                        ).then(() => {
                            location.reload();
                        });
                    },
                    error: function (xhr, status, error) {
                        Swal.fire(
                            'Fehler!',
                            'Die Kategorie konnte nicht gelöscht werden.',
                            'error'
                        );
                    }
                });
            }
        });
    });
});