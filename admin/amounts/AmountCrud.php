<?php
trait AmountCrud{
	use StripeHelper;
	function wpsd_register_amount_pages(){
		add_submenu_page(
			'wpsd-admin-settings',
			esc_html__('All Amounts', 'wp-stripe-donation'),
			esc_html__('All Amounts', 'wp-stripe-donation'),
			'manage_options',
			'wpsd-all-amounts',
			array($this, WPSD_PRFX . 'all_amounts')
		);
		add_submenu_page(
			'wpsd-admin-settings',
			esc_html__('Add Amount', 'wp-stripe-donation'),
			esc_html__('Add Amount', 'wp-stripe-donation'),
			'manage_options',
			'wpsd-add-amount',
			array($this, WPSD_PRFX . 'add_amount')
		);
		add_submenu_page( null,
			esc_html__('Edit Amount', 'wp-stripe-donation'),
			esc_html__('Edit Amount', 'wp-stripe-donation'),
			'manage_options',
			'wpsd-edit-amount',
			array($this, WPSD_PRFX . 'edit_amount')
		);
		add_submenu_page( null,
			esc_html__('Delete Amount', 'wp-stripe-donation'),
			esc_html__('Delete Amount', 'wp-stripe-donation'),
			'manage_options',
			'wpsd-delete-amount',
			array($this, WPSD_PRFX . 'delete_amount')
		);
	}
	
	function wpsd_all_amounts()
	{
		$wpsdColumns = array(
			'wpsd_amount' 		=> esc_html__('Amount', 'wp-stripe-donation'),
			'wpsd_stripe_product_id' 		=> esc_html__('Stripe Product ID', 'wp-stripe-donation'),
			'wpsd_campaign_ids' 		=> esc_html__('Campaign IDs', 'wp-stripe-donation'),
			'wpsd_amount_actions' 		=> esc_html__('Actions', 'wp-stripe-donation'),
		);
		register_column_headers('wpsd-amounts-column-table', $wpsdColumns);
		require_once WPSD_PATH . 'admin/amounts/' . $this->wpsd_assets_prefix . 'list.php';
	}
	protected function wpsd_get_all_amounts()
	{
		global $wpdb;
		$tableName = WPSD_TABLE_AMOUNT;
		return $wpdb->get_results($wpdb->prepare("SELECT * FROM $tableName WHERE %d", 1));
	}
	
	// was a private function
	private function dc( $data ) { 
		if ( is_object( $data ) ) { $output = "<script>console.log(".json_encode($data).");</script>"; } 
		else if ( is_array( $data ) ) { $output = "<script>console.log(".json_encode($data).");</script>"; } 
		else { $output = "<script>console.log('$data');</script>"; }
		echo $output;
	}
	
