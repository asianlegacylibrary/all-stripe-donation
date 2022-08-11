=== ALL Stripe Donation ===
Contributors: devvly.com, jessewaitz, joelcrawford
Donate link: https://asianlegacylibrary.org/
Tags: all asianlegacylibrary stripe donation
Requires at least: 5.0
Tested up to: 5.5
Stable tag: master
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This WordPress Stripe Donation is a simple plugin that allows you to collect donations on your website via Stripe payment method and send the donation info to Kindful

== Description ==

This WordPress Stripe Donation is a simple plugin that allows you to collect donations on your website via Stripe payment method and send the donation info to Kindful

== Installation ==

How to install the ALL Stripe Donation plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/all-stripe-donation` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the ALL Stripe Donation plugin through the 'Plugins' screen in WordPress.

== Changelog ==

= 1.0.8 =
- NOTE: Joel Crawford edits, June-Aug 2021 // not sure how the version numbering works here // new branch feature/nf_css until merged
- Huge updates, new logic in Stripe calls...yes...more docs to come.
 
* CSS updates to integrate with NoFormat design (wpsd-front-style.css, some wpsd-admin-style.css)

* removal of FC_POST_ID code in as it was causing some issues on admin side (probably missing some state?) (wpsd_front_view.php)
<?php if (in_array(FC_POST_ID, array(3722, 4357, 4391, 3925, 3791))) { ?>
    <p class="wpsd-donation-sponsor-volume"><?php esc_html_e( $wpsd_donate_sponsor_volume, 'wp-stripe-donation' ); ?></p>
<?php } ?> 

* shortcode updates for donation_amounts list
[wp_stripe_donation custom_amount="true" campaign="ALL General" campaign_id="1085081" fund="ALL General Fund" fund_id="153434" donation_amounts="1,10,20,50"]

* js listener for 'choose amount' radio btns, IIFE called on window with a bunch of jQuery (wpsd-front-script.js)
* rewrote the radio btns and how they interact with the 'other amount', when you click radio btn the 'other amount'
* populates and when you submit the form, the form just takes whatever is in 'other amount'




= 1.0.7 =
* added a redirect from the donation form (to https://asianlegacylibrary.org/donate/thank-you/) on a successfull order.

= 1.0.6 =
* removed in memory of fields from the front view form completely.

= 1.0.5 =
* Made the donate button show translations
* Made the error, and success messages to appear with branded red color, and to be persistent on the page for 90 seconds.

= 1.0.4 =
* enabled front end for showing amounts by campaign ID.

= 1.0.1 =
* fork of wp-stripe-donation plugin to include ALL customizations for adding campaign ids to each amount.

= 1.0.0 =
* Initial release, setting up plugin-update-checker for future plugin releases.


