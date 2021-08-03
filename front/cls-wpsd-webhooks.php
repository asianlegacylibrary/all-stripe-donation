<?php

use \Stripe\StripeClient;
use \MenaraSolutions\Geographer\Exceptions\MisconfigurationException;
use \MenaraSolutions\Geographer\Earth;
use \MenaraSolutions\Geographer\Services\TranslationAgency;
use \MenaraSolutions\Geographer\Collections\MemberCollection;
use \MenaraSolutions\Geographer\Country;
use \MenaraSolutions\Geographer\State;
class Wpsd_Webhooks {
	use StripeHelper;
	
	/** @var StripeClient */
	protected $client;
	
	public function __construct(){
		$wpsdKeySettings = stripslashes_deep(unserialize(get_option('wpsd_key_settings')));
		$secret_key = base64_decode($wpsdKeySettings['wpsd_secret_key']);
		if($secret_key) {
			$this->client = new StripeClient($secret_key);
		}
	}
	
	/**
	 * Handles stripe webhooks.
	 */
	function wpsd_stripe_webhooks_handler(){
		$result = array(
			'status' => 'error',
			'message' => null,
		);
		
		$wpsdKeySettings = stripslashes_deep(unserialize(get_option('wpsd_key_settings')));
		if(!is_array($wpsdKeySettings)){
			$result['message'] = esc_html__("Please fill the Webhooks Key field for WPSD Stripe Donation plugin.", 'wp-stripe-donation');
			wp_send_json_error($result, 400);
		}
		$secret_key = base64_decode($wpsdKeySettings['wpsd_secret_key']);
		$endpoint_secret = $wpsdKeySettings['wpsd_webhooks_key'];
		
		$payload = @file_get_contents('php://input');
		
		$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

		\Stripe\Stripe::setApiKey($secret_key);

		try {
			$event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
			$this->wpsd_stripe_handle_stripe_event($event);
		} catch(\UnexpectedValueException $e) {
			// Invalid payload
			$result['message'] = esc_html__("Invalid payload", 'wp-stripe-donation');
			wp_send_json_error($result, 400);
		} catch(\Stripe\Exception\SignatureVerificationException $e) {
			// Invalid signature
			$result['message'] = esc_html__("Invalid signature yo yo", 'wp-stripe-donation');
			// $result['secrets'] = array(
			// 	'secret' => $secret_key,
			// 	'endpoint_secret' => $endpoint_secret,
			// 	'sig_header' => $sig_header,
			// 	'payload' => $payload
			// );
			
			wp_send_json_error($result, 400);
		}
		$result['message'] = esc_html__("Hook ran successfully", 'wp-stripe-donation');
		$result['status'] = esc_html__("complete");
		
		wp_send_json_success($result, 200);
	}

	/**
	 * calls the appropriate function based on the event type.
	 * @param \Stripe\Event $event
	 */
	function wpsd_stripe_handle_stripe_event($event){
		$stop = null;
		switch ($event->type) {
			case "payment_intent.succeeded":
				$payment_intents = $event->data->values();
				foreach ( $payment_intents as $payment_intent ) {
					//echo var_dump('payment intent', $payment_intent);
					$this->wpsd_handle_payment_success($payment_intent);
				}
				break;
			case "customer.updated":
				$customer_id = $event->data->object->id;
				$customer = $this->wpsd_get_stripe_customer_by_id($customer_id);
				echo var_dump('CUSTOMER!', $customer);
			default:
				//
				break;
		}
	}

	/**
	 * Handles the payment successful event.
	 *
	 * @param \Stripe\PaymentIntent $paymentIntent: the payment inent
	 */
	function wpsd_handle_payment_success($paymentIntent){
		// try to find existing customer with the email to prevent duplicates:
		$customer = $this->wpsd_get_stripe_customer($donation->wpsd_donator_email);
		
		$this->wpsd_update_payment_status($paymentIntent);
		$donation = $this->wpsd_get_donation($paymentIntent->id);
		echo var_dump('payment intent customer data', $donation);
		$this->wpsd_send_to_kindful($donation, $paymentIntent->charges->first());
		$recurring = (int) $donation->wpsd_is_recurring;
		$is_subscribed = $donation->wpsd_subscription && !empty($donation->wpsd_subscription);
		// if this is a recurring payment, and there is no subscription, create one so that we charge the user monthly:
		if ($recurring && !$is_subscribed) {
			$subscription = $this->wpsd_create_stripe_subscription($donation);
			if (is_string($subscription)) {
				wp_send_json_error($subscription, 500);
			}
			$this->wpsd_update_donation_subscription($donation, $subscription);
		}
	}

	
	
