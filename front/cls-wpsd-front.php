<?php

/**
 *	Front CLass
 */

use \MenaraSolutions\Geographer\Earth;
use \MenaraSolutions\Geographer\Exceptions\MisconfigurationException;
use \MenaraSolutions\Geographer\Services\TranslationAgency;
class Wpsd_Front
{
	use HM_Currency;
	use AmountCrud;
	use KindfulHelper;
	
	private $wpsd_version;

	private $countries;
	private $locale;

	public function __construct($version)
	{
		$this->wpsd_version = $version;
		$this->wpsd_assets_prefix = substr(WPSD_PRFX, 0, -1) . '-';
		$earth = new Earth();
		$locale = get_locale();
		$locale = substr($locale, 0, 2);
		$this->locale = $locale;
		$countries = $earth->setLocale( $locale )->getCountries();
		try {
			$countries->toArray();
		} catch ( MisconfigurationException $e ) {
			// no translation found, fallback to english
			$countries = $earth->setLocale(TranslationAgency::LANG_ENGLISH)->getCountries();
		}
		$this->countries = $countries;
	}

	public function wpsd_front_assets()
	{
		wp_enqueue_style(
			$this->wpsd_assets_prefix . 'front-style',
			WPSD_ASSETS . 'css/' . $this->wpsd_assets_prefix . 'front-style.css',
			array(),
			$this->wpsd_version,
			FALSE
		);
		if (!wp_script_is('jquery')) {
			wp_enqueue_script('jquery');
		}

		wp_enqueue_script('checkout-stripe-js', '//js.stripe.com/v3/');
		wp_enqueue_script(
			$this->wpsd_assets_prefix . 'front-script',
			WPSD_ASSETS . 'js/' . $this->wpsd_assets_prefix . 'front-script.js',
			array('jquery'),
			$this->wpsd_version,
			TRUE
		);
		$wpsdKeySettings = stripslashes_deep(unserialize(get_option('wpsd_key_settings')));
		if (is_array($wpsdKeySettings)) {
			$wpsdPublishableKey = !empty($wpsdKeySettings['wpsd_publishable_key']) ? $wpsdKeySettings['wpsd_publishable_key'] : "";
		} else {
			$wpsdPublishableKey = "";
		}
		$wpsdGeneralSettings = stripslashes_deep(unserialize(get_option('wpsd_general_settings')));
		if (is_array($wpsdGeneralSettings)) {
			$wpsdPaymentTitle = esc_html__($wpsdGeneralSettings['wpsd_payment_title'], 'wp-stripe-donation');
			$wpsdDonateCurrency = $wpsdGeneralSettings['wpsd_donate_currency'];
		} else {
			$wpsdPaymentTitle = esc_html__('Donate Us', 'wp-stripe-donation');
			$wpsdDonateCurrency = "USD";
		}

		$wpsdAdminArray = array(
			'publishable_key'	=> $wpsdPublishableKey,
			'locale'        => $this->locale,
			'ajaxurl' 		=> admin_url('admin-ajax.php'),
			'title'			=> $wpsdPaymentTitle,
			'currency'		=> $wpsdDonateCurrency,
			'donation_for'  => $wpsdGeneralSettings['wpsd_donation_for']? $wpsdGeneralSettings['wpsd_donation_for']: get_bloginfo('name'),
			'countries' => $this->wpsd_get_countries(),
		);
		$js_strings = $this->wpsd_get_js_strings();
		$wpsdAdminArray = array_merge($wpsdAdminArray, $js_strings);
		wp_localize_script($this->wpsd_assets_prefix . 'front-script', 'wpsdAdminScriptObj', $wpsdAdminArray);
	}

	function wpsd_get_countries(){
		$countries = array_map(function ($item){
			$stop = null;
			return [
				'name' => $item['name'],
				'code' => $item['code'],
			];
		}, $this->countries->toArray());
		return $countries;
	}

