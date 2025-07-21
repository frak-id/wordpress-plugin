<?php

class Frak_Plugin {

    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    private function define_constants() {
        define('FRAK_PLUGIN_DIR', plugin_dir_path(dirname(__FILE__)));
        define('FRAK_PLUGIN_URL', plugin_dir_url(dirname(__FILE__)));
        define('FRAK_PLUGIN_FILE', dirname(dirname(__FILE__)) . '/frak-integration.php');
    }

    private function includes() {
        require_once FRAK_PLUGIN_DIR . 'includes/class-frak-webhook-helper.php';
        require_once FRAK_PLUGIN_DIR . 'admin/class-frak-admin.php';
        require_once FRAK_PLUGIN_DIR . 'includes/class-frak-frontend.php';
        require_once FRAK_PLUGIN_DIR . 'includes/class-frak-config-endpoint.php';
        
        if (class_exists('WooCommerce')) {
            require_once FRAK_PLUGIN_DIR . 'includes/class-frak-woocommerce.php';
        }
    }

    private function init_hooks() {
        register_activation_hook(FRAK_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(FRAK_PLUGIN_FILE, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
    }

    public function init() {
        // Initialize config endpoint for all contexts
        Frak_Config_Endpoint::instance();
        
        if (is_admin()) {
            Frak_Admin::instance();
        } else {
            Frak_Frontend::instance();
        }
        
        if (class_exists('WooCommerce') && get_option('frak_enable_purchase_tracking', 0)) {
            Frak_WooCommerce::instance();
        }
    }

    public function activate() {
        // Flush rewrite rules for config endpoint
        Frak_Config_Endpoint::instance()->flush_rewrite_rules();
    }

    public function deactivate() {
        // Cleanup if needed
    }
}