<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    BR_AIT_Paypal_Subscriptions
 * @subpackage BR_AIT_Paypal_Subscriptions/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    BR_AIT_Paypal_Subscriptions
 * @subpackage BR_AIT_Paypal_Subscriptions/includes
 * @author     Your Name <email@example.com>
 */

use PayPal\CoreComponentTypes\BasicAmountType;
use PayPal\EBLBaseComponents\AddressType;
use PayPal\EBLBaseComponents\BillingAgreementDetailsType;
use PayPal\EBLBaseComponents\PaymentDetailsItemType;
use PayPal\EBLBaseComponents\PaymentDetailsType;
use PayPal\EBLBaseComponents\SetExpressCheckoutRequestDetailsType;
use PayPal\PayPalAPI\SetExpressCheckoutReq;
use PayPal\PayPalAPI\SetExpressCheckoutRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;

use PayPal\EBLBaseComponents\ActivationDetailsType;
use PayPal\EBLBaseComponents\BillingPeriodDetailsType;
use PayPal\EBLBaseComponents\CreateRecurringPaymentsProfileRequestDetailsType;
use PayPal\EBLBaseComponents\CreditCardDetailsType;
use PayPal\EBLBaseComponents\RecurringPaymentsProfileDetailsType;
use PayPal\EBLBaseComponents\ScheduleDetailsType;
use PayPal\PayPalAPI\CreateRecurringPaymentsProfileReq;
use PayPal\PayPalAPI\CreateRecurringPaymentsProfileRequestType;

use PayPal\EBLBaseComponents\ManageRecurringPaymentsProfileStatusRequestDetailsType;
use PayPal\PayPalAPI\ManageRecurringPaymentsProfileStatusRequestType;
use PayPal\PayPalAPI\ManageRecurringPaymentsProfileStatusReq;

use PayPal\IPN\PPIPNMessage;

class AitPaypalSubscriptions {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Plugin_Name_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	private static $instance = null;
	private static $logFileInfo = 'paypal-info.log';
	private static $logFileError = 'paypal-error.log';
	private static $temporaryDataPrefix = '_ait_paypal_token_';
	private static $profileDataPrefix = '_ait_paypal_profile_';

	private $api;