	private function wpsd_get_js_strings(){
		return array(
			'validation' => array(
				'required' => array(
					'first_name' => esc_html__('Please Enter Your First Name', 'wp-stripe-donation'),
					'last_name' => esc_html__('Please Enter Your Last Name', 'wp-stripe-donation'),
					'address' => esc_html__('Please Enter Your Address', 'wp-stripe-donation'),
					'email' => esc_html__('Please Enter Donor Email', 'wp-stripe-donation'),
				),
				'not_valid' => array(
					'email' => esc_html__('Please Enter Valid Email', 'wp-stripe-donation'),
					'donation_amount' => esc_html__('Please select an amount to donate', 'wp-stripe-donation'),
					'publishable_key' => esc_html__('Stripe key is missing!', 'wp-stripe-donation'),
				),
			),
		);
	}
	public function wpsd_load_shortcode()
	{
		add_shortcode('wp_stripe_donation', array($this, 'wpsd_load_shortcode_view'));
	}
	
	public function wpsd_get_general_option_value($key, $default){
		$wpsdGeneralSettings = stripslashes_deep( unserialize( get_option('wpsd_general_settings') ) );
		if (is_array($wpsdGeneralSettings)) {
			if(array_key_exists($key, $wpsdGeneralSettings) && !empty($wpsdGeneralSettings[$key])) {
				return $wpsdGeneralSettings[$key];
			}
		}
		return $default;
	}
	
	public function wpsd_load_shortcode_view($atts)
	{
		$output = '';
		ob_start();
		$expected_attr = array(
			"campaign" => "General",
			'campaign_id' => "",
			'custom_amount' => "true",
			"fund" => "General",
			"fund_id" => "",
			"imof" => "",
		);
		$params = shortcode_atts($expected_attr, $atts);
		include(plugin_dir_path(__FILE__) . '/view/wpsd-front-view.php');
		$output .= ob_get_clean();
		return $output;
	}

	function wpsd_get_states_handler(){
		if(!isset($_GET['code']) || empty($_GET['code'])){
			$err = array(
				"status" => "error",
				"message" => esc_html__('Country Code is required', 'wp-stripe-donation'),
			);
			wp_send_json_error($err, 400);
		}
		$country_code = $_GET['code'];
		/** @var  \MenaraSolutions\Geographer\Country $country */
		$country = $this->countries->findOne(array('code' => $country_code));
		if(!$country){
			$err = array(
				"status" => "error",
				"message" => esc_html__('No country found', 'wp-stripe-donation'),
			);
			wp_send_json_error($err, 400);
		}
		$states = $country->getStates()->sortBy("name")->toArray();
		$data = array(
			"status" => "success",
			"states" => $states,
		);
		wp_send_json_success($data);
	}
	
	function wpsd_donation_handler()
	{
		$payload = @file_get_contents('php://input');
		$data = json_decode($payload, true);
		$data = $this->wpsd_get_default_donation_values($data);
		$this->wpsd_check_donation_required_fields($data);
		$this->wpsd_validate_int_fields($data);
		// processing:
		$amountObj = $this->wpsd_get_amount_val($data);
		$donation_id = $this->wpsd_donation_insert_donation_data($data, $amountObj);
		if(!$donation_id){
			$err = array(
				'status' => 'error',
				'message' => esc_html__('Failed to create donation', 'wp-stripe-donation'),
			);
			wp_send_json_error($err, 500);
		}
		
		$response = array(
			'status' => 'success',
			'message' => esc_html__("Thank you for your donation.", 'wp-stripe-donation'),
			'donation_id' => $donation_id,
		);
		wp_send_json_success($response);
	}
	
