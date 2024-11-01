/* global jQuery, objusertocrm */
(function ($) {
    "use strict";
    $(function () {
        $(document).ready(function () {
            $("#OEPL_Test_and_Save_Changes").on("click", function () {
                $(".OEPL_Sugar_ErrMsg").hide();
                $(".OEPL_Sugar_SuccessMsg").hide();
                var sugarurl = $("#OEPL_WP_USER_TO_CRM_SUGARCRM_URL")
                    .val()
                    .trim();
                var sugaruser = $("#OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_USER")
                    .val()
                    .trim();
                var sugarpass = $("#OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_PASS")
                    .val()
                    .trim();
                var oeplnonce = $("#oepl_nonce").val();
                var data = {};

                if (sugarurl === "" || sugarurl === null) {
                    alert("Please provide CRM URL");
                    $("#OEPL_WP_USER_TO_CRM_SUGARCRM_URL").focus();
                    return false;
                } else if (sugaruser === "" || sugaruser === null) {
                    alert("Please provide CRM Admin User");
                    $("#OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_USER").focus();
                    return false;
                } else if (sugarpass === "" || sugarpass === null) {
                    alert("Please provide CRM Admin Password");
                    $("#OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_PASS").focus();
                    return false;
                }

                data.action = "OEPL_WPUserToCRM_Test_and_Save_Changes";
                data.sugarurl = sugarurl;
                data.sugaruser = sugaruser;
                data.sugarpass = sugarpass;
                data.security = oeplnonce;

                $(".oe-loader-section").show();
                $.post(objusertocrm.ajaxurl, data, function (response) {
                    if (response.status === "Y") {
                        $(".OEPL_Sugar_SuccessMsg").show();
                        $(".OEPL_Sugar_SuccessMsg").html(response.message);
                        setTimeout(function () {
                            window.location.reload();
                        }, 2000);
                    } else {
                        $(".OEPL_Sugar_ErrMsg").show();
                        $(".OEPL_Sugar_ErrMsg").html(response.message);
                    }
                    $("html, body").animate({ scrollTop: 0 }, "slow");
                    $(".oe-loader-section").hide();
                });
                return false;
            });
            $("#OEPL_Synchronize_Contacts_Fields").on("click", function () {
                $(".OEPL_Sugar_ErrMsg").hide();
                $(".OEPL_Sugar_SuccessMsg").hide();
                var data = {};
                data.action = "OEPL_WPUserToCRMContactsFieldSync";
                $(".oe-loader-section").show();

                $.post(objusertocrm.ajaxurl, data, function (response) {
                    if (response.status === "Y") {
                        $(".OEPL_Sugar_SuccessMsg").show();
                        $(".OEPL_Sugar_SuccessMsg").html(response.message);
                    } else {
                        $(".OEPL_Sugar_ErrMsg").show();
                        $(".OEPL_Sugar_ErrMsg").html(response.message);
                    }
                    $("html, body").animate({ scrollTop: 0 }, "slow");
                    $(".oe-loader-section").hide();
                });
                return false;
            });
        });
    });
})(jQuery);