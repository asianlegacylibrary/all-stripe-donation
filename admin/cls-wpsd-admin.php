<?php

/**
 *	Admin Panel Parent Class
 */
class Wpsd_Admin
{
	use HM_Currency;
	use AmountCrud;
	private $wpsd_version;
	private $wpsd_option_group;
	private $wpsd_assets_prefix;
	protected $wpsdTable;

	public function __construct($version)
	{
		$this->wpsd_version = $version;
		$this->wpsdTable = WPSD_TABLE;
		$this->wpsd_option_group = WPSD_PRFX . 'options_group';
		$this->wpsd_assets_prefix = substr(WPSD_PRFX, 0, -1) . '-';
	}

	/**
	 *	Loading the admin menu
	 */
	public function wpsd_admin_menu()
	{
		add_menu_page(
			esc_html__('ALL Stripe Donation', 'wp-stripe-donation'),
			esc_html__('ALL Stripe Donation', 'wp-stripe-donation'),
			'',
			'wpsd-admin-settings',
			'',
			'dashicons-smiley',
			100
		);

		add_submenu_page(
			'wpsd-admin-settings',
			esc_html__('Key Settings', 'wp-stripe-donation'),
			esc_html__('Key Settings', 'wp-stripe-donation'),
			'manage_options',
			'wpsd-key-settings',
			array($this, WPSD_PRFX . 'key_settings')
		);

		add_submenu_page(
			'wpsd-admin-settings',
			esc_html__('General Settings', 'wp-stripe-donation'),
			esc_html__('General Settings', 'wp-stripe-donation'),
			'manage_options',
			'wpsd-general-settings',
			array($this, WPSD_PRFX . 'general_settings')
		);

		add_submenu_page(
			'wpsd-admin-settings',
			esc_html__('Template Settings', 'wp-stripe-donation'),
			esc_html__('Template Settings', 'wp-stripe-donation'),
			'manage_options',
			'wpsd-template-settings',
			array($this, WPSD_PRFX . 'template_settings')
		);

		add_submenu_page(
			'wpsd-admin-settings',
			esc_html__('All Donations', 'wp-stripe-donation'),
			esc_html__('All Donations', 'wp-stripe-donation'),
			'manage_options',
			'wpsd-all-donations',
			array($this, WPSD_PRFX . 'all_donations')
		);
		$this->wpsd_register_amount_pages();
	}

	/**
	 *	Loading admin panel assets
	 */
	function wpsd_admin_assets()
	{
		wp_enqueue_style(
			$this->wpsd_assets_prefix . 'admin-style',
			WPSD_ASSETS . 'css/' . $this->wpsd_assets_prefix . 'admin-style.css',
			array(),
			$this->wpsd_version,
			FALSE
		);

		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('wp-color-picker');

		wp_enqueue_media();

		if (!wp_script_is('jquery')) {
			wp_enqueue_script('jquery');
		}
		wp_enqueue_script(
			$this->wpsd_assets_prefix . 'admin-script',
			WPSD_ASSETS . 'js/' . $this->wpsd_assets_prefix . 'admin-script.js',
			array('jquery'),
			$this->wpsd_version,
			TRUE
		);
		$wpsd_settings = get_option('wpsd_settings');
		$options = new OptionsHelper();
		$currency =   $options->get_value("general", "wpsd_donate_currency","USD", false);
		$wpsdAdminArray = array(
			'wpsdIdsOfColorPicker' => array(),
            'currency' => $currency
		);
		wp_localize_script($this->wpsd_assets_prefix . 'admin-script', 'wpsdAdminScript', $wpsdAdminArray);
	}

	/**
	 *	Loading admin panel view/forms
	 */
	function wpsd_key_settings()
	{
		require_once WPSD_PATH . 'admin/view/' . $this->wpsd_assets_prefix . 'key-settings.php';
	}

	function wpsd_general_settings()
	{
		require_once WPSD_PATH . 'admin/view/' . $this->wpsd_assets_prefix . 'general-settings.php';
	}

	function wpsd_template_settings()
	{
		require_once WPSD_PATH . 'admin/view/' . $this->wpsd_assets_prefix . 'template-settings.php';
	}

	function wpsd_all_donations()
	{
		$wpsdColumns = array(
			'wpsd_donated_amount' 		=> esc_html__('Amount', 'wp-stripe-donation'),
			'&nbsp;'					=> "&nbsp;",
			'wpsd_donator_first_name'	=> esc_html__('First Name', 'wp-stripe-donation'),
			'wpsd_donator_last_name'	=> esc_html__('Last Name', 'wp-stripe-donation'),
			'wpsd_donator_email'		=> esc_html__('Email', 'wp-stripe-donation'),
			'wpsd_is_recurring'		    => esc_html__('Recurring', 'wp-stripe-donation'),
			'wpsd_donation_datetime'	=> esc_html__('Date', 'wp-stripe-donation')
		);
		register_column_headers('wpsd-column-table', $wpsdColumns);
		require_once WPSD_PATH . 'admin/view/' . $this->wpsd_assets_prefix . 'all-donations.php';
	}

	protected function wpsd_get_all_donations()
	{
		global $wpdb;
		$amounts_table = WPSD_TABLE_AMOUNT;
		$query = "SELECT * FROM $this->wpsdTable AS d " .
                 "LEFT JOIN $amounts_table AS a " .
                 "ON d.wpsd_amount_id = a.wpsd_amount_id " .
		         "WHERE %d";
		return $wpdb->get_results($wpdb->prepare( $query , 1));
	}

	
	
	function wpsd_start_session(){
		if( !session_id() )
		{
			session_start();
		}
    }

	function wpsd_display_notification($args = null)
	{
	    if(!$args || empty($args)){
	        return;
        }
	    if(!isset($args['wpsd_type']) || !isset($args['wpsd_message'])){
	        return;
        }
	    $message = $args['wpsd_message'];
	    $type = $args['wpsd_type'];
	    if(isset($args['wpsd_session']) && $args['wpsd_session']){
		    $_SESSION['wpsd_message'] = $message;
		    $_SESSION['wpsd_type'] = $type;
		    return;
        }
	    ?>
        <div class="<?php echo esc_html($type); ?> notice wpsd-is-dismissable">
            <p><?php esc_html_e($message, 'wp-stripe-donation'); ?></p>
        </div>
     <?php
	}

	function wpsd_get_image()
	{
		if (isset($_GET['id'])) {
			$image = wp_get_attachment_image(filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT), esc_html($_GET['img_type']), false, array('id' => esc_html($_GET['prev_id'])));
			$data = array(
				'image' => $image,
			);
			wp_send_json_success($data);
		} else {
			wp_send_json_error();
		}
	}
}
?>