	/**
	 * check if required fields exist.
	 * @param array $data the donation post request data.
	 */
	private function wpsd_check_donation_required_fields($data){
		$required = [
			'amount',
			'custom_amount',
			'currency',
			'first_name',
			'last_name',
			'country',
			'city',
			'zip',
			'address',
			'phone',
			'email',
			'is_recurring',
		];
		// validation:
		$wpsdGeneralSettings = stripslashes_deep(unserialize(get_option('wpsd_general_settings')));
		$campaign = isset($data['campaign']) && !empty($data['campaign'])? $data['campaign']: $wpsdGeneralSettings['wpsd_campaign'];
		$campaign_id = isset($data['campaign_id']) && !empty($data['campaign_id'])? $data['campaign_id']: $wpsdGeneralSettings['wpsd_campaign_id'];
		$fund = isset($data['fund']) && !empty($data['fund'])? $data['fund']: $wpsdGeneralSettings['wpsd_fund'];
		$fund_id = isset($data['fund_id']) && !empty($data['fund_id'])? $data['fund_id']: $wpsdGeneralSettings['wpsd_fund_id'];
		if(!isset($fund) || empty($fund)) {
			$required[] = 'fund';
		}
		if(!isset($fund_id) || empty($fund_id)) {
			$required[] = 'fund_id';
		}
		
		if(!isset($campaign) || empty($campaign)) {
			$required[] = 'campaign';
		}
		if(!isset($campaign_id) || empty($campaign_id)) {
			$required[] = 'campaign_id';
		}
		$this->wpsd_check_required_fields($required, $data);
	}
	
	/**
	 * adds the default fields values.
	 * @param array $data the donation post request data.
	 *
	 * @return array the updated data with default values.
	 */
	private function wpsd_get_default_donation_values($data){
		$wpsdGeneralSettings = stripslashes_deep(unserialize(get_option('wpsd_general_settings')));
		$fund = isset($data['fund']) && !empty($data['fund'])? $data['fund']: $wpsdGeneralSettings['wpsd_fund'];
		$fund_id = isset($data['fund_id']) && !empty($data['fund_id'])? $data['fund_id']: $wpsdGeneralSettings['wpsd_fund_id'];
		$fund_empty = !isset($fund) || empty($fund);
		$fund_id_empty = !isset($fund_id) || empty($fund_id);
		
		$campaign = isset($data['campaign']) && !empty($data['campaign'])? $data['campaign']: $wpsdGeneralSettings['wpsd_campaign'];
		$campaign_id = isset($data['campaign_id']) && !empty($data['campaign_id'])? $data['campaign_id']: $wpsdGeneralSettings['wpsd_campaign_id'];
		$campaign_empty = !isset($campaign) || empty($campaign);
		$campaign_id_empty = !isset($campaign_id) || empty($campaign_id);
		if($fund_empty || $fund_id_empty) {
			$funds = $this->getKindfulFunds();
			if($fund_empty) {
				$fund = $funds[0]['name'];
			}
			if($fund_id_empty) {
				$fund_id = $funds[0]['id'];
			}
		}
		
		if($campaign_empty || $campaign_id_empty) {
			$campaigns = $this->getKindfulCampaigns();
			if($campaign_empty) {
				$campaign = $campaigns[0]['name'];
			}
			if($campaign_id_empty) {
				$campaign_id = $campaigns[0]['id'];
			}
		}
		$data['fund'] = $fund;
		$data['fund_id'] = $fund_id;
		$data['campaign'] = $campaign;
		$data['campaign_id'] = $campaign_id;
		return $data;
	}
	
