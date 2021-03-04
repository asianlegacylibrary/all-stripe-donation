<?php
if (isset($wpsdErrorMessage)){
	do_action('admin_notices', array('wpsd_type' => "error", 'wpsd_message' => $wpsdErrorMessage));
}
if (isset($wpsdSuccessMessage)){
	do_action('admin_notices', array('wpsd_type' => "updated", 'wpsd_message' => $wpsdSuccessMessage));
}
?>
<div id="wpsd-wrap-all" class="wrap">
		<div class="settings-banner">
			<h2><?php _e('Add Amount', 'wp-stripe-donation'); ?></h2>
		</div>
		<form name="wpsd-new-amount-form"  id="new_amount_form" role="form" class="form-horizontal" method="post" action="">
			<table class="form-table">
				<tr class="wpsd_amount">
					<th scope="row">
						<label for="wpsd_amount"><?php _e('Amount', 'wp-stripe-donation'); ?>:</label>
					</th>
					<td>
						<input type="currency" name="wpsd_amount" id="wpsd_amount" value="<?php echo $wpsdAmount->wpsd_amount;?>" class="regular-text" autocomplete="off" required/>
					</td>
				</tr>
				<tr class="wpsd_campaign_ids">
					<th scope="row">
						<label for="wpsd_campaign_ids"><?php _e('Campaign IDs', 'wp-stripe-donation'); ?>:</label>
					</th>
					<td>
						<input type="currency" name="wpsd_campaign_ids" id="wpsd_campaign_ids" class="regular-text" value="<?php echo esc_html($wpsdAmount->wpsd_campaign_ids); ?>" autocomplete="off" required /><br/>
						Enter Campaign IDs, separated by commas.  (ie. 111, 222, 333)
					</td>
				</tr>
			</table>
			<p class="submit">
				<button id="add_amount" name="add_amount" class="button button-primary"><?php _e('Add Amount', 'wp-stripe-donation'); ?></button>
			</p>
		</form>
</div>
