<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    wp_die(esc_html__('File is not called by WordPress', 'WPUSERTOCRM'));
}
global $wpdb;

delete_option('OEPL_WP_USER_TO_CRM_SUGARCRM_URL');
delete_option('OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_USER');
$wpdb->query("DROP TABLE IF EXISTS oepl_wp_user_to_crm_map_field");