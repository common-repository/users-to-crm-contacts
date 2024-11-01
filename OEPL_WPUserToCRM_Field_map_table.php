<?php
if ( isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
	exit(esc_html__('Please don\'t access this file directly.', 'WPUSERTOCRM'));
}

if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class OEPL_WPUserToCRM_Field_map_table extends WP_List_Table {
    function __construct(){
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'Contact',     //singular name of the listed records
            'plural'    => 'Contact',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
    }

    function column_default($item, $column_name){
        switch($column_name){
            case 'field_name':
				return (isset($item['wp_meta_label']) ? $item['wp_meta_label'] : '');
		            
            case 'is_show':
				$isshow = '';
				
				if($item['is_show'] === 'Y')
					$isshow = "checked='checked'";
				
				return '<input type="checkbox" id="OEPL_Contact_grid_status'.$item['pid'].'" data-action="OEPL_Change_Status" data-pid='.$item['pid'].' class="OEPL_is_show OEPL_Contact_grid_status" value="1" '.$isshow.'>';
				
            case 'wp_user_meta_fields':
				global $wpdb;
				$sql = "SELECT Distinct(meta_key) as meta_key
						FROM
							".$wpdb->prefix."usermeta
						WHERE
							meta_key NOT IN ('rich_editing','comment_shortcuts','admin_color','use_ssl','show_admin_bar_front','wp_capabilities','wp_user_level','dismissed_wp_pointers','show_welcome_panel','session_tokens','wp_dashboard_quick_press_last_post_id','wp_user-settings','wp_user-settings-time','managenav-menuscolumnshidden','metaboxhidden_nav-menus','nav_menu_recently_edited','closedpostboxes_dashboard','metaboxhidden_dashboard','last_update') ORDER BY meta_key";
				$metadata = $wpdb->get_results($sql);
				$meta_fields = array();
				foreach ($metadata as $metadataval) {
					$meta_fields[$metadataval->meta_key] = $metadataval->meta_key;
				}
				$meta_fields['username'] = 'user_login';
				$meta_fields['email'] = 'user_email';
				$meta_fields['url'] = 'user_url';
				$meta_fields['display name'] = 'display_name';
				
				$select = '<select name="OEPL_user_meta" id="OEPL_user_meta'.$item['pid'].'" class="OEPL_custom_meta">';
					$select .= '<option value="">-----</option>';
					foreach ($meta_fields as $metakey => $metavalue) {
						$selected = '';
						
						if($item['wp_user_meta_fields'] == $metavalue){
							$selected = 'selected=selected';
						}
						$select .= '<option value='.$metavalue.' '.$selected.'>'.$metakey.'</option>';
					}
				$select .= '</select>';
              	return $select.'<a class="OEPL_small_button OEPL_save_custom_meta" data-pid='.$item['pid'].' title="Save Custom Meta"><img src="'.WPUSERTOCRM_PLUGIN_URL.'/images/right.png" align="middle"></a>';
            	break;
					
            default:
                return __('No Data', 'WPUSERTOCRM'); //Show the whole array for troubleshooting purposes
        }
    }
    function get_columns(){
        $columns = array(
            'field_name'	=> 'CRM Field Name',
            'wp_user_meta_fields' => 'WP User Fields',
            'is_show'    	=> 'Save to CRM',
        );
        return $columns;
    }
	
    function get_sortable_columns() {
        $sortable_columns = array(
            'field_name'	=> array('field_name',false),     
            'is_show'    	=> array('is_show',true),			//true means it's already sorted
		);
        return $sortable_columns;
    }
	
	function extra_tablenav($item){
		echo '<span class="subsubsub" >';
		
		echo "<a href=".esc_url(admin_url('admin.php?page=User_To_CRM_mapping_table&is_show=Y'))." class='".(isset($_GET['is_show']) && $_GET['is_show'] === 'Y' ? "current":"")."'>".esc_html("Enabled")."</a>&nbsp;|&nbsp;";
		
		echo "<a href=".esc_url(admin_url('admin.php?page=User_To_CRM_mapping_table&is_show=N'))." class='".(isset($_GET['is_show']) && $_GET['is_show'] === 'N' ? "current":"")."'>".esc_html("Disabled")."</a>&nbsp;|&nbsp;";
		
		echo "<a href=".esc_url(admin_url('admin.php?page=User_To_CRM_mapping_table')).">".esc_html("Reset")."</a>";
		echo '</span>';
	}
	
	public function search_box( $text, $input_id ) { ?>
	    <p class="search-box">
	      <label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
          <input type="search" id="<?php echo $input_id ?>" name="ContactSearch" value="<?php if(isset($_POST['ContactSearch']))echo $_POST['ContactSearch']; ?>" />
	      <?php submit_button( $text, 'button', 'ContactSearchSubmit', false, array('id' => 'ContactSearchSubmit') ); ?>
	  	</p>
	<?php }
	
    function prepare_items() {
        global $wpdb;
        $per_page = 10;
        $columns = $this->get_columns();
        $hidden = array();
        $query = 'SELECT pid, wp_meta_label, is_show, wp_user_meta_fields  FROM '.WPUSERTOCRM_MAP_FIELD.' WHERE 1';
		
		if (isset($_POST['ContactSearch']) && !empty($_POST['ContactSearch'])) {
			$where = ' AND wp_meta_label LIKE "%'.$_POST['ContactSearch'].'%"';
			$query .= $where;
		}
		if (isset($_GET['is_show']) && !empty($_GET['is_show'])) {
			$where = ' AND is_show = "'.$_GET['is_show'].'"';
			$query .= $where;
		}
		if (isset($_GET['custom_field']) && !empty($_GET['custom_field']) && $_GET['custom_field'] === 'Y') {
			$where = " AND custom_field ='".$_GET['custom_field']."'";
			$query .= $where;
		}
		$orderby 	= (isset($_GET["orderby"]) && !empty($_GET["orderby"]) 	? $_GET["orderby"]: 'ASC');
		$order 		= (isset($_GET["order"]) && !empty($_GET["order"]) 	? $_GET["order"]:'');
		if (!empty($orderby) & !empty($order)) {
			$query .=' ORDER BY '.$orderby.' '.$order;
		} else {
			$query .= ' ORDER BY is_show ASC,display_order ASC';
		}

        $data = $wpdb->get_results($query,ARRAY_A);
		$sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        $this->items = $data;
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page),   //WE have to calculate the total number of pages
        ) );
    }
}