	function wpsd_payment_intent_handler(){
		$payload = @file_get_contents('php://input');
		$data = json_decode($payload, true);
		$this->dc($data);
		$required = [
			'donation_id',
			'payment_method_id',
			'customer_id',
		];
		$this->wpsd_check_required_fields($required, $data);
		$donation_id = filter_var( $data['donation_id'], FILTER_SANITIZE_NUMBER_INT ) ? $data['donation_id'] : 0;
		$err = array(
			"status" => "error",
			"message" => esc_html__("Donation not found", 'wp-stripe-donation'),
		);
		if (!$donation_id) {
			wp_send_json_error($err, 404);
		}
		$donation = $this->wpsd_get_donation($donation_id);
		if (!$donation) {
			wp_send_json_error($err, 404);
		}
		$amount_val = null;
		if ($donation->wpsd_amount_id) {
			$amountObj = $this->wpsd_get_amount($donation->wpsd_amount_id);
			$amount_val = intval(str_replace('.', '', $amountObj->wpsd_amount));
		}
		else {
			$amount_val = intval(str_replace('.', '', $donation->wpsd_donated_amount));
		}
		$paymentMethod = sanitize_text_field($data['payment_method_id']);
		$customer = sanitize_text_field($data['customer_id']);
		$paymentIntent = $this->wpsd_create_payment_intent($donation, $amount_val, $customer, $paymentMethod);
		if(is_string($paymentIntent)) {
			$err = array(
				"status" => "error",
				"message" => $paymentIntent,
			);
			wp_send_json_error($err, 400);
		}
		global $wpdb;
		$tableName = WPSD_TABLE;
		$updatedDonation = array(
			'wpsd_customer_id' => $customer,
			'wpsd_payment_method' => $paymentMethod,
			'wpsd_payment_intent_id' => $paymentIntent->id
		);
		$where = array(
			'wpsd_id' => $donation->wpsd_id
		);
		$result = $wpdb->update($tableName, $updatedDonation, $where, array('%s','%s', '%s'), array('%d'));
		$response = array(
			'status' => 'success',
			'message' => esc_html__("Thank you for your donation.", 'wp-stripe-donation'),
			'client_key' => $paymentIntent->client_secret,
		);
		wp_send_json_success($response);
	}
	function wpsd_create_customer_handler() {
		$payload = @file_get_contents('php://input');
		$data = json_decode($payload, true);
		// debugging data...
		//$this->dc($data);
		$required_fields = array(
			'donation_id',
			'payment_method_id',
		);
		$this->wpsd_check_required_fields($required_fields, $data);
		$donation_id = filter_var( $data['donation_id'], FILTER_SANITIZE_NUMBER_INT ) ? $data['donation_id'] : 0;
		$paymentMethodId = sanitize_text_field($data['payment_method_id']);
		$err = array(
			"status" => "error",
			"message" => esc_html__("Donation not found", 'wp-stripe-donation'),
		);
		if (!$donation_id) {
			wp_send_json_error($err, 404);
		}
		$donation = $this->wpsd_get_donation($donation_id);
		if (!$donation) {
			wp_send_json_error($err, 404);
		}
		// try to find existing customer with the email to prevent duplicates:
		$customer = $this->wpsd_get_stripe_customer($donation->wpsd_donator_email);
		if($customer && !is_string($customer)){
			// attach payment:
			$res = $this->wpsd_attach_payment_method($paymentMethodId, $customer->id);
			if(is_string($res)){
				$err = array(
					'status' => 'error',
					'message' => $res,
				);
				wp_send_json_error($err, 500);
			}
		}
		if(!$customer || is_string($customer)){
			// customer not found, create one:
			$country = $this->countries->findOne(array('code' => $donation->wpsd_donator_country));
			$customer_details = array(
				'email' => $donation->wpsd_donator_email,
				'first_name' => $donation->wpsd_donator_first_name,
				'last_name' => $donation->wpsd_donator_last_name,
				'phone' => $donation->wpsd_donator_phone,
				'city' => $donation->wpsd_donator_city,
				'country' => $country->toArray()['code'],
				'address' => $donation->wpsd_donator_address,
				'zip' => $donation->wpsd_donator_zip,
			);
			if($donation->wpsd_donator_state){
				$states = $country->getStates();
				$state = $states->find(['name' => $donation->wpsd_donator_state]);
				$state = $state->toArray();
				$state = reset($state);
				$customer_details['state'] = $state['name'];
			}
			$customer = $this->wpsd_create_stripe_customer($customer_details['email'], $customer_details, $paymentMethodId);
			if(is_string($customer)){
				$err = array(
					'status' => 'error',
					'message' => $customer,
				);
				wp_send_json_error($err, 500);
			}
		}
		$data = array(
			'wpsd_customer_id' => $customer->id,
			'wpsd_payment_method' => $paymentMethodId,
		);
		$where = array(
			'wpsd_id' => $donation->wpsd_id,
		);
		global $wpdb;
		$tableName = WPSD_TABLE;
		$result = $wpdb->update($tableName, $data, $where, array('%s'), array('%d'));
		$response = array(
			'status' => 'success',
			'update' => $result,
			'customer_id' => $customer->id,
		);
		wp_send_json_success($response);
		
	}
	function wpsd_get_donation($id){
		global $wpdb;
		$tableName = WPSD_TABLE;
		return $wpdb->get_row( "SELECT * FROM $tableName WHERE wpsd_id = $id");
	}
	
