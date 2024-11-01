<?php
if ( isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    exit(esc_html__('Please don\'t access this file directly.', 'WPUSERTOCRM'));
}

## Save sugarCRM config START
add_action('wp_ajax_OEPL_WPUserToCRM_Test_and_Save_Changes', 'OEPL_WPUserToCRM_Test_and_Save_Changes');
function OEPL_WPUserToCRM_Test_and_Save_Changes() {
	global $objUserToCRM;
	$my_nonce = (isset($_POST['security']) ? $_POST['security'] : '');
	if ( wp_verify_nonce( $my_nonce) && current_user_can( 'administrator' ) ) {
		$sugarurl = (isset($_POST['sugarurl']) ? $_POST['sugarurl'] : '');
		$sugaruser = (isset($_POST['sugaruser']) ? $_POST['sugaruser'] : '');
		$sugarpass = (isset($_POST['sugarpass']) ? md5($_POST['sugarpass']) : '');
		
		$objUserToCRM->SugarURL  = $sugarurl;
		$objUserToCRM->SugarUser = $sugaruser;
		$objUserToCRM->SugarPass = $sugarpass; 
		
		$sugar_id = $objUserToCRM->WPUserToCRM_LoginToSugar();
		
		if (strlen($sugar_id) > 10) {
			update_option('OEPL_WP_USER_TO_CRM_SUGARCRM_URL' , sanitize_text_field($sugarurl));
			update_option('OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_USER' , sanitize_user($sugaruser));
			update_option('OEPL_WP_USER_TO_CRM_SUGARCRM_ADMIN_PASS' , sanitize_text_field($sugarpass));
			
			$response['status'] = 'Y';
			$response['message'] = __('SugarCRM credentials saved successfully', 'WPUSERTOCRM');
		} else {
			$response['status'] = 'N';
			$response['message'] = __('Invalid SugarCRM credentials. Please try again', 'WPUSERTOCRM');
		}
	} else {
		$response['message'] = __('Security check', 'WPUSERTOCRM');
	}
	wp_send_json($response);
}
## Save sugarCRM config END

## Save sugarCRM config START
add_action('wp_ajax_OEPL_WPUserToCRMContactsFieldSync', 'OEPL_WPUserToCRMContactsFieldSync');
function OEPL_WPUserToCRMContactsFieldSync() {
	global $objUserToCRM;
	
	$sugar_id = $objUserToCRM->WPUserToCRM_LoginToSugar();
	if (!strlen($sugar_id) > 10) {
		$response['status'] = 'N';
		$response['message'] = __('Error occured while synchronizing fields. Please try again.', 'WPUSERTOCRM');
	} else {
		WPUserToCRMContactsFieldSynchronize();
		$response['status'] = 'Y';
		$response['message'] = __('Fields synchronized successfully', 'WPUSERTOCRM');
	}
	wp_send_json($response);
}

