$(document).ready(function () {

  $("#createUserModal").on("hidden.bs.modal", function (event) {
    $("#user-create-form").trigger("reset");
  });

  $("#user-create-form").on("submit", function (e) {
    e.preventDefault();
    var form = $(this);
    var serializedData = form.serialize();


    $.ajax({
      url: "/admin/users",
      type: "POST",
      data: serializedData,
      success: function (response) {
        $("#user_tree").jstree("refresh");
        $("#user-edit").hide();
        $("#user-create-form").trigger("reset");
        $("#createUserModal").modal("hide");
        Swal.fire({
          title: translation["CMSControl.Views.Users.Sweetalert.Created"],
          icon: "success",
        });
      },
      error: function (response) {
        if (!response.responseJSON) {
          Swal.fire({
            title:
              translation[
              "CMSControl.Views.Users.Sweetalert.Error.Generic"
              ],
            icon: "error",
          });
          return;
        }
        Swal.fire({
          title:
            translation["CMSControl.Views.Users.Sweetalert.Error.Generic"],
          text:
            translation[response.responseJSON.message] ??
            response.responseJSON.message,
          icon: "error",
        });
      },
    });
  });


  $("#delete-user").on("click", function () {
    let id = $("#user-id").val();
    let button = $(this);

    Swal.fire({
      title:
        translation["CMSControl.Views.Users.Sweetalert.Prompt.Delete"],
      showDenyButton: true,
      confirmButtonText:
        translation[
        "CMSControl.Views.Users.Sweetalert.Prompt.Delete.Abort"
        ],
      denyButtonText:
        translation[
        "CMSControl.Views.Users.Sweetalert.Prompt.Delete.Confirm"
        ],
      reverseButtons: true,
    }).then((result) => {
      if (result.isDenied) {
        $.ajax({
          url: "/admin/user/" + id,
          type: "DELETE",
          success: function (response) {
            $("#user_tree").jstree("refresh");
            $("#user-edit").hide();

            Swal.fire({
              title:
                translation["CMSControl.Views.Users.Sweetalert.Deleted"],
              icon: "success",
            });
          },
          error: function (response) {
            if (!response.responseJSON) {
              Swal.fire({
                title:
                  translation[
                  "CMSControl.Views.Users.Sweetalert.Error.Generic"
                  ],
                icon: "error",
              });
              return;
            }
            Swal.fire({
              title:
                translation[
                "CMSControl.Views.Users.Sweetalert.Error.Generic"
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

  $("#user_tree").jstree({
    core: {
      multiple: false,
      data: {
        url: "/admin/users.json",
        dataType: "json", // needed only if you do not supply JSON headers
      },
    },
  });
  $("#user_tree").on("changed.jstree", function (e, data) {
    $("#user-edit-form").trigger("reset");
    if (data.node == undefined) {
      return;
    }

    if (data.node.id == "user-id-root" || data.node.id.startsWith("role")) {
      $("#user-edit").hide();
      return;
    }

    $.ajax({
      url: "/admin/user/" + data.node.li_attr.user_id + ".json",
      type: "GET",
      success: function (response) {
        $("#user-edit").show();
        $("#user-id").val(response.id);
        $("#user-name").val(response.name);
        $("#user-email").val(response.email);
        $("#user-username").val(response.username);
        $("#user-role").val(response.role.id);
        $("#user-email-verified").prop("checked", response.emailVerified);
      },
    });
  });

  $("#user-edit-form").on("submit", function (e) {
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

    if (formData.permissions === undefined) {
      formData.permissions = 0;
    }

    $.ajax({
      url: "/admin/user/" + $("#user-id").val(),
      type: "PUT",
      contentType: "application/json",
      data: JSON.stringify(formData),
      success: function (data) {
        $("#user_tree").jstree("refresh");
        Swal.fire({
          title: translation["CMSControl.Views.Users.Sweetalert.Saved"],
          icon: "success",
        });
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
