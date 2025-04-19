$(document).ready(function () {

    $("form").submit(function (e) {
        e.preventDefault();

        $.ajax({
            url: window.location.href,
            type: "POST",
            data: {
                name: $("#name").val(),
                username: $("#username").val(),
                password: $("#password").val(),
                password_confirm: $("#password_confirm").val(),
                email: $("#email").val(),
                csrf: csrf
            },
            success: function (response) {
                Swal.fire({
                    title: translation['CMSControl.Libs.Sweetalert.Success.Title'],
                    text: translation['CMSControl.Views.Register.Sweetalert.Success'],
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                    willClose: () => {
                        window.location.href = "/admin";
                    }
                });
            },
            error: function (response, status, error) {
                switch (response.status) {
                    case 400:
                        Swal.fire({
                            title: translation['CMSControl.Libs.Sweetalert.Error.Title'],
                            text: translation[JSON.parse(response.responseText).message] ?? JSON.parse(response.responseText).message,
                            icon: 'error'
                        });
                        break;

                    case 500:
                    case 501:
                    case 502:
                    case 503:
                    case 504:
                    case 404:
                    default:
                        Swal.fire({
                            title: translation['CMSControl.Libs.Sweetalert.Error.Title'],
                            text: translation['CMSControl.Libs.Sweetalert.Error.ServerError'],
                            icon: 'error'
                        });
                        break;
                }
            }
        });
    });


});