## CRM field synchronization - START
function WPUserToCRMContactsFieldSynchronize() {
	global $objUserToCRM, $wpdb;

	$sugar_id = '';
	if ($objUserToCRM->SugarSessID === '') {
		$sugar_id = $objUserToCRM->WPUserToCRM_LoginToSugar();
	}

	if (!strlen($sugar_id)>10) {
		return false;	
	}

	## Start - Set Module Fields in Table
	if (count($objUserToCRM->ModuleList) > 0) {
		foreach ($objUserToCRM->ModuleList as $key => $val) {
			$ModuleName = $val;
			$ModuleFileds = $objUserToCRM->WPUserToCRM_getContactFieldsList();
			$SugarFlds = array();
			
			$count = count((array)$ModuleFileds->module_fields);
			if (is_object($ModuleFileds->module_fields)) {
				$count = count((array)$ModuleFileds->module_fields);
			}

			if (!empty($ModuleFileds->module_fields) && $count > 0) {
				foreach ($ModuleFileds->module_fields as $fkey => $fval) {
					$fType = $fval->type;
					$insAry = array();
					switch($fType) {
						case 'enum':
							$insAry['field_type']  = 'select';
							$insAry['field_value'] = serialize($fval->options);
							break;
						case 'radioenum':
							$insAry['field_type']  = 'radio';
							$insAry['field_value'] = serialize($fval->options);
							break;
						case 'bool':
							$insAry['field_type']  = 'checkbox';
							$insAry['field_value'] = serialize($fval->options);
							break;
						case 'text':
							$insAry['field_type'] 	= 'textarea';
							$insAry['field_value'] 	= '';
							break;
						case 'file':
							$insAry['field_type'] 	= 'file';
							$insAry['field_value'] 	= '';
							break;
						default:
							$insAry['field_type']  = 'text';
							$insAry['field_value'] = '';
							break;
					}
					$insAry['module'] 		 = $ModuleName;
					$insAry['field_name'] 	 = $fkey;
					$insAry['wp_meta_key'] 	 = WPUSERTOCRM_METAKEY_EXT . strtolower($ModuleName). '_' . $fkey;
					$insAry['wp_meta_label'] = $fval->label;
					$insAry['data_type'] 	 = $fval->type;
					$insAry['wp_meta_label'] = str_replace(':','', trim($insAry['wp_meta_label']) );
					
					$query = $wpdb->prepare("SELECT count(*) as tot FROM ".WPUSERTOCRM_MAP_FIELD." 
					WHERE module ='%s' AND field_name='%s'", $insAry['module'], $insAry['field_name']);
					$RecCount = $wpdb->get_results($query, ARRAY_A);
					
					if (!in_array($insAry['field_name'], $objUserToCRM->ExcludeFields)) {
						$SugarFlds[] = $insAry['field_name'];
						
						if ( $RecCount[0]['tot'] <= 0 ) {
							$insArray = array(
								'module' 	 => sanitize_text_field($insAry['module']), 
								'field_type' => sanitize_text_field($insAry['field_type']), 
								'data_type'  => sanitize_text_field($insAry['data_type']), 
								'field_name' => sanitize_text_field($insAry['field_name']), 
								'field_value'=> sanitize_text_field(str_replace("'", "\'", $insAry['field_value'])), 
								'wp_meta_label' => sanitize_text_field($insAry['wp_meta_label']) , 
								'wp_meta_key'   => sanitize_text_field($insAry['wp_meta_key']) 
							);
							$wpdb->insert(WPUSERTOCRM_MAP_FIELD, $insArray);
							
							## Update wp_user_meta_fields email address default selected email code START
							$where = array('field_name' => 'email1');
							$updArray = array(
								'wp_user_meta_fields' 	=> 'user_email',
								'is_show' 	=> 'Y'
							);
							$wpdb->update(WPUSERTOCRM_MAP_FIELD, $updArray, $where);
							## END
							
						} else {
							$where = array('module' => sanitize_text_field($insAry['module']), 'field_name' => sanitize_text_field($insAry['field_name']));

							$updArray = array(
								'module' 		=> sanitize_text_field($insAry['module']), 
								'field_type' 	=> sanitize_text_field($insAry['field_type']), 
								'data_type' 	=> sanitize_text_field($insAry['data_type']), 
								'field_name' 	=> sanitize_text_field($insAry['field_name']), 
								'field_value'   => sanitize_text_field(str_replace("'", "\'", $insAry['field_value'])), 
								'wp_meta_label' => sanitize_text_field($insAry['wp_meta_label']), 
								'wp_meta_key'   => sanitize_text_field($insAry['wp_meta_key']) 
							);
							$wpdb->update(WPUSERTOCRM_MAP_FIELD, $updArray, $where);
						}
					}
				}
			}

			$query = $wpdb->prepare("SELECT pid, field_name, wp_meta_key FROM ".WPUSERTOCRM_MAP_FIELD." WHERE module ='%s'", $ModuleName);
			$WPFieldsRS = $wpdb->get_results($query, ARRAY_A);
			$fcnt = count($WPFieldsRS);
			for ($i=0; $i<$fcnt; $i++) {
				if (!in_array($WPFieldsRS[$i]['field_name'], $SugarFlds)) {

					$delSql = $wpdb->prepare("DELETE FROM ".WPUSERTOCRM_MAP_FIELD." WHERE pid ='%s' AND module='%s'", $WPFieldsRS[$i]['pid'], $ModuleName);
					$wpdb->query($delSql);
				}
			}
		}
	}
	## End - Set Module Fields in Table
}
## CRM field synchronization - END

## Submenu under SugarCRM Menu START
function OEPL_WPUserToCRM_mapping_table() {
	## Add js and stylesheet code START
	wp_register_style( 'oepl_mapping_css', WPUSERTOCRM_PLUGIN_URL.'style/style.css', false, '1.0.0' );
	wp_enqueue_style( 'oepl_mapping_css' );	

	wp_register_script('oepl_mapping_script', WPUSERTOCRM_PLUGIN_URL.'js/field_list.js',array('jquery'), false, true);
	wp_enqueue_script('oepl_mapping_script');

	wp_localize_script( 'oepl_mapping_script', 'objusertocrm',
		array( 
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		)
	);

	## Add js and stylesheet code END
?>
	<div class='wrap'>
		<div class="mapping_tbl">
			<h1>
				<?php esc_html_e('SugarCRM Contacts module field list', 'WPUSERTOCRM'); ?>
			</h1>
		</div>
		<br clear="all">
		<div class="OEPL_Sugar_ErrMsg">
			<?php esc_html_e('This is Error Message.', 'WPUSERTOCRM'); ?>
		</div>
		<div class="OEPL_Sugar_SuccessMsg">
			<?php esc_html_e('This is success message.', 'WPUSERTOCRM'); ?>
		</div>
		
		<?php	  
		echo '<form id="OEPL-Leads_table" method="post">';
		require_once(WPUSERTOCRM_PLUGIN_DIR . 'OEPL_WPUserToCRM_Field_map_table.php');
		$table = new OEPL_WPUserToCRM_Field_map_table;
		echo '<input type="hidden" name="page" value="User_To_CRM_mapping_table" />';
		$table->search_box('Search', 'ContactSearchID');
		$table->prepare_items();
		$table->display();
		echo '</form>';
		?>
	</div>
	<?php
}
##Submenu under SugarCRM Menu END

## WPUserToCRM Contact grid status - START
add_action('wp_ajax_OEPL_WPUserToCRM_Contact_grid_status','OEPL_WPUserToCRM_Contact_grid_status');
function OEPL_WPUserToCRM_Contact_grid_status() {
	global $objUserToCRM, $wpdb;
	
	if ($objUserToCRM->SugarSessID === '') {
		$sugar_id = $objUserToCRM->WPUserToCRM_LoginToSugar();
	}
	
	if (!strlen($sugar_id) > 10 || $sugar_id === '') {
		return false;	
	}
	
	$action = (isset($_POST['OEPL_Action']) ? $_POST['OEPL_Action'] : '');
	$updData = array();
	$flag = FALSE;
	if ($action === 'OEPL_Change_Status') {
		$flag = TRUE;

		$sql = $wpdb->prepare("SELECT is_show FROM ".WPUSERTOCRM_MAP_FIELD." WHERE pid ='%s'", (int)$_POST['pid']);
		$status = $wpdb->get_row($sql, ARRAY_A);
		if(isset($status['is_show']) && !empty($status)) {
			if($status['is_show'] === 'Y') {
				$updData['is_show'] = 'N';
			} else {
				$updData['is_show'] = 'Y';
			}
		}
	}

	$response = array();
	if ($flag && isset($updData) && !empty($updData)) {
		$upd = $wpdb->update( WPUSERTOCRM_MAP_FIELD, $updData, array('pid' => (int)$_POST['pid']));
		if($upd != FALSE && $upd>0) {
			$response['message'] = __('Field Updated Successfully', 'WPUSERTOCRM');
		} else {
			$response['message'] = __('Error While updating field. Please try again', 'WPUSERTOCRM');
		}
	} else {
		$response['message'] = __('Error While updating field. Please try again', 'WPUSERTOCRM');
	}
	wp_send_json($response);
}
## WPUserToCRM Contact grid status - END

### syncronize WP user meta fields To CRM Fields code START ###
add_action('wp_ajax_OEPL_WPUserToCRM_save_custom_meta','OEPL_WPUserToCRM_save_custom_meta');
function OEPL_WPUserToCRM_save_custom_meta() {
	global $wpdb;
	$upd = '';
	$meta_field = (isset($_POST['meta_field']) ? $_POST['meta_field'] : '');
	$pid = (isset($_POST['pid']) ? (int)($_POST['pid']) : '');
	if (!empty($pid)) {
		$upd = $wpdb->update( WPUSERTOCRM_MAP_FIELD, array('wp_user_meta_fields' => $meta_field), array('pid' => $pid));
	}
	
	$response = array();
	if ($upd != FALSE && $upd > 0) {
		$response['status'] = 'Y';
		$response['message'] = __('WP User Meta Fields changed succesfully', 'WPUSERTOCRM');
	} else {
		$response['status'] = 'N';
		$response['message'] = __('Error occured while saving WP User Meta Fields. Please try again', 'WPUSERTOCRM');
	}
	wp_send_json($response);
}
### END ###

### Save WP user meta fields in CRM contact module code START ### 
add_action('wp_ajax_OEPL_SaveWPUserToCRM_Contactsmodule','OEPL_SaveWPUserToCRM_Contactsmodule');
function OEPL_SaveWPUserToCRM_Contactsmodule() {
	global $objUserToCRM;
	
	if ($objUserToCRM->SugarSessID === '') {
		$sugar_id = $objUserToCRM->WPUserToCRM_LoginToSugar();
	}
	
	if (!strlen($sugar_id) > 10 || $sugar_id === '') {
		$response['message'] = __('CRM Settings required in WP-User-CRM-Contacts.', 'WPUSERTOCRM');
	} else {
		$user_id = sanitize_text_field($_POST['user_id']);
		if ($user_id === '') {
			$user_id = 1;
		}
		$response = OEPLSaveWPUserCRMUserDetails($sugar_id,$user_id);	
	}
	wp_send_json($response);
}
### END ###

## Creat New WP User To CRM Contacts - START
add_action('wp_ajax_OEPLCreatNewWPUserToCRMContacts','OEPLCreatNewWPUserToCRMContacts');
function OEPLCreatNewWPUserToCRMContacts() {
	global $objUserToCRM;
	
	if ($objUserToCRM->SugarSessID === '') {
		$sugar_id = $objUserToCRM->WPUserToCRM_LoginToSugar();
	}

	if (!strlen($sugar_id) > 10) {
		return false;	
	}

	$user_id = sanitize_text_field((isset($_POST['user_id']) ? $_POST['user_id'] : ''));
	if ($user_id === '') {
		$user_id = 1;
	}
	
	$contact_filed_arr = OEPLCRMFieldArr($user_id);
	$name_value_list = $contact_filed_arr['contact_filed_arr'];
	
	if ($sugar_id != '') {
		$ContactRslt = OEPLCRMSetEntry($sugar_id, $name_value_list);
		$response = array();
		add_user_meta($user_id, 'OEPL_CRM_CONTACT_ID', $ContactRslt->id);
		$response['status'] = 'Y';
		$response['message'] = __('User detail submitted successfully.', 'WPUSERTOCRM');
	}
	wp_send_json($response);
}
## Creat New WP User To CRM Contacts - END

## Update Existing WP User To CRM Contacts - START
add_action('wp_ajax_OEPLUpdateExistingWPUserToCRMContacts','OEPLUpdateExistingWPUserToCRMContacts');
function OEPLUpdateExistingWPUserToCRMContacts() {
	global $objUserToCRM;
	if ($objUserToCRM->SugarSessID === '') {
		$sugar_id = $objUserToCRM->WPUserToCRM_LoginToSugar();
	}

	if (!strlen($sugar_id) >10 ) {
		return false;	
	}

	$user_id = sanitize_text_field((isset($_POST['user_id']) ? $_POST['user_id'] : ''));
	if ($user_id === '') {
		$user_id = 1;
	}

	$name_value_list = array();
	$contact_filed_arr = OEPLCRMFieldArr($user_id);
	$name_value_list = (isset($contact_filed_arr['contact_filed_arr']) ? $contact_filed_arr['contact_filed_arr'] : '');
	$email = (isset($contact_filed_arr['email']) ? $contact_filed_arr['email'] : '');
	if ($sugar_id != '') {
		$get_entry_list_parameters = array(
	                        'session' => $sugar_id,
	                        'module_name' => 'Contacts',
	                        'query' => " contacts.id IN ( SELECT eabr.bean_id FROM email_addr_bean_rel eabr, email_addresses ea WHERE ea.id = eabr.email_address_id AND eabr.deleted = 0 AND eabr.primary_address = 1 AND ea.deleted = 0 AND eabr.bean_module = 'Contacts' AND ea.email_address LIKE '".$email."' ) ", 
	                        'order_by' => "",
	                        'offset' => '0',
	                        'select_fields' => array(),
	                        'link_name_to_fields_array' => array(),
	                        'max_results' => '100',
	                        'deleted' => '0',
	                        'Favorites' => false,
	                    );
	    $contcatemail = $objUserToCRM->WPUserToCRM_SugarCall("get_entry_list", $get_entry_list_parameters, $objUserToCRM->SugarURL);
		
		$crm_rec_id = $contcatemail->entry_list[0]->id;
		$name_value_list[] = array('name' => 'id', 'value' => $crm_rec_id);
		$ContactRslt = OEPLCRMSetEntry($sugar_id,$name_value_list);
		
		$response = array();
		add_user_meta($user_id, 'OEPL_CRM_CONTACT_ID', $ContactRslt->id);
		$response['status'] = 'Y';
		$response['message'] = __('User detail submitted successfully.', 'WPUSERTOCRM');
	}
	wp_send_json($response);
}
## Update Existing WP User To CRM Contacts - END

## Create contact field mapping array for save in CRM - START
function OEPLCRMFieldArr($user_id){
	global $wpdb, $contact_filed_arr;
	
	$sql = "SELECT A.meta_key, A.meta_value, B.user_login, B.user_email, B.user_url
				FROM 
					".$wpdb->prefix."usermeta AS A
				INNER JOIN 
					".$wpdb->prefix."users AS B
				ON 
					 A.user_id=B.ID
				AND 
					user_id=".$user_id." ";
	$user_meta_data = $wpdb->get_results($sql, ARRAY_A);
	
	$sql = $wpdb->prepare("SELECT field_name,wp_user_meta_fields FROM ".WPUSERTOCRM_MAP_FIELD." WHERE is_show ='%s'", 'Y');
	$get_sync_meta_fields = $wpdb->get_results($sql, ARRAY_A);
	$email = '';
	$contact_filed_arr = array();
	if (count($get_sync_meta_fields) > 0) {
		foreach ($get_sync_meta_fields as $key => $sync_meta_fields_val) {
			$filedmetakey = $sync_meta_fields_val['wp_user_meta_fields'];
			if (count($user_meta_data) > 0) {
				foreach ($user_meta_data as $key => $user_meta_field_data)  {
					if($filedmetakey == $user_meta_field_data['meta_key']) {
						$contact_filed_arr[] = array(
							'name' => $sync_meta_fields_val['field_name'], 
							'value' =>$user_meta_field_data['meta_value']
						);
					
					} else if ($filedmetakey === 'user_login') {
						
						$contact_filed_arr[] = array(
							'name' => $sync_meta_fields_val['field_name'], 
							'value' =>$user_meta_field_data['user_login']
						);
						break;
					
					} else if ($filedmetakey === 'user_email') {
						
						$email = $user_meta_field_data['user_email'];
						$contact_filed_arr[] = array(
							'name' => $sync_meta_fields_val['field_name'], 
							'value' =>$user_meta_field_data['user_email']
						);
						break;
							
					} else if ($filedmetakey === 'user_url') {
						$contact_filed_arr[] = array(
							'name' => $sync_meta_fields_val['field_name'], 
							'value' =>$user_meta_field_data['user_url']
						);
						break;
					}
				}
			}
		}
	}
	$CRMFieldarr = array();
	$CRMFieldarr['contact_filed_arr'] = $contact_filed_arr;
	$CRMFieldarr['email'] = $email;
	return $CRMFieldarr;
}
## Create contact field mapping array for save in CRM - END

## Save WP user to CRM contact - START
function OEPLCRMSetEntry($sugar_id, $name_value_list) {
	global $objUserToCRM;

	$set_entry_parameters = array(	"session" => $sugar_id, 
        	                        "module_name" => "Contacts", 
	        	                    "name_value_list" => $name_value_list,
	                            );
								  
	$ContactRslt = $objUserToCRM->WPUserToCRM_SugarCall("set_entry", $set_entry_parameters, $objUserToCRM->SugarURL);
	return $ContactRslt;
}
## Save WP user to CRM contact - END

## Save WP user detail into CRM contact  - START
function OEPLSaveWPUserCRMUserDetails($sugar_id,$user_id) {
	global $objUserToCRM, $wpdb;
	
	$name_value_list = array();
	$contact_filed_arr = OEPLCRMFieldArr($user_id);
	$name_value_list = (isset($contact_filed_arr['contact_filed_arr']) ? $contact_filed_arr['contact_filed_arr'] : '');
	$email = (isset($contact_filed_arr['email']) ? $contact_filed_arr['email'] : '');
	$contcatemail = '';
	
	if ($sugar_id != '') {
		$sql = $wpdb->prepare("SELECT meta_value FROM ".$wpdb->prefix."usermeta WHERE meta_key ='%s' AND user_id='%s'", 'OEPL_CRM_CONTACT_ID', $user_id);
		$CRM_record = $wpdb->get_results($sql);
		
		$recod_id = '';
		if (!empty($CRM_record) && count($CRM_record) > 0) {
			$recod_id = $CRM_record[0]->meta_value;
		}

		if (empty($recod_id)) {
			$get_entry_list_parameters = array(
	                        'session' => $sugar_id,
	                        'module_name' => 'Contacts',
	                        'query' => " contacts.id IN ( SELECT eabr.bean_id FROM email_addr_bean_rel eabr, email_addresses ea WHERE ea.id = eabr.email_address_id AND eabr.deleted = 0 AND eabr.primary_address = 1 AND ea.deleted = 0 AND eabr.bean_module = 'Contacts' AND ea.email_address LIKE '".$email."' ) ", 
	                        'order_by' => "",
	                        'offset' => '0',
	                        'select_fields' => array(),
	                        'link_name_to_fields_array' => array(),
	                        'max_results' => '100',
	                        'deleted' => '0',
	                        'Favorites' => false,
	                    );
	    	$contcatemail = $objUserToCRM->WPUserToCRM_SugarCall("get_entry_list", $get_entry_list_parameters, $objUserToCRM->SugarURL);
		}
	
		$response= array();
		if (!empty($contcatemail->total_count) && $contcatemail->total_count === '1' && empty($recod_id)) {
			$response['status'] =  'confirm';
			$response['message'] = __('This E-mail ID is already exist.', 'WPUSERTOCRM');
			return $response;
			exit;
		
		} else if (!empty($recod_id)) {
				$name_value_list[] = array('name' => 'id', 'value' => $recod_id);
				$ContactRslt = OEPLCRMSetEntry($sugar_id,$name_value_list);
				
		} else if (!empty($contcatemail->total_count) && $contcatemail->total_count > 1 && empty($recod_id)) {
			$radiobtn = array();
			foreach ($contcatemail->entry_list as $key => $contcatemaildata) {
				$radiobtn[$contcatemaildata->id] = $contcatemaildata->name_value_list->name->value;
			}
			$response['status'] = 'record selection';
			$response['duplicate_contact'] = $radiobtn;  
			$response['message'] = __('Please select user to update.', 'WPUSERTOCRM');;
			return $response;
			exit;
		} else {
			$ContactRslt = OEPLCRMSetEntry($sugar_id,$name_value_list);
		}
		
		if ($ContactRslt->id != '') {
			if (!empty($recod_id)) {
				update_user_meta($user_id, 'OEPL_CRM_CONTACT_ID', $ContactRslt->id);
				$response['status'] = 'Y';
				$response['message'] = __('User detail updated successfully.', 'WPUSERTOCRM');;
			} else {
				add_user_meta($user_id, 'OEPL_CRM_CONTACT_ID', $ContactRslt->id);
				$response['status'] = 'Y';
				$response['message'] = __('User detail submitted successfully.', 'WPUSERTOCRM');
			}
		}
	}
	return $response;
	exit;
}
## Save WP user detail into CRM contact  - END

## Save WP user detail into CRM contact  - START
add_action('wp_ajax_OEPLSaveUserToCRMContacts','OEPLSaveUserToCRMContacts');
function OEPLSaveUserToCRMContacts() {
	global $objUserToCRM;
	
	if($objUserToCRM->SugarSessID === ''){
		$sugar_id = $objUserToCRM->WPUserToCRM_LoginToSugar();
	}
	
	if (!strlen($sugar_id) > 10) {
		return false;	
	}

	$user_id = sanitize_text_field((isset($_POST['user_id']) ? $_POST['user_id'] : ''));
	$record_id = sanitize_text_field((isset($_POST['record_id']) ? $_POST['record_id'] : ''));
	
	if ($user_id === '') {
		$user_id = 1;
	}
	$name_value_list = array();
	$contact_filed_arr = OEPLCRMFieldArr($user_id);
	$name_value_list = (isset($contact_filed_arr['contact_filed_arr']) ? $contact_filed_arr['contact_filed_arr'] : '');
	
	if ($sugar_id != '') {
		$name_value_list[] = array('name' => 'id', 'value' => $record_id);
		$ContactRslt = OEPLCRMSetEntry($sugar_id,$name_value_list);
		$response = array();
		add_user_meta($user_id, 'OEPL_CRM_CONTACT_ID', $ContactRslt->id);
		$response['status'] = 'Y';
		$response['message'] = __('User detail submitted successfully.', 'WPUSERTOCRM');
	}
	wp_send_json($response);
}
## Save WP user detail into CRM contact  - START