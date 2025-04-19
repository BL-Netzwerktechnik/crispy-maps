$(document).ready(function () {
  let selectedCategory = null;

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

  $("#category_tree").on("ready.jstree", function (e, data) {

    $("#category_tree").jstree(true).deselect_all();


    if($("#category-parent").val().length == 0){
      $("#category_tree").jstree(true).select_node("category-id-root");
      selectedCategory = null;
    }
    $("#category_tree")
      .jstree(true)
      .select_node("category-id-" + $("#category-parent").val());
  });

  $("#category_tree").on("changed.jstree", function (e, data) {
    if (data.node == undefined) {
      return;
    }
    if (data.node.id == "category-id-root") {
      selectedCategory = null;
      return;
    }

    $.ajax({
      url: "/admin/category/" + data.node.li_attr.category_id + ".json",
      type: "GET",
      success: function (response) {
        selectedCategory = response.id;
      },
    });
  });

  $("#move-button").on("click", function (e) {
    e.preventDefault();
    // Convert form data to an object
    var formData = {
      parent: selectedCategory,
    };
    $.ajax({
      url: window.location.href,
      type: "PUT",
      contentType: "application/json",
      data: JSON.stringify(formData),
      success: function (data) {
        window.location.href = "/admin/categories";
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
