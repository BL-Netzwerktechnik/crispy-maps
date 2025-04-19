$(document).ready(function () {
  let selectedCategory = null;

  $("#createCategoryModal").on("hidden.bs.modal", function (event) {
    $("#category-create-form").trigger("reset");
  });

  $("#category-create-form").on("submit", function (e) {
    e.preventDefault();
    var form = $(this);
    var serializedData =
      form.serialize() + "&parent=" + encodeURIComponent(selectedCategory);

    if (selectedCategory == null) {
      serializedData = form.serialize();
    }

    $.ajax({
      url: "/admin/categories",
      type: "POST",
      data: serializedData,
      success: function (response) {
        $("#category_tree").jstree("refresh");
        $("#category-edit").hide();
        $("#category-create-form").trigger("reset");
        $("#createCategoryModal").modal("hide");
        Swal.fire({
          title: translation["CMSControl.Views.Categories.Sweetalert.Created"],
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
            translation["CMSControl.Views.Categories.Sweetalert.Error.Generic"],
          text:
            translation[response.responseJSON.message] ??
            response.responseJSON.message,
          icon: "error",
        });
      },
    });
  });

  $("#move-category").on("click", function () {
    window.location.href = "/admin/category/" + selectedCategory + "/move";
  });

  $("#delete-category").on("click", function () {
    let id = $("#category-id").val();
    let button = $(this);

    Swal.fire({
      title:
        translation["CMSControl.Views.Categories.Sweetalert.Prompt.Delete"],
      showDenyButton: true,
      confirmButtonText:
        translation[
          "CMSControl.Views.Categories.Sweetalert.Prompt.Delete.Abort"
        ],
      denyButtonText:
        translation[
          "CMSControl.Views.Categories.Sweetalert.Prompt.Delete.Confirm"
        ],
      reverseButtons: true,
    }).then((result) => {
      if (result.isDenied) {
        $.ajax({
          url: "/admin/category/" + id,
          type: "DELETE",
          success: function (response) {
            $("#category_tree").jstree("refresh");
            $("#category-edit").hide();

            Swal.fire({
              title:
                translation["CMSControl.Views.Categories.Sweetalert.Deleted"],
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

  $("#category_tree").jstree({
    state: { key: "categories" },
    plugins: ["state"],
    core: {
      multiple: false,
      data: {
        url: "/admin/categories.json",
        dataType: "json", // needed only if you do not supply JSON headers
      },
    },
  });
  $("#category_tree").on("changed.jstree", function (e, data) {
    $("#category-edit-form").trigger("reset");
    if (data.node == undefined) {
      return;
    }

    $("#new-category-parent").val(data.node.text);

    if (data.node.id == "category-id-root") {
      selectedCategory = null;
      $("#category-edit").hide();
      return;
    }

    $.ajax({
      url: "/admin/category/" + data.node.li_attr.category_id + ".json",
      type: "GET",
      success: function (response) {
        selectedCategory = response.id;
        $("#category-edit").show();
        $("#category-id").val(response.id);
        $("#category-name").val(response.name);
        $("#category-slug").val(response.slug);
        $("#category-edit-form input[name='properties']").each(function () {
          var bitValue = parseInt($(this).val(), 10); // Get the integer bitmask value from input
          var isChecked = (response.properties & bitValue) !== 0; // Check if bit is set

          $(this).prop("checked", isChecked); // Set checked state
        });

        if (data.node.children.length > 0) {
          $("#delete-category").attr("disabled", true);
          $("#delete-category-tooltip").attr(
            "title",
            translation[
              "CMSControl.Views.Categories.Card.EditCategory.Form.Delete.Error.HasChildren"
            ]
          );
        } else if (!$("#delete-category").data("readonly")) {
          $("#delete-category").attr("disabled", false);
          $("#delete-category-tooltip").attr("data-bs-original-title", "");
        }
        var tooltipTriggerList = [].slice.call(
          document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl);
        });
      },
    });
  });

  $("#category-edit-form").on("submit", function (e) {
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
      url: "/admin/category/" + selectedCategory,
      type: "PUT",
      contentType: "application/json",
      data: JSON.stringify(formData),
      success: function (data) {
        $("#category_tree").jstree("refresh");
        Swal.fire({
          title: translation["CMSControl.Views.Categories.Sweetalert.Saved"],
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
