<?php
use Stripe\StripeClient;
trait StripeHelper{
	/**
	 * @param $price
	 *
	 * @return string|\Stripe\Product|null
	 */
	function wpsd_create_stripe_product($price){
		$stripe = $this->wpsd_get_stripe_client();
		$product = null;
		try {
			// 1. create product
			$product = $stripe->products->create(array(
				'name' => "wp_stripe_donation_" . $price,
				'description' => 'auto generated product for use in WP Stripe Donation plugin.',
				'statement_descriptor' => "Donation",
			));
		} catch ( Exception $e ) {
			return $e->getMessage();
			
		}
		try {
			$stripe_price = $this->wpsd_create_stripe_price( $price, $product->id );
		} catch ( Exception $e ) {
			return $e->getMessage();
		}
		
		if ($product) {
			return $product;
		}
		return null;
	}
	
	function wpsd_create_stripe_price($amount, $product_id){
		$stripe = $this->wpsd_get_stripe_client();
		$currency = $this->wpsd_get_currency();
		// 2. create recurring price
		$price = null;
		$price = $stripe->prices->create( array(
			'unit_amount' => $amount,
			'currency'    => $currency,
			'recurring'   => array(
				'interval' => 'month'
			),
			'product'     => $product_id,
		) );
		return $price;
	}
	
	/**
	 * @return string
	 */
	protected function wpsd_get_currency(){
		$wpsdGeneralSettings = stripslashes_deep( unserialize( get_option('wpsd_general_settings') ) );
		$currency = isset($wpsdGeneralSettings['wpsd_donate_currency'])
		            && !empty($wpsdGeneralSettings['wpsd_donate_currency'])?
			$wpsdGeneralSettings['wpsd_donate_currency']: "USD";
		return $currency;
	}
	
	function wpsd_update_stripe_price($amount, $product_id){
		$stripe = $this->wpsd_get_stripe_client();
		$price = null;
		try {
			// 1. set all prices for this product as inactive.
			$prices = $stripe->prices->all(array('product' => $product_id));
			/** @var \Stripe\Price $item */
			foreach ( $prices as $item ) {
				if ($item->active) {
					$stripe->prices->update($item->id, array('active' => false));
				}
			}
			// 2. create new price and attach it to this product:
			$price = $this->wpsd_create_stripe_price($amount, $product_id);
		} catch ( Exception $e ) {
			return null;
		}
		return $price;
	}
	
	/**
	 * Gets a stripe price by product id.
	 *
	 * @param $product_id
	 *
	 * @return string|\Stripe\Price
	 */
	function wpsd_get_stripe_price($product_id) {
		$stripe = $this->wpsd_get_stripe_client();
		/** @var \Stripe\Price $price */
		$price = null;
		try {
			$prices = $stripe->prices->all( array( 'product' => $product_id ) );
			$price = $prices->first();
		} catch ( \Exception $e ) {
			$err = $e->getMessage();
			return $err;
		}
		return $price;
	}
	
	/**
	 * @param $email
	 *
	 * @return string|\Stripe\Customer
	 */
	public function wpsd_get_stripe_customer($email){
		$stripe = $this->wpsd_get_stripe_client();
		$error = null;
		$customer = null;
		try {
			$customers = $stripe->customers->all(array('email' => $email));
			if($customers->count()){
				$customer = $customers->first();
			}
		} catch ( \Exception $e ) {
			return $e->getMessage();
		}
		return $customer;
	}
	
	/**
	 * @param $email
	 * @param $data
	 * @param $paymentMethodId
	 *
	 * @return \Stripe\Customer|string
	 */
	function wpsd_create_stripe_customer($email, $data, $paymentMethodId = null){
		$stripe = $this->wpsd_get_stripe_client();
		$customer = null;
		$customer_details = array(
			'email' => $email,
			'name' => $data['first_name'] . ' ' . $data['last_name'],
			'phone' => $data['phone'],
			'address' => array(
				'city' => $data['city'],
				'country' => $data['country'],
				'line1' => $data['address'],
				'postal_code' => $data['zip'],
			)
		);
		if(isset($data['state'])){
			$customer_details['address']['state'] = $data['state'];
		}
		try {
			$customer = $stripe->customers->create($customer_details);
		} catch ( \Exception $e ) {
			return $e->getMessage();
		}
		
		if(!$paymentMethodId){
			return $customer;
		}
		$customer = $this->wpsd_attach_payment_method($paymentMethodId, $customer->id);
		return $customer;
	}
	
	public function wpsd_attach_payment_method($paymentMethodId, $customerId){
		$stripe = $this->wpsd_get_stripe_client();
		$customer = null;
		try {
			$stripe->paymentMethods->attach($paymentMethodId, array('customer' => $customerId));
		} catch ( \Exception $e ) {
			return $e->getMessage();
		}
		try {
			$params = [
				"invoice_settings" => [
					"default_payment_method" => $paymentMethodId,
				],
			];
			$customer = $stripe->customers->update($customerId, $params);
		} catch ( \Exception $e ) {
			return $e->getMessage();
		}
		return $customer;
	}
	
	/**
	 * @return StripeClient
	 */
	protected function wpsd_get_stripe_client(){
		$wpsdKeySettings = stripslashes_deep(unserialize(get_option('wpsd_key_settings')));
		$stripe_key = base64_decode($wpsdKeySettings['wpsd_secret_key']);
		$stripe = new StripeClient($stripe_key);
		return $stripe;
	}
	
	protected function wpsd_clean_amount_val($amount_val){
		return filter_var($amount_val, FILTER_SANITIZE_NUMBER_INT);
	}
}