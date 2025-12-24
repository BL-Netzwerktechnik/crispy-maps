$(document).on("editorReady", function (event) {
  let editorInstance = event.detail;

  $("[role='delete-report']").on("click", function () {
    $.ajax({
      url: $(this).attr("href"),
      type: "DELETE",
      success: function (data) {
        Swal.fire({
          title: "Aktion erfolgreich",
          icon: "success",
        });
        window.location.reload();
      },
      error: function (data) {
        Swal.fire({
          title: "Aktion fehlgeschlagen",
          text: "Es gab ein Problem bei der Aktion. Bitte versuche es später erneut.",
          icon: "error",
        });
      },
    });
  });

  $("[role='delete']").on("click", function () {
    Swal.fire({
      title: "Löschen bestätigen",
      text: "Möchten Sie diese Location wirklich löschen?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Ja, löschen!",
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: window.location.href,
          type: "DELETE",
          success: function (data) {
            Swal.fire({
              title: "Löschen erfolgreich",
              icon: "success",
            });
            window.location.href = "/admin/map";
          },
          error: function (data) {
            Swal.fire({
              title: "Aktion fehlgeschlagen",
              text: "Es gab ein Problem bei der Aktion. Bitte versuche es später erneut.",
              icon: "error",
            });
          },
        });
      }
    });
  });

  $("form").on("submit", function (e) {
    e.preventDefault();
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

    formData.description = editorInstance.getData();

    $.ajax({
      url: window.location.href,
      type: "POST",
      data: formData,
      success: function (data) {
        Swal.fire({
          title: "Aktion erfolgreich",
          icon: "success",
        });

        if (!$("#id").val()) {
          window.location.href = "/admin/map";
        } else {
          window.location.reload();
        }
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
