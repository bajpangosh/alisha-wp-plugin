<?php
if (!defined('ABSPATH')) {
    exit;
}

class Alisha_Config_Endpoints
{

    public static function init()
    {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }

    public static function register_routes()
    {
        register_rest_route('alisha/v1', '/app-config', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_config'),
            'permission_callback' => '__return_true', // Open endpoint, secure using App ID header if needed later
        ));
    }

    public static function get_config($request)
    {
        $app_id = $request->get_param('app_id');

        // Optional: Validate App ID if needed
        // if ( 'com.kloudboy.alisha' !== $app_id ) ...

        $config = get_option('alisha_app_config', array());

        // Fill defaults if missing
        $defaults = array(
            'app_name' => 'Alisha',
            'developer_name' => 'KloudBoy',
            'developer_website' => 'https://kloudboy.com',
            'base_web_url' => site_url(),
            'api_base_url' => site_url('/wp-json/alisha/v1'),
            'primary_color' => '#6200EE',
            'secondary_color' => '#03DAC6',
            'maintenance_mode' => false,
            'dark_mode_enabled' => true,
            'firebase_enabled' => false,
            'push_notifications_enabled' => false,
            'ads_enabled' => false,
            'force_update_version' => '1.0.0',
            'environment' => 'prod',
            'force_update_version' => '1.0.0',
            'environment' => 'prod',
            'drawer_menu_json' => '[]',
            'footer_menu_json' => '[]',
        );
        $config = wp_parse_args($config, $defaults);

        // Ensure specific types
        $response_data = array(
            'app_name' => (string) $config['app_name'],
            'developer_name' => (string) $config['developer_name'],
            'developer_website' => (string) $config['developer_website'],
            'base_web_url' => (string) $config['base_web_url'],
            'api_base_url' => (string) $config['api_base_url'],
            'styling' => array(
                'primary_color' => (string) $config['primary_color'],
                'secondary_color' => (string) $config['secondary_color'],
                'dark_mode_enabled' => (bool) $config['dark_mode_enabled'],
            ),
            'features' => array(
                'firebase_enabled' => (bool) $config['firebase_enabled'],
                'push_notifications_enabled' => (bool) $config['push_notifications_enabled'],
                'ads_enabled' => (bool) $config['ads_enabled'],
                'maintenance_mode' => (bool) $config['maintenance_mode'],
            ),
            'updates' => array(
                'force_update_version' => (string) $config['force_update_version'],
            ),
            'environment' => (string) $config['environment'],
            'environment' => (string) $config['environment'],
            'menus' => array(
                'drawer' => json_decode(html_entity_decode((string) $config['drawer_menu_json'])),
                'footer' => json_decode(html_entity_decode((string) $config['footer_menu_json'])),
            ),
        );

        return rest_ensure_response($response_data);
    }
}
