<?php
if ( isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    exit(esc_html__('Please don\'t access this file directly.', 'WPUSERTOCRM'));
}

define('WPUSERTOCRM_PLUGIN_URL', plugin_dir_url(__FILE__));	// define the plugin folder url
define ('WPUSERTOCRM_PLUGIN_DIR', plugin_dir_path(__FILE__));	// define the plugin folder dir
define ('WPUSERTOCRM_METAKEY_EXT', 'oepl_');
define('WPUSERTOCRM_MAP_FIELD', 'oepl_wp_user_to_crm_map_field');  // define the table name 
define('OEPL_WP_USER_TO_CRM_SUGARCRM_URL', get_option('OEPL_WP_USER_TO_CRM_SUGARCRM_URL') );
define('OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_USER', get_option('OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_USER'));
define('OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_PASS', get_option('OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_PASS'));

require_once(WPUSERTOCRM_PLUGIN_DIR. "OEPL_WPUserToCrm.Cls.php");
$objUserToCRM = new OEPL_WPUserToCRMClass;
$objUserToCRM->SugarURL  = OEPL_WP_USER_TO_CRM_SUGARCRM_URL;
$objUserToCRM->SugarUser = OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_USER; 
$objUserToCRM->SugarPass = OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_PASS;

## Error notice code START
if (empty($objUserToCRM->SugarURL) || empty($objUserToCRM->SugarUser) || empty($objUserToCRM->SugarPass)) {
	add_action( 'admin_notices', 'OEPL_WPUserToCRM_error_notice' );
} else {
	add_action( 'user_register', 'OEPL_WP_User_To_CRM_created_user', 10, 1 );
}

function OEPL_WPUserToCRM_error_notice() {
?>
    <div class="error notice">
    	<p><?php esc_html_e( 'CRM Settings required in WP-User-CRM-Contacts', 'WPUSERTOCRM' ); ?></p>
    </div>
<?php }	
## Error notice code END

require_once(WPUSERTOCRM_PLUGIN_DIR. "WPUserToCRM_admin_functions.php");
if (is_admin()) {
	add_action('admin_menu', 'OEPL_WPUserToCRM_create_menu');
}

## Create CRM setting menu - START
function OEPL_WPUserToCRM_create_menu() {
	add_menu_page('User To CRM','User to CRM Setting', 'administrator', 'User_To_CRM', 'OEPL_WPUserToCRM_Settings', $icon_url = '', $position = null );
	add_submenu_page( 'User_To_CRM', 'WPUserToCRM Contact Module', 'Contact Module', 'manage_options', 'User_To_CRM_mapping_table', 'OEPL_WPUserToCRM_mapping_table');
}
## Create CRM setting menu - END

## Display CRM setting page  - START
function OEPL_WPUserToCRM_Settings() {
	require_once(WPUSERTOCRM_PLUGIN_DIR . 'OEPL_WPUserToCRM_Settings.php');
}
## Display CRM setting page  - END

### Save WP new user details in CRM code START
function OEPL_WP_User_To_CRM_created_user( $user_id) {
	global $objUserToCRM;
	
	if ($objUserToCRM->SugarSessID === '') {
		$sugar_id = $objUserToCRM->WPUserToCRM_LoginToSugar();
	}
	
	if (!strlen($sugar_id)>10) {
		return false;	
	}
	$response = OEPLSaveWPUserCRMUserDetails($sugar_id,$user_id);
	if (isset($response['status']) && $response['status'] === 'confirm') {
		return false;
	}
}
### Save WP new user details in CRM code END

