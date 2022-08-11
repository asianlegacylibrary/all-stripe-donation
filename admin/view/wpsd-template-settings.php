<?php
$wpsdTempShowMessage = false;
if( isset( $_POST['updateTempSettings'] ) ) {

    $wpsdTempSettingsInfo = array(
        'wpsd_form_banner'          => ( sanitize_file_name( $_POST['wpsd_form_banner'] ) != '' ) ? sanitize_file_name( $_POST['wpsd_form_banner'] ) : '',
        'wpsd_display_header'       => isset( $_POST['wpsd_display_header'] ) && filter_var( $_POST['wpsd_display_header'], FILTER_SANITIZE_NUMBER_INT ) ? $_POST['wpsd_display_header'] : '',
        'wpsd_donation_for_label'   => ( sanitize_text_field( $_POST['wpsd_donation_for_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_donation_for_label'] ) : 'Donation For',
        'wpsd_custom_amount_label'   => ( sanitize_text_field( $_POST['wpsd_custom_amount_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_custom_amount_label'] ) : 'Enter Your Amount',
        'wpsd_donator_first_name_label'   => ( sanitize_text_field( $_POST['wpsd_donator_first_name_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_donator_first_name_label'] ) : 'First Name',
        'wpsd_donator_last_name_label'   => ( sanitize_text_field( $_POST['wpsd_donator_last_name_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_donator_last_name_label'] ) : 'Last Name',
        'wpsd_donator_country_label'   => ( sanitize_text_field( $_POST['wpsd_donator_country_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_donator_country_label'] ) : 'Country',
        'wpsd_donator_state_label'   => ( sanitize_text_field( $_POST['wpsd_donator_state_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_donator_state_label'] ) : 'State',
        'wpsd_donator_city_label'   => ( sanitize_text_field( $_POST['wpsd_donator_city_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_donator_city_label'] ) : 'City',
        'wpsd_donator_zip_label'   => ( sanitize_text_field( $_POST['wpsd_donator_zip_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_donator_zip_label'] ) : 'ZIP',
        'wpsd_donator_phone_label'   => ( sanitize_text_field( $_POST['wpsd_donator_phone_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_donator_phone_label'] ) : 'Phone',
        'wpsd_donator_address_label'   => ( sanitize_text_field( $_POST['wpsd_donator_address_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_donator_address_label'] ) : 'Address',
        'wpsd_donator_email_label'  => ( sanitize_text_field( $_POST['wpsd_donator_email_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_donator_email_label'] ) : 'Donator Email',
        'wpsd_in_memory_of_label'  => ( sanitize_text_field( $_POST['wpsd_in_memory_of_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_in_memory_of_label'] ) : 'In memory of',
        'wpsd_one_time_label'  => ( sanitize_text_field( $_POST['wpsd_one_time_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_one_time_label'] ) : 'One Time',
        'wpsd_monthly_label'  => ( sanitize_text_field( $_POST['wpsd_monthly_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_monthly_label'] ) : 'Monthly',
        'wpsd_card_label'  => ( sanitize_text_field( $_POST['wpsd_card_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_card_label'] ) : 'Credit Card Details',
        'wpsd_card_agreement'  => ( sanitize_text_field( $_POST['wpsd_card_agreement'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_card_agreement'] ) : '',
        'wpsd_donate_amount_label'  => ( sanitize_text_field( $_POST['wpsd_donate_amount_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_donate_amount_label'] ) : 'Donate Amount',
        'wpsd_donate_details_label'  => ( sanitize_text_field( $_POST['wpsd_donate_details_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_donate_details_label'] ) : 'Personal Details',
        'wpsd_donate_assistance_label'  => ( sanitize_text_field( $_POST['wpsd_donate_assistance_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_donate_assistance_label'] ) : 'If you need assistance with your donation, please email',
        'wpsd_donate_in_us_dollars'  => ( sanitize_text_field( $_POST['wpsd_donate_in_us_dollars'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_donate_in_us_dollars'] ) : 'All donations are in US dollars',
        'wpsd_donate_assistance_email'  => ( sanitize_text_field( $_POST['wpsd_donate_assistance_email'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_donate_assistance_email'] ) : 'donations@asianlegacylibrary.org',
        'wpsd_donate_req_fields_msg_label'  => ( sanitize_text_field( $_POST['wpsd_donate_req_fields_msg_label'] ) != '' ) ? sanitize_text_field( $_POST['wpsd_donate_req_fields_msg_label'] ) : 'Required Fields Label',
    );

    $wpsdTempShowMessage = update_option('wpsd_temp_settings', serialize( $wpsdTempSettingsInfo ) );
}

$wpsdTempSettings = stripslashes_deep( unserialize( get_option('wpsd_temp_settings') ) );

if ( is_array( $wpsdTempSettings ) ) {
    $wpsdFormBanner = $wpsdTempSettings['wpsd_form_banner'];
} else {
    $wpsdFormBanner = '';
}
$options = new OptionsHelper();
$wpsd_display_header = $options->get_value('template','wpsd_display_header', '', false);
$wpsd_donation_for_label = $options->get_value('template','wpsd_donation_for_label', 'Donation For', false);
$wpsd_custom_amount_label = $options->get_value('template','wpsd_custom_amount_label', 'Enter Your Amount', false);
$wpsd_donator_first_name_label = $options->get_value('template','wpsd_donator_first_name_label', 'First Name', false);
$wpsd_donator_last_name_label = $options->get_value('template','wpsd_donator_last_name_label', 'Last Name', false);
$wpsd_donator_country_label = $options->get_value('template','wpsd_donator_country_label', 'Country', false);
$wpsd_donator_city_label = $options->get_value('template','wpsd_donator_city_label', 'City', false);
$wpsd_donator_state_label = $options->get_value('template','wpsd_donator_state_label', 'State', false);
$wpsd_donator_zip_label = $options->get_value('template','wpsd_donator_zip_label', 'ZIP', false);
$wpsd_donator_phone_label = $options->get_value('template','wpsd_donator_phone_label', 'Phone', false);
$wpsd_donator_address_label = $options->get_value('template','wpsd_donator_address_label', 'Address', false);
$wpsd_donator_email_label = $options->get_value('template','wpsd_donator_email_label', 'Email', false);
$wpsd_in_memory_of_label = $options->get_value('template','wpsd_in_memory_of_label', 'In memory of', false);
$wpsd_donate_amount_label = $options->get_value('template','wpsd_donate_amount_label', 'Choose Your Amount', false);

// added more template fields, JC 2021
$wpsd_donate_details_label = $options->get_value('template','wpsd_donate_details_label', 'Personal Details', false);
$wpsd_donate_req_fields_msg_label = $options->get_value('template','wpsd_donate_req_fields_msg_label', 'Required Fields Label', false);
$wpsd_donate_assistance_label = $options->get_value('template','wpsd_donate_assistance_label', 'If you need assistance with your donation, please email', false);
$wpsd_donate_in_us_dollars = $options->get_value('template','wpsd_donate_in_us_dollars', 'All donations are in US dollars', false);
$wpsd_donate_assistance_email = $options->get_value('template','wpsd_donate_assistance_email', 'donations@asianlegacylibrary.org', false);

$wpsd_one_time_label = $options->get_value('template','wpsd_one_time_label', 'One Time', false);
$wpsd_monthly_label = $options->get_value('template','wpsd_one_monthly_label', 'Monthly', false);
$wpsd_card_label = $options->get_value('template','wpsd_card_label', 'Credit Card Details', false);
$wpsd_card_agreement = $options->get_value('template','wpsd_card_agreement', '', false);

?>
<div id="wpsd-wrap-all" class="wrap">
    <div class="settings-banner">
        <h2><?php _e('WP Stripe Template Settings', 'wp-stripe-donation'); ?></h2>
    </div>
    <?php if( $wpsdTempShowMessage ) { $this->wpsd_display_notification('success', 'Your information updated successfully.'); } ?>

    <form name="wpsd-temp-settings-form" role="form" class="form-horizontal" method="post" action=""
        id="wpsd-temp-settings-form-id">
        <table class="form-table">
            <tr class="wpsd_form_banner">
                <th scope="row">
                    <label for="wpsd_form_banner"><?php _e('Donation Form Banner', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="hidden" name="wpsd_form_banner" id="wpsd_form_banner"
                        value="<?php echo esc_attr($wpsdFormBanner); ?>" class="regular-text" />
                    <input type='button' class="button-primary"
                        value="<?php esc_attr_e('Select a banner', 'wp-stripe-donation'); ?>" id="wpsd_media_manager"
                        data-image-type="full" />
                    <?php
                    //$wpsdFormBannerImageId = esc_attr($wpsdFormBanner);
                    $wpsdFormBannerImage = "";
                    if( intval( $wpsdFormBanner ) > 0 ) {
                        $wpsdFormBannerImage = wp_get_attachment_image( $wpsdFormBanner, 'full', false, array('id' => 'wpsd-form-banner-preview-image') );
                    }
                    ?>
                    <div id="wpsd-form-banner-preview-image">
                        <?php echo $wpsdFormBannerImage; ?>
                    </div>
                </td>
            </tr>
            <tr class="wpsd_display_header">
                <th scope="row">
                    <label for="wpsd_display_header"><?php _e('Display Header', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="checkbox" name="wpsd_display_header" class="wpsd_display_header" value="1" <?php if( '1' === $wpsd_display_header ) { echo 'checked'; } ?> >
                </td>
            </tr>
            <tr class="wpsd_donation_for_label">
                <th scope="row">
                    <label for="wpsd_donation_for_label"><?php _e('Donation For Label', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donation_for_label" class="medium-text" placeholder="Donation For"
                        value="<?php echo $wpsd_donation_for_label; ?>">
                </td>
            </tr>
            <tr class="wpsd_custom_amount_label">
                <th scope="row">
                    <label for="wpsd_custom_amount_label"><?php _e('Custom Amount Label', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_custom_amount_label" class="medium-text" placeholder="Enter Your Amount"
                           value="<?php echo $wpsd_custom_amount_label; ?>">
                </td>
            </tr>
            <tr class="wpsd_donator_first_name_label">
                <th scope="row">
                    <label for="wpsd_donator_first_name_label"><?php _e('First Name', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donator_first_name_label" class="medium-text" placeholder="First Name"
                        value="<?php echo $wpsd_donator_first_name_label; ?>">
                </td>
            </tr>
            <tr class="wpsd_donator_last_name_label">
                <th scope="row">
                    <label for="wpsd_donator_last_name_label"><?php _e('Last Name', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donator_last_name_label" class="medium-text" placeholder="Last Name"
                           value="<?php echo $wpsd_donator_last_name_label; ?>">
                </td>
            </tr>
            <tr class="wpsd_donator_email_label">
                <th scope="row">
                    <label for="wpsd_donator_email_label"><?php _e('Email', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donator_email_label" class="medium-text" placeholder="Email"
                        value="<?php echo $wpsd_donator_email_label; ?>">
                </td>
            </tr>
            <tr class="wpsd_donator_phone_label">
                <th scope="row">
                    <label for="wpsd_donator_phone_label"><?php _e('Phone', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donator_phone_label" class="medium-text" placeholder="Phone"
                           value="<?php echo $wpsd_donator_phone_label; ?>">
                </td>
            </tr>
            <tr class="wpsd_donator_country_label">
                <th scope="row">
                    <label for="wpsd_donator_country_label"><?php _e('Country', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donator_country_label" class="medium-text" placeholder="Country"
                           value="<?php echo $wpsd_donator_country_label; ?>">
                </td>
            </tr>
            <tr class="wpsd_donator_state_label">
                <th scope="row">
                    <label for="wpsd_donator_state_label"><?php _e('State', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donator_state_label" class="medium-text" placeholder="State"
                           value="<?php echo $wpsd_donator_state_label; ?>">
                </td>
            </tr>
            <tr class="wpsd_donator_city_label">
                <th scope="row">
                    <label for="wpsd_donator_city_label"><?php _e('City', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donator_city_label" class="medium-text" placeholder="City"
                           value="<?php echo $wpsd_donator_city_label; ?>">
                </td>
            </tr>
            <tr class="wpsd_donator_zip_label">
                <th scope="row">
                    <label for="wpsd_donator_zip_label"><?php _e('ZIP', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donator_zip_label" class="medium-text" placeholder="ZIP"
                           value="<?php echo $wpsd_donator_zip_label; ?>">
                </td>
            </tr>
            <tr class="wpsd_donator_address_label">
                <th scope="row">
                    <label for="wpsd_donator_address_label"><?php _e('Address', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donator_address_label" class="medium-text" placeholder="Address"
                           value="<?php echo $wpsd_donator_address_label; ?>">
                </td>
            </tr>
            <tr class="wpsd_in_memory_of_label">
                <th scope="row">
                    <label for="wpsd_in_memory_of_label"><?php _e('In memory of', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_in_memory_of_label" class="medium-text" placeholder="In memory of"
                           value="<?php echo $wpsd_in_memory_of_label; ?>">
                </td>
            </tr>
            <tr class="wpsd_donate_amount_label">
                <th scope="row">
                    <label for="wpsd_donate_amount_label"><?php _e('Donate Amount', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donate_amount_label" class="medium-text" placeholder="Donate Amount"
                        value="<?php echo $wpsd_donate_amount_label; ?>">
                </td>
            </tr>
            
            <!-- Addition of template fields, JC 2021 -->
            <tr class="wpsd_donate_details_label">
                <th scope="row">
                    <label for="wpsd_donate_details_label"><?php _e('Personal Details', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donate_details_label" class="medium-text" placeholder="Personal Details"
                        value="<?php echo $wpsd_donate_details_label; ?>">
                </td>
            </tr>
            <tr class="wpsd_donate_req_fields_msg_label">
                <th scope="row">
                    <label for="wpsd_donate_req_fields_msg_label"><?php _e('Required Fields Label', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donate_req_fields_msg_label" class="long-text" placeholder="Please be sure to fill out all required fields..."
                        value="<?php echo $wpsd_donate_req_fields_msg_label; ?>">
                       
                    </input>
                </td>
            </tr>
            <tr class="wpsd_donate_assistance_label">
                <th scope="row">
                    <label for="wpsd_donate_assistance_label"><?php _e('Assistance Label', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donate_assistance_label" class="long-text" placeholder="If you need assistance with your donation, please email..."
                        value="<?php echo $wpsd_donate_assistance_label; ?>">
                </td>
            </tr>
            <tr class="wpsd_donate_assistance_email">
                <th scope="row">
                    <label for="wpsd_donate_assistance_email"><?php _e('Assistance Email', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donate_assistance_email" class="medium-text" placeholder="donations@asianlegacylibrary.org..."
                        value="<?php echo $wpsd_donate_assistance_email; ?>">
                </td>
            </tr>
            <tr class="wpsd_donate_in_us_dollars">
                <th scope="row">
                    <label for="wpsd_donate_in_us_dollars"><?php _e('Donation Currency Label', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_donate_in_us_dollars" class="long-text" placeholder="All donations in US dollars"
                        value="<?php echo $wpsd_donate_in_us_dollars; ?>">
                </td>
            </tr>
            <tr class="wpsd_one_time_label">
                <th scope="row">
                    <label for="wpsd_one_time_label"><?php _e('One Time Label', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_one_time_label" class="medium-text" placeholder="One Time"
                           value="<?php echo $wpsd_one_time_label; ?>">
                </td>
            </tr>
            <tr class="wpsd_monthly_label">
                <th scope="row">
                    <label for="wpsd_monthly_label"><?php _e('Monthly Label', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_monthly_label" class="medium-text" placeholder="Monthly"
                           value="<?php echo $wpsd_monthly_label; ?>">
                </td>
            </tr>
            <tr class="wpsd_card_label">
                <th scope="row">
                    <label for="wpsd_card_label"><?php _e('Card Label', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_card_label" class="medium-text" placeholder="Credit Card Details"
                           value="<?php echo $wpsd_card_label; ?>">
                </td>
            </tr>
            <tr class="wpsd_card_agreement">
                <th scope="row">
                    <label for="wpsd_card_agreement"><?php _e('Card Agreement', 'wp-stripe-donation'); ?>:</label>
                </th>
                <td>
                    <input type="text" name="wpsd_card_agreement" class="medium-text" placeholder="Card Agreement"
                           value="<?php echo $wpsd_card_agreement; ?>">
                </td>
            </tr>
        </table>
        <p class="submit"><button id="updateTempSettings" name="updateTempSettings" class="button button-primary"><?php esc_attr_e('Update Settings', 'wp-stripe-donation'); ?></button>
        </p>
    </form>
</div>