	function wpsd_edit_amount(){
		if (isset($_POST['updateAmount'])) {
			$this->wpsd_update_amount();
			return;
		}
		$id = (int)$_GET['wpsd_amount_id'];
		$wpsdAmount = null;
		if ($id) {
			$wpsdAmount = $this->wpsd_get_amount($id);
		}
		if ($wpsdAmount) {
			$wpsdAmount->wpsd_amount = number_format($wpsdAmount->wpsd_amount/100, 2);
			$wpsdAmount->wpsd_campaign_ids = $wpsdAmount->wpsd_campaign_ids;
			require_once WPSD_PATH . 'admin/amounts/' . $this->wpsd_assets_prefix . 'edit.php';
			return;
		}
		$this->wpsd_redirect_to_amounts();
	}
	function wpsd_update_amount(){
		if(!isset($_GET['wpsd_amount_id']) || empty($_GET['wpsd_amount_id'])){
			$this->wpsd_redirect_to_amounts();
			return;
		}
		$id = intval($_GET['wpsd_amount_id']);
		if(!$id){
			$this->wpsd_redirect_to_amounts();
			return;
		}
		global $wpdb;
		$tableName = WPSD_TABLE_AMOUNT;
		$campaign_ids = isset($_POST['wpsd_campaign_ids']) && !empty($_POST['wpsd_campaign_ids']) ? sanitize_text_field($_POST['wpsd_campaign_ids']): null;
		$amount_val = isset($_POST['wpsd_amount']) && !empty($_POST['wpsd_amount']) ? sanitize_text_field($_POST['wpsd_amount']): null;
		$amount_val = $this->wpsd_clean_amount_val($amount_val);
		// abort if the amount is not valid:
		$int_amount_val = intval($amount_val); //$this->dc("$int_amount_val");
		if(!$amount_val || !$int_amount_val){
			$wpsdErrorMessage = "Amount is not valid!";
			require_once WPSD_PATH . 'admin/amounts/' . $this->wpsd_assets_prefix . 'edit.php';
			return;
		}
		
		$wpsdAmount = $this->wpsd_get_amount_by_val($amount_val);
		// abort if this is a duplicate amount value:
		if ($wpsdAmount && $id != $wpsdAmount->wpsd_amount_id) {
			$wpsdErrorMessage = "Amount already exists!";
			require_once WPSD_PATH . 'admin/amounts/' . $this->wpsd_assets_prefix . 'edit.php';
			return;
		}
		$prev_amount = floatval($wpsdAmount->wpsd_amount);
		if($prev_amount === $amount_val){
			$wpsdSuccessMessage = "Amount updated successfully";
			require_once WPSD_PATH . 'admin/amounts/' . $this->wpsd_assets_prefix . 'edit.php';
			return;
		}
		$wpsdAmount->wpsd_amount = $amount_val;
		$updated = $this->wpsd_update_stripe_price($int_amount_val, $wpsdAmount->wpsd_stripe_product_id);
		if (!$updated) {
			$wpsdErrorMessage = "Failed to update product price";
			require_once WPSD_PATH . 'admin/amounts/' . $this->wpsd_assets_prefix . 'edit.php';
			return;
		}
		$data = array(
			'wpsd_amount' => $amount_val,
			'wpsd_campaign_ids' => $campaign_ids,
		);
		$where = array(
			'wpsd_amount_id' => $id
		);
		$result = $wpdb->update($tableName, $data, $where, array('%s'), array('%d'));
		if ($wpsdAmount) {
			$wpsdAmount->wpsd_amount = number_format($wpsdAmount->wpsd_amount/100, 2);
			$wpsdAmount->wpsd_campaign_ids = $campaign_ids;
			$wpsdSuccessMessage = "Amount updated successfully";
			require_once WPSD_PATH . 'admin/amounts/' . $this->wpsd_assets_prefix . 'edit.php';
			return;
		}
	}
	
	function wpsd_delete_amount(){
		global $wpdb;
		$tableName = WPSD_TABLE_AMOUNT;
		$id = (int)$_GET['wpsd_amount_id'];
		if ($id) {
			try {
				// 1. set all stripe prices as inactive:
				$amount = $this->wpsd_get_amount($id);
				$stripe = $this->wpsd_get_stripe_client();
				$prices = $stripe->prices->all( array( 'product' => $amount->wpsd_stripe_product_id ) );
				/** @var \Stripe\Price $price */
				foreach ( $prices as $price ) {
					if ( $price->active ) {
						$stripe->prices->update( $price->id, array( 'active' => false ) );
					}
				}
				// 2. set the product as inactive:
				$stripe->products->update($amount->wpsd_stripe_product_id, array('active' => false));
				
				// 3. delete amount:
				$res = $wpdb->delete($tableName, array('wpsd_amount_id' => $id),  array('%d'));
			} catch ( \Exception $e ) {
				if ($e->getStripeCode() === "resource_missing") {
					// the product is deleted externally:
					// 3. delete amount:
					$res = $wpdb->delete($tableName, array('wpsd_amount_id' => $id),  array('%d'));
				}
				$message = "An error occurred during deleting the amount: " . $e->getMessage();
				$message_data = array(
					'wpsd_type' => 'error',
					'wpsd_message' => $message . ' ',
					'wpsd_session' => true,
				);
				do_action('admin_notices', $message_data);
			}
			
		}
		$this->wpsd_redirect_to_amounts();
		wp_die();
	}
	
