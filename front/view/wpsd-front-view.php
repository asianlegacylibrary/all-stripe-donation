<?php

$options = new OptionsHelper();
$wpsdDonateCurrency = $options->get_value("general", "wpsd_donate_currency", "USD", false);
$wpsd_donation_amounts = $options->get_value("general", "wpsd_donation_amounts", "", false);


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
$wpsd_donate_req_fields_msg_label = $options->get_value("template", "wpsd_donate_req_fields_msg_label", "Required Fields Label", false);
$wpsd_donate_assistance_label = $options->get_value("template", "wpsd_donate_assistance_label", "If you need assistance with your donation, please email", false);
$wpsd_donate_assistance_email = $options->get_value("template", "wpsd_donate_assistance_email", "donations@asianlegacylibrary.org", false);
$wpsd_donate_in_us_dollars = $options->get_value("template", "wpsd_donate_in_us_dollars", "All donations are in US dollars", false);
$wpsd_donate_sponsor_volume = $options->get_value("template", "wpsd_donate_sponsor_volume", "Donations that total $120 USD sponsor a volume in your name.", false);
//$wpsd_in_memory_of_label = $options->get_value("template", "wpsd_in_memory_of_label", "In memory of", false);
$wpsd_one_time_label = $options->get_value("template", "wpsd_one_time_label", "One Time", false);
$wpsd_monthly_label = $options->get_value("template", "wpsd_monthly_label", "Monthly", false);
$wpsd_card_label = $options->get_value('template','wpsd_card_label', 'Credit Card Details', false);
$wpsd_card_agreement = $options->get_value('template','wpsd_card_agreement', '', false);
$wpsd_custom_amount_label = $options->get_value('template','wpsd_custom_amount_label', 'Enter Your Amount', false);

// Let's trick WPLM into translating everything because WPLM can't read variables we set in the Admin.
// this is a real workaround because it has to match the current english translation
// if you change the english translation in settings then you have to change here.
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
	'wpsd_donor_personal_details'		=> esc_html__('Personal Details Label', 'wp-stripe-donation'),
	//'wpsd_donor_memory'		    => esc_html__('In memory of', 'wp-stripe-donation'),
	'wpsd_donor_one'	=> esc_html__('One Time', 'wp-stripe-donation'),
	'wpsd_donor_monthly'		=> esc_html__('Monthly', 'wp-stripe-donation'),
	'wpsd_donor_card_details'		    => esc_html__('Credit Card Details', 'wp-stripe-donation'),
	'wpsd_donor_enter_amount'	=> esc_html__('Enter Your Amount', 'wp-stripe-donation'),
	'wpsd_donor_agreement'	=> esc_html__('Agreement', 'wp-stripe-donation'),
	'wpsd_donate_assistance_label' => esc_html__('Assistance Label', 'wp-stripe-donation'),
	'wpsd_donate_in_us_dollars' => esc_html__('All donations are in US dollars', 'wp-stripe-donation'),
	'wpsd_donate_req_fields_msg_label' => esc_html__('Required Fields Label', 'wp-stripe-donation'),
	'dontmatterwhatcalled' => esc_html__('If you need assistance with your donation, please email', 'wp-stripe-donation'),
	'reallyitdoesnt' => esc_html__('Please be sure to fill out all required fields, * denotes a required field', 'wp-stripe-donation')
);

// could fix this above by looping through template settings

$campaign = $params['campaign'];
$campaign_id = $params['campaign_id'];  //$this->dc($campaign_id);
$fund = $params['fund'];
$fund_id = $params['fund_id'];
//$in_memory_of_field_id = $params['imof'];
$custom_amount = $params['custom_amount'] === "true";
$allow_recurring = $params['allow_recurring'] === "true";

$countries = $this->wpsd_get_countries();
//$amountsb = $this->wpsd_get_all_amounts();

