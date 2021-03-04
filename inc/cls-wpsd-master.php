<?php

/**
 * Our main plugin class
 */
class Wpsd_Master
{

	protected $wpsd_loader;
	protected $wpsd_version;

	/**
	 * Class Constructor
	 */
	public function __construct()
	{
		$this->wpsd_version = WPSD_VERSION;
		add_action('plugins_loaded', array($this, WPSD_PRFX . 'load_plugin_textdomain'));
		$this->wpsd_load_dependencies();
		$this->wpsd_trigger_admin_hooks();
		$this->wpsd_trigger_front_hooks();
		$this->register_webhooks();
	}

	public function wpsd_load_plugin_textdomain()
	{
		load_plugin_textdomain('wp-stripe-donation', FALSE, 'wp-stripe-donation' . '/locale/');
		$ss = null;
	}

	private function wpsd_load_dependencies()
	{
		require_once WPSD_PATH . 'inc/OptionsHelper.php';
		require_once WPSD_PATH . 'inc/cls-hm-currency.php';
		require_once WPSD_PATH . 'inc/StripeHelper.php';
		require_once WPSD_PATH . 'inc/KindfulHelper.php';
		require_once WPSD_PATH . 'admin/amounts/AmountCrud.php';
		require_once WPSD_PATH . 'admin/' . WPSD_CLS_PRFX . 'admin.php';
		require_once WPSD_PATH . 'front/' . WPSD_CLS_PRFX . 'front.php';
		require_once WPSD_PATH . 'front/' . WPSD_CLS_PRFX . 'webhooks.php';
		require_once WPSD_PATH . 'inc/' . WPSD_CLS_PRFX . 'loader.php';
		require_once WPSD_PATH . 'inc/' . WPSD_CLS_PRFX . 'upgrade.php';
		$this->wpsd_loader = new Wpsd_Loader();
	}

	private function wpsd_trigger_admin_hooks()
	{
		$wpsd_admin = new Wpsd_Admin($this->wpsd_version());
		$wpsd_upgrade = new Wpsd_Upgrade();
		$this->wpsd_loader->add_action('admin_enqueue_scripts', $wpsd_admin, WPSD_PRFX . 'admin_assets');
		$this->wpsd_loader->add_action('init', $wpsd_admin, 'wpsd_start_session');
		$this->wpsd_loader->add_action('admin_menu', $wpsd_admin, WPSD_PRFX . 'admin_menu');
		$this->wpsd_loader->add_action('wp_ajax_wpsd_get_image', $wpsd_admin, 'wpsd_get_image');
		$this->wpsd_loader->add_action('wp_ajax_nopriv_wpsd_get_image', $wpsd_admin, 'wpsd_get_image');
		$this->wpsd_loader->add_action('admin_notices', $wpsd_admin, 'wpsd_display_notification');
		$this->wpsd_loader->add_action( 'plugins_loaded', $wpsd_upgrade, 'wpsd_perform_upgrade');
	}

	private function wpsd_trigger_front_hooks()
	{
		$wpsd_front = new Wpsd_Front($this->wpsd_version());
		$this->wpsd_loader->add_action('wp_enqueue_scripts', $wpsd_front, WPSD_PRFX . 'front_assets');
		$this->wpsd_loader->add_action('wp_ajax_wpsd_donation', $wpsd_front, 'wpsd_donation_handler');
		$this->wpsd_loader->add_action('wp_ajax_nopriv_wpsd_donation', $wpsd_front, 'wpsd_donation_handler');
		$this->wpsd_loader->add_action('wp_ajax_wpsd_get_states', $wpsd_front, 'wpsd_get_states_handler');
		$this->wpsd_loader->add_action('wp_ajax_nopriv_wpsd_get_states', $wpsd_front, 'wpsd_get_states_handler');
		$this->wpsd_loader->add_action('wp_ajax_wpsd_payment_intent', $wpsd_front, 'wpsd_payment_intent_handler');
		$this->wpsd_loader->add_action('wp_ajax_nopriv_wpsd_payment_intent', $wpsd_front, 'wpsd_payment_intent_handler');
		$this->wpsd_loader->add_action('wp_ajax_wpsd_create_customer', $wpsd_front, 'wpsd_create_customer_handler');
		$this->wpsd_loader->add_action('wp_ajax_nopriv_wpsd_create_customer', $wpsd_front, 'wpsd_create_customer_handler');
		$wpsd_front->wpsd_load_shortcode();
	}