	function wpsd_add_amount(){
		if (isset($_POST['add_amount'])) {
			$this->wpsd_create_amount();
		}
		$wpsdAmount = array(
			'wpsd_amount' => 0.00,
		);
		$wpsdAmount = (object) $wpsdAmount;
		require_once WPSD_PATH . 'admin/amounts/' . $this->wpsd_assets_prefix . 'add.php';
	}
	function wpsd_create_amount(){
		global $wpdb;
		$tableName = WPSD_TABLE_AMOUNT;
		$campaign_ids = isset($_POST['wpsd_campaign_ids']) && !empty($_POST['wpsd_campaign_ids']) ? sanitize_text_field($_POST['wpsd_campaign_ids']): null;
		$amount_val = isset($_POST['wpsd_amount']) && !empty($_POST['wpsd_amount']) ? sanitize_text_field($_POST['wpsd_amount']): null;
		$amount_val = $this->wpsd_clean_amount_val($amount_val);
		$amount = $this->wpsd_get_amount_by_val($amount_val);
		$wpsdAmount = array(
			'wpsd_amount' => $amount_val,
		);
		$wpsdAmount = (object) $wpsdAmount;
		$int_amount_val = intval($amount_val);
		if(!$amount_val || !$int_amount_val){
			$wpsdErrorMessage = "Amount is not valid!";
			require_once WPSD_PATH . 'admin/amounts/' . $this->wpsd_assets_prefix . 'add.php';
			return;
		}
		if($amount){
			$wpsdErrorMessage = "Amount already exists!";
			require_once WPSD_PATH . 'admin/amounts/' . $this->wpsd_assets_prefix . 'add.php';
			return;
		}
		/** @var \Stripe\Product $product */
		$product = $this->wpsd_create_stripe_product($int_amount_val);
		if(is_string($product)){
			$message_data = array(
				'wpsd_type' => 'error',
				'wpsd_message' => $product,
				'wpsd_session' => true,
			);
			do_action('admin_notices', $message_data);
			$this->wpsd_redirect_to_amounts();
			return;
		}
		$data = array(
			'wpsd_amount' => $amount_val,
			'wpsd_campaign_ids' => $campaign_ids,
			'wpsd_stripe_product_id' => $product->id,
		);
		$wpdb->insert($tableName, $data);
		global $pagenow;
		if($pagenow === 'admin.php'){
			$message = "Amount is created successfully!";
			$message_data = array(
				'wpsd_type' => 'updated',
				'wpsd_message' => $message,
				'wpsd_session' => true,
			);
			do_action('admin_notices', $message_data);
			$this->wpsd_redirect_to_amounts();
			return;
		}
		$url = home_url();
		$script = '<script>window.location = "' . $url . '" </script>';
		echo $script;
		return;
	}
	
	function wpsd_get_amount($id){
		global $wpdb;
		$tableName = WPSD_TABLE_AMOUNT;
		return $wpdb->get_row( "SELECT * FROM $tableName WHERE wpsd_amount_id = $id");
	}
	
	function wpsd_get_amount_by_val($amount){
		global $wpdb;
		$tableName = WPSD_TABLE_AMOUNT;
		return $wpdb->get_row( "SELECT * FROM $tableName WHERE wpsd_amount = $amount");
	}
	
	protected function wpsd_redirect_to_amounts(){
		global $pagenow;
		$url = home_url();
		if($pagenow === 'admin.php'){
			$url = admin_url( '/admin.php?page=wpsd-all-amounts' );
		}
		$script = '<script>window.location = "' . $url . '" </script>';
		echo $script;
	}
	
	/**
	 * @return string
	 */
	protected function wpsd_get_currency(){
		$wpsdGeneralSettings = stripslashes_deep( unserialize( get_option('wpsd_general_settings') ) );
		$currency = isset($wpsdGeneralSettings['wpsd_donate_currency'])
		            && !empty($wpsdGeneralSettings['wpsd_donate_currency'])?
			$wpsdGeneralSettings['wpsd_donate_currency']: "usd";
		return $currency;
	}
}