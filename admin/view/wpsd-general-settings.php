<?php
$updated = false;
$site_name = get_bloginfo('name');
if (isset($_POST['updateGeneralSettings'])) {
    $wpsdGeneralSettingsInfo = array(
        'wpsd_donation_email' => (sanitize_email($_POST['wpsd_donation_email']) != '') ? sanitize_email($_POST['wpsd_donation_email']) : '',
        'wpsd_payment_title' => (sanitize_text_field($_POST['wpsd_payment_title']) != '') ? sanitize_text_field($_POST['wpsd_payment_title']) : '',
        'wpsd_donation_for' => (sanitize_text_field($_POST['wpsd_donation_for']) != '') ? sanitize_text_field($_POST['wpsd_donation_for']) : $site_name,
        'wpsd_campaign' => (sanitize_textarea_field($_POST['wpsd_campaign']) != '') ? sanitize_textarea_field($_POST['wpsd_campaign']) : '',
        'wpsd_campaign_id' => (sanitize_textarea_field($_POST['wpsd_campaign_id']) != '') ? sanitize_textarea_field($_POST['wpsd_campaign_id']) : '',
        'wpsd_fund' => (sanitize_textarea_field($_POST['wpsd_fund']) != '') ? sanitize_textarea_field($_POST['wpsd_fund']) : '',
        'wpsd_fund_id' => (sanitize_textarea_field($_POST['wpsd_fund_id']) != '') ? sanitize_textarea_field($_POST['wpsd_fund_id']) : '',
        'wpsd_in_memory_of_field' => (sanitize_textarea_field($_POST['wpsd_in_memory_of_field']) != '') ? sanitize_textarea_field($_POST['wpsd_in_memory_of_field']) : '',
        'wpsd_donate_button_text' => (sanitize_text_field($_POST['wpsd_donate_button_text']) != '') ? sanitize_text_field($_POST['wpsd_donate_button_text']) : '',
        'wpsd_donate_currency' => (sanitize_text_field($_POST['wpsd_donate_currency']) != '') ? sanitize_text_field($_POST['wpsd_donate_currency']) : 'USD',
        'wpsd_donation_amounts' => (sanitize_text_field($_POST['wpsd_donation_amounts']) != '') ? sanitize_text_field($_POST['wpsd_donation_amounts']) : '',
    );
	$updated = update_option('wpsd_general_settings', serialize($wpsdGeneralSettingsInfo));
    if($updated){
        $message = "Your information updated successfully.";
        do_action('admin_notices', array('wpsd_type' => 'updated', 'wpsd_message' => $message));
    }
}
$wpsdGeneralSettings = stripslashes_deep(unserialize(get_option('wpsd_general_settings')));
$campaignsOptions = array('' => 'Please select a campaign');
if (is_array($wpsdGeneralSettings)) {
    $wpsdDonationEmail = $wpsdGeneralSettings['wpsd_donation_email'];
    $wpsdPaymentTitle = $wpsdGeneralSettings['wpsd_payment_title'];
	$wpsdDonationFor = $wpsdGeneralSettings['wpsd_donation_for'];
	$wpsdCampaign = $wpsdGeneralSettings['wpsd_campaign'];
	$wpsdCampaignId = $wpsdGeneralSettings['wpsd_campaign_id'];
	$wpsdFund = $wpsdGeneralSettings['wpsd_fund'];
	$wpsdFundId = $wpsdGeneralSettings['wpsd_fund_id'];
	$wpsdInMemoryOfField = $wpsdGeneralSettings['wpsd_in_memory_of_field'];
    $wpsdDonateButtonText = $wpsdGeneralSettings['wpsd_donate_button_text'];
    $wpsdDonateCurrency = $wpsdGeneralSettings['wpsd_donate_currency'];
    $wpsdDonateAmounts = $wpsdGeneralSettings['wpsd_donation_amounts'];
} else {
    $wpsdDonationEmail = "";
    $wpsdPaymentTitle = esc_html__("Donate Us", 'wp-stripe-donation');
	$wpsdDonationFor = $site_name;
	$wpsdCampaign = "";
	$wpsdCampaignId = "";
	$wpsdFund = "";
	$wpsdFundId = "";
	$wpsdInMemoryOfField = "";
	$wpsdDonateButtonText = esc_html__("Donate Now", 'wp-stripe-donation');
    $wpsdPaymentLogo = "";
    $wpsdDonateCurrency = "USD";
    $wpsdDonateAmounts = "";
}