	function wpsd_update_donation_subscription($donation, $subscription){
		// save subscription to db:
		$data = array(
			'wpsd_subscription' => $subscription->id,
		);
		$where = array(
			'wpsd_id' => $donation->wpsd_id,
		);
		global $wpdb;
		$tableName = WPSD_TABLE;
		$result = $wpdb->update($tableName, $data, $where, array('%s'), array('%d'));
		return $result;
	}

	/**
	 * Gets the payment data from db.
	 *
	 * @param string $id: payment intent id
	 *
	 * @return array|object|void|null
	 */
	private function wpsd_get_donation($id){
		global $wpdb;
		$tableName = WPSD_TABLE;
		return $wpdb->get_row( "SELECT * FROM $tableName WHERE wpsd_payment_intent_id = '$id'");
	}
	/**
	 * Sets the payment as complete for a specific donation.
	 *
	 * @param \Stripe\PaymentIntent $paymentIntent
	 */
	function wpsd_update_payment_status($paymentIntent){
		global $wpdb;
		$tableName = WPSD_TABLE;

		// if there is no record in the database for this payment intent, then add the record now
		// This is a hack for donorbox, where the donation was not submitted through the form on the ALL site.
		$idExists = $this->wpsd_get_donation($paymentIntent->id);

		// if there's nothing in the database then just quit already! donorbox going through zapier... 
		if($idExists === null) {
			return false;
		}

		// if ($idExists === null) {
		// 	$tableName = WPSD_TABLE;
		// 	list($first_name, $last_name) = explode(" ", $paymentIntent->charges->data[0]->billing_details->name, 2);
		// 	$country_temp = ($paymentIntent->charges->data[0]->billing_details->address->country !== null) ? $paymentIntent->charges->data[0]->billing_details->address->country : $paymentIntent->charges->data[0]->payment_method_details->card->country;
		// 	$country_code = ($country_temp !== null) ? $country_temp: "ZZ";
		// 	$campaign_temp = ($paymentIntent->charges->data[0]->metadata->donorbox_campaign !== null) ? $paymentIntent->charges->data[0]->metadata->donorbox_campaign: "";
		// 	$recurring_temp = ($paymentIntent->charges->data[0]->metadata->donorbox_recurring_donation !== null) ? $paymentIntent->charges->data[0]->metadata->donorbox_recurring_donation: "false";
		// 	$values_and_format = array(
		// 		'wpsd_donation_for' 					=> [ 'value' => 'Asian Legacy Library', 'format' => '%s' ],
		// 		'wpsd_donator_first_name' 		=> [ 'value' => $first_name, 'format' => '%s', ],
		// 		'wpsd_donator_last_name' 			=> [ 'value' => $last_name, 'format' => '%s', ],
		// 		'wpsd_donator_email' 					=> [ 'value' => $paymentIntent->receipt_email, 'format' => '%s', ],
		// 		'wpsd_donator_phone' 					=> [ 'value' => $paymentIntent->charges->data[0]->billing_details->phone, 'format' => '%s', ],
		// 		'wpsd_donator_country' 				=> [ 'value' => $country_code, 'format' => '%s', ],
		// 		'wpsd_donator_state' 					=> [ 'value' => $paymentIntent->charges->data[0]->billing_details->address->state, 'format' => '%s', ],
		// 		'wpsd_donator_city' 					=> [ 'value' => $paymentIntent->charges->data[0]->billing_details->address->city, 'format' => '%s', ],
		// 		'wpsd_donator_zip' 						=> [ 'value' => $paymentIntent->charges->data[0]->billing_details->address->postal_code, 'format' => '%s', ],
		// 		'wpsd_donator_address' 				=> [ 'value' => $paymentIntent->charges->data[0]->billing_details->address->line1 . ',' . $paymentIntent->charges->data[0]->billing_details->address->line2, 'format' => '%s', ],
		// 		'wpsd_campaign' 							=> [ 'value' => $campaign_temp, 'format' => '%s', ],
		// 		'wpsd_campaign_id' 						=> [ 'value' => '', 'format' => '%s', ],
		// 		'wpsd_fund' 									=> [ 'value' => '', 'format' => '%s', ],
		// 		'wpsd_fund_id' 								=> [ 'value' => '', 'format' => '%s', ],
		// 		'wpsd_in_memory_of_field_id' 	=> [ 'value' => '', 'format' => '%s', ],
		// 		'wpsd_in_memory_of' 					=> [ 'value' => '', 'format' => '%s', ],
		// 		'wpsd_is_recurring' 					=> [ 'value' => $recurring_temp, 'format' => '%d', ],
		// 		'wpsd_payment_intent_id' 			=> [ 'value' => $paymentIntent->id, 'format' => '%s', ],
		// 		'wpsd_payment_complete' 			=> [ 'value' => 0, 'format' => '%d', ],
		// 		'wpsd_donated_amount' 				=> [ 'value' => $paymentIntent->amount, 'format' => '%s', ],
		// 		'wpsd_amount_id' 							=> [ 'value' => 0, 'format' => '%d', ],
		// 		'wpsd_donation_datetime' 			=> [ 'value' => date('Y-m-d h:i:s'), 'format' => '%s', ],
		// 		'wpsd_currency' 							=> [ 'value' => $paymentIntent->charges->data[0]->currency, 'format' => '%s', ],
		// 		'wpsd_payment_method' 				=> [ 'value' => $paymentIntent->charges->data[0]->payment_method, 'format' => '%s', ],
		// 		'wpsd_customer_id' 						=> [ 'value' => $paymentIntent->charges->data[0]->customer, 'format' => '%s', ],
		// 	);
		// 	// formats:
		// 	// %s: string, %d: int, %f: float
		// 	$formats = [];
		// 	$values = [];
		// 	foreach ( $values_and_format as $field => $data_item ) {
		// 		$formats[] = $data_item['format'];
		// 		$values[$field] = $data_item['value'];
		// 	}
		// 	$result = $wpdb->insert($tableName, $values, $formats);
		// 	if($result){ $insertedID = $wpdb->insert_id; }
		// }
		// now that we know that a DB record exists, update the payment complete field and return true;
		
		$data = array( 'wpsd_payment_complete' => 1, );
		$where = array( 'wpsd_payment_intent_id' => $paymentIntent->id, );
		$result = $wpdb->update($tableName, $data, $where, array('%d'));

		return false !== $result;
	}