	/**
	 * @param $donation
	 * @param $amountObj
	 * @param null $customer
	 * @param null $paymentMethod
	 *
	 * @return string|\Stripe\PaymentIntent
	 */
	function wpsd_create_payment_intent($donation, $amountObj, $customer = null, $paymentMethod = null){
		$amount_val = $amountObj;
		if(is_object($amountObj)){
			$amount_val = $amountObj->wpsd_amount;
		}
		$wpsdGeneralSettings = stripslashes_deep(unserialize(get_option('wpsd_general_settings')));
		$donationFor = $wpsdGeneralSettings['wpsd_donation_for']? $wpsdGeneralSettings['wpsd_donation_for']: get_bloginfo('name');
		$fullName = $donation->wpsd_donator_first_name  . " " . $donation->wpsd_donator_last_name;
		$currency = $donation->wpsd_currency;
		$amount_val = (int) $this->wpsd_clean_amount_val($amount_val);
		$error = null;
		$paymentIntent = null;
		$paymentIntentData = array(
			'amount' => $amount_val,
			'currency' => $currency,
			'description' => $fullName . " donated for " . $donationFor,
			'receipt_email' => $donation->wpsd_donator_email,
		);
		if($customer){
			$paymentIntentData['customer'] = $customer;
		}
		if($paymentMethod){
			$paymentIntentData['payment_method'] = $paymentMethod;
		}
		$stripe = $this->wpsd_get_stripe_client();
		try {
			$paymentIntent = $stripe->paymentIntents->create($paymentIntentData);
		} catch (\Exception $e) {
			// Upon unsuccessful transaction/rejection, respond with Error message
			$error = $e->getMessage();
		}
		if ($error) {
			return $error;
		}
		return $paymentIntent;
	}
	
	/**
	 * initializes and gets the amount to be paid.
	 *
	 * @param $data
	 *
	 * @return mixed|int: if it's recurring, it returns wp_wpsd_stripe_amounts row, otherwise the amount value itself
	 */
	private function wpsd_get_amount_val($data){
		$selected_amount = $data['amount'];
		$amount_val = null;
		$amountObj = null;
		$custom_amount = $data['custom_amount'];
		if ($custom_amount) {
			$selected_amount = $this->wpsd_clean_amount_val($selected_amount);
			$amountObj = $this->wpsd_get_amount_by_val($selected_amount);
			if(!$amountObj){
				$amount_val = $data['amount'];
			}
		}
		else {
			$amountObj = $this->wpsd_get_amount($selected_amount);
			if(!$amountObj){
				$err['message'] = esc_html__('Amount not found', 'wp-stripe-donation');
				wp_send_json_error($err, 404);
			}
		}
		if($amountObj){
			$amountObj->wpsd_amount = (int) str_replace('.','', $amountObj->wpsd_amount);
			return $amountObj;
		}
		return $amount_val;
	}
	private function wpsd_validate_int_fields($data){
		$err = array(
			"status" => "error",
			"message" => null,
		);
		// make sure that all boolean and integer fields are integer
		$wpsdAmount = $this->wpsd_clean_amount_val($data['amount']);
		$intAmount = intval($wpsdAmount);
		if (!$wpsdAmount || !$intAmount) {
			$err['message'] = esc_html__('Amount is not valid!', 'wp-stripe-donation');
		}
		
		$custom_amount = filter_var( $data['custom_amount'], FILTER_SANITIZE_NUMBER_INT ) ? $data['custom_amount'] : 0;
		if(!is_int($custom_amount)){
			$err['message'] = esc_html__('Custom Amount is not valid!', 'wp-stripe-donation');
		}
		
		$custom_amount = filter_var( $data['is_recurring'], FILTER_SANITIZE_NUMBER_INT ) ? $data['is_recurring'] : 0;
		if(!is_int($custom_amount)){
			$err['message'] = esc_html__('Recurring is not valid!', 'wp-stripe-donation');
		}
		if($err['message']){
			wp_send_json_error($err, 400);
		}
		return true;
	}
	
