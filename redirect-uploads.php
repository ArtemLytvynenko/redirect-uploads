<?php
/*
Plugin Name: Redirect Uploads to Live Site
Description: Redirects media file requests to the live site.

Plugin URI:  https://github.com/ArtemLytvynenko/redirect-uploads
Author URI:  https://github.com/ArtemLytvynenko
Author: Artem Lytvynenko

Requires at least: 4.6
Requires PHP: 5.6

License: GPL-3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Version: 2.0
*/

if(!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Add settings link on plugin page
function redirect_uploads_plugin_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=redirect-uploads-settings">Settings</a>';
    array_unshift($links, $settings_link);

    return $links;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'redirect_uploads_plugin_settings_link');

// Register settings
function redirect_uploads_register_settings() {
    register_setting('redirect_uploads_options_group', 'redirect_uploads_live_domain');
    register_setting('redirect_uploads_options_group', 'redirect_uploads_cache_duration');
}

add_action('admin_init', 'redirect_uploads_register_settings');

// Add settings page
function redirect_uploads_settings_page() {
    add_options_page('Redirect Uploads Settings', 'Redirect Uploads', 'manage_options', 'redirect-uploads-settings', 'redirect_uploads_settings_page_html');
}

add_action('admin_menu', 'redirect_uploads_settings_page');

function redirect_uploads_settings_page_html() {
    if(!current_user_can('manage_options')) {
        return;
    }

    if(isset($_GET['settings-updated'])) {
        add_settings_error('redirect_uploads_messages', 'redirect_uploads_message', __('Settings Saved', 'redirect_uploads'), 'updated');
    }

    settings_errors('redirect_uploads_messages');

    $default_live_domain = get_option('redirect_uploads_live_domain', get_default_live_domain());
    $cache_duration      = get_option('redirect_uploads_cache_duration', 1800); // Default cache duration: 1800 seconds (30 minutes)
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <!-- Add a button to clear the cache -->
        <form method="post" action="">
            <?php wp_nonce_field('clear_cache_action', 'clear_cache_nonce'); ?>
            <?php submit_button('Clear Cache', 'secondary', 'clear_cache'); ?>
        </form>

        <form action="options.php" method="post">
            <?php
            settings_fields('redirect_uploads_options_group');
            do_settings_sections('redirect-uploads-settings');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Live Site Domain</th>
                    <td>
                        <input type="text" name="redirect_uploads_live_domain" value="<?php echo esc_attr($default_live_domain); ?>"/>
                        <p class="description">Enter the domain of the live site (e.g., https://domain.com).</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Cache Duration (in seconds)</th>
                    <td>
                        <input type="number" name="redirect_uploads_cache_duration" value="<?php echo esc_attr($cache_duration); ?>"/>
                        <p class="description">Enter the cache duration in seconds. Default: 1800 (30 minutes).</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}

// Clear cache handler
function redirect_uploads_clear_cache() {
    if(isset($_POST['clear_cache']) && check_admin_referer('clear_cache_action', 'clear_cache_nonce')) {
        global $wpdb;
        // Clear all transients related to the plugin
        $transients = $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_redirect_images_%'");
        foreach($transients as $transient) {
            $key = str_replace('_transient_', '', $transient);
            delete_transient($key);
        }
        add_action('admin_notices', 'redirect_uploads_cache_cleared_notice');
    }
}

add_action('admin_init', 'redirect_uploads_clear_cache');

// Notification of successful cache clearing
function redirect_uploads_cache_cleared_notice() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('Cache cleared successfully!', 'redirect_uploads'); ?></p>
    </div>
    <?php
}

// Get default live domain based on current domain
function get_default_live_domain() {
    $local_domain = home_url();
    $parsed_url   = parse_url($local_domain);
    $host         = $parsed_url['host'];

    // Change the TLD to .com
    $host_parts                         = explode('.', $host);
    $host_parts[count($host_parts) - 1] = 'com';
    $live_domain                        = $parsed_url['scheme'] . '://' . implode('.', $host_parts);

    return $live_domain;
}

// Optimized function to replace URLs in any content
function replace_media_urls($content) {
    $local_domain = home_url();
    $parsed_url   = parse_url($local_domain);
    $host         = $parsed_url['host'] . (!empty($parsed_url['port']) ? ':' . $parsed_url['port'] : '');
    $live_domain  = get_option('redirect_uploads_live_domain', get_default_live_domain());

    $protocols = [
        $parsed_url['scheme'] . '://' . $host . '/wp-content/uploads/',
    ];

    $cache_key      = 'redirect_images_' . md5($content);
    $cached_content = get_transient($cache_key);

    if($cached_content !== false) {
        return $cached_content;
    }

    foreach($protocols as $protocol) {
        if(preg_match_all('/' . preg_quote($protocol, '/') . '(.*?)(?=\s|"|\'|$)/', $content, $matches)) {
            foreach($matches[0] as $url) {
                $file_path = str_replace($protocol, ABSPATH . 'wp-content/uploads/', $url);
                if(!file_exists($file_path)) {
                    $new_url = str_replace($protocol, $live_domain . '/wp-content/uploads/', $url);
                    $content = str_replace($url, $new_url, $content);
                }
            }
        }
    }

    // Get cache duration from settings, default is 1800 seconds (30 minutes)
    $cache_duration = get_option('redirect_uploads_cache_duration', 1800);
    set_transient($cache_key, $content, $cache_duration);

    return $content;
}

add_filter('wp_get_attachment_url', 'replace_media_urls');

function redirect_uploads_buffer_start() {
    ob_start('redirect_uploads_buffer_callback');
}

add_action('wp_loaded', 'redirect_uploads_buffer_start');

function redirect_uploads_buffer_callback($buffer) {
    return replace_media_urls($buffer);
}