	/**
	 * Sends payment data to Kindful.
	 *
	 * @param $donation
	 * @param \Stripe\Charge $charge
	 */
	private function wpsd_send_to_kindful($donation, $charge){
		$wpsdKeySettings = stripslashes_deep(unserialize(get_option('wpsd_key_settings')));
		
		$currency = $this->wpsd_get_currency();
		$amount_val = null;
		if($donation->wpsd_amount_id){
			$amount = $this->wpsd_get_amount($donation->wpsd_amount_id);
			$amount_val = $amount->wpsd_amount;
		}
		else {
			$amount_val =  $donation->wpsd_donated_amount;
		}
		if ($donation->wpsd_donator_country != "ZZ") {
			$countries = $this->wpsd_init_countries();
			/** @var  Country $country */
			//$country = $countries->findOne(array('code' => $donation->wpsd_donator_country));
			//$country_long = $country->getName();
			$country_long = "ZZ";
			$stateCode = null;
			if($donation->wpsd_donator_state){
				$states = $country->getStates();
				$state = $states->find(array('name' => $donation->wpsd_donator_state));
				$state = $state->first();
				$isoCode = $state->isoCode;
				$stateCode = substr($isoCode, strlen($isoCode) -2, 2);
			}
		}
		$campaign = $donation->wpsd_campaign;
		$campaign_id = $donation->wpsd_campaign_id;
		$fund = $donation->wpsd_fund;
		$fund_id = $donation->wpsd_fund_id;
		$recurring = (bool) $donation->wpsd_is_recurring;
		$transaction_type = $recurring ? "offline_recurring": "credit";
		$data = array(
			array(
				"id"                                 => $donation->wpsd_donator_email,
				"first_name"                         => $donation->wpsd_donator_first_name,
				"last_name"                          => $donation->wpsd_donator_last_name,
				"email"                              => $donation->wpsd_donator_email,
				"addr1"                              => $donation->wpsd_donator_address,
				"city"                               => $donation->wpsd_donator_city,
				"state"                              => $stateCode,
				"postal"                             => $donation->wpsd_donator_zip,
				"country"                            => $country_long,
				"primary_phone"                      => $donation->wpsd_donator_phone,
				"stripe_customer_id"                 => $donation->wpsd_customer_id,
				"transaction_id"                     => null,
				"amount_in_cents"                    => $amount_val,
				"currency"                           => strtolower($currency),
				"campaign"                           => $campaign,
				"campaign_id"                        => $campaign_id,
				"fund"                               => $fund,
				"fund_id"                            => $fund_id,
				"stripe_charge_id"                   => $charge->id,
				"transaction_type"                   => $transaction_type,
			)
		);
		$body_data = array(
			"data_format"  => "contact_with_transaction",
			"action_type" => "update",
			"data_type" => "json",
			"match_by" => array(
				'fund' => 'id',
				'campaign' => 'id',
				'contact' => 'email',
				"custom_field" => "id",
			),
			"funds" => array($fund_id),
			"campaigns" => array($campaign_id),
			"contacts" => array($donation->wpsd_donator_email),
		);
		// set the custom fields values:
		$in_memory_of_field_id = $donation->wpsd_in_memory_of_field_id;
		if ($in_memory_of_field_id) {
			$body_data['match_by']['custom_field'] = 'id';
			$body_data['custom_fields'] = array($in_memory_of_field_id);
			$data[0][$in_memory_of_field_id] = $donation->wpsd_in_memory_of;
		}
		$body_data['data'] = $data;
		$token = $wpsdKeySettings['wpsd_kindful_token'];
		$url = $wpsdKeySettings['wpsd_kindful_url']  . "/api/v1/imports";
		$args = array(
			'body' => json_encode($body_data),
			'headers' => array(
				'Authorization' => 'Token token="' . $token . '"',
                'Content-Type' => 'application/json'
			)
		);
		$result = wp_remote_post($url, $args);
	}
	
