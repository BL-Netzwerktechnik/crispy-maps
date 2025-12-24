$(document).on("mapsConfigLoaded", function (event) {
  let config = event.detail;
  document.dispatchEvent(
    new CustomEvent("leafletFinishedLoading", { detail: config })
  );
});
