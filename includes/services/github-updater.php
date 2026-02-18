<?php
if (!defined('ABSPATH')) {
    exit;
}

class Alisha_GitHub_Updater
{

    private $slug; // plugin slug
    private $plugin_data; // plugin data
    private $username; // GitHub username
    private $repo; // GitHub repo name
    private $plugin_file; // __FILE__ of main plugin file
    private $github_api_result; // holder for API result
    private $access_token; // optional access token for private repos

    public function __construct($plugin_file, $github_username, $github_repo, $access_token = '')
    {
        $this->plugin_file = $plugin_file;
        $this->username = $github_username;
        $this->repo = $github_repo;
        $this->access_token = $access_token;
    }

    public function init()
    {
        add_filter('pre_set_site_transient_update_plugins', array($this, 'set_transient'));
        add_filter('plugins_api', array($this, 'set_plugin_info'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'post_install'), 10, 3);
    }

    private function get_plugin_data()
    {
        if (isset($this->plugin_data)) {
            return $this->plugin_data;
        }

        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $this->plugin_data = get_plugin_data($this->plugin_file);
        $this->slug = plugin_basename($this->plugin_file);

        return $this->plugin_data;
    }

    private function get_repo_release_info()
    {
        if (!empty($this->github_api_result)) {
            return $this->github_api_result;
        }

        // Query the GitHub API
        $url = "https://api.github.com/repos/{$this->username}/{$this->repo}/releases/latest";

        // We use a transient to cache the response for 12 hours
        $transient_key = 'alisha_github_updater_' . $this->username . '_' . $this->repo;
        $cached_response = get_transient($transient_key);

        if ($cached_response) {
            $this->github_api_result = $cached_response;
            return $this->github_api_result;
        }

        $args = array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
            ),
        );

        if (!empty($this->access_token)) {
            $args['headers']['Authorization'] = "token {$this->access_token}";
        }

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return false;
        }

        $response_body = json_decode(wp_remote_retrieve_body($response));

        if (empty($response_body)) {
            return false;
        }

        set_transient($transient_key, $response_body, 12 * HOUR_IN_SECONDS);
        $this->github_api_result = $response_body;

        return $this->github_api_result;
    }

    public function set_transient($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $this->get_plugin_data();
        $remote_info = $this->get_repo_release_info();

        if (!$remote_info) {
            return $transient;
        }

        $current_version = ltrim((string) $this->plugin_data['Version'], 'vV');
        $remote_version = ltrim((string) $remote_info->tag_name, 'vV');
        $do_update = version_compare($current_version, $remote_version, '<');

        if ($do_update) {
            $package = $remote_info->zipball_url;

            // If there are assets, look for a specific zip (better than zipball which can be nested weirdly)
            if (!empty($remote_info->assets)) {
                foreach ($remote_info->assets as $asset) {
                    if ('application/zip' === $asset->content_type || 'application/x-zip-compressed' === $asset->content_type || substr($asset->name, -4) === '.zip') {
                        $package = $asset->browser_download_url;
                        break;
                    }
                }
            }

            $obj = new stdClass();
            $obj->slug = $this->slug;
            $obj->new_version = $remote_info->tag_name;
            $obj->url = $remote_info->html_url;
            $obj->package = $package; // The zip file URL

            // WordPress expects the slug to match the directory name
            // So we need to ensure the zip extracts correctly or WP handles it. 
            // But for transients, we just provide the data.
            $transient->response[$this->slug] = $obj;
        }

        return $transient;
    }

    public function set_plugin_info($false, $action, $response)
    {
        $this->get_plugin_data();

        if (empty($response->slug) || $response->slug !== $this->slug) {
            return $false;
        }

        $remote_info = $this->get_repo_release_info();

        if (!$remote_info) {
            return $false;
        }

        $obj = new stdClass();
        $obj->slug = $this->slug;
        $obj->name = $this->plugin_data['Name'];
        $obj->plugin_name = $this->plugin_data['Name'];
        $obj->new_version = $remote_info->tag_name;
        $obj->requires = '5.0'; // Minimum WP version
        $obj->tested = '6.7'; // Tested up to
        $obj->download_link = $remote_info->zipball_url;
        $obj->trunk = $remote_info->zipball_url;
        $obj->last_updated = $remote_info->published_at;

        $obj->sections = array(
            'description' => $this->plugin_data['Description'],
            'changelog' => nl2br($remote_info->body), // GitHub release body as changelog
        );

        return $obj;
    }

    public function post_install($true, $hook_extra, $result)
    {
        global $wp_filesystem;
        if (empty($result['destination']) || empty($wp_filesystem)) {
            return $result;
        }

        $plugin_folder = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname($this->slug);
        $source = $result['destination'];

        // Skip move when destination is already the plugin folder.
        if (untrailingslashit($source) !== untrailingslashit($plugin_folder)) {
            if ($wp_filesystem->is_dir($plugin_folder)) {
                $wp_filesystem->delete($plugin_folder, true);
            }

            $moved = $wp_filesystem->move($source, $plugin_folder, true);
            if (!$moved) {
                return $result;
            }
            $result['destination'] = $plugin_folder;
        }

        // Re-activate plugin if needed
        $activate = is_plugin_active($this->slug);
        if ($activate) {
            activate_plugin($this->slug);
        }

        return $result;
    }
}