$wpsdKeySettings = stripslashes_deep(unserialize(get_option('wpsd_key_settings')));
$token = $wpsdKeySettings['wpsd_kindful_token'];
$args = array(
	'headers' => array(
		'Authorization' => 'Token token="' . $token . '"',
	)
);
$kindfulApi = $wpsdKeySettings["wpsd_kindful_url"];
$url = $kindfulApi . "/api/v1/custom_fields";
$result = wp_remote_get($url, $args);
$kindfulFields = array();
if ($result['response']['code'] === 200) {
	$result = json_decode($result['body'], true);
	foreach ( $result as $item ) {
		$kindfulFields[] = array(
            'id' => $item['id'],
            'name' => $item['name'],
            'group' => $item['custom_field_group']['id']
        );
	}
}

$url = $kindfulApi . "/api/v1/funds";
$result = wp_remote_get($url, $args);
$kindfulFunds = array();
if ($result['response']['code'] === 200) {
	$result = json_decode($result['body'], true);
	foreach ( $result as $item ) {
		$kindfulFunds[] = array(
			'id' => $item['id'],
			'name' => $item['name'],
		);
	}
}

$url = $kindfulApi . "/api/v1/campaigns";
$result = wp_remote_get($url, $args);
$kindFulCampaigns = array();
if ($result['response']['code'] === 200) {
	$result = json_decode($result['body'], true);
	foreach ( $result as $item ) {
		$kindFulCampaigns[] = array(
			'id' => $item['id'],
			'name' => $item['name'],
		);
	}
}
?>
<script type="application/javascript">
    var kindfulFunds = <?php echo json_encode($kindfulFunds);?>;
    var kindfulCampaigns = <?php echo json_encode($kindFulCampaigns);?>;
