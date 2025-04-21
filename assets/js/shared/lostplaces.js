$(document).ready(function () {

    $.get("/config.json", function (data) {
        $(document).trigger("configLoaded", data);
        console.log("Config loaded");
        console.log(data);
    });

});