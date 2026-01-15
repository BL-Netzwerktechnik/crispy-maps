$(document).ready(function () {
  $.get("/config.json", function (data) {
    document.dispatchEvent(
      new CustomEvent("mapsConfigLoaded", { detail: data })
    );
  });
});
