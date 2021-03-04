<?php

/**
 * Our main plugin class
 */
class Wpsd_Upgrade
{
	function updateVersionVal($v){
		$settings = stripslashes_deep(unserialize(get_option('wpsd_general_settings')));
		if(is_array($settings)){
			$settings['wpsd_version'] = $v;
		}
		else {
			$settings = ['wpsd_version' => $v];
		}
		$updated = update_option('wpsd_general_settings', serialize($settings));
	}
	function get_version($default = "1.4"){
		$settings = stripslashes_deep(unserialize(get_option('wpsd_general_settings')));
		if(is_array($settings)){
			if(array_key_exists('wpsd_version', $settings)){
				return $settings['wpsd_version'];
			}
		}
		return $default;
	}
	public function wpsd_perform_upgrade(){
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$this->updates_v141();
		$this->updates_v142();
	}
	protected function updates_v141(){
		$currentVersion = $this->get_version();
		if($currentVersion !== "1.4"){
			return;
		}
		global $wpdb;
		$table_name = WPSD_TABLE;
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
			// table in db, update the table:
			$sql = "ALTER TABLE `$table_name`
    		ADD COLUMN `wpsd_fund` VARCHAR(155),
    		ADD COLUMN `wpsd_fund_id` VARCHAR(255),
    		ADD COLUMN `wpsd_currency` VARCHAR(255)";
			$res = $wpdb->query($sql);
			$stop = null;
		}
		$this->updateVersionVal("1.4.1");
	}
	
	protected function updates_v142(){
		$currentVersion = $this->get_version();
		if($currentVersion !== "1.4.1"){
			return;
		}
		global $wpdb;
		$table_name = WPSD_TABLE;
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
			// table in db, update the table:
			$sql = "ALTER TABLE `$table_name`
    		ADD COLUMN `wpsd_campaign_id` VARCHAR(255) AFTER `wpsd_campaign`,
    		ADD COLUMN `wpsd_in_memory_of_field_id` VARCHAR(255) AFTER `wpsd_amount_id`";
			$res = $wpdb->query($sql);
			$stop = null;
		}
		$this->updateVersionVal("1.4.2");
	}
}