	public $options;
	public static $getParameterName = 'ait-paypal-subscriptions-action';


	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'BR_AIT_PAYPAL_SUBSCRIPTIONS_VERSION' ) ) {
			$this->version = BR_AIT_PAYPAL_SUBSCRIPTIONS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'br-ait-paypal-subscriptions';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Plugin_Name_Loader. Orchestrates the hooks of the plugin.
	 * - Plugin_Name_i18n. Defines internationalization functionality.
	 * - Plugin_Name_Admin. Defines all hooks for the admin area.
	 * - Plugin_Name_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-br-ait-paypal-subscriptions-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-br-ait-paypal-subscriptions-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-br-ait-paypal-subscriptions-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-br-ait-paypal-subscriptions-public.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';

		$this->loader = new BR_AIT_Paypal_Subscriptions_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Plugin_Name_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new BR_AIT_Paypal_Subscriptions_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new BR_AIT_Paypal_Subscriptions_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_filter( 'ait-theme-config', $plugin_admin, 'config', 11 );

		$this->loader->add_action('after_setup_theme', $this, 'getInstance' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new BR_AIT_Paypal_Subscriptions_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Plugin_Name_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	public static function getInstance() {
		if (null == self::$instance) {
			self::$instance = new self;
			self::$instance->setOptions();
			self::$instance->setLogging();
			self::$instance->handleNotification();
			self::$instance->handleReturn();
		}
		return self::$instance;
	}

	private function setOptions() {
		if (!function_exists('aitOptions')) {
			throw new BR_AIT_Paypal_Subscriptions_Exception("PayPal plugin is compatible only with AIT framework 2.0");
		}
		$options = aitOptions()->get('theme');
		if (isset($options->paypal)) {
			$this->options = $options->paypal;
			// URLs
			$this->options->urls = new StdClass;
			$urlReturn = (!empty($this->options->returnPage)) ? get_permalink($this->options->returnPage) : home_url('/');
			$urlCancel = (!empty($this->options->cancelPage)) ? get_permalink($this->options->cancelPage) : home_url('/');
			$this->options->urls->return = add_query_arg(self::$getParameterName, 'return', $urlReturn);
			$this->options->urls->cancel = add_query_arg(self::$getParameterName, 'cancel', $urlCancel);
			$this->options->urls->notify = add_query_arg(self::$getParameterName, 'notification', home_url());
		}
	}

	private function setLogging() {
		if ($this->options->logging) {
			add_action('ait-paypal-subscriptions-notification', function ($message) {
				AitPaypalSubscriptions::log($message, 'NOTIFICATION');
			});
		}
	}

	public static function log($message, $title = '') {
		$message = print_r($message, true);
		$title = (!empty($title)) ? " - " . $title : "";
		$message = date("Y-m-d H:i:s") . $title . "\n\n" . $message . "\n";
		$file = WP_CONTENT_DIR."/".self::$logFileInfo;
		error_log($message, 3, $file);
	}

	public function handleNotification() {
		if (isset($_GET[self::$getParameterName]) && $_GET[self::$getParameterName] == 'notification' && file_get_contents('php://input')) {
			try {
				AitPaypalSubscriptions::log('PHP: Notificacion IPN Recibida', 'TRACERT');
				$config = $this->options->sandboxMode ? array("mode" => "sandbox") : array("mode" => "live");
				if ($this->options->logging) {
					$config = $config + array(
						'log.LogEnabled' => true,
						'log.FileName' => WP_CONTENT_DIR."/".self::$logFileInfo,
						'log.LogLevel' => 'DEBUG' // PLEASE USE `FINE` LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
						// 'validation.level' => 'log'
					);
				}
				$ipnMessage = new PPIPNMessage(null, $config);
				if(!$ipnMessage->validate()) {
					$message = file_get_contents('php://input');
					$messageDump = print_r($message, true);
					throw new BR_AIT_Paypal_Subscriptions_Exception("Error with validation of IPN message\n".$messageDump);	
				}
				$transactionType = $ipnMessage->getTransactionType();
				if (empty($transactionType)) {
					throw new BR_AIT_Paypal_Subscriptions_Exception("Empty transaction type");	
				}
				$message = $ipnMessage->getRawData();
				do_action('ait-paypal-subscriptions-notification', $message);
				// only messages for recurring payments
				if (isset($message['recurring_payment_id'])) {
					$profileId = $message['recurring_payment_id'];	
					$profile = get_option(self::$profileDataPrefix.$profileId);
					if (!$profile) {
						$messageDump = print_r($message, true);
						throw new BR_AIT_Paypal_Subscriptions_Exception("Missing profile data in DB. Profile ID: ".$profileId."\n".$messageDump);
					}
					$message = (object) $message;
					$message->data = $profile->data;
					switch ($transactionType) {
						case 'recurring_payment':
							AitPaypalSubscriptions::log('PHP: Transaccion-recurring_payment', 'TRACERT');
							do_action('ait-paypal-subscriptions-payment-completed', $message);
							break;
						case 'recurring_payment_profile_created':
							do_action('ait-paypal-subscriptions-profile-created', $message);
							break;
						case 'recurring_payment_profile_cancel':
							do_action('ait-paypal-subscriptions-profile-canceled', $message);
							break;
					}
				}
			} catch (Exception $e) {
				self::error($e);
			}
		}
	}

		public function handleReturn() {
		if (isset($_GET[self::$getParameterName]) && $_GET[self::$getParameterName] == 'return' && isset($_GET['token'])) {
			$token = $_GET['token'];
			$agreement = get_transient($temporaryDataPrefix.$token);
			delete_transient($temporaryDataPrefix.$token);
			if ($agreement) {
				try {
					$api = $this->getApi();

					// Create profile
					$createRPProfileRequestDetail = new CreateRecurringPaymentsProfileRequestDetailsType();
					$createRPProfileRequestDetail->Token = $token;

					if (empty($agreement->initialAmount)) {
						$startDate = date(DATE_ISO8601, time()+10); // + 10 seconds to work with PayPal API
					} else {
						$startDate = date(DATE_ISO8601, strtotime('+'.$interval.' days'));
					}
					$RPProfileDetails = new RecurringPaymentsProfileDetailsType();
					$RPProfileDetails->BillingStartDate = $startDate;
					$createRPProfileRequestDetail->RecurringPaymentsProfileDetails = $RPProfileDetails;

					$currencyCode = $agreement->currency;
					$paymentBillingPeriod =  new BillingPeriodDetailsType();
					$paymentBillingPeriod->BillingFrequency = $agreement->interval;
					$paymentBillingPeriod->BillingPeriod = 'Day';
					$paymentBillingPeriod->Amount = new BasicAmountType($currencyCode, $agreement->amount);

					if ($agreement->trialTime > 0) {
						$trialBillingPeriod =  new BillingPeriodDetailsType();
						$trialBillingPeriod->BillingFrequency = $agreement->trialTime;
						$trialBillingPeriod->BillingPeriod = 'Day';
						$trialBillingPeriod->TotalBillingCycles = 1;
						$trialBillingPeriod->Amount = new BasicAmountType($currencyCode, 0);
					}
					
					$scheduleDetails = new ScheduleDetailsType();
					$scheduleDetails->Description = $agreement->description;
					$scheduleDetails->PaymentPeriod = $paymentBillingPeriod;
					if ($agreement->trialTime > 0) {
						$scheduleDetails->TrialPeriod  = $trialBillingPeriod;
					}
					$createRPProfileRequestDetail->ScheduleDetails = $scheduleDetails;


					$createRPProfileRequest = new CreateRecurringPaymentsProfileRequestType();
					$createRPProfileRequest->CreateRecurringPaymentsProfileRequestDetails = $createRPProfileRequestDetail;

					$createRPProfileReq =  new CreateRecurringPaymentsProfileReq();
					$createRPProfileReq->CreateRecurringPaymentsProfileRequest = $createRPProfileRequest;

					// Create
					$createRPProfileResponse = $api->CreateRecurringPaymentsProfile($createRPProfileReq);

					if (empty($createRPProfileResponse->CreateRecurringPaymentsProfileResponseDetails->ProfileID)) {
						throw new BR_AIT_Paypal_Subscriptions_Exception("Problem with creating profile");
					}

					$agreement->id = $createRPProfileResponse->CreateRecurringPaymentsProfileResponseDetails->ProfileID;

					// Call action
					do_action('ait-paypal-subscriptions-agreement-confirmed', $agreement);

					// Save profile to DB
					update_option(self::$profileDataPrefix.$agreement->id, $agreement);

				} catch (Exception $e) {
					self::error($e);
				}
			}
		}
	}

	public function createAgreement($data, $agreement) {
		try {
			// Convert agreement array to object
			$agreement = (object) $agreement;
			// Correct strings
			$agreement->name = substr($agreement->name, 0, 127);
			$agreement->description = substr($agreement->description, 0, 127);
			if (empty($agreement->description)) {
				$agreement->description = $agreement->name;
			}
			// Translate strings
			if (class_exists('AitLangs')) {
				$agreement->name = AitLangs::getCurrentLocaleText($agreement->name);
				$agreement->description = AitLangs::getCurrentLocaleText($agreement->description);
			}
			$agreement->data = $data;

			$api = $this->getApi();

			// Notify URL for recurring payments isn't available
			// $paymentDetails = new PaymentDetailsType();
			// $paymentDetails->NotifyURL = $this->options->urls->notify;
			// $setECReqDetails->PaymentDetails = $paymentDetails;

			// Get token
			$setECReqDetails = new SetExpressCheckoutRequestDetailsType();
			$setECReqDetails->CancelURL = $this->options->urls->cancel;
			$setECReqDetails->ReturnURL = $this->options->urls->return;
			$billingAgreementDetails = new BillingAgreementDetailsType('RecurringPayments');
			$billingAgreementDetails->BillingAgreementDescription = $agreement->description;
			$setECReqDetails->BillingAgreementDetails = array($billingAgreementDetails);
			$setECReqType = new SetExpressCheckoutRequestType();
			$setECReqType->SetExpressCheckoutRequestDetails = $setECReqDetails;
			$setECReq = new SetExpressCheckoutReq();
			$setECReq->SetExpressCheckoutRequest = $setECReqType;

			$setECResponse = $api->SetExpressCheckout($setECReq);
			if(!isset($setECResponse) || $setECResponse->Ack != 'Success') {
				throw new BR_AIT_Paypal_Subscriptions_Exception($setECResponse->LongMessage);
			}

			// save temporary data to WP DB (three hours)
			set_transient($temporaryDataPrefix.$setECResponse->Token, $agreement, 60 * 60 * 3);

			// Approve token
			if ($this->options->sandboxMode) {
				header('Location: https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token='.$setECResponse->Token);
			} else {
				header('Location: https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token='.$setECResponse->Token);
			}			
			die();
		} catch (Exception $e) {
			self::error($e);
		}
	}

		public function cancelPackage($data, $agreement) {
		try {
			/*
			* The ManageRecurringPaymentsProfileStatus API operation cancels, suspends, or reactivates a recurring payments profile. 
			*/
						$manageRPPStatusReqestDetails = new ManageRecurringPaymentsProfileStatusRequestDetailsType();
			/*
			*  (Required) The action to be performed to the recurring payments profile. Must be one of the following:

				Cancel – Only profiles in Active or Suspended state can be canceled.

				Suspend – Only profiles in Active state can be suspended.

				Reactivate – Only profiles in a suspended state can be reactivated.

			*/
			$manageRPPStatusReqestDetails->Action =  ucwords($data['operation']);
			/*
			* (Required) Recurring payments profile ID returned in the CreateRecurringPaymentsProfile response.
			*/
			$manageRPPStatusReqestDetails->ProfileID =  $data['recurring-payment-id'];

			$manageRPPStatusReqest = new ManageRecurringPaymentsProfileStatusRequestType();
			$manageRPPStatusReqest->ManageRecurringPaymentsProfileStatusRequestDetails = $manageRPPStatusReqestDetails;


			$manageRPPStatusReq = new ManageRecurringPaymentsProfileStatusReq();
			$manageRPPStatusReq->ManageRecurringPaymentsProfileStatusRequest = $manageRPPStatusReqest;

			/*
			* 	 ## Creating service wrapper object
			Creating service wrapper object to make API call and loading
			Configuration::getAcctAndConfig() returns array that contains credential and config parameters
			*/
			$api = $this->getApi();
			try {
				/* wrap API method calls on the service object with a try catch */
				$manageRPPStatusResponse = $api->ManageRecurringPaymentsProfileStatus($manageRPPStatusReq);
			} catch (Exception $ex) {
				self::error($ex);
			}

			if(isset($manageRPPStatusResponse)) {
				$this->subscriptionsCanceled($data);
			} else {
				// redirect back
				$this->subscriptionsCanceled($data);
				$redirect = home_url().'/?ait-notification=user-registration-error';
				wp_safe_redirect( $redirect );
				exit();
			}
	
		} catch (Exception $e) {
			self::error($e);
		}
	}

	private function subscriptionsCanceled($payment) {
		$data = $payment;
		$user = new Wp_User($data['user']);
		$packages = new ThemePackages();
		$packageOptions = $packages->getPackageBySlug($data['package'])->getOptions();
		$defaultRole = get_option('default_role');
	
		if($data['operation'] === 'cancel'){
			$user->set_role($defaultRole);
			if($packageOptions['trialTime'] != 0){
				update_user_meta( $user->ID, 'trial_status', array('status' => 'canceled', 'payment_id' => $payment->recurring_payment_id) );
				update_user_meta( $user->ID, 'package_status', array('status' => 'canceled', 'payment_id' => $payment->recurring_payment_id) );
			} else {
				update_user_meta( $user->ID, 'trial_status', array('status' => 'not set', 'payment_id' => $payment->recurring_payment_id) );
				update_user_meta( $user->ID, 'package_status', array('status' => 'canceled', 'payment_id' => $payment->recurring_payment_id) );
			}
		}
	}

	private function getApi()
	{
		if (empty($this->api)) {
			$this->setApi();
		}
		return $this->api;
	}

	private function setApi() {
		if (empty($this->options)) {
			throw new BR_AIT_Paypal_Subscriptions_Exception("Missing theme options for Paypal");
		}
		if (empty($this->options->realApiUsername) || empty($this->options->realApiPassword) || empty($this->options->realApiSignature)) {
			throw new BR_AIT_Paypal_Subscriptions_Exception("Missing API credentials for Paypal");
		}
		$config = array(
			"mode" => $this->options->sandboxMode ? "sandbox": "live",
			"acct1.UserName" => $this->options->realApiUsername,
			"acct1.Password" => $this->options->realApiPassword,
			"acct1.Signature" => $this->options->realApiSignature
		);
		if ($this->options->logging) {
			$config = $config + array(
				'log.LogEnabled' => true,
				'log.FileName' => WP_CONTENT_DIR."/".self::$logFileInfo,
				'log.LogLevel' => 'DEBUG' // PLEASE USE `FINE` LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
				// 'validation.level' => 'log'
			);
		}
		$api = new PayPalAPIInterfaceServiceService($config);
		$this->api = $api;
		return $api;
	}

	public static function error($message) {
		if ($message instanceof Exception) {
			$message = $message->getMessage();
		} else {
			$message = print_r($message, true);
		}
		$message = date("Y-m-d H:i:s") . " - " . $message . "\n";
		$file = WP_CONTENT_DIR."/".self::$logFileError;
		error_log($message, 3, $file);
	}

}

class BR_AIT_Paypal_Subscriptions_Exception extends Exception {}
