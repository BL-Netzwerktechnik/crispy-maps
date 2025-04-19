$(document).ready(function () {

    var editor = CodeMirror.fromTextArea(document.getElementById("editor"), {
        mode: "htmlmixed",
        theme: "default",
        lineNumbers: true,
        matchBrackets: true
    });
    editor.setSize("100%", "600px"); // Width: 100%, Height: 600px


    $("form").submit(function (e) {
        e.preventDefault();
        document.getElementById("editor").value = editor.getValue();

        var form = $(this);
        // Convert form data to an object
        var formData = {};
        $.each(form.serializeArray(), function (_, field) {
            if (formData[field.name]) {
                // If the field already exists, convert it into an array
                if (!Array.isArray(formData[field.name])) {
                    formData[field.name] = [formData[field.name]];
                }
                formData[field.name].push(field.value);
            } else {
                formData[field.name] = field.value;
            }
        });
        $.ajax({
            url: window.location.href,
            type: "POST",
            data: formData,
            success: function (data) {
                Swal.fire({
                    title: translation["CMSControl.Views.Layouts.Sweetalert.Saved"],
                    icon: "success",
                });

                window.location.href = "/admin/layouts";
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