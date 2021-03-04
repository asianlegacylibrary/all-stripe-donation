<?php
$wpsdKeyShowMessage = false;

if (isset($_POST['updateKeySettings'])) {
     $wpsdKeySettingsInfo = array(
          'wpsd_publishable_key' => (!empty($_POST['wpsd_publishable_key']) && (sanitize_text_field($_POST['wpsd_publishable_key']) != '')) ? sanitize_text_field($_POST['wpsd_publishable_key']) : '',
          'wpsd_secret_key'  => (!empty($_POST['wpsd_secret_key']) && (sanitize_text_field($_POST['wpsd_secret_key']) != '')) ? sanitize_text_field(base64_encode($_POST['wpsd_secret_key'])) : '',
          'wpsd_webhooks_key'  => (!empty($_POST['wpsd_webhooks_key']) && (sanitize_text_field($_POST['wpsd_webhooks_key']) != '')) ? sanitize_text_field($_POST['wpsd_webhooks_key']) : '',
          'wpsd_kindful_token'  => (!empty($_POST['wpsd_kindful_token']) && (sanitize_text_field($_POST['wpsd_kindful_token']) != '')) ? sanitize_text_field($_POST['wpsd_kindful_token']) : '',
          'wpsd_kindful_url'  => (!empty($_POST['wpsd_kindful_url']) && (sanitize_text_field($_POST['wpsd_kindful_url']) != '')) ? sanitize_text_field($_POST['wpsd_kindful_url']) : '',
     );
     $wpsdKeyShowMessage = update_option('wpsd_key_settings', serialize($wpsdKeySettingsInfo));
}
$wpsdKeySettings = stripslashes_deep(unserialize(get_option('wpsd_key_settings')));
if (is_array($wpsdKeySettings)) {
     $wpsdPublishableKey = !empty($wpsdKeySettings['wpsd_publishable_key']) ? $wpsdKeySettings['wpsd_publishable_key'] : "";
     $wpsdSecretKey = !empty($wpsdKeySettings['wpsd_secret_key']) ? base64_decode($wpsdKeySettings['wpsd_secret_key']) : "";
	 $wpsdWebhooksKey = !empty($wpsdKeySettings['wpsd_webhooks_key']) ? $wpsdKeySettings['wpsd_webhooks_key'] : "";
	 $wpsdKindfulToken = !empty($wpsdKeySettings['wpsd_kindful_token']) ? $wpsdKeySettings['wpsd_kindful_token'] : "";;
	 $wpsdKindfulUrl = !empty($wpsdKeySettings['wpsd_kindful_url']) ? $wpsdKeySettings['wpsd_kindful_url'] : "";;
} else {
	$wpsdPublishableKey = "";
     $wpsdSecretKey = "";
	 $wpsdWebhooksKey = "";
	 $wpsdKindfulToken = "";
	 $wpsdKindfulUrl = "";
}
?>
<div id="wpsd-wrap-all" class="wrap">
    <div class="settings-banner">
        <h2><?php _e('WP Stripe Donation Key Settings', 'wp-stripe-donation'); ?></h2>
    </div>
    <?php if ($wpsdKeyShowMessage) : $this->wpsd_display_notification('success', 'Your information updated successfully.');
     endif; ?>

    <form name="wpsd-general-settings-form" role="form" class="form-horizontal" method="post" action=""
        id="wpsd-settings-form-id">
        <table class="form-table">
            <tr class="wpsd_publishable_key">
                <th scope="row">
                    <label for="wpsd_publishable_key"><?php _e('Stripe Publishable Key', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_publishable_key" id="wpsd_publishable_key" class="regular-text"
                        value="<?php echo esc_html($wpsdPublishableKey); ?>" autocomplete="off" />
                </td>
            </tr>
            <tr class="wpsd_secret_key">
                <th scope="row">
                    <label for="wpsd_secret_key"><?php _e('Stripe Secret Key', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="password" name="wpsd_secret_key" id="wpsd_secret_key" class="regular-text"
                        value="<?php echo esc_html($wpsdSecretKey); ?>" autocomplete="off" />
                </td>
            </tr>
            <tr class="wpsd_webhooks_key">
                <th scope="row">
                    <label for="wpsd_webhooks_key"><?php _e('Stripe Webhooks Key', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="password" name="wpsd_webhooks_key" id="wpsd_webhooks_key" class="regular-text"
                           value="<?php echo esc_html($wpsdWebhooksKey); ?>" autocomplete="off" />
                </td>
            </tr>
            <tr class="wpsd_kindful_token">
                <th scope="row">
                    <label for="wpsd_kindful_token"><?php _e('Kindful Token', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="password" name="wpsd_kindful_token" id="wpsd_kindful_token" class="regular-text"
                           value="<?php echo esc_html($wpsdKindfulToken); ?>" autocomplete="off" />
                </td>
            </tr>
            <tr class="wpsd_kindful_url">
                <th scope="row">
                    <label for="wpsd_kindful_url"><?php _e('Kindful URL', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_kindful_url" id="wpsd_kindful_url" class="regular-text"
                           value="<?php echo esc_html($wpsdKindfulUrl); ?>" autocomplete="off" />
                </td>
            </tr>
        </table>
        <p class="submit"><button id="updateKeySettings" name="updateKeySettings"
                class="button button-primary"><?php _e('Update Settings', 'wp-stripe-donation'); ?></button></p>
    </form>
</div>