// added section to allow shortcode option to choose default donation amounts, pulls from general settings if no short code
$donation_amounts = array_key_exists('donation_amounts', $params) ? $params['donation_amounts'] : $wpsd_donation_amounts;
$donation_amounts = gettype($donation_amounts) === 'array' 
	? array_map('intval', $donation_amounts) 
	: array_map('intval', explode(',', $donation_amounts));

$new_amounts = array();
foreach($donation_amounts as $a) {
	if(intval($a) != 0) {
		array_push($new_amounts, intval($a));
	}
}
$new_amounts = array_unique($new_amounts);
sort($new_amounts);

// note this is function to console.log
//$this->dc('hi there again again and again');


?>


<script>

async function sendDonationInfo() {

	let shortcodes = Object.assign(
        {},
        ...Object.keys(wpsdSetShortcodes).map((key) => ({
            [`wpsd_${key}`]: wpsdSetShortcodes[key]
        }))
    )
	const currency = shortcodes.wpsd_currency ? shortcodes.wpsd_currency : 'USD'

	let is_recurring = isNaN(
		parseInt($('#wpsd_is_recurring:checked').val())
	)
		? 0
		: parseInt($('#wpsd_is_recurring:checked').val())

	// as far as I can tell custom amount is always true (1)
	// so I'm just setting it to 1 here
	const requestData = {
		action: 'wpsd_donation',
		wpsdSecretKey: wpsdAdminScriptObj.publishable_key,
		amount: $('#wpsd_donate_other_amount').val(),
		custom_amount: 1,
		currency: currency,
		first_name: $('#wpsd_donator_first_name').val(),
		last_name: $('#wpsd_donator_last_name').val(),
		email: $('#wpsd_donator_email').val(),
		phone: $('#wpsd_donator_phone').val(),
		country: $('#wpsd_donator_country').val(),
		state: $('#wpsd_donator_state').val(),
		city: $('#wpsd_donator_city').val(),
		zip: $('#wpsd_donator_zip').val(),
		address: $('#wpsd_donator_address').val(),
		wpsd_token: $('input[name=wpsd_token]').val(),
		campaign: wpsdGeneralSettings.wpsd_campaign,
		campaign_id: wpsdGeneralSettings.wpsd_campaign_id,
		fund: wpsdGeneralSettings.wpsd_fund,
		fund_id: wpsdGeneralSettings.wpsd_fund_id,
		is_recurring: is_recurring
	}

	return await request('wpsd_donation', 'POST', requestData)
}


	async function request(action, type, data = null, params = null) {
        return new Promise((resolve, reject) => {
            //disableSubmitBtn()
            // get current locale to prevent a bug in wordpress:
            var url = wpsdAdminScriptObj.ajaxurl + '?action=' + action
            //console.log('in request', action, type, url, data)
            var lang = window.location.href.match(/lang=\w+/g)

            if (lang && lang.length) {
                lang = lang[0]
                lang = lang.replace('lang=', '')
                url += '&lang=' + lang
            }

            const requestOptions = {
                url: url,
                dataType: 'JSON',
                success: function (response) {
                    //console.log('success', response)
                    //activateSubmitBtn()
                    resolve(response.data)
                },
                error: function (response) {
                    //activateSubmitBtn()
                    if (response?.responseJSON?.data) {
                        reject(response.responseJSON.data)
                    } else if (response?.statusText) {
                        reject(response.statusText)
                    }
                }
            }

            if (type === 'POST') {
                requestOptions.type = type
                requestOptions.contentType = 'application/json'
            }

            if (data) {
                requestOptions.data = JSON.stringify(data)
            }

            if (params) {
                const fields = Object.keys(params)
                for (let field of fields) {
                    requestOptions.url += '&' + field + '=' + params[field]
                }
            }

            $.ajax(requestOptions)
        })
    }

async function setTokenVal(t)
{
    $('input[name=wpsd_token]').val(t);
    return true;
}

async function onSubmit19(token)
{
    const set_token_result = await setTokenVal(token);
    console.log("+131 @ts set_token_result is: ", set_token_result);
	sendDonationInfo()
}

