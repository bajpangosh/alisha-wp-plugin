<?php
if (!defined('ABSPATH')) {
    exit;
}

class Alisha_Onboarding_Endpoints
{

    public static function init()
    {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }

    public static function register_routes()
    {
        register_rest_route('alisha/v1', '/onboarding', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_config'),
            'permission_callback' => array(__CLASS__, 'validate_request'),
        ));
    }

    public static function validate_request($request)
    {
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
        $config = get_option('alisha_onboarding_config', array());

        // Default structure
        $defaults = array(
            'enabled' => true,
            'version' => '1.0',
            'style' => 'fullscreen', // fullscreen, card, minimal
            'settings' => array(
                'primaryColor' => '#6200EE',
                'textColor' => '#FFFFFF',
                'overlayOpacity' => 0.5,
                'showSkip' => true,
            ),
            'steps' => array(
                array(
                    'id' => 'welcome',
                    'title' => 'Welcome to Alisha',
                    'description' => 'Discover a faster and cleaner way to browse your WordPress-powered content.',
                    'imageUrl' => 'https://images.unsplash.com/photo-1557682250-33bd709cbe85?auto=format&fit=crop&w=1080&q=80',
                    'buttonText' => 'Next',
                ),
                array(
                    'id' => 'menus',
                    'title' => 'Smart Navigation',
                    'description' => 'Use the drawer and bottom menu to jump quickly between key pages.',
                    'imageUrl' => 'https://images.unsplash.com/photo-1518773553398-650c184e0bb3?auto=format&fit=crop&w=1080&q=80',
                    'buttonText' => 'Continue',
                ),
                array(
                    'id' => 'ready',
                    'title' => 'You Are Ready',
                    'description' => 'Personalize settings anytime from your dashboard and publish updates instantly.',
                    'imageUrl' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1080&q=80',
                    'buttonText' => 'Get Started',
                ),
            )
        );

        $config = wp_parse_args($config, $defaults);

        // Ensure steps is an array
        if (empty($config['steps']) || !is_array($config['steps'])) {
            $config['steps'] = $defaults['steps'];
        }

        // Sanitize/Prepare response
        $response = array(
            'enabled' => (bool) $config['enabled'],
            'version' => (string) $config['version'],
            'style' => (string) $config['style'],
            'settings' => array(
                'primaryColor' => (string) ($config['settings']['primaryColor'] ?? '#6200EE'),
                'textColor' => (string) ($config['settings']['textColor'] ?? '#FFFFFF'),
                'overlayOpacity' => (float) ($config['settings']['overlayOpacity'] ?? 0.5),
                'showSkip' => (bool) ($config['settings']['showSkip'] ?? true),
            ),
            'steps' => array_map(function ($step) {
                return array(
                    'id' => (string) ($step['id'] ?? uniqid('step_')),
                    'title' => html_entity_decode((string) ($step['title'] ?? '')),
                    'description' => html_entity_decode((string) ($step['description'] ?? '')),
                    'imageUrl' => (string) ($step['imageUrl'] ?? ''),
                    'buttonText' => (string) ($step['buttonText'] ?? 'Next'),
                );
            }, $config['steps'])
        );

        return rest_ensure_response($response);
    }
}