</script>
<div id="wpsd-wrap-all" class="wrap">
    <div class="settings-banner">
        <h2><?php _e('WP Stripe General Settings', 'wp-stripe-donation'); ?></h2>
    </div>

    <form name="wpsd-general-settings-form" role="form" class="form-horizontal" method="post" action=""
        id="wpsd-settings-form-id">
        <table class="form-table">
            <tr class="wpsd_donation_email">
                <th scope="row">
                    <label
                        for="wpsd_donation_email"><?php esc_html_e('Donation Info Email', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donation_email" id="wpsd_donation_email" class="regular-text"
                        value="<?php echo esc_attr($wpsdDonationEmail); ?>" />
                    <br>
                    <code><?php _e('Donation information will send to this email', 'wp-stripe-donation'); ?>.</code>
                </td>
            </tr>
            <tr class="wpsd_payment_title">
                <th scope="row">
                    <label for="wpsd_payment_title"><?php _e('Donation Title', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_payment_title" id="wpsd_payment_title" class="regular-text"
                        value="<?php echo esc_attr($wpsdPaymentTitle); ?>" />
                </td>
            </tr>
            <tr class="wpsd_donation_for">
                <th scope="row">
                    <label for="wpsd_donation_for"><?php _e('Donation For', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donation_for" id="wpsd_donation_for" class="regular-text"
                           value="<?php echo esc_attr($wpsdDonationFor); ?>" />
                </td>
            </tr>
            <tr class="wpsd_donate_button_text">
                <th scope="row">
                    <label
                        for="wpsd_donate_button_text"><?php _e('Donate Button Text', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donate_button_text" id="wpsd_donate_button_text" class="regular-text"
                        value="<?php echo esc_attr($wpsdDonateButtonText); ?>" />
                </td>
            </tr>
            <tr class="wpsd_donate_currency">
                <th scope="row">
                    <label for="wpsd_donate_currency"><?php _e('Currency', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <select name="wpsd_donate_currency" id="wpsd_donate_currency" class="regular-text">
                        <?php
                        $wpsdCurrency = $this->hm_get_all_currency();
                        foreach ($wpsdCurrency as $wpsdcurr) { ?>
                        <option <?php if ($wpsdDonateCurrency == $wpsdcurr->abbreviation) echo 'selected'; ?>
                            value="<?php echo esc_attr($wpsdcurr->abbreviation); ?>">
                            <?php echo esc_html($wpsdcurr->currency); ?>-<?php echo esc_html($wpsdcurr->abbreviation); ?>-<?php echo esc_html($wpsdcurr->symbol); ?>
                        </option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr class="wpsd_in_memory_of_field">
                <th scope="row">
                    <label for="wpsd_in_memory_of_field"><?php _e('In memory of field','wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <select name="wpsd_in_memory_of_field" id="wpsd_in_memory_of_field" class="regular-text">
				        <?php
				        foreach ($kindfulFields as $key =>  $kindfulField) { ?>
                            <option <?php if ($kindfulField['id'] === $wpsdInMemoryOfField) echo 'selected'; ?>
                                    value="<?php echo esc_attr($kindfulField['id']); ?>">
						        <?php echo $kindfulField['name'] ?>
                            </option>
				        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr class="wpsd_campaign">
                <th scope="row">
                    <label
                            for="wpsd_campaign"><?php _e('Campaign', WPSD_TXT_DOMAIN); ?>:</label>
                </th>
                <td>
                    <select name="wpsd_campaign" id="wpsd_campaign" class="regular-text" required>
                        <option value=""><?php _e('Select option', WPSD_TXT_DOMAIN); ?></option>
				        <?php
				        foreach ($kindFulCampaigns as $key =>  $kindFulCampaign) { ?>
                            <option <?php if (strval($kindFulCampaign['name']) === $wpsdCampaign) echo 'selected'; ?>
                                    value="<?php echo esc_attr($kindFulCampaign['name']); ?>">
						        <?php echo $kindFulCampaign['name'] ?>
                            </option>
				        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr class="wpsd_campaign_id">
                <th scope="row">
                    <label
                            for="wpsd_campaign_id"><?php _e('Campaign Id', WPSD_TXT_DOMAIN); ?>:</label>
                </th>
                <td>
                    <select name="wpsd_campaign_id" id="wpsd_campaign_id" class="regular-text" required>
                        <option value=""><?php _e('Select option', WPSD_TXT_DOMAIN); ?></option>
                    </select>
                </td>
            </tr>
            <tr class="wpsd_fund">
                <th scope="row">
                    <label
                            for="wpsd_fund"><?php _e('Fund', WPSD_TXT_DOMAIN); ?>:</label>
                </th>
                <td>
                    <select name="wpsd_fund" id="wpsd_fund" class="regular-text" required>
                        <option value=""><?php _e('Select option', WPSD_TXT_DOMAIN); ?></option>
                        <?php
		                foreach ($kindfulFunds as $key =>  $kindfulFund) { ?>
                            <option <?php if (strval($kindfulFund['name']) === $wpsdFund) echo 'selected'; ?>
                                    value="<?php echo esc_attr($kindfulFund['name']); ?>">
				                <?php echo $kindfulFund['name'] ?>
                            </option>
		                <?php } ?>
                    </select>
                </td>
            </tr>
            <tr class="wpsd_fund_id">
                <th scope="row">
                    <label
                            for="wpsd_fund_id"><?php _e('Fund Id', WPSD_TXT_DOMAIN); ?>:</label>
                </th>
                <td>
                    <select name="wpsd_fund_id" id="wpsd_fund_id" class="regular-text" required>
                        <option value=""><?php _e('Select option', WPSD_TXT_DOMAIN); ?></option>
                    </select>
                </td>
            </tr>
            <tr class="wpsd_allow_custom_amount">
                <th scope="row">
                    <label for="wpsd_allow_custom_amount"><?php _e('Allow custom amount','wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="checkbox" id="wpsd_allow_custom_amount" name="wpsd_allow_custom_amount" checked>
                </td>
            </tr>
            <tr class="wpsd_allow_recurring">
                <th scope="row">
                    <label for="wpsd_allow_recurring"><?php _e('Allow recurring','wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="checkbox" id="wpsd_allow_recurring" name="wpsd_allow_recurring" checked>
                </td>
            </tr>
            <tr class="wpsd_donation_amounts">
                <th scope="row">
                    <label for="wpsd_donation_amounts"><?php _e('Donation amounts','wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" id="wpsd_donation_amounts" name="wpsd_donation_amounts"
                    value="<?php echo esc_attr($wpsdDonateAmounts); ?>" />
                </td>
            </tr>
            <tr class="wpsd_shortcode">
                <th scope="row">
                    <label for="wpsd_shortcode"><?php _e('Shortcode', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_shortcode" id="wpsd_shortcode" class="regular-text"
                        value="[wp_stripe_donation]" readonly />
                </td>
            </tr>
        </table>
        <p class="submit"><button id="updateGeneralSettings" name="updateGeneralSettings"
                class="button button-primary"><?php _e('Update General Settings', 'wp-stripe-donation'); ?></button>
        </p>
    </form>
</div>