	/**
	 * @return MemberCollection
	 */
	function wpsd_init_countries() {
		$earth = new Earth();
		$countries = $earth->setLocale( get_locale() )->getCountries();
		try {
			$countries->toArray();
		} catch ( MisconfigurationException $e ) {
			// no translation found, fallback to english
			$countries = $earth->setLocale(TranslationAgency::LANG_ENGLISH)->getCountries();
		}
		return $countries;
	}
	/**
	 * Creates Stripe payment subscription.
	 *
	 * @param object $donation: the donation
	 *
	 * @return string|\Stripe\Subscription
	 */
	private function wpsd_create_stripe_subscription($donation){
		
		// 1. get or create product:
		$product = $this->wpsd_get_stripe_product($donation);
		if (is_string($product)) {
			wp_send_json_error($product, 500);
			wp_die();
		}
		$price = $this->wpsd_get_stripe_price($product->id);
		if (is_string($price)) {
			wp_send_json_error($price, 500);
			wp_die();
		}
		$customer = $donation->wpsd_customer_id;

		// 2. create subscription:
		$stripe = $this->wpsd_get_stripe_client();
		
		// Create the subscription
		$trial_end = strtotime("+1 month");
		$error = null;
		$subscription = null;
		try {
			$subscription = $stripe->subscriptions->create( [
				'customer' => $customer,
				'items'    => [
					[
						'price' => $price->id,
					],
				],
				'trial_end' => $trial_end,
				'expand'   => [ 'latest_invoice.payment_intent' ],
			] );
		} catch ( \Stripe\Exception\ApiErrorException $e ) {
			$error = $e->getMessage();
		}
		if ($error) {
			return $error;
		}
		return $subscription;
	}
	
	protected function wpsd_get_amount($id){
		global $wpdb;
		$tableName = WPSD_TABLE_AMOUNT;
		return $wpdb->get_row( "SELECT * FROM $tableName WHERE wpsd_amount_id = $id");
	}
	
	protected function wpsd_get_stripe_product($donation){
		$amount = $donation->wpsd_donated_amount;
		$amount_id = $donation->wpsd_amount_id;
		/** @var \Stripe\Product $product */
		$product = null;
		$error = null;
		if ($amount_id) {
			$amountObj = $this->wpsd_get_amount($amount_id);
			if ($amountObj) {
				// get already existing product:
				try {
					$product = $this->client->products->retrieve( $amountObj->wpsd_stripe_product_id );
				}
				catch ( \Exception $e ) {
					$error = $e->getMessage();
				}
			}
			else {
				// create product with the donation amount:
				try {
					$product = $this->wpsd_create_stripe_product( $amount );
				}
				catch ( \Exception $e ) {
					$error = $e->getMessage();
				}
			}
		}
		else {
			// create product with the donation amount:
			try {
				$amount = intval($amount);
				$product = $this->wpsd_create_stripe_product( $amount );
			}
			catch ( \Exception $e ) {
				$error = $e->getMessage();
			}
		}
		if ($error) {
			return $error;
		}
		return $product;
	}
}