### WP edited User details to CRM submit code START ###
add_action( 'show_user_profile', 'OEPL_WPUserToCRM_extra_profile_fields' );
add_action( 'edit_user_profile', 'OEPL_WPUserToCRM_extra_profile_fields' );
function OEPL_WPUserToCRM_extra_profile_fields( $user ) {
	global $wpdb;
	
	add_action( 'admin_footer', 'OEPL_WPUserToCrm_footer' );

	$user_id = '';
	if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
		$user_id = $_GET['user_id'];
	}
	
	if (empty($user_id)) {
		$user_id = 1;
	}
	
	$sql = $wpdb->prepare("SELECT user_id FROM ".$wpdb->prefix."usermeta WHERE meta_key ='%s' AND user_id='%s'", 'OEPL_CRM_CONTACT_ID', $user_id);
	$chk_meta_key = $wpdb->get_results($sql);
	
	$ch_user_id = '';
	if (isset($chk_meta_key[0])) {
		$ch_user_id = $chk_meta_key[0]->user_id; 
	}
	
	if ($user_id === $ch_user_id ) {
		$submit_to_crm_btn_arr = array( 
			'input' => array('class' => array("button button-primary submit_to_crm"), 'id' => array("Update_to_CRM"), 'name' => array('Update_to_CRM'), 'type' => array('button'), 'value' => array('Update to CRM'))
		);	
		
		$submit_to_crm_btn = '<input type="button" name="Update_to_CRM" id="Update_to_CRM" class="button button-primary submit_to_crm" value="Update to CRM">';
		echo wp_kses( $submit_to_crm_btn, $submit_to_crm_btn_arr );
	
	} else {

		$submit_to_crm_btn_arr = array( 
			'input' => array('class' => array("button button-primary submit_to_crm"), 'id' => array("Submit_to_CRM"), 'name' => array('Submit_to_CRM'), 'type' => array('button'), 'value' => array('Submit to CRM'))
		);	
		
		$submit_to_crm_btn = '<input type="button" name="Submit_to_CRM" id="Submit_to_CRM" class="button button-primary submit_to_crm" value="Submit to CRM">';
		echo wp_kses( $submit_to_crm_btn, $submit_to_crm_btn_arr );
	}

	$dialog_div_arr = array( 
		'div' => array('class' => array("dialog1", "dialog2"))
	);
	
	$dialog_div = '<div class="dialog1"></div>';	// Dialog display div
	$dialog_div .= '<div class="dialog2"></div>';	// Dialog display div
	echo wp_kses( $dialog_div, $dialog_div_arr );

	OEPL_WPUserToCRM_field_placement_js($user_id);	// Load js code
}

## Load js and CSS - START
function OEPL_WPUserToCRM_field_placement_js($user_id) { 
		
	wp_register_style( 'oepl_admin_css', WPUSERTOCRM_PLUGIN_URL.'style/style.css', false, '1.0.0' );
	wp_enqueue_style( 'oepl_admin_css' );

	wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_style (  'wp-jquery-ui-dialog' );
	
	wp_register_script( 'sweetalert_script', WPUSERTOCRM_PLUGIN_URL . 'js/sweetalert2.min.js', array(), false, true );
	wp_enqueue_script( 'sweetalert_script');

	wp_register_script( 'oepl_user_script', WPUSERTOCRM_PLUGIN_URL . 'js/OEPL_users.js', array( 'jquery' ), false, true );
	wp_enqueue_script( 'oepl_user_script');

	wp_localize_script( 'oepl_user_script', 'objusertocrm',
		array( 
			'user_id' => $user_id,
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		)
	);
	
}
### WP User to CRM submit user detail code END ###

## footer code START
if(isset($_REQUEST['page']) && ($_REQUEST['page'] === 'User_To_CRM' || $_REQUEST['page'] === 'User_To_CRM_mapping_table')) {
	add_action( 'admin_footer', 'OEPL_WPUserToCrm_footer' );
}
function OEPL_WPUserToCrm_footer() {
	$arr = array( 
		'br' => array('clear' => array("all")), 
		'section' => array('class' => array("oe-loader-section")), 
		'div' => array('class' => array("oe-loading-section-title", "oe-loader-icon")),
		'img' => array(
			'src' => WPUSERTOCRM_PLUGIN_URL.'images/loader.svg',
			'alt' => "Offshore Evolution loader"
		),
	);

	$str = '<br clear="all" />
		<section class="oe-loader-section">
			<div class="oe-loading-section-title">
				<div class="oe-loader-icon">
					<img src="'.WPUSERTOCRM_PLUGIN_URL.'images/loader.svg" alt="Offshore Evolution loader">
				</div>
			</div>
		</section>';
	echo wp_kses( $str, $arr );

	$divarr = array( 
		'div' => array('id' => array("flydialog"), 'title' => array("Submit To CRM"))
	);
	$divstr = '<div id="flydialog" title="Submit To CRM"></div>';
	echo wp_kses( $divstr, $divarr );

}
//END