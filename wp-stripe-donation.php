<?php

/**
 * Plugin Name: 	ALL Stripe Donation
 * Text Domain:   all-stripe-donation
 * Description: 	This WordPress Stripe Donation is a simple plugin that allows you to collect donations on your website 
 *                via Stripe payment method and send the donation info to Kindful.
 * Plugin URI: https://asianlegacylibrary.org/
 * Description: Adds the FC Gutenberg Blocks to the page editor
 * Author: Jesse Waitz
 * Author URI: http://flagstaffconnection.com
 * Version: 1.0.7
 * License: GPL2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('WPINC')) {
    die;
}
if (!defined('ABSPATH')) {
    exit;
}

/**
 * plugin-update-checker
 */
require_once 'inc/plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/asianlegacylibrary/all-stripe-donation/', __FILE__, 'all-stripe-donation' );
$myUpdateChecker->setAuthentication( '71bf466bbcefda0b3f8b6e610500d8d32edaa56a' );
$myUpdateChecker->setBranch( 'master' );

global $wpdb;

define('WPSD_PATH', plugin_dir_path(__FILE__));
define('WPSD_ASSETS', plugins_url('/assets/', __FILE__));
define('WPSD_LANG', plugins_url('/languages/', __FILE__));
define('WPSD_SLUG', plugin_basename(__FILE__));
define('WPSD_PRFX', 'wpsd_');
define('WPSD_CLS_PRFX', 'cls-wpsd-');
define('WPSD_TXT_DOMAIN', 'all-stripe-donation');
define('WPSD_VERSION', '1.4.1');
define('WPSD_TABLE', $wpdb->prefix . 'wpsd_stripe_donation');
define('WPSD_TABLE_AMOUNT', $wpdb->prefix . 'wpsd_stripe_amounts');

// load dependencies:
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}
// boostrap the plugin:
require_once WPSD_PATH . 'inc/' . WPSD_CLS_PRFX . 'master.php';
$wpsd = new Wpsd_Master();
register_activation_hook(__FILE__, array($wpsd, WPSD_PRFX . 'install_tables'));
$wpsd->wpsd_run();
register_deactivation_hook(__FILE__, array($wpsd, WPSD_PRFX . 'unregister_settings'));