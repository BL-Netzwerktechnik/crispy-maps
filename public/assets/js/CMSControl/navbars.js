function updateSelect(navbar) {
  return;

  let url;
  

  if(navbar.type == 1){
    url = "/admin/categories.json?format=select2";
  }

  if(navbar.type == 2){
    url = "/admin/pages.json?format=select2";
  }







  $('#navbar-target').select2({
    ajax: {
      url: url,
      dataType: 'json'
    }
  });

  $('#navbar-target').val(navbar.target.id).trigger('change');
}


$(document).ready(function () {
  let selectedCategory = null;

  $("#createPageModal").on("hidden.bs.modal", function (event) {
    $("#page-create-form").trigger("reset");
  });




  $("#move-page").on("click", function () {
    window.location.href = "/admin/page/" + $("#page-id").val() + "/move";
  });

  $("#page-create-form").on("submit", function (e) {
    e.preventDefault();
    var form = $(this);
    var serializedData =
      form.serialize() + "&category=" + encodeURIComponent(selectedCategory);

    if (selectedCategory == null) {
      serializedData = form.serialize();
    }

    $.ajax({
      url: "/admin/navbars",
      type: "POST",
      data: serializedData,
      success: function (response) {
        goToPage = response.parameters.page.id;
        $("#page_tree").jstree("refresh");
        $("#page-edit").hide();
        $("#page-create-form").trigger("reset");
        $("#createPageModal").modal("hide");
        Swal.fire({
          title: translation["CMSControl.Views.Pages.Sweetalert.Created"],
          icon: "success",
        });
      },
      error: function (response) {
        if (!response.responseJSON) {
          Swal.fire({
            title:
              translation[
              "CMSControl.Views.Pages.Sweetalert.Error.Generic"
              ],
            icon: "error",
          });
          return;
        }
        Swal.fire({
          title:
            translation["CMSControl.Views.Pages.Sweetalert.Error.Generic"],
          text:
            translation[response.responseJSON.message] ??
            response.responseJSON.message,
          icon: "error",
        });
      },
    });
  });

  $("#delete-page").on("click", function () {
    let id = $("#page-id").val();
    let button = $(this);

    Swal.fire({
      title:
        translation["CMSControl.Views.Pages.Sweetalert.Prompt.Delete"],
      showDenyButton: true,
      confirmButtonText:
        translation[
        "CMSControl.Views.Pages.Sweetalert.Prompt.Delete.Abort"
        ],
      denyButtonText:
        translation[
        "CMSControl.Views.Pages.Sweetalert.Prompt.Delete.Confirm"
        ],
      reverseButtons: true,
    }).then((result) => {
      if (result.isDenied) {
        $.ajax({
          url: "/admin/page/" + id,
          type: "DELETE",
          success: function (response) {
            $("#page_tree").jstree("refresh");
            $("#page-edit").hide();

            Swal.fire({
              title:
                translation["CMSControl.Views.Pages.Sweetalert.Deleted"],
              icon: "success",
            });
          },
          error: function (response) {
            if (!response.responseJSON) {
              Swal.fire({
                title:
                  translation[
                  "CMSControl.Views.Categories.Sweetalert.Error.Generic"
                  ],
                icon: "error",
              });
              return;
            }
            Swal.fire({
              title:
                translation[
                "CMSControl.Views.Categories.Sweetalert.Error.Generic"
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

  $("#navbar_tree").jstree({
    state: { key: "navbars" },
    plugins: ["state"],
    core: {
      multiple: false,
      data: {
        url: "/admin/navbars.json",
        dataType: "json", // needed only if you do not supply JSON headers
      },
    },
  });

  $("#navbar_tree").on("changed.jstree", function (e, data) {
    $("#navbar-edit-form").trigger("reset");



    if (data.node == undefined) {
      return;
    }

    if (data.node.id == "category-id-root") {

      selectedCategory = null;

      $("#new-navbar-parent").val(data.node.text);
      $("#navbar-edit").hide();
      return;
    }

    $.ajax({
      url: "/admin/navbar/" + data.node.li_attr.navbar_id + ".json",
      type: "GET",
      success: function (response) {

        //updateSelect(response);
        selectedCategory = data.node.li_attr.navbar_id;
        $("#navbar-edit").show();
        $("#navbar-id").val(response.id);
        $("#navbar-text").val(response.text);
        $("#navbar-target").val(response.target);
        $("#navbar-icon").val(response.icon);
        $("#navbar-edit-form input[name='properties']").each(function () {
          var bitValue = parseInt($(this).val(), 10); // Get the integer bitmask value from input
          var isChecked = (response.properties & bitValue) !== 0; // Check if bit is set

          $(this).prop("checked", isChecked); // Set checked state
        });
      },
    });
  });

  $("#navbar-edit-form").on("submit", function (e) {
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

    if (formData.properties === undefined) {
      formData.properties = 0;
    }

    $.ajax({
      url: "/admin/navbar/" + $("#navbar-id").val(),
      type: "PUT",
      contentType: "application/json",
      data: JSON.stringify(formData),
      success: function (data) {
        $("#navbar_tree").jstree("refresh");
        Swal.fire({
          title: translation["CMSControl.Views.Pages.Sweetalert.Saved"],
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
