$(document).ready(function () {

  $("[data-action='delete']").on("click", function () {
    let id = $(this).data("id");

    Swal.fire({
      title:
        translation["CMSControl.Views.Templates.Sweetalert.Prompt.Delete"],
      showDenyButton: true,
      confirmButtonText:
        translation[
        "CMSControl.Views.Templates.Sweetalert.Prompt.Delete.Abort"
        ],
      denyButtonText:
        translation[
        "CMSControl.Views.Templates.Sweetalert.Prompt.Delete.Confirm"
        ],
      reverseButtons: true,
    }).then((result) => {
      if (result.isDenied) {
        $.ajax({
          url: "/admin/templates/" + id,
          type: "DELETE",
          success: function (response) {
            window.location.reload();

            Swal.fire({
              title:
                translation["CMSControl.Views.Templates.Sweetalert.Deleted"],
              icon: "success",
            });
          },
          error: function (response) {
            if (!response.responseJSON) {
              Swal.fire({
                title:
                  translation[
                  "CMSControl.Views.Templates.Sweetalert.Error.Generic"
                  ],
                icon: "error",
              });
              return;
            }
            Swal.fire({
              title:
                translation[
                "CMSControl.Views.Templates.Sweetalert.Error.Generic"
                ],
              text:
                translation[response.responseJSON.message] ??
                response.responseJSON.message,
              icon: "error",
            });
          },
        });
      }
    });
  });
});
