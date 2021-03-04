<?php
$wpsdDonations = $this->wpsd_get_all_donations();
$wpsdGeneralSettings = stripslashes_deep(unserialize(get_option('wpsd_general_settings')));
if (is_array($wpsdGeneralSettings)) {
    $wpsdDonateCurrency = $wpsdGeneralSettings['wpsd_donate_currency'];
} else {
    $wpsdDonateCurrency = "USD";
}
?>

<div id="wpsd-wrap-all" class="wrap">
    <h2><?php _e('List of all donations', 'wp-stripe-donation'); ?></h2><br>
    <table class="wp-list-table widefat fixed striped posts" cellspacing="0" id="wpc_data_table">
        <thead>
            <tr>
                <?php print_column_headers('wpsd-column-table'); ?>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <?php print_column_headers('wpsd-column-table', false); ?>
            </tr>
        </tfoot>
        <tbody id="the-list">
            <?php
            if (count($wpsdDonations) > 0) :
                foreach ($wpsdDonations as $donation) : ?>
            <tr>
                <td class="wpsd-donated-amount">
                    <?php
                    $amount_val = 0;
                    if($donation->wpsd_donated_amount){
                        $amount_val = number_format($donation->wpsd_donated_amount/100,2);
                    }
                    else {
	                    $amount_val = number_format($donation->wpsd_amount/100,2);
                    }
                    printf('%s', $amount_val);
                    ?>
                </td>
                <td><?php echo esc_html($wpsdDonateCurrency); ?></td>
                <td><?php printf('%s', $donation->wpsd_donator_first_name); ?></td>
                <td><?php printf('%s', $donation->wpsd_donator_last_name); ?></td>
                <td><?php printf('%s', $donation->wpsd_donator_email); ?></td>
                <td><?php printf('%s', $donation->wpsd_is_recurring === "1"? "Yes": "No"); ?></td>
                <td><?php printf('%s', date('D d M Y - h:i A', strtotime($donation->wpsd_donation_datetime))); ?>
                </td>
            </tr>
            <?php endforeach;
            endif;
            ?>
        </tbody>
    </table>
</div>