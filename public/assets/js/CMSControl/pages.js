let editorInstance;

$(document).ready(function () {
  let selectedCategory = null;
  let goToPage = null;

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
      url: "/admin/pages",
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

  $("#page_tree").jstree({
    state: { key: "pages" },
    plugins: ["state"],
    core: {
      multiple: false,
      data: {
        url: "/admin/pages.json",
        dataType: "json", // needed only if you do not supply JSON headers
      },
    },
  });

  $("#page_tree").on("ready.jstree", function (e, data) {
    if (goToPage != null) {
      $("#page_tree").jstree("select_node", "page-id-" + goToPage);
      console.log("Go to page: " + goToPage);
      goToPage = null;
    }
  });

  $("#page_tree").on("changed.jstree", function (e, data) {
    $("#page-edit-form").trigger("reset");
    editorInstance.setData("");
    if (data.node == undefined) {
      return;
    }

    if (data.node.id == "category-id-root" || data.node.li_attr.type == "category") {
      if (data.node.li_attr.type == "category") {
        selectedCategory = data.node.li_attr.category_id;
      } else {
        selectedCategory = null;
      }
      $("#new-page-category").val(data.node.text);
      $("#page-edit").hide();
      return;
    }

    $.ajax({
      url: "/admin/page/" + data.node.li_attr.page_id + ".json",
      type: "GET",
      success: function (response) {
        $("#page-edit").show();
        $("#page-id").val(response.id);
        $("#page-name").val(response.name);
        $("#page-slug").val(response.slug);
        $("#page-template").val(response.template.id);
        $("#page-url").html(window.location.protocol + '//' + window.location.host + '/' + response.computedUrl);
        $("#page-url").attr("href", $("#page-url").html());
        editorInstance.setData(response.content ?? "");
        $("#page-edit-form input[name='properties']").each(function () {
          var bitValue = parseInt($(this).val(), 10); // Get the integer bitmask value from input
          var isChecked = (response.properties & bitValue) !== 0; // Check if bit is set

          $(this).prop("checked", isChecked); // Set checked state
        });
      },
    });
  });

  $("#page-edit-form").on("submit", function (e) {
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

    formData.content = editorInstance.getData();

    $.ajax({
      url: "/admin/page/" + $("#page-id").val(),
      type: "PUT",
      contentType: "application/json",
      data: JSON.stringify(formData),
      success: function (data) {
        $("#page_tree").jstree("refresh");
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
