<?php
/**
 * Plugin Name: Alisha App Manager
 * Plugin URI: https://kloudboy.com/alisha
 * Description: Total control panel for the Alisha mobile application. Manage configuration, features, and notifications.
 * Version: 1.5.0
 * Author: KloudBoy
 * Author URI: https://kloudboy.com
 * License: GPLv2 or later
 * Text Domain: alisha-app-manager
 */

if (!defined('ABSPATH')) {
	exit;
}

define('ALISHA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ALISHA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ALISHA_VERSION', '1.5.0');

// Autoloader-ish includes
require_once ALISHA_PLUGIN_DIR . 'includes/helpers/sanitizer.php';
require_once ALISHA_PLUGIN_DIR . 'includes/services/config-service.php';
require_once ALISHA_PLUGIN_DIR . 'includes/services/token-service.php';
require_once ALISHA_PLUGIN_DIR . 'includes/services/firebase-service.php';
require_once ALISHA_PLUGIN_DIR . 'includes/services/github-updater.php';
require_once ALISHA_PLUGIN_DIR . 'includes/api/config-endpoints.php';
require_once ALISHA_PLUGIN_DIR . 'includes/api/auth-endpoints.php';
require_once ALISHA_PLUGIN_DIR . 'includes/api/notification-endpoints.php';
require_once ALISHA_PLUGIN_DIR . 'includes/api/onboarding-endpoints.php';
require_once ALISHA_PLUGIN_DIR . 'includes/admin/dashboard.php';

class Alisha_App_Manager
{

	private static $instance = null;

	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct()
	{
		add_action('plugins_loaded', array($this, 'init'));
	}

	public function init()
	{
		// Initialize services
		Alisha_Config_Service::init();
		// Token/Firebase services are placeholders and should not be initialized until implemented.

		// Initialize Updater
		// Note: For private repos, pass a token as the 4th argument.
		// Since this is public (implied by github link), we leave it empty.
		if (is_admin()) {
			$updater = new Alisha_GitHub_Updater(__FILE__, 'bajpangosh', 'alisha-wp-plugin');
			$updater->init();
		}

		// Initialize API
		Alisha_Config_Endpoints::init();
		Alisha_Onboarding_Endpoints::init();
		// Auth/Notification endpoints are placeholders and should not be exposed until implemented.

		// Initialize Admin
		if (is_admin()) {
			Alisha_Admin_Dashboard::init();
		}
	}
}

Alisha_App_Manager::get_instance();
