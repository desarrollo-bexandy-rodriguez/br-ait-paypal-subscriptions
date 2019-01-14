<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    BR_AIT_Paypal_Subscriptions
 * @subpackage BR_AIT_Paypal_Subscriptions/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    BR_AIT_Paypal_Subscriptions
 * @subpackage BR_AIT_Paypal_Subscriptions/includes
 * @author     Your Name <email@example.com>
 */
class BR_AIT_Paypal_Subscriptions_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'br-ait-paypal-subscriptions',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
