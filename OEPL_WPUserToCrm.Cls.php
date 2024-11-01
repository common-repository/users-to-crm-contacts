<?php
if ( isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    exit(esc_html__('Please don\'t access this file directly.', 'WPUSERTOCRM'));
}

class OEPL_WPUserToCRMClass {

	function __construct() {
		$this->SugarURL = '';
		$this->SugarUser = '';
		$this->SugarPass = '';
		$this->SugarClient = '';
		$this->SugarSessID = '';
		$this->SugarSessData = '';
		$this->ModuleList = array('Contacts');
		$this->ExcludeFields = array('id', 'date_entered', 'date_modified', 'modified_user_id', 'modified_by_name', 'created_by', 'created_by_name', 'deleted', 'assigned_user_id', 'assigned_user_name', 'team_id', 'team_set_id', 'team_count', 'team_name', 'email_addresses_non_primary', 'account_description', 'opportunity_name', 'opportunity_amount', 'email2', 'invalid_email', 'email_opt_out', 'webtolead_email1', 'webtolead_email2', 'webtolead_email_opt_out', 'webtolead_invalid_email', 'email', 'full_name', 'reports_to_id', 'report_to_name', 'contact_id', 'account_id', 'opportunity_id', 'refered_by', 'c_accept_status_fields', 'm_accept_status_fields','lead_remote_ip_c');
		$this -> ExcludeFieldTypes = array();
	}
	
	## Create CRM mapping table when plugin active 
	function WPUserToCRM_Activation() {
		global $wpdb;
		$sql = "CREATE TABLE IF NOT EXISTS `".WPUSERTOCRM_MAP_FIELD."` (
				  `pid` int(11) NOT NULL AUTO_INCREMENT,
				  `module` varchar(100) NOT NULL,
				  `field_type` enum('text','select','radio','checkbox','textarea','file','filler') NOT NULL DEFAULT 'text',
				  `data_type` varchar(50) NOT NULL,
				  `field_name` varchar(255) NOT NULL,
				  `field_value` text NOT NULL,
				  `wp_meta_key` varchar(150) NOT NULL,
				  `wp_meta_label` varchar(200) NOT NULL,
				  `wp_custom_label` varchar(50) NOT NULL,
				  `display_order` int(11) NOT NULL,
			      `wp_user_meta_fields` varchar(255) NOT NULL,
				  `required` enum('Y','N') NOT NULL DEFAULT 'N',
				  `hidden` enum('Y','N') NOT NULL DEFAULT 'N',
				  `is_show` enum('Y','N') NOT NULL DEFAULT 'N',
				  `show_column` enum('1','2') NOT NULL DEFAULT '1',
				  PRIMARY KEY (`pid`)
				) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";
		$wpdb->query($sql);
	}
	
	## CRM login method
	function WPUserToCRM_LoginToSugar() {
		if ( !empty( $this->SugarUser ) && !empty( $this->SugarPass ) && !empty( $this->SugarURL ) ) {
			$login_parameters = array(	 "user_auth" =>
												array(  
													"user_name"	=> $this->SugarUser,
													"password"	=> $this->SugarPass,
													"version"		=> "1"
												),
										"application_name"	=>	"RestTest",
										"name_value_list"	=>	array()
									);
			$this->SugarSessData = $this->WPUserToCRM_SugarCall("login", $login_parameters, $this->SugarURL);

			if (isset($this->SugarSessData->id)){
				$this->SugarSessID = $this->SugarSessData->id;
				return $this->SugarSessID;
			}
		}
		return false;
	}

	## CRM logout method
	function WPUserToCRM_LogoutToSugar() {
		$login_parameters = array(
									"user_auth" => array(
													"user_name" => $this->SugarUser, 
													"password" => md5($this->SugarPass), 
													"version" => "1"
												), 
									"application_name" => "RestTest", 
									"name_value_list" => array(), 
							);
		$this->WPUserToCRM_SugarCall("logout", $login_parameters, $this -> SugarURL);
	}

	## CURL calling method
	function WPUserToCRM_SugarCall($method, $parameters, $url) {
		$headers = array();
		
		$jsonEncodedData = json_encode($parameters);
		 
 		$postArray = array("method" => $method, "input_type" => "JSON", "response_type" => "JSON", "rest_data" => $jsonEncodedData);
		
		$args = array(	'method' 		=> 'POST',
			  			'timeout' 		=> 45,
						'redirection' 	=> 5,
						'httpversion' 	=> '1.0',
						'blocking' 		=> true,
						'headers' 		=> $headers,
						'body' 			=> $postArray,
						'cookies' 		=> array(),
				);
		$response = wp_remote_post( $url, $args );
		
		if(isset($response->errors) && isset($response->errors['http_request_failed']) && ($response->errors['http_request_failed']['0']) && !empty($response->errors['http_request_failed']['0'])){
			$response = $response->errors;
		} else {	
			$response = json_decode($response['body']);
		}
		
		return $response;
	}
	
	## Get Contact fields
	function WPUserToCRM_getContactFieldsList() {
		$result = (object)array();

		if($this->SugarSessID === '') {
			$this->WPUserToCRM_LoginToSugar();
		}
		
		if ($this->SugarSessID) {
			$set_entry_parameters = array(	 "session" 		=> $this->SugarSessID,
											 "module_name"	=> "Contacts"
										 );
			$result = $this->WPUserToCRM_SugarCall("get_module_fields", $set_entry_parameters, $this->SugarURL);
		}
		return $result;
	}
}