	/**
	 * Inserts donation data to db
	 *
	 * @param array
	 * @param object|string
	 * @param string $paymentIntent
	 *
	 * @return int|null the donation id, null on fail
	 */
	private function wpsd_donation_insert_donation_data($data, $amountObj, $paymentIntentId = null){
		global $wpdb;
		$tableName = WPSD_TABLE;
		$wpsdGeneralSettings = stripslashes_deep(unserialize(get_option('wpsd_general_settings')));
		$donation_for = $wpsdGeneralSettings['wpsd_donation_for']? $wpsdGeneralSettings['wpsd_donation_for']: get_bloginfo('name');
		$first_name = sanitize_text_field($data['first_name']);
		$last_name = sanitize_text_field($data['last_name']);
		$email = sanitize_email($data['email']);
		$phone = sanitize_text_field($data['phone']);
		$country = sanitize_text_field($data['country']);
		$state = isset($data['state']) && !empty($data['state'])? sanitize_text_field($data['state']): null;
		$city = sanitize_text_field($data['city']);
		$zip = sanitize_text_field($data['zip']);
		$address = sanitize_text_field($data['address']);
		$campaign = isset($data['campaign']) && !empty($data['campaign'])? sanitize_text_field($data['campaign']): "General";
		$campaign_id = isset($data['campaign_id']) && !empty($data['campaign_id'])? sanitize_text_field($data['campaign_id']): null;
		$fund = isset($data['fund']) && !empty($data['fund'])? sanitize_text_field($data['fund']): "General";
		$fund_id = isset($data['fund_id']) && !empty($data['fund_id'])? sanitize_text_field($data['fund_id']): null;
		$in_memory_of_field_id = isset($data['in_memory_of_field_id']) && !empty($data['in_memory_of_field_id'])? sanitize_text_field($data['in_memory_of_field_id']): null;
		$in_memory_of = isset($data['in_memory_of']) && !empty($data['in_memory_of'])? sanitize_text_field($data['in_memory_of']): null;
		$is_recurring = filter_var( $data['is_recurring'], FILTER_SANITIZE_NUMBER_INT ) ? $data['is_recurring'] : 0;
		$amount = is_object($amountObj)? null: $this->wpsd_clean_amount_val($data['amount']);
		$amount_id = is_object($amountObj)? $amountObj->wpsd_amount_id: null;
		$currency = sanitize_text_field($data['currency']);
		if (is_array($wpsdGeneralSettings)) {
			$wpsdDonationEmail = !empty($wpsdGeneralSettings['wpsd_donation_email']) ? $wpsdGeneralSettings['wpsd_donation_email'] : "";
			if (!empty($wpsdDonationEmail)) {
				// Send the email if the charge successful.
				$amount_val = $amount;
				if($amount_id){
					$amount = $this->wpsd_get_amount($amount_id);
					$amount_val = $amount->wpsd_amount;
				}
				$wpsdEmailSubject = "New Donation for " . $donation_for;
				$wpsdEmailMessage = "Name: " . $first_name . "<br>Email: " . $email . "<br>Amount: " . substr($amount_val, 0, -2) . ' ' . $currency . "<br>For: " . $donation_for . "<br>";
				$headers = array('Content-Type: text/html; charset=UTF-8');
				wp_mail($wpsdDonationEmail, $wpsdEmailSubject, $wpsdEmailMessage, $headers);
			}
		}
		$values_and_format = array(
			'wpsd_donation_for' => [
				'value' => $donation_for,
				'format' => '%s'
			],
			'wpsd_donator_first_name' => [
				'value' => $first_name,
				'format' => '%s',
			],
			'wpsd_donator_last_name' => [
				'value' => $last_name,
				'format' => '%s',
			],
			'wpsd_donator_email' => [
				'value' => $email,
				'format' => '%s',
			],
			'wpsd_donator_phone' => [
				'value' => $phone,
				'format' => '%s',
			],
			'wpsd_donator_country' => [
				'value' => $country,
				'format' => '%s',
			],
			'wpsd_donator_state' => [
				'value' => $state,
				'format' => '%s',
			],
			'wpsd_donator_city' => [
				'value' => $city,
				'format' => '%s',
			],
			'wpsd_donator_zip' => [
				'value' => $zip,
				'format' => '%s',
			],
			'wpsd_donator_address' => [
				'value' => $address,
				'format' => '%s',
			],
			'wpsd_campaign' => [
				'value' => $campaign,
				'format' => '%s',
			],
			'wpsd_campaign_id' => [
				'value' => $campaign_id,
				'format' => '%s',
			],
			'wpsd_fund' => [
				'value' => $fund,
				'format' => '%s',
			],
			'wpsd_fund_id' => [
				'value' => $fund_id,
				'format' => '%s',
			],
			'wpsd_in_memory_of_field_id' => [
				'value' => $in_memory_of_field_id,
				'format' => '%s',
			],
			'wpsd_in_memory_of' => [
				'value' => $in_memory_of,
				'format' => '%s',
			],
			'wpsd_is_recurring' => [
				'value' => $is_recurring,
				'format' => '%d',
			],
			'wpsd_payment_intent_id' => [
				'value' => $paymentIntentId,
				'format' => '%s',
			],
			'wpsd_payment_complete' => [
				'value' => 0,
				'format' => '%d',
			],
			'wpsd_donated_amount' => [
				'value' => $amount,
				'format' => '%s',
			],
			'wpsd_amount_id' => [
				'value' => $amount_id,
				'format' => '%d',
			],
			'wpsd_donation_datetime' => [
				'value' => date('Y-m-d h:i:s'),
				'format' => '%s',
			],
			'wpsd_currency' => [
				'value' => $currency,
				'format' => '%s',
			],
		);
		// formats:
		// %s: string, %d: int, %f: float
		$formats = [];
		$values = [];
		foreach ( $values_and_format as $field => $data_item ) {
			$formats[] = $data_item['format'];
			$values[$field] = $data_item['value'];
		}
		
		$result = $wpdb->insert($tableName, $values, $formats);
		if($result){
			return $wpdb->insert_id;
		}
		return null;
	}
	
	/**
	 * check if required fields exist.
	 */
	private function wpsd_check_required_fields($required_fields, $data){
		$failed = array();
		foreach ($required_fields as $field){
			if((!array_key_exists($field, $data) || empty($data[$field])) && !is_int($data[$field])){
				$field = str_replace('_',' ', $field);
				$field = ucwords($field);
				$failed[] = esc_html__($field, WPSD_TXT_DOMAIN);
			}
		}
		if(count($failed)){
			$err = array(
				"status" => "error",
				"message" => esc_html__("Please fill all the required fields", 'wp-stripe-donation'),
				"errors" => $failed,
			);
			wp_send_json_error($err, 400);
		}
	}
}
