<?php
/*
Plugin Name: Users to CRM Contacts
Description: Plugin is submitting Users to CRM Contacts module.
Version: 1.5
Author: Offshore Evolution Pvt Ltd
Author URI: https://offshoreevolution.com/
License: GPL
*/
require_once("OEPL_UserToCRM.conf.php");

/* START of all plugin hook */
register_activation_hook(__FILE__, 'WPUserToCRM_Activation');
/* END of all hook  */

function WPUserToCRM_Activation() {
     $Insrt = new OEPL_WPUserToCRMClass;
     $Insrt->WPUserToCRM_Activation();
}