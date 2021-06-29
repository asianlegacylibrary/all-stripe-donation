<?php
$options = new OptionsHelper();
$wpsdDonateCurrency = $options->get_value("general", "wpsd_donate_currency", "USD", false);
$currencies = $this->hm_get_all_currency();
$currency_symbol = null;
foreach ( $currencies as $item ) {
	if($item->abbreviation === $wpsdDonateCurrency){
		$currency_symbol = $item->symbol;
	}
}
$wpsdDonateButtonText = $options->get_value("general", "wpsd_donate_button_text", "Donate Now");
$wpsdFormBanner = $options->get_value("template", "wpsd_form_banner", "", false);
$wpsd_display_header = $options->get_value("template", "wpsd_display_header", "", false);
$wpsd_donator_first_name_label = $options->get_value("template", "wpsd_donator_first_name_label", "First Name", false);
$wpsd_donator_last_name_label = $options->get_value("template", "wpsd_donator_last_name_label", "Last Name", false);
$wpsd_donator_email_label = $options->get_value("template", "wpsd_donator_email_label", "Email", false);
$wpsd_donator_phone_label = $options->get_value("template", "wpsd_donator_phone_label", "Phone", false);
$wpsd_donator_country_label = $options->get_value("template", "wpsd_donator_country_label", "Country", false);
$wpsd_donator_state_label = $options->get_value("template", "wpsd_donator_state_label", "State", false);
$wpsd_donator_city_label = $options->get_value("template", "wpsd_donator_city_label", "City", false);
$wpsd_donator_zip_label = $options->get_value("template", "wpsd_donator_zip_label", "Zip", false);
$wpsd_donator_address_label = $options->get_value("template", "wpsd_donator_address_label", "Address", false);
$wpsd_donate_amount_label = $options->get_value("template", "wpsd_donate_amount_label", "Choose Your Amount", false);
$wpsd_donate_details_label = $options->get_value("template", "wpsd_donate_details_label", "Personal Details", false);
$wpsd_donate_sponsor_volume = $options->get_value("template", "wpsd_donate_sponsor_volume", "Donations that total $120 USD sponsor a volume in your name.", false);
//$wpsd_in_memory_of_label = $options->get_value("template", "wpsd_in_memory_of_label", "In memory of", false);
$wpsd_one_time_label = $options->get_value("template", "wpsd_one_time_label", "One Time", false);
$wpsd_monthly_label = $options->get_value("template", "wpsd_monthly_label", "Monthly", false);
$wpsd_card_label = $options->get_value('template','wpsd_card_label', 'Credit Card Details', false);
$wpsd_card_agreement = $options->get_value('template','wpsd_card_agreement', '', false);
$wpsd_custom_amount_label = $options->get_value('template','wpsd_custom_amount_label', 'Enter Your Amount', false);

// Let's trick WPLM into translating everything because WPLM can't read variables we set in the Admin.
$wpsdTranslations = array(
	'wpsd_donate_now' 		=> esc_html__('Donate Now', 'wp-stripe-donation'),
	'wpsd_donor_phone'	=> esc_html__('Phone', 'wp-stripe-donation'),
	'wpsd_donor_country'	=> esc_html__('Country', 'wp-stripe-donation'),
	'wpsd_donor_state'		=> esc_html__('State', 'wp-stripe-donation'),
	'wpsd_donor_city'		    => esc_html__('City', 'wp-stripe-donation'),
	'wpsd_donor_zip'	=> esc_html__('Zip', 'wp-stripe-donation'),
	'wpsd_donor_postal'		=> esc_html__('Postal Code', 'wp-stripe-donation'),
	'wpsd_donor_address'		    => esc_html__('Address', 'wp-stripe-donation'),
	'wpsd_donor_address2'	=> esc_html__('Address 2', 'wp-stripe-donation'),
	'wpsd_donor_choose_amount'		=> esc_html__('Choose Your Amount', 'wp-stripe-donation'),
	//'wpsd_donor_memory'		    => esc_html__('In memory of', 'wp-stripe-donation'),
	'wpsd_donor_one'	=> esc_html__('One Time', 'wp-stripe-donation'),
	'wpsd_donor_monthly'		=> esc_html__('Monthly', 'wp-stripe-donation'),
	'wpsd_donor_card_details'		    => esc_html__('Credit Card Details', 'wp-stripe-donation'),
	'wpsd_donor_enter_amount'	=> esc_html__('Enter Your Amount', 'wp-stripe-donation'),
	'wpsd_donor_agreement'	=> esc_html__('Agreement', 'wp-stripe-donation')
);

$campaign = $params['campaign'];
$campaign_id = $params['campaign_id'];  //$this->dc($campaign_id);
$fund = $params['fund'];
$fund_id = $params['fund_id'];
//$in_memory_of_field_id = $params['imof'];
$custom_amount = $params['custom_amount'] === "true";
$countries = $this->wpsd_get_countries();
$amounts = $this->wpsd_get_all_amounts();  //$this->dc($amounts);

