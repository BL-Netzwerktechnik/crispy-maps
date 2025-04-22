$(document).ready(function () {

    let likeBtn = $("[data-action='like']");
    let dislikeBtn = $("[data-action='dislike']");

    $("[data-action='like']").on("click", function (e) {

        $.ajax({
            url: window.location.href + "/vote",
            type: "POST",
            data: {
                vote: 1
            },
            success: function (data) {
                likeBtn.attr('disabled', true);
                dislikeBtn.attr('disabled', false);
            },
            error: function (data) {
                if (data.status == 400) {
                    var errors = data.responseJSON.parameters.errors;
                    var errorString = "";

                    // Loop through the errors and append them to the errorString with translation
                    for (var key in errors) {
                        errorString += translation[errors[key]] ?? errors[key] + "\n";
                    }

                    Swal.fire({
                        title: translation["CMSControl.Libs.Sweetalert.Error.Title"],
                        text: errorString,
                        icon: "error",
                    });
                }
            },
        });
    });

    
    $("[data-action='dislike']").on("click", function (e) {

        $.ajax({
            url: window.location.href + "/vote",
            type: "POST",
            data: {
                vote: 0
            },
            success: function (data) {
                likeBtn.attr('disabled', false);
                dislikeBtn.attr('disabled', true);
            },
            error: function (data) {
                if (data.status == 400) {
                    var errors = data.responseJSON.parameters.errors;
                    var errorString = "";

                    // Loop through the errors and append them to the errorString with translation
                    for (var key in errors) {
                        errorString += translation[errors[key]] ?? errors[key] + "\n";
                    }

                    Swal.fire({
                        title: translation["CMSControl.Libs.Sweetalert.Error.Title"],
                        text: errorString,
                        icon: "error",
                    });
                }
            },
        });
    });
});