function testEmail() {
  $.ajax({
    url: "/admin/settings/testEmail",
    type: "POST",
    success: function (data) {
      Swal.fire({
        title: translation["CMSControl.Views.Settings.Sweetalert.EmailSent"],
        icon: "success",
      });
    },
    error: function (data) {
      if (data.status == 400) {
        var errors = data.responseJSON.parameters.errors;
        var errorString = "";

        // Loop through the errors and append them to the errorString with translation
        for (var key in errors) {
          errorString += (translation[errors[key]] ?? errors[key]) + "<br>";
        }

        Swal.fire({
          title: translation["CMSControl.Libs.Sweetalert.Error.Title"],
          html: errorString,
          icon: "error",
        });
      }
    },
  });
}

function updateRequiredIndicators() {



  $("form").find('input[required], select[required], textarea[required]').each(function () {
    var $this = $(this);
    var tabId = $this.closest('.tab-pane').attr('id'); // Get the ID of the tab pane containing the input

    var tabControl = $('.nav-link[aria-controls="' + tabId + '"]'); // Find the nav-link with the corresponding aria-controls
    var $badge = tabControl.find('.badge'); // Corrected: Find the badge in the corresponding nav-item

    if (!$this.val()) {
      $badge.show();
    } else {
      $badge.hide();
    }
  });
}

$(document).ready(function () {
  let changedInputs = [];
  updateRequiredIndicators();



  $("input, textarea, select").on("input", function () {
    updateRequiredIndicators();
    if (!changedInputs.includes($(this))) {
      changedInputs.push($(this));
    }

    if ($(this).attr("aria-describedby")) {
      // make it bold
      $("#" + $(this).attr("aria-describedby")).css("font-weight", "bold");
    }
  });

  $("form").on("reset", function () {
    changedInputs.forEach(function (input) {

      if (input.attr("aria-describedby")) {
        $("#" + input.attr("aria-describedby")).attr("style", "");
      }
    });
    changedInputs = [];

    setTimeout(function () {
      updateRequiredIndicators();
    }, 1);
  });


  $("form").on("submit", function (e) {
    updateRequiredIndicators();

    e.preventDefault();


    let formData = {};

    changedInputs.forEach(function (input) {
      if (input.attr("type") == "checkbox") {
        formData[input.attr("name")] = input.is(":checked");
      } else {
        formData[input.attr("name")] = input.val();
      }
    });

    $.ajax({
      url: "/admin/settings",
      type: "PUT",
      contentType: "application/json",
      data: JSON.stringify(formData),
      success: function (data) {
        Swal.fire({
          title: translation["CMSControl.Views.Settings.Sweetalert.Saved"],
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
