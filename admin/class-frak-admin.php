<?php

class Frak_Admin {

    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add AJAX handlers for webhook operations
        add_action('wp_ajax_frak_generate_webhook_secret', array($this, 'ajax_generate_webhook_secret'));
        add_action('wp_ajax_frak_test_webhook', array($this, 'ajax_test_webhook'));
        add_action('wp_ajax_frak_clear_webhook_logs', array($this, 'ajax_clear_webhook_logs'));
        add_action('wp_ajax_frak_check_webhook_status', array($this, 'ajax_check_webhook_status'));
    }

    public function add_admin_menu() {
        add_options_page(
            'Frak Settings',
            'Frak',
            'manage_options',
            'frak-settings',
            array($this, 'settings_page')
        );
    }

    public function register_settings() {
        register_setting('frak_settings', 'frak_app_name');
        register_setting('frak_settings', 'frak_logo_url');
        register_setting('frak_settings', 'frak_enable_purchase_tracking');
        register_setting('frak_settings', 'frak_enable_floating_button');
        register_setting('frak_settings', 'frak_show_reward');
        register_setting('frak_settings', 'frak_button_classname');
        register_setting('frak_settings', 'frak_floating_button_position');
        register_setting('frak_settings', 'frak_modal_language');
        register_setting('frak_settings', 'frak_modal_i18n', array(
            'sanitize_callback' => function($input) {
                if (is_array($input)) {
                    return json_encode(array_filter($input, function($value) {
                        return $value !== '';
                    }));
                }
                return $input;
            }
        ));
        register_setting('frak_settings', 'frak_webhook_secret');
    }

    public function enqueue_scripts($hook) {
        if ('settings_page_frak-settings' !== $hook) {
            return;
        }
        
        wp_enqueue_code_editor(array('type' => 'text/javascript'));
        wp_enqueue_script('frak-admin', plugin_dir_url(dirname(__FILE__)) . 'admin/js/admin.js', array('jquery'), '1.0', true);
        wp_enqueue_style('frak-admin', plugin_dir_url(dirname(__FILE__)) . 'admin/css/admin.css', array(), '1.0');
        
        // Get logo URL for autofill
        $logo_url = '';
        $site_icon_id = get_option('site_icon');
        if ($site_icon_id) {
            $logo_url = wp_get_attachment_image_url($site_icon_id, 'full');
        }
        if (!$logo_url) {
            $custom_logo_id = get_theme_mod('custom_logo');
            if ($custom_logo_id) {
                $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
            }
        }
        
        wp_localize_script('frak-admin', 'frak_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('frak_ajax_nonce'),
            'site_info' => array(
                'name' => get_bloginfo('name'),
                'logo_url' => $logo_url
            )
        ));
        
        wp_add_inline_style('wp-admin', '
            .frak-links {
                margin: 20px 0;
                padding: 15px;
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .frak-links a {
                margin-right: 20px;
                text-decoration: none;
            }
            .frak-links a:hover {
                text-decoration: underline;
            }
            .frak-webhook-status {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 600;
            }
            .frak-webhook-status.status-active {
                background: #d4edda;
                color: #155724;
            }
            .frak-webhook-status.status-inactive {
                background: #f8d7da;
                color: #721c24;
            }
            .frak-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin: 20px 0;
            }
            .frak-stat-box {
                background: #fff;
                border: 1px solid #ccd0d4;
                padding: 15px;
                text-align: center;
            }
            .frak-stat-box h3 {
                margin: 0;
                font-size: 24px;
            }
            .frak-stat-box p {
                margin: 5px 0 0;
                color: #666;
            }
            .frak-webhook-logs table {
                margin-top: 20px;
            }
            .frak-webhook-section {
                margin-top: 30px;
                padding: 20px;
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
        ');
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        $this->render_settings_page();
    }

    private function save_settings() {
        $app_name = sanitize_text_field($_POST['frak_app_name']);
        
        // Handle file upload if present
        $logo_url = esc_url_raw($_POST['frak_logo_url']);
        if (!empty($_FILES['frak_logo_file']) && $_FILES['frak_logo_file']['error'] == UPLOAD_ERR_OK) {
            $uploaded_logo = $this->handle_logo_upload($_FILES['frak_logo_file']);
            if ($uploaded_logo) {
                $logo_url = $uploaded_logo;
            }
        }
        
        $enable_tracking = isset($_POST['frak_enable_purchase_tracking']) ? 1 : 0;
        $enable_button = isset($_POST['frak_enable_floating_button']) ? 1 : 0;
        $show_reward = isset($_POST['frak_show_reward']) ? 1 : 0;
        $button_classname = isset($_POST['frak_button_classname']) ? sanitize_text_field($_POST['frak_button_classname']) : '';
        $floating_button_position = isset($_POST['frak_floating_button_position']) ? sanitize_text_field($_POST['frak_floating_button_position']) : 'right';
        $modal_language = isset($_POST['frak_modal_language']) ? sanitize_text_field($_POST['frak_modal_language']) : 'en';
        
        // Handle modal i18n
        $modal_i18n = isset($_POST['frak_modal_i18n']) ? $_POST['frak_modal_i18n'] : array();
        if (is_array($modal_i18n)) {
            // Sanitize each value
            foreach ($modal_i18n as $key => $value) {
                // For text areas, preserve line breaks but sanitize content
                if (in_array($key, ['sharing.text', 'sdk.wallet.login.text_sharing', 'sdk.wallet.login.text_referred'])) {
                    $modal_i18n[$key] = sanitize_textarea_field($value);
                } else {
                    $modal_i18n[$key] = sanitize_text_field($value);
                }
            }
            
            // Remove empty values
            $modal_i18n = array_filter($modal_i18n, function($value) {
                return $value !== '';
            });
            
            // Handle special case: if text_referred is set, also set it as text
            if (isset($modal_i18n['sdk.wallet.login.text_referred'])) {
                $modal_i18n['sdk.wallet.login.text'] = $modal_i18n['sdk.wallet.login.text_referred'];
            }
        }

        update_option('frak_app_name', $app_name);
        update_option('frak_logo_url', $logo_url);
        update_option('frak_enable_purchase_tracking', $enable_tracking);
        update_option('frak_enable_floating_button', $enable_button);
        update_option('frak_show_reward', $show_reward);
        update_option('frak_button_classname', $button_classname);
        update_option('frak_floating_button_position', $floating_button_position);
        update_option('frak_modal_language', $modal_language);
        update_option('frak_modal_i18n', json_encode($modal_i18n));
        
        // Clear any caches to ensure the new configuration is loaded
        $this->clear_caches();

    }

    /**
     * Clear caches from popular caching plugins
     */
    private function clear_caches() {
        // WP Rocket
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }
        
        // W3 Total Cache
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        
        // WP Super Cache
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        
        // WP Fastest Cache
        if (class_exists('WpFastestCache') && method_exists('WpFastestCache', 'deleteCache')) {
            $wpfc = new WpFastestCache();
            $wpfc->deleteCache(true);
        }
        
        // LiteSpeed Cache
        if (class_exists('LiteSpeed\Purge')) {
            LiteSpeed\Purge::purge_all();
        }
        
        // Autoptimize
        if (class_exists('autoptimizeCache') && method_exists('autoptimizeCache', 'clearall')) {
            autoptimizeCache::clearall();
        }
        
        // Clear WordPress object cache
        wp_cache_flush();
    }

    private function render_settings_page() {
        // Get default values from WordPress site info
        $default_app_name = get_bloginfo('name');
        $default_logo_url = $this->get_site_icon_url();
        
        $app_name = get_option('frak_app_name', $default_app_name);
        $logo_url = get_option('frak_logo_url', $default_logo_url);
        // Auto-enable WooCommerce tracking if WooCommerce is active and setting hasn't been configured yet
        $enable_tracking_option = get_option('frak_enable_purchase_tracking', null);
        if ($enable_tracking_option === null && class_exists('WooCommerce')) {
            $enable_tracking = 1;
            update_option('frak_enable_purchase_tracking', 1);
        } else {
            $enable_tracking = get_option('frak_enable_purchase_tracking', 0);
        }
        $enable_button = get_option('frak_enable_floating_button', 0);
        $show_reward = get_option('frak_show_reward', 0);
        $button_classname = get_option('frak_button_classname', '');
        $floating_button_position = get_option('frak_floating_button_position', 'right');
        $modal_language = get_option('frak_modal_language', 'default');
        $modal_i18n = json_decode(get_option('frak_modal_i18n', '{}'), true);
        
        include FRAK_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    private function get_site_icon_url() {
        $site_icon_id = get_option('site_icon');
        if ($site_icon_id) {
            $site_icon_url = wp_get_attachment_image_url($site_icon_id, 'full');
            if ($site_icon_url) {
                return $site_icon_url;
            }
        }
        
        // Fallback: try to get logo from theme customizer
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $custom_logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
            if ($custom_logo_url) {
                return $custom_logo_url;
            }
        }
        
        return '';
    }


    private function handle_logo_upload($file) {
        // Check file type
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/svg+xml');
        if (!in_array($file['type'], $allowed_types)) {
            return false;
        }
        
        // Check file size (2MB max)
        if ($file['size'] > 2 * 1024 * 1024) {
            return false;
        }
        
        // Use WordPress media upload handler
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($file, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            // Upload successful
            return $movefile['url'];
        }
        
        return false;
    }

    // AJAX Handlers
    public function ajax_generate_webhook_secret() {
        check_ajax_referer('frak_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $secret = wp_generate_password(32, false);
        update_option('frak_webhook_secret', $secret);
        
        wp_send_json_success(array(
            'secret' => $secret,
            'message' => __('Webhook secret generated successfully', 'frak')
        ));
    }
    
    public function ajax_test_webhook() {
        check_ajax_referer('frak_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $result = Frak_Webhook_Helper::test_webhook();
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => sprintf(__('Webhook test successful (%dms)', 'frak'), $result['execution_time']),
                'details' => $result
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Webhook test failed: ', 'frak') . $result['error'],
                'details' => $result
            ));
        }
    }
    
    public function ajax_clear_webhook_logs() {
        check_ajax_referer('frak_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        Frak_Webhook_Helper::clear_webhook_logs();
        
        wp_send_json_success(array(
            'message' => __('Webhook logs cleared successfully', 'frak')
        ));
    }
    
    public function ajax_check_webhook_status() {
        check_ajax_referer('frak_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $status = Frak_Webhook_Helper::get_webhook_status();
        
        wp_send_json_success(array(
            'status' => $status
        ));
    }
}