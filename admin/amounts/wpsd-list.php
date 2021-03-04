<?php
$wpsdAmounts = $this->wpsd_get_all_amounts();
$wpsdGeneralSettings = stripslashes_deep(unserialize(get_option('wpsd_general_settings')));
if (is_array($wpsdGeneralSettings)) {
	$wpsdDonateCurrency = $wpsdGeneralSettings['wpsd_donate_currency'];
} else {
	$wpsdDonateCurrency = "USD";
}
?>

<div id="wpsd-wrap-all" class="wrap">
	<h2><?php _e('List of all amounts', 'wp-stripe-donation'); ?></h2><br>
	<?php
	if (isset($_SESSION['wpsd_message'])){
		do_action('admin_notices', array('wpsd_type' => $_SESSION['wpsd_type'], 'wpsd_message' => $_SESSION['wpsd_message']));
		unset($_SESSION['wpsd_message']);
		unset($_SESSION['wpsd_type']);
	}
	?>
	<table class="wp-list-table widefat fixed striped posts" cellspacing="0" id="wpc_data_table">
		<thead>
			<tr>
				<?php print_column_headers('wpsd-amounts-column-table'); ?>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<?php print_column_headers('wpsd-amounts-column-table', false); ?>
			</tr>
		</tfoot>
		<tbody id="the-list">
		<?php if (count($wpsdAmounts) > 0) :
			foreach ($wpsdAmounts as $amount) : ?>
				<tr>
					<td><?php printf('%s', number_format($amount->wpsd_amount/100, 2)); ?></td>
					<td><?php printf('%s', $amount->wpsd_stripe_product_id); ?></td>
					<td><?php printf('%s', $amount->wpsd_campaign_ids); ?></td>
					<td>
						<a href="?page=wpsd-edit-amount&wpsd_amount_id=<?php echo $amount->wpsd_amount_id?>"><?php esc_html_e('Edit', 'wp-stripe-donation'); ?></a> &bull; 
						<a href="?page=wpsd-delete-amount&wpsd_amount_id=<?php echo $amount->wpsd_amount_id?>"><?php esc_html_e('Delete', 'wp-stripe-donation'); ?></a>
					</td>
				</tr>
			<?php endforeach;
		endif; ?>
		</tbody>
	</table>
	<a id="add_amount" href="<?php echo admin_url('admin.php?page=wpsd-add-amount') ?>" class="button button-primary"><?php _e('Add Amount', 'wp-stripe-donation'); ?></a>
</div>