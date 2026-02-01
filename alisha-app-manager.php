<?php
/**
 * Plugin Name: Alisha App Manager
 * Plugin URI: https://kloudboy.com/alisha
 * Description: Total control panel for the Alisha mobile application. Manage configuration, features, and notifications.
 * Version: 1.0.0
 * Author: KloudBoy
 * Author URI: https://kloudboy.com
 * License: GPLv2 or later
 * Text Domain: alisha-app-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ALISHA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALISHA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ALISHA_VERSION', '1.0.0' );

// Autoloader-ish includes
require_once ALISHA_PLUGIN_DIR . 'includes/helpers/sanitizer.php';
require_once ALISHA_PLUGIN_DIR . 'includes/services/config-service.php';
require_once ALISHA_PLUGIN_DIR . 'includes/services/token-service.php';
require_once ALISHA_PLUGIN_DIR . 'includes/services/firebase-service.php';
require_once ALISHA_PLUGIN_DIR . 'includes/api/config-endpoints.php';
require_once ALISHA_PLUGIN_DIR . 'includes/api/auth-endpoints.php';
require_once ALISHA_PLUGIN_DIR . 'includes/api/notification-endpoints.php';
require_once ALISHA_PLUGIN_DIR . 'includes/admin/dashboard.php';

class Alisha_App_Manager {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	public function init() {
		// Initialize services
		Alisha_Config_Service::init();
		Alisha_Token_Service::init();
		Alisha_Firebase_Service::init();
		
		// Initialize API
		Alisha_Config_Endpoints::init();
		Alisha_Auth_Endpoints::init();
		Alisha_Notification_Endpoints::init();

		// Initialize Admin
		if ( is_admin() ) {
			Alisha_Admin_Dashboard::init();
		}
	}
}

Alisha_App_Manager::get_instance();