foreach ( $amounts as $wpsd_amount ) {
	$last_2 = substr($wpsd_amount->wpsd_amount, strlen($wpsd_amount->wpsd_amount) -2);
	if ($last_2 === "00") { $formatted = number_format($wpsd_amount->wpsd_amount/100, 0); }
	else { $formatted = number_format($wpsd_amount->wpsd_amount/100, 2); }
	$wpsd_amount->wpsd_amount = $formatted;
}

// THIS WAS CAUSING FATAL ERROR, name conflict so changed name from compareByName to comapareByAmount
function compareByAmount($a, $b) {
	return $a->wpsd_amount - $b->wpsd_amount;
}

usort($amounts, 'compareByAmount'); //$this->dc($amounts);

?>

<div class="wpsd-master-wrapper wpsd-template-0" id="wpsd-wrap-all">
	<?php if( '1' === $wpsd_display_header ) { ?>
	<div class="wpsd-wrapper-header">
		<h2><?php _e('WP Stripe Donation', 'wp-stripe-donation'); ?></h2>
	</div>
	<?php } ?>
	<?php if( intval( $wpsdFormBanner ) > 0 ) {
		echo wp_get_attachment_image( $wpsdFormBanner, 'full', false, array('class' => 'wpsd-form-banner') );
	} ?>
	<!-- OLD LOCATION <div class="wpsd-wrapper-content"> -->
		<form action="" method="POST" id="wpsd-donation-form-id">
			<!-- Input section -->
			<input type="hidden" required name="wpsd_campaign" id="wpsd_campaign" class="wpsd-text-field" value="<?php echo $campaign; ?>">
			<input type="hidden" required name="wpsd_campaign_id" id="wpsd_campaign_id" class="wpsd-text-field" value="<?php echo $campaign_id; ?>">
			<input type="hidden" required name="wpsd_fund" id="wpsd_fund" class="wpsd-text-field" value="<?php echo $fund; ?>">
			<input type="hidden" required name="wpsd_fund_id" id="wpsd_fund_id" class="wpsd-text-field" value="<?php echo $fund_id; ?>">
			<!-- <input type="hidden" required name="wpsd_in_memory_of_field_id" id="wpsd_in_memory_of_field_id" class="wpsd-text-field" value="<?php //echo $in_memory_of_field_id; ?>"> -->
			
			<div class="wpsd-wrapper-content">

				<div class="flex-row">

					<div class="flex-column">

						<!-- PERSONAL DETAILS -->
						<div id="wpsd_donate_details">
							<label for="wpsd_donate_details" class="wpsd-donation-form-label"><?php esc_html_e( $wpsd_donate_details_label, 'wp-stripe-donation' ); ?></label>
							
							<div class="wpsd_flex_item w-100">
								<input type="text" name="wpsd_donator_first_name" id="wpsd_donator_first_name" class="wpsd-text-field" placeholder="<?php esc_attr_e( $wpsd_donator_first_name_label, 'wp-stripe-donation'); ?>" required>
							</div>
							<div class="wpsd_flex_item w-100">
								<input type="text" name="wpsd_donator_last_name" id="wpsd_donator_last_name" class="wpsd-text-field" placeholder="<?php esc_attr_e( $wpsd_donator_last_name_label,'wp-stripe-donation' ); ?>" required>
							</div>
							<div class="wpsd_flex_item w-100">
								<input type="email" name="wpsd_donator_email" id="wpsd_donator_email" class="wpsd-text-field" placeholder="<?php esc_attr_e( $wpsd_donator_email_label, 'wp-stripe-donation' ); ?>" required>
							</div>
							<div class="wpsd_flex_item w-100">
								<select class="wpsd-select-field" name="wpsd_donator_country" id="wpsd_donator_country" required>
									<option value=""><?php _e('Country', 'wp-stripe-donation'); ?></option>
									<?php foreach ($countries as $country) { ?>
									<option value="<?php echo $country['code']; ?>"><?php echo $country['name']; ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="wpsd_flex_item w-100">
								<input type="text" name="wpsd_donator_address" id="wpsd_donator_address" class="wpsd-text-field" placeholder="<?php esc_attr_e( $wpsd_donator_address_label, 'wp-stripe-donation' ); ?>" required>
							</div>
							<div class="wpsd_flex_item w-100">
								<input type="text" name="wpsd_donator_city" id="wpsd_donator_city" class="wpsd-text-field" placeholder="<?php esc_attr_e( $wpsd_donator_city_label, 'wp-stripe-donation' ); ?>" required>
							</div>
							<div class="wpsd_flex_item w-100">
								<select class="wpsd-select-field" name="wpsd_donator_state" id="wpsd_donator_state">
									<option value="" class="wpsd_default_option"><?php _e('State', 'wp-stripe-donation'); ?></option>
								</select>
							</div>
							<div class="wpsd_flex_item w-100">
								<input type="text" name="wpsd_donator_zip" id="wpsd_donator_zip" class="wpsd-text-field" placeholder="<?php esc_attr_e( $wpsd_donator_zip_label, 'wp-stripe-donation' ); ?>" required>
							</div>
							<div class="wpsd_flex_item w-100">
								<input type="text" name="wpsd_donator_phone" id="wpsd_donator_phone" class="wpsd-text-field" placeholder="<?php esc_attr_e( $wpsd_donator_phone_label, 'wp-stripe-donation' ); ?>" required>
							</div>
							<!-- <div class="wpsd_flex_item w-100">
								<div class="wpsd_in_memory_of_con">
									<input type="text" name="wpsd_in_memory_of" id="wpsd_in_memory_of" class="wpsd-text-field" placeholder="<?php //esc_attr_e( $wpsd_in_memory_of_label, 'wp-stripe-donation' ); ?>">
								</div>
							</div> -->
							
						</div>

						<!-- CREDIT CARD / STRIPE -->
						<div class="wpsd_flex_item w-100 wpsd_card_label_con">
							<div class="wpsd-donation-form-label">
								<?php _e($wpsd_card_label, 'wp-stripe-donation'); ?>
							</div>
						</div>
						<div class="wpsd_flex_item w-100">
							<div id="card-element"><!--Stripe.js injects the Card Element--></div>
						</div>
							
					</div>
					<div class="flex-column">	
						<!-- DONATION AMOUNT / TYPE -->
						<div id="wpsd_donate_amount">
							<div class="wpsd_flex_con bg-grey nudge-pad">
								<label for="wpsd_donate_amount" class="wpsd-donation-form-label"><?php esc_html_e( $wpsd_donate_amount_label, 'wp-stripe-donation' ); ?></label>
								<!-- FC_POST_ID array location -->
								<?php foreach ( $amounts as $wpsdKey => $wpsdAmount) {
									$campaign_ids = array_map('trim', explode(',', $wpsdAmount->wpsd_campaign_ids));  //$this->dc($campaign_ids);
									if (in_array($campaign_id, $campaign_ids)) { ?>
										<label class="wpsd_flex_item w-25 wpsd_radio_con">
											<input type="radio" id="wpsd_amount_<?php echo esc_html($wpsdAmount->wpsd_amount_id); ?>" name="wpsd_donate_amount" value="<?php esc_attr_e($wpsdAmount->wpsd_amount_id, 'wp-stripe-donation' ); ?>" <?php echo $wpsdKey === 0? "checked": ""; ?>>
											<span class="label_text">
												<?php echo esc_html( $currency_symbol ) . esc_html__($wpsdAmount->wpsd_amount); ?>
											</span>
										</label>
								<?php } 
								} ?>
									<?php if($custom_amount){ ?>
										<div class="wpsd_flex_item w-100" id="wpsd_donate_other_amount_wrapper">
											<input id="wpsd_donate_other_amount" type="currency" class="wpsd_donate_amount wpsd-text-field" name="wpsd_donate_other_amount" placeholder="<?php esc_html_e($wpsd_custom_amount_label, 'wp-stripe-donation'); ?>">
										</div>
									<?php } ?>
							</div>
						
						
							<div class="wpsd_flex_left_con bg-grey nudge-pad wpsd_is_recurring_wrapper">
								<div class="wpsd_flex_item wpsd_radio_btn_con">
									<label class="wpsd_radio_con">
										<input type="radio" name="wpsd_is_recurring" id="wpsd_is_recurring" class="wpsd_is_recurring" value="0" checked>
										<span class="label_text"><?php esc_html_e( $wpsd_one_time_label , 'wp-stripe-donation' ); ?></span>
									</label>
								</div>
								<div class="wpsd_flex_item wpsd_radio_btn_con">
									<label class="wpsd_radio_con">
										<input type="radio" name="wpsd_is_recurring" id="wpsd_is_recurring" class="wpsd_is_recurring" value="1">
										<span class="label_text"><?php esc_html_e( $wpsd_monthly_label , 'wp-stripe-donation' ); ?></span>
									</label>
								</div>
							</div>
					
							<!-- SUBMIT! -->
							<div id="wpsd_donate_submit">
								<div class="w-100">
									<input type="submit" name="wpsd-donate-button" class="wpsd-donate-button" value="<?php echo esc_html__('Donate Now', 'wp-stripe-donation'); ?>">
								</div>
							</div>

							<!-- Find location for these, related to submission success -->
							<div class="wpsd_flex_item w-100 wpsd-donation-message-con message-hidden">
								<br>
								<div id="wpsd-donation-message" class="wpsd-alert">&nbsp</div>
							</div>
							<div class="wpsd_flex_item w-100">
								<p class="wpsd_card_agreement"><?php _e($wpsd_card_agreement, 'wp-stripe-donation'); ?></p>
							</div>

						</div>
					</div>	
				</div>
			</div>
		</form>
	<!-- OLD LOCATION <div class="wpsd-wrapper-content"> -->
</div>