$(document).ready(function () {
  let selectedCategory = null;

  $("#createRoleModal").on("hidden.bs.modal", function (event) {
    $("#role-create-form").trigger("reset");
  });

  $("#role-create-form").on("submit", function (e) {
    e.preventDefault();
    var form = $(this);
    var serializedData =
      form.serialize() + "&parent=" + encodeURIComponent(selectedCategory);

    if (selectedCategory == null) {
      serializedData = form.serialize();
    }

    $.ajax({
      url: "/admin/roles",
      type: "POST",
      data: serializedData,
      success: function (response) {
        $("#role_tree").jstree("refresh");
        $("#role-edit").hide();
        $("#role-create-form").trigger("reset");
        $("#createRoleModal").modal("hide");
        Swal.fire({
          title: translation["CMSControl.Views.Roles.Sweetalert.Created"],
          icon: "success",
        });
      },
      error: function (response) {
        if (!response.responseJSON) {
          Swal.fire({
            title:
              translation[
              "CMSControl.Views.Roles.Sweetalert.Error.Generic"
              ],
            icon: "error",
          });
          return;
        }
        Swal.fire({
          title:
            translation["CMSControl.Views.Roles.Sweetalert.Error.Generic"],
          text:
            translation[response.responseJSON.message] ??
            response.responseJSON.message,
          icon: "error",
        });
      },
    });
  });


  $("#delete-role").on("click", function () {
    let id = $("#role-id").val();
    let button = $(this);

    Swal.fire({
      title:
        translation["CMSControl.Views.Roles.Sweetalert.Prompt.Delete"],
      showDenyButton: true,
      confirmButtonText:
        translation[
        "CMSControl.Views.Roles.Sweetalert.Prompt.Delete.Abort"
        ],
      denyButtonText:
        translation[
        "CMSControl.Views.Roles.Sweetalert.Prompt.Delete.Confirm"
        ],
      reverseButtons: true,
    }).then((result) => {
      if (result.isDenied) {
        $.ajax({
          url: "/admin/role/" + id,
          type: "DELETE",
          success: function (response) {
            $("#role_tree").jstree("refresh");
            $("#role-edit").hide();

            Swal.fire({
              title:
                translation["CMSControl.Views.Roles.Sweetalert.Deleted"],
              icon: "success",
            });
          },
          error: function (response) {
            if (!response.responseJSON) {
              Swal.fire({
                title:
                  translation[
                  "CMSControl.Views.Roles.Sweetalert.Error.Generic"
                  ],
                icon: "error",
              });
              return;
            }
            Swal.fire({
              title:
                translation[
                "CMSControl.Views.Roles.Sweetalert.Error.Generic"
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

  $("#role_tree").jstree({
    state: { key: "roles" },
    plugins: ["state"],
    core: {
      multiple: false,
      data: {
        url: "/admin/roles.json",
        dataType: "json", // needed only if you do not supply JSON headers
      },
    },
  });
  $("#role_tree").on("changed.jstree", function (e, data) {
    $("#role-edit-form").trigger("reset");
    if (data.node == undefined) {
      return;
    }

    if (data.node.id == "role-id-root") {
      $("#role-edit").hide();
      return;
    }

    $.ajax({
      url: "/admin/role/" + data.node.li_attr.role_id + ".json",
      type: "GET",
      success: function (response) {
        $("#role-edit").show();
        $("#role-id").val(response.id);
        $("#role-name").val(response.name);
        $("#role-edit-form input[name='permissions']").each(function () {
          var bitValue = parseInt($(this).val(), 10); // Get the integer bitmask value from input
          var isChecked = (response.permissions & bitValue) !== 0; // Check if bit is set

          $(this).prop("checked", isChecked); // Set checked state
        });
      },
    });
  });

  $("#role-edit-form").on("submit", function (e) {
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
      url: "/admin/role/" + $("#role-id").val(),
      type: "PUT",
      contentType: "application/json",
      data: JSON.stringify(formData),
      success: function (data) {
        $("#role_tree").jstree("refresh");
        Swal.fire({
          title: translation["CMSControl.Views.Roles.Sweetalert.Saved"],
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