	private function register_webhooks(){
		$webhooks = new Wpsd_Webhooks();
		$this->wpsd_loader->add_action('wp_ajax_wpsd_stripe_webhooks', $webhooks, 'wpsd_stripe_webhooks_handler');
		$this->wpsd_loader->add_action('wp_ajax_nopriv_wpsd_stripe_webhooks', $webhooks, 'wpsd_stripe_webhooks_handler');
	}

	function wpsd_run()
	{
		$this->wpsd_loader->wpsd_run();
	}

	function wpsd_version()
	{
		return $this->wpsd_version;
	}

	function wpsd_install_tables()
	{
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		global $wpdb;
		$table_name = WPSD_TABLE;
		$sql1 = "DROP TABLE IF EXISTS $table_name";
		$wpdb->query($sql1);
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			//table not in database. Create new table
			$charset_collate = $wpdb->get_charset_collate();
			$sql = "CREATE TABLE $table_name (
					wpsd_id INT(11) NOT NULL AUTO_INCREMENT,
					wpsd_donation_for VARCHAR(255),
					wpsd_donator_first_name VARCHAR(155),
					wpsd_donator_last_name VARCHAR(155),
					wpsd_donator_email VARCHAR(155),
					wpsd_donator_phone VARCHAR(155),
					wpsd_donator_country VARCHAR(155),
					wpsd_donator_state VARCHAR(155),
					wpsd_donator_city VARCHAR(155),
					wpsd_donator_zip VARCHAR(155),
					wpsd_donator_address VARCHAR(255),
					wpsd_donated_amount VARCHAR(155),
					wpsd_amount_id int UNSIGNED,
					wpsd_in_memory_of VARCHAR(155),
					wpsd_is_recurring SMALLINT,
					wpsd_payment_intent_id VARCHAR(255),
					wpsd_payment_complete SMALLINT,
					wpsd_payment_method VARCHAR(255),
					wpsd_customer_id VARCHAR(255),
					wpsd_subscription VARCHAR(255),
					wpsd_campaign VARCHAR(155),
					wpsd_donation_datetime DATETIME,
					PRIMARY KEY (`wpsd_id`)
			) $charset_collate;";
			dbDelta($sql);
		}

		$table_name = WPSD_TABLE_AMOUNT;
		$sql1 = "DROP TABLE IF EXISTS $table_name";
		$wpdb->query($sql1);
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			//table not in database. Create new table
			$charset_collate = $wpdb->get_charset_collate();
			$sql = "CREATE TABLE $table_name (
					wpsd_amount_id INT(11) NOT NULL AUTO_INCREMENT,
					wpsd_amount VARCHAR(155),
					wpsd_stripe_product_id VARCHAR(255),
					wpsd_campaign_ids VARCHAR(255),
					PRIMARY KEY (`wpsd_amount_id`)
			) $charset_collate;";
			dbDelta($sql);
		}
	}
	function wpsd_install_amounts_table()
	{
		global $wpdb;

	}

	function wpsd_unregister_settings()
	{
		global $wpdb;

		$tbl = $wpdb->prefix . 'options';
		$search_string = WPSD_PRFX . '%';

		$sql = $wpdb->prepare("SELECT option_name FROM $tbl WHERE option_name LIKE %s", $search_string);
		$options = $wpdb->get_results($sql, OBJECT);

		if (is_array($options) && count($options)) {
			foreach ($options as $option) {
				delete_option($option->option_name);
				delete_site_option($option->option_name);
			}
		}
	}
}