</script>

<div class="wpsd-master-wrapper wpsd-template-0 ts-ln-116" id="wpsd-wrap-all">
	<!-- OLD LOCATION banner / header -->
	<!-- OLD LOCATION <div class="wpsd-wrapper-content"> -->
	<form action="" method="POST" id="wpsd-donation-form-id">
		<!-- Input section -->
        <input type="hidden" name="wpsd_token" value="yo"  id="wpsd-token1">
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
					<div>
						<p class="wpsd-metadata"><?php esc_html_e( $wpsd_donate_req_fields_msg_label, 'wp-stripe-donation' ); ?></p>
					</div>
				</div>
				<div class="flex-column">	
					<!-- DONATION AMOUNT / TYPE -->
					<div id="wpsd_donate_amount">
						
						<div class="wpsd_flex_con bg-grey nudge-pad">
							<label for="wpsd_donate_amount" class="wpsd-donation-form-label radio-label"><?php esc_html_e( $wpsd_donate_amount_label, 'wp-stripe-donation' ); ?></label>
							<!-- FC_POST_ID array location, look in README to find it -->
							<!-- for loop for amounts associated with campaigns -->

							<!-- NEW amounts array for defaults, will add previous back once I understand -->
							<?php foreach($new_amounts as $a) { ?>
								<label class="wpsd_flex_item w-25 wpsd_radio_con">
								<input type="radio" id="<?php echo esc_html($a); ?>" name="wpsd_donate_amount_radio" value="<?php esc_attr_e($a, 'wp-stripe-donation' ); ?>">
								<span class="label_text">
									<?php echo esc_html( $currency_symbol ) . esc_html__($a); ?>
								</span>
							</label>
							<?php } ?>

							<?php if($custom_amount){ ?>
								<div class="wpsd_flex_item w-100" id="wpsd_donate_other_amount_wrapper">
									<input id="wpsd_donate_other_amount" type="currency" class="wpsd_donate_amount no-transform wpsd-text-field other_amount" name="wpsd_donate_other_amount" placeholder="<?php esc_html_e($wpsd_custom_amount_label, 'wp-stripe-donation'); ?>">
								</div>
							<?php } ?>
							<div class="squish-item w-100 ">
								<p class="wpsd-metadata-squish"><?php esc_html_e( $wpsd_donate_in_us_dollars, 'wp-stripe-donation' ); ?></p>
							</div>
							
						
						</div>
					
						<?php if($allow_recurring){ ?>
						<div class="wpsd_flex_left_con bg-grey nudge-pad-no-top wpsd_is_recurring_wrapper">
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
						<?php } ?>
				
						<!-- SUBMIT! -->
						<div id="wpsd_donate_submit">
							<div class="w-100">
								<input 
									type="submit" 
									class="wpsd-donate-button ts-ln-249 g-recaptcha"
                                    data-sitekey="6Ld-pa8kAAAAAKhG5QfKB5ATZewmwEc_TLSrhGbE"
									data-callback="onSubmit19"
									data-action="submit"
									name="wpsd-donate-button" 
									value="<?php echo esc_html__('Donate Now', 'wp-stripe-donation'); ?>">
							</div>
						</div>
						
						<div>
							<p class="wpsd-metadata">
								<?php esc_html_e( $wpsd_donate_assistance_label, 'wp-stripe-donation' ); ?>
								<span>
									<a href="mailto:<?php esc_html_e( $wpsd_donate_assistance_email, 'wp-stripe-donation' ); ?>">
										<?php esc_html_e( $wpsd_donate_assistance_email, 'wp-stripe-donation' ); ?>
									</a>
								</span>
							</p>
						</div>

						<!-- These alerts are server side...need to update styles, also add client side error checking pre-submit -->
						<!-- check out stripe docs, they have a pre-submit that just tricks / pretends it's submitting to validate -->
						<!-- https://github.com/stripe/elements-examples/blob/master/js/index.js >>> triggerBrowserValidation -->
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
	
</div>