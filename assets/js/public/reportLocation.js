$(document).on('configLoaded', function (event, config) {

    // if report Reason checkbox with value 4 is checked, redirect to config.NETZDG_REPORT_URL

    if (config.netzdg_report_url && config.netzdg_report_url.length > 0) {
        $("#reportForm input[type='checkbox']").on("change", function () {
            if ($(this).val() == 4 && $(this).is(":checked")) {
                window.location.href = config.netzdg_report_url;
            }
        });
    }


    $("#reportForm").on("submit", function (e) {
        e.preventDefault();

        $.ajax({
            url: window.location.href + "/report",
            type: "POST",
            data: $(this).serialize(),
            success: function (data) {
                Swal.fire({
                    title: "Vielen Dank!",
                    text: "Deine Meldung wurde erfolgreich gesendet.",
                    icon: "success",
                });
            },
            error: function (data) {
                Swal.fire({
                    title: "Fehler",
                    text: "Es gab ein Problem bei der Meldung. Bitte versuche es später erneut.",
                    icon: "error",
                });
            },
        });
    });
});