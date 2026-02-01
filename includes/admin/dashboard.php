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
        // In a real build, these would be built assets. For now, we'll inline some styles for the "shadcn" look.
        wp_enqueue_style('alisha-admin-css', ALISHA_PLUGIN_URL . 'assets/css/admin.css', array(), ALISHA_VERSION);
    }

    public static function save_settings()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('alisha_save_settings', 'alisha_nonce');

        $settings = array(
            'app_name' => sanitize_text_field($_POST['app_name']),
            'developer_name' => sanitize_text_field($_POST['developer_name']),
            'base_web_url' => esc_url_raw($_POST['base_web_url']),
            'primary_color' => sanitize_hex_color($_POST['primary_color']),
            'secondary_color' => sanitize_hex_color($_POST['secondary_color']),
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? true : false,
            'dark_mode_enabled' => isset($_POST['dark_mode_enabled']) ? true : false,
            'push_enabled' => isset($_POST['push_enabled']) ? true : false,
            'firebase_enabled' => isset($_POST['firebase_enabled']) ? true : false,
            'ads_enabled' => isset($_POST['ads_enabled']) ? true : false,
            'force_update_version' => sanitize_text_field($_POST['force_update_version']),
            'force_update_version' => sanitize_text_field($_POST['force_update_version']),
            'environment' => sanitize_text_field($_POST['environment']),
            'drawer_menu_json' => wp_kses_post($_POST['drawer_menu_json']), // Keeping JSON structure but safe
            'footer_menu_json' => wp_kses_post($_POST['footer_menu_json']),
        );

        update_option('alisha_app_config', $settings);

        wp_redirect(admin_url('admin.php?page=alisha-app-manager&status=success'));
        exit;
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
            'push_enabled' => false,
            'firebase_enabled' => false,
            'ads_enabled' => false,
            'force_update_version' => '1.0.0',
            'force_update_version' => '1.0.0',
            'environment' => 'prod',
            'drawer_menu_json' => '[]',
            'footer_menu_json' => '[]',
        );
        $config = wp_parse_args($config, $defaults);
        ?>
        <div class="wrap alisha-wrap">
            <h1>Alisha App Controller</h1>
            <p>Manage your mobile application behavior in real-time.</p>

            <?php if (isset($_GET['status']) && 'success' === $_GET['status']): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Settings saved successfully.</p>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="alisha-form">
                <input type="hidden" name="action" value="alisha_save_settings">
                <?php wp_nonce_field('alisha_save_settings', 'alisha_nonce'); ?>

                <div class="card">
                    <h2>General Configuration</h2>
                    <table class="form-table">
                        <tr>
                            <th><label>App Name</label></th>
                            <td><input type="text" name="app_name" value="<?php echo esc_attr($config['app_name']); ?>"
                                    class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label>Developer Name</label></th>
                            <td><input type="text" name="developer_name"
                                    value="<?php echo esc_attr($config['developer_name']); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label>Web View Base URL</label></th>
                            <td><input type="url" name="base_web_url" value="<?php echo esc_attr($config['base_web_url']); ?>"
                                    class="regular-text"></td>
                        </tr>
                    </table>
                </div>

                <div class="card">
                    <h2>Appearance & Behavior</h2>
                    <table class="form-table">
                        <tr>
                            <th><label>Primary Color</label></th>
                            <td><input type="color" name="primary_color"
                                    value="<?php echo esc_attr($config['primary_color']); ?>"></td>
                        </tr>
                        <tr>
                            <th><label>Secondary Color</label></th>
                            <td><input type="color" name="secondary_color"
                                    value="<?php echo esc_attr($config['secondary_color']); ?>"></td>
                        </tr>
                        <tr>
                            <th><label>Environment</label></th>
                            <td>
                                <select name="environment">
                                    <option value="dev" <?php selected($config['environment'], 'dev'); ?>>Development</option>
                                    <option value="staging" <?php selected($config['environment'], 'staging'); ?>>Staging
                                    </option>
                                    <option value="prod" <?php selected($config['environment'], 'prod'); ?>>Production
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="card">
                    <h2>Features & Flags</h2>
                    <p>Toggle features remotely without app updates.</p>
                    <label class="toggle-switch">
                        <input type="checkbox" name="maintenance_mode" <?php checked($config['maintenance_mode']); ?>>
                        <span>Maintenance Mode (Lock App)</span>
                    </label>
                    <br><br>
                    <label class="toggle-switch">
                        <input type="checkbox" name="dark_mode_enabled" <?php checked($config['dark_mode_enabled']); ?>>
                        <span>Allow Dark Mode</span>
                    </label>
                    <br><br>
                    <label class="toggle-switch">
                        <input type="checkbox" name="push_enabled" <?php checked($config['push_enabled']); ?>>
                        <span>Push Notifications</span>
                    </label>
                    <br><br>
                    <label class="toggle-switch">
                        <input type="checkbox" name="firebase_enabled" <?php checked($config['firebase_enabled']); ?>>
                        <span>Enable Firebase</span>
                    </label>
                    <br><br>
                    <label class="toggle-switch">
                        <input type="checkbox" name="ads_enabled" <?php checked($config['ads_enabled']); ?>>
                        <span>Enable Ads</span>
                    </label>
                </div>

                <div class="card">
                    <h2>Updates</h2>
                    <table class="form-table">
                        <tr>
                            <th><label>Force Update Below Version</label></th>
                            <td>
                                <input type="text" name="force_update_version"
                                    value="<?php echo esc_attr($config['force_update_version']); ?>" class="small-text">
                                <p class="description">Users with app versions lower than this will be forced to update.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="card">
                    <h2>Menus & Navigation</h2>
                    <p class="description">Define menus using JSON format. Example:
                        <code>[{"label": "Home", "icon": "home", "action": "url", "value": "https://..."}]</code></p>
                    <table class="form-table">
                        <tr>
                            <th><label>Drawer (Hamburger) Menu JSON</label></th>
                            <td>
                                <textarea name="drawer_menu_json" rows="10" cols="50"
                                    class="large-text code"><?php echo esc_textarea($config['drawer_menu_json']); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Footer Menu JSON</label></th>
                            <td>
                                <textarea name="footer_menu_json" rows="10" cols="50"
                                    class="large-text code"><?php echo esc_textarea($config['footer_menu_json']); ?></textarea>
                            </td>
                        </tr>
                    </table>
                </div>

                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
                </p>
            </form>
        </div>
        <?php
    }
}
