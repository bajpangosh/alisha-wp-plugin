<?php
if (!defined('ABSPATH')) {
    exit;
}

class Alisha_Admin_Dashboard
{

    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('admin_post_alisha_save_settings', array(__CLASS__, 'save_settings'));
        add_action('wp_ajax_alisha_save_settings', array(__CLASS__, 'ajax_save_settings'));
    }

    public static function add_menu_page()
    {
        add_menu_page(
            'Alisha App',
            'Alisha App',
            'manage_options',
            'alisha-app-manager',
            array(__CLASS__, 'render_dashboard'),
            'dashicons-smartphone',
            30
        );
    }

    public static function enqueue_assets($hook)
    {
        if ('toplevel_page_alisha-app-manager' !== $hook) {
            return;
        }
        // Fonts & Icons (Inter + Material Icons)
        wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', array(), null);
        wp_enqueue_style('material-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons', array(), null);

        // In a real build, these would be built assets. For now, we'll inline some styles for the "shadcn" look.
        $css_version = file_exists(ALISHA_PLUGIN_DIR . 'assets/css/admin.css') ? filemtime(ALISHA_PLUGIN_DIR . 'assets/css/admin.css') : ALISHA_VERSION;
        $js_version = file_exists(ALISHA_PLUGIN_DIR . 'assets/js/admin.js') ? filemtime(ALISHA_PLUGIN_DIR . 'assets/js/admin.js') : ALISHA_VERSION;
        wp_enqueue_style('alisha-admin-css', ALISHA_PLUGIN_URL . 'assets/css/admin.css', array(), $css_version);
        wp_enqueue_script('alisha-admin-js', ALISHA_PLUGIN_URL . 'assets/js/admin.js', array(), $js_version, true);

        wp_localize_script('alisha-admin-js', 'alishaAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('alisha_save_settings')
        ));
    }

    public static function save_settings()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('alisha_save_settings', 'alisha_nonce');

        self::save_config_data($_POST);

        wp_redirect(admin_url('admin.php?page=alisha-app-manager&status=success'));
        exit;
    }

    public static function ajax_save_settings()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        check_ajax_referer('alisha_save_settings', 'alisha_nonce');

        self::save_config_data($_POST);

        wp_send_json_success('Settings saved successfully');
    }

    private static function save_config_data($data)
    {
        $onboarding_steps = array();
        if (isset($data['onboarding_steps_json'])) {
            $decoded_steps = json_decode(wp_unslash($data['onboarding_steps_json']), true);
            if (is_array($decoded_steps)) {
                $onboarding_steps = $decoded_steps;
            }
        }

        $overlay_opacity = isset($data['onboarding_overlay_opacity']) ? floatval(wp_unslash($data['onboarding_overlay_opacity'])) : 0.5;
        $overlay_opacity = max(0, min(1, $overlay_opacity));

        $settings = array(
            'app_name' => sanitize_text_field(wp_unslash($data['app_name'] ?? 'Alisha')),
            'developer_name' => sanitize_text_field(wp_unslash($data['developer_name'] ?? 'KloudBoy')),
            'base_web_url' => esc_url_raw(wp_unslash($data['base_web_url'] ?? site_url())),
            'primary_color' => sanitize_hex_color(wp_unslash($data['primary_color'] ?? '#6200EE')),
            'secondary_color' => sanitize_hex_color(wp_unslash($data['secondary_color'] ?? '#03DAC6')),
            'maintenance_mode' => isset($data['maintenance_mode']) ? true : false,
            'dark_mode_enabled' => isset($data['dark_mode_enabled']) ? true : false,
            'push_notifications_enabled' => isset($data['push_notifications_enabled']) ? true : false,
            'firebase_enabled' => isset($data['firebase_enabled']) ? true : false,
            'ads_enabled' => isset($data['ads_enabled']) ? true : false,
            'force_update_version' => sanitize_text_field(wp_unslash($data['force_update_version'] ?? '1.0.0')),
            'environment' => sanitize_text_field(wp_unslash($data['environment'] ?? 'prod')),
            'drawer_menu_json' => isset($data['drawer_menu_json']) ? trim(wp_unslash($data['drawer_menu_json'])) : '[]',
            'footer_menu_json' => isset($data['footer_menu_json']) ? trim(wp_unslash($data['footer_menu_json'])) : '[]',
            'drawer_menu_enabled' => isset($data['drawer_menu_enabled']) ? true : false,
            'footer_menu_enabled' => isset($data['footer_menu_enabled']) ? true : false,
        );

        // Save Onboarding Config
        $onboarding_settings = array(
            'enabled' => isset($data['onboarding_enabled']) ? true : false,
            'version' => sanitize_text_field(wp_unslash($data['onboarding_version'] ?? '1.0')),
            'style' => sanitize_text_field(wp_unslash($data['onboarding_style'] ?? 'fullscreen')),
            'settings' => array(
                'primaryColor' => sanitize_hex_color(wp_unslash($data['onboarding_primary_color'] ?? '#6200EE')),
                'textColor' => sanitize_hex_color(wp_unslash($data['onboarding_text_color'] ?? '#FFFFFF')),
                'overlayOpacity' => $overlay_opacity,
                'showSkip' => isset($data['onboarding_show_skip']) ? true : false,
            ),
            'steps' => $onboarding_steps,
        );
        update_option('alisha_onboarding_config', $onboarding_settings);

        update_option('alisha_app_config', $settings);
        return true;
    }

    public static function render_dashboard()
    {
        $config = get_option('alisha_app_config', array());
        $defaults = array(
            'app_name' => 'Alisha',
            'developer_name' => 'KloudBoy',
            'base_web_url' => site_url(),
            'primary_color' => '#6200EE',
            'secondary_color' => '#03DAC6',
            'maintenance_mode' => false,
            'dark_mode_enabled' => true,
            'push_notifications_enabled' => false,
            'firebase_enabled' => false,
            'ads_enabled' => false,
            'force_update_version' => '1.0.0',
            'environment' => 'prod',
            'drawer_menu_json' => '[]',
            'footer_menu_json' => '[]',
            'drawer_menu_enabled' => true,
            'footer_menu_enabled' => true,
        );
        $config = wp_parse_args($config, $defaults);

        // Onboarding Config
        $onboarding_config = get_option('alisha_onboarding_config', array());
        $onboarding_defaults = array(
            'enabled' => true,
            'version' => '1.0',
            'style' => 'fullscreen',
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
        $onboarding_config = wp_parse_args($onboarding_config, $onboarding_defaults);
        if (empty($onboarding_config['steps']) || !is_array($onboarding_config['steps'])) {
            $onboarding_config['steps'] = $onboarding_defaults['steps'];
        }
        ?>
        <div class="wrap alisha-wrap">
            <div class="alisha-header">
                <h1>Alisha App Controller</h1>
                <p>Manage your mobile application behavior and appearance in real-time.</p>
            </div>

            <?php if (isset($_GET['status']) && 'success' === $_GET['status']): ?>
                <div class="notice notice-success is-dismissible" style="margin-left:0; margin-bottom: 24px;">
                    <p>Settings saved successfully.</p>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="alisha-form">
                <input type="hidden" name="action" value="alisha_save_settings">
                <?php wp_nonce_field('alisha_save_settings', 'alisha_nonce'); ?>

                <div class="alisha-dashboard-grid">
                    <!-- Main Content -->
                    <div class="alisha-col-main">
                        <!-- General Config -->
                        <div class="shadcn-card">
                            <div class="shadcn-card-header">
                                <h3 class="shadcn-card-title">General Configuration</h3>
                                <p class="shadcn-card-description">Basic details about your application.</p>
                            </div>
                            <div class="shadcn-card-content">
                                <table class="form-table">
                                    <tr>
                                        <th><label>App Name</label></th>
                                        <td><input type="text" name="app_name"
                                                value="<?php echo esc_attr($config['app_name']); ?>" class="shadcn-input"></td>
                                    </tr>
                                    <tr>
                                        <th><label>Developer Name</label></th>
                                        <td><input type="text" name="developer_name"
                                                value="<?php echo esc_attr($config['developer_name']); ?>" class="shadcn-input">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label>Web View URL</label></th>
                                        <td><input type="url" name="base_web_url"
                                                value="<?php echo esc_attr($config['base_web_url']); ?>" class="shadcn-input">
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Menu Editor -->
                        <div class="shadcn-card">
                            <div class="shadcn-card-header">
                                <h3 class="shadcn-card-title">Menus & Navigation</h3>
                                <p class="shadcn-card-description">Configure the menus that appear in your app.</p>
                            </div>
                            <div class="shadcn-card-content">
                                <div
                                    style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                                    <h4 style="margin:0; font-size:14px;">Drawer (Hamburger) Menu</h4>
                                    <label class="shadcn-switch" style="margin:0;">
                                        <input type="checkbox" name="drawer_menu_enabled" <?php checked($config['drawer_menu_enabled']); ?>>
                                        <span style="font-size:12px;">Enable</span>
                                    </label>
                                </div>
                                <div id="drawer-menu-editor"></div>
                                <textarea name="drawer_menu_json"
                                    style="display:none;"><?php echo esc_textarea($config['drawer_menu_json']); ?></textarea>

                                <div style="height: 32px;"></div> <!-- Spacer -->

                                <div
                                    style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                                    <h4 style="margin:0; font-size:14px;">Footer Menu</h4>
                                    <label class="shadcn-switch" style="margin:0;">
                                        <input type="checkbox" name="footer_menu_enabled" <?php checked($config['footer_menu_enabled']); ?>>
                                        <span style="font-size:12px;">Enable</span>
                                    </label>
                                </div>
                                <div id="footer-menu-editor"></div>
                                <textarea name="footer_menu_json"
                                    style="display:none;"><?php echo esc_textarea($config['footer_menu_json']); ?></textarea>
                            </div>
                        </div>

                        <!-- Onboarding Editor -->
                        <div class="shadcn-card">
                            <div class="shadcn-card-header">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <h3 class="shadcn-card-title">Onboarding Screens</h3>
                                        <p class="shadcn-card-description">Manage the welcome tour for new users.</p>
                                    </div>
                                    <label class="shadcn-switch">
                                        <input type="checkbox" name="onboarding_enabled" <?php checked($onboarding_config['enabled']); ?>>
                                        <span>Enable</span>
                                    </label>
                                </div>
                            </div>
                            <div class="shadcn-card-content">
                                <!-- Global Styles -->
                                <div
                                    style="background: hsl(var(--muted)); padding: 16px; border-radius: var(--radius); margin-bottom: 24px;">
                                    <h4 style="margin-top:0; margin-bottom:12px; font-size:14px;">Global Style</h4>
                                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                        <div>
                                            <label style="display:block; font-size:12px; margin-bottom:4px;">Layout</label>
                                            <select name="onboarding_style" class="shadcn-select" style="background:white;">
                                                <option value="fullscreen" <?php selected($onboarding_config['style'], 'fullscreen'); ?>>Fullscreen Image</option>
                                                <option value="card" <?php selected($onboarding_config['style'], 'card'); ?>>
                                                    Centered Card</option>
                                                <option value="minimal" <?php selected($onboarding_config['style'], 'minimal'); ?>>Minimal</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label style="display:block; font-size:12px; margin-bottom:4px;">Overlay
                                                Opacity</label>
                                            <input type="number" step="0.1" min="0" max="1" name="onboarding_overlay_opacity"
                                                value="<?php echo esc_attr($onboarding_config['settings']['overlayOpacity']); ?>"
                                                class="shadcn-input" style="background:white;">
                                        </div>
                                        <div>
                                            <label style="display:block; font-size:12px; margin-bottom:4px;">Primary
                                                Color</label>
                                            <input type="color" name="onboarding_primary_color"
                                                value="<?php echo esc_attr($onboarding_config['settings']['primaryColor']); ?>"
                                                class="shadcn-input" style="height:40px; padding:2px; background:white;">
                                        </div>
                                        <div>
                                            <label style="display:block; font-size:12px; margin-bottom:4px;">Text Color</label>
                                            <input type="color" name="onboarding_text_color"
                                                value="<?php echo esc_attr($onboarding_config['settings']['textColor']); ?>"
                                                class="shadcn-input" style="height:40px; padding:2px; background:white;">
                                        </div>
                                    </div>
                                    <div style="margin-top:12px;">
                                        <label class="shadcn-switch">
                                            <input type="checkbox" name="onboarding_show_skip" <?php checked($onboarding_config['settings']['showSkip']); ?>>
                                            <span style="font-size:13px;">Show Skip Button</span>
                                        </label>
                                    </div>
                                    <input type="hidden" name="onboarding_version"
                                        value="<?php echo esc_attr($onboarding_config['version']); ?>">
                                </div>

                                <!-- Steps Editor -->
                                <h4 style="margin: 0 0 12px 0; font-size:14px;">Steps</h4>
                                <div id="onboarding-steps-editor"></div>
                                <textarea name="onboarding_steps_json"
                                    style="display:none;"><?php echo esc_textarea(json_encode($onboarding_config['steps'])); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="alisha-col-sidebar">

                            <!-- Primary Save Action (Sticky-ish) -->
                            <div class="shadcn-card" style="border: 1px solid hsl(var(--primary)); background: #fcfcfc;">
                                <div class="shadcn-card-content" style="padding: 16px;">
                                    <input type="submit" name="submit" id="submit" class="shadcn-button shadcn-button-primary"
                                        value="Save Changes" style="width:100%;">
                                </div>
                            </div>

                            <!-- Appearance -->
                            <div class="shadcn-card">
                                <div class="shadcn-card-header">
                                    <h3 class="shadcn-card-title">Appearance</h3>
                                </div>
                                <div class="shadcn-card-content">
                                    <table class="form-table">
                                        <tr>
                                            <th><label>Primary</label></th>
                                            <td><input type="color" name="primary_color"
                                                    value="<?php echo esc_attr($config['primary_color']); ?>"
                                                    class="shadcn-input"></td>
                                        </tr>
                                        <tr>
                                            <th><label>Secondary</label></th>
                                            <td><input type="color" name="secondary_color"
                                                    value="<?php echo esc_attr($config['secondary_color']); ?>"
                                                    class="shadcn-input"></td>
                                        </tr>
                                        <tr>
                                            <th><label>Env</label></th>
                                            <td>
                                                <select name="environment" class="shadcn-select">
                                                    <option value="dev" <?php selected($config['environment'], 'dev'); ?>>
                                                        Development</option>
                                                    <option value="staging" <?php selected($config['environment'], 'staging'); ?>>Staging</option>
                                                    <option value="prod" <?php selected($config['environment'], 'prod'); ?>>
                                                        Production</option>
                                                </select>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Features -->
                            <div class="shadcn-card">
                                <div class="shadcn-card-header">
                                    <h3 class="shadcn-card-title">Features</h3>
                                </div>
                                <div class="shadcn-card-content">
                                    <label class="shadcn-switch">
                                        <input type="checkbox" name="maintenance_mode" <?php checked($config['maintenance_mode']); ?>>
                                        <span>Maintenance Mode</span>
                                    </label>
                                    <label class="shadcn-switch">
                                        <input type="checkbox" name="dark_mode_enabled" <?php checked($config['dark_mode_enabled']); ?>>
                                        <span>Dark Mode</span>
                                    </label>
                                    <label class="shadcn-switch">
                                        <input type="checkbox" name="push_notifications_enabled" <?php checked($config['push_notifications_enabled']); ?>>
                                        <span>Push Notifications</span>
                                    </label>
                                    <label class="shadcn-switch">
                                        <input type="checkbox" name="firebase_enabled" <?php checked($config['firebase_enabled']); ?>>
                                        <span>Firebase</span>
                                    </label>
                                    <label class="shadcn-switch">
                                        <input type="checkbox" name="ads_enabled" <?php checked($config['ads_enabled']); ?>>
                                        <span>Enable Ads</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Updates -->
                            <div class="shadcn-card">
                                <div class="shadcn-card-header">
                                    <h3 class="shadcn-card-title">App Version</h3>
                                </div>
                                <div class="shadcn-card-content">
                                    <label
                                        style="display:block; margin-bottom:8px; font-size:13px; color:hsl(var(--muted-foreground));">Force
                                        Update Version</label>
                                    <input type="text" name="force_update_version"
                                        value="<?php echo esc_attr($config['force_update_version']); ?>" class="shadcn-input">
                                </div>
                            </div>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}
