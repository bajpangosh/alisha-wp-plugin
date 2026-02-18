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
            'permission_callback' => array(__CLASS__, 'validate_request'),
        ));
    }

    public static function validate_request($request)
    {
        // Allow authenticated admins for debugging from WP dashboard.
        if (current_user_can('manage_options')) {
            return true;
        }

        $expected_app_id = apply_filters('alisha_expected_app_id', 'com.kloudboy.alisha');
        $app_id = sanitize_text_field((string) $request->get_param('app_id'));

        if (empty($app_id)) {
            $app_id = sanitize_text_field((string) $request->get_header('X-Alisha-App-Id'));
        }

        return !empty($app_id) && hash_equals($expected_app_id, $app_id);
    }

    public static function get_config($request)
    {
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
            'drawer_menu_json' => '[]',
            'footer_menu_json' => '[]',
            'drawer_menu_enabled' => true,
            'footer_menu_enabled' => true,
        );
        $config = wp_parse_args($config, $defaults);
        $drawer = json_decode(html_entity_decode((string) $config['drawer_menu_json']), true);
        $footer = json_decode(html_entity_decode((string) $config['footer_menu_json']), true);
        if (!is_array($drawer)) {
            $drawer = array();
        }
        if (!is_array($footer)) {
            $footer = array();
        }

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
            'menus' => array(
                'drawer_enabled' => (bool) $config['drawer_menu_enabled'],
                'drawer' => $drawer,
                'footer_enabled' => (bool) $config['footer_menu_enabled'],
                'footer' => $footer,
            ),
        );

        return rest_ensure_response($response_data);
    }
}
