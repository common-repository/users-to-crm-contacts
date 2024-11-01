/* global jQuery, objusertocrm */
(function ($) {
    "use strict";
    $(function () {
        $(document).ready(function () {
            $(".OEPL_custom_meta").on("change", function () {
                $(this).siblings(".OEPL_small_button").show();
            });

            $(".OEPL_Contact_grid_status").on("change", function () {
                var action = $(this).data("action");
                var pid = $(this).data("pid");
                var data = {};
                data.action = "OEPL_WPUserToCRM_Contact_grid_status";
                data.OEPL_Action = action;
                data.pid = pid;
                $.post(objusertocrm.ajaxurl, data, function (response) {});
                return false;
            });

            $(".OEPL_save_custom_meta").on("click", function () {
                var val = $(this).siblings(".OEPL_custom_meta").val();
                var pid = $(this).data("pid");
                var data = {};
                data.action = "OEPL_WPUserToCRM_save_custom_meta";
                data.pid = pid;
                data.meta_field = val;
                $(this).find(".fa").removeClass("fa-check-square");
                $(this).find(".fa").addClass("fa-spinner");
                $(this).find(".fa").addClass("fa-spin");
                $(".oe-loader-section").show();
                $(this).hide();
                $.post(objusertocrm.ajaxurl, data, function (response) {
                    if (response.status === "Y") {
                        $(".OEPL_Sugar_SuccessMsg").show();
                        $(".OEPL_Sugar_ErrMsg").hide();
                        $(".OEPL_Sugar_SuccessMsg").html(response.message);
                        $(".OEPL_Sugar_SuccessMsg").fadeOut(5000);
                    } else {
                        $(".OEPL_Sugar_ErrMsg").show();
                        $(".OEPL_Sugar_ErrMsg").html(response.message);
                    }
                    $(this).find(".fa").addClass("fa-check-square");
                    $(this).find(".fa").removeClass("fa-spinner");
                    $(this).find(".fa").removeClass("fa-spin");
                    $(this).hide();
                    $(".oe-loader-section").hide();
                });
                return false;
            });
        });
    });
})(jQuery);