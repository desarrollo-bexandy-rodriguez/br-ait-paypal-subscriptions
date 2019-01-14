<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    BR_AIT_Paypal_Subscriptions
 * @subpackage BR_AIT_Paypal_Subscriptions/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    BR_AIT_Paypal_Subscriptions
 * @subpackage BR_AIT_Paypal_Subscriptions/admin
 * @author     Your Name <email@example.com>
 */
class BR_AIT_Paypal_Subscriptions_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	
	public static $getParameterName = 'ait-paypal-subscriptions-action';

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/br-ait-paypal-subscriptions-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/br-ait-paypal-subscriptions-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function config($config)
	{
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		
		// titles
		$titles = array();
		for ($i=1; $i <= 4; $i++) {
			$titles[$i] = new NNeonEntity;
			$titles[$i]->value = 'section';
		}
		$titles[2]->attributes = array('title' => __('Credentials','ait-paypal-subscriptions'), 'help' => __('The correct URL setting for Instant Payment Notifications (IPN) on PayPal website is also required for handling automatic recurring payments. See details <a href="https://www.ait-themes.club/doc/paypal-subscriptions-plugin-theme-options/">here</a>', 'ait-paypal-subscriptions').'<br><br>'.__('Your URL for notifications:', 'ait-paypal-subscriptions').' '.home_url('?ait-paypal-subscriptions-action=notification'));
		$titles[3]->attributes = array('title' => __('Redirections','ait-paypal-subscriptions'));
		$titles[4]->attributes = array('title' => __('Logging','ait-paypal-subscriptions'));

		// PayPal Single Payments plugin is active
		if (isset($config['paypal'])) {
			// Add help text
			$config['paypal']['options'][2] = $titles[2];
		} else {
			$config['paypal'] = array(
				'title' => 'PayPal BR',
				'options' => array(

					2 => $titles[2],

					'sandboxMode' => array(
						'label' => __('Sandbox Mode','ait-paypal-subscriptions'),
						'type' => 'on-off',
						'default' => false,
						'help' => __('Enable for test purchases.','ait-paypal-subscriptions')
					),
					'realApiUsername' => array(
						'label' => __('API Username','ait-paypal-subscriptions'),
						'type' => 'code',
						'default' => ''
					),
					'realApiPassword' => array(
						'label' => __('API Password','ait-paypal-subscriptions'),
						'type' => 'code',
						'default' => ''
					),
					'realApiSignature' => array(
						'label' => __('API Signature','ait-paypal-subscriptions'),
						'type' => 'code',
						'default' => ''
					),

					3 => $titles[3],

					'returnPage' => array(
						'label' => __('After approving of payment','ait-paypal-subscriptions'),
						'type' => 'posts',
						'cpt' => 'page',
						'default' => '',
						'help' => __('Visitor is redirected to selected page after successful payment','ait-paypal-subscriptions')
					),
					'cancelPage' => array(
						'label' => __('After cancelling of payment process','ait-paypal-subscriptions'),
						'type' => 'posts',
						'cpt' => 'page',
						'default' => '',
						'help' => __('Visitor is redirected to selected page after cancelled payment','ait-paypal-subscriptions')
					),

					4 => $titles[4],

					'logging' => array(
						'label' => __('Enable logging','ait-paypal-subscriptions'),
						'type' => 'on-off',
						'default' => false,
						'help' => __('Logs are stored in wp-content/paypal-info.log file','ait-paypal-subscriptions')
					)

				)
			);
		}

		return $config;
	}

}
