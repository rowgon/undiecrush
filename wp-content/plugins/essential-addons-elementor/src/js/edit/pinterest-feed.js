(function ($) {
    "use strict";

    $(window).on("elementor:init", function () {
        elementor.channels.editor.on("eaelPinterestFetchBoards", function (view) {
            var model = view.model || view.options.model;
            var settings = model.get("settings");
            var token = settings.get("eael_pinterest_feed_access_token");

            if (!token) {
                elementor.notifications.showToast({
                    message: "Please enter an Access Token first.",
                    type: "warning",
                });
                return;
            }

            elementor.notifications.showToast({
                message: "Fetching boards...",
                type: "info",
            });

            $.ajax({
                url: localize.ajaxurl,
                type: "POST",
                data: {
                    action: "eael_pinterest_fetch_boards",
                    security: localize.nonce,
                    access_token: token,
                },
                success: function (response) {
                    if (response.success && response.data) {
                        var control = model.controls.eael_pinterest_feed_board_id;
                        if (control) {
                            control.options = response.data;
                            view.renderUI();
                        }
                        elementor.notifications.showToast({
                            message:
                                Object.keys(response.data).length +
                                " boards loaded!",
                            type: "success",
                        });
                    } else {
                        elementor.notifications.showToast({
                            message:
                                response.data ||
                                "No boards found. Check your token.",
                            type: "error",
                        });
                    }
                },
                error: function () {
                    elementor.notifications.showToast({
                        message: "Failed to fetch boards. Please try again.",
                        type: "error",
                    });
                },
            });
        });
    });
})(jQuery);
