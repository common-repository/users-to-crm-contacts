<?php
if ( isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
	exit(esc_html__('Please don\'t access this file directly.', 'WPUSERTOCRM'));
}

## Add stylesheet and js file code START
wp_register_style( 'oepl_admin_css', WPUSERTOCRM_PLUGIN_URL.'style/style.css', false, '1.0.0' );
wp_enqueue_style( 'oepl_admin_css' );

wp_register_script( 'oepl_admin_script', WPUSERTOCRM_PLUGIN_URL . 'js/admin.js', array( 'jquery' ), false, true );
wp_enqueue_script( 'oepl_admin_script');

wp_localize_script( 'oepl_admin_script', 'objusertocrm',
    array( 
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
    )
);

## Add stylesheet and js file code END
?>
<div class="wrap">
    <table align="left" cellpadding="1" width="100%" cellspacing="1" border="0">
        <tr>
            <td valign="top">
                <table align="left" cellpadding="1" width="100%" cellspacing="1" border="0">
                    <tr height="30">
                        <td>
                            <div>
                                <h2 class="crm_setting_h2">
                                <?php esc_html_e('WordPress to SugarCRM / SuiteCRM Contacts', 'WPUSERTOCRM'); ?>
                                </h2>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top">
                            <div class="content">
                                <form name="OEPl_Contact_sugarSettings" id="OEPl_Contact_sugarSettings" method="post">
                                    <div class="OEPL_Sugar_ErrMsg"></div>
                                    <div class="OEPL_Sugar_SuccessMsg"></div>
                                    <input type="hidden" value="<?php echo wp_create_nonce()?>" name='_nonce' id="oepl_nonce" />
                                    <table class="form-table crm_setting_tbl">
                                        <div class="title">
                                            <span class="fa fa-gears fa-1x"></span> 
                                            <?php esc_html_e('SugarCRM / SuiteCRM REST API Settings', 'WPUSERTOCRM'); ?>
                                        </div>
                                        <tr>
                                            <td><strong><?php esc_html_e('Sugar / SuiteCRM REST API URL :', 'WPUSERTOCRM'); ?></strong><br />
                                                <input name="OEPL_WP_USER_TO_CRM_SUGARCRM_URL" type="text" id="OEPL_WP_USER_TO_CRM_SUGARCRM_URL" class="OEPLtextbox" value="<?php echo get_option('OEPL_WP_USER_TO_CRM_SUGARCRM_URL'); ?>" size="53" required />
                                                <p class="description">
                                                    <?php esc_html_e("Your Sugar / SuiteCRM REST API URL will be ", "WPUSERTOCRM");
                                                    echo esc_html("<Domain>/service/v4_1/rest.php."); ?> <br>
                                                    <?php echo esc_html("i.e. http://mycrm.com/service/v4_1/rest.php"); ?> <b>
                                                    <?php esc_html_e("or", "WPUSERTOCRM"); ?></b> <?php echo "http://demo.mycrm.com/service/v4_1/rest.php"; ?>
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php esc_html_e("SugarCRM / SuiteCRM Admin User :", "WPUSERTOCRM"); ?></strong><br />
                                                <input name="OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_USER" autocomplete="off" type="text" class="OEPLtextbox" id="OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_USER" value="<?php echo get_option('OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_USER'); ?>" size="25" required />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php esc_html_e("SugarCRM / SuiteCRM Admin Password :", "WPUSERTOCRM");?></strong><br />
                                                <input name="OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_PASS" autocomplete="off" type="text" class="OEPLtextbox" id="OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_PASS" required size="25" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="OEPL_reload_this">
                                                <input type="submit" value="<?php _e('Test and Save Changes') ?>" id="OEPL_Test_and_Save_Changes" class="button button-primary button-large OEPLSugarsaveConfig" /> &nbsp;&nbsp; <?php if(OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_USER != '' && OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_PASS != '' ){
										?>
                                                <input type="button" value="<?php _e('Synchronize Contacts Fields') ?>" id="OEPL_Synchronize_Contacts_Fields" class="button button-primary button-large" />
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    </table>
                                </form>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>