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
        register_setting('frak_settings', 'frak_custom_config', array(
            'sanitize_callback' => function($input) {
                return stripslashes($input);
            }
        ));
        register_setting('frak_settings', 'frak_enable_purchase_tracking');
        register_setting('frak_settings', 'frak_enable_floating_button');
        register_setting('frak_settings', 'frak_show_reward');
        register_setting('frak_settings', 'frak_button_classname');
    }

    public function enqueue_scripts($hook) {
        if ('settings_page_frak-settings' !== $hook) {
            return;
        }
        
        wp_enqueue_code_editor(array('type' => 'text/javascript'));
        
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
        $logo_url = esc_url_raw($_POST['frak_logo_url']);
        $custom_config = stripslashes($_POST['frak_custom_config']);
        $enable_tracking = isset($_POST['frak_enable_purchase_tracking']) ? 1 : 0;
        $enable_button = isset($_POST['frak_enable_floating_button']) ? 1 : 0;
        $show_reward = isset($_POST['frak_show_reward']) ? 1 : 0;
        $button_classname = isset($_POST['frak_button_classname']) ? sanitize_text_field($_POST['frak_button_classname']) : '';

        update_option('frak_app_name', $app_name);
        update_option('frak_logo_url', $logo_url);
        update_option('frak_custom_config', $custom_config);
        update_option('frak_enable_purchase_tracking', $enable_tracking);
        update_option('frak_enable_floating_button', $enable_button);
        update_option('frak_show_reward', $show_reward);
        update_option('frak_button_classname', $button_classname);

    }

    private function render_settings_page() {
        // Get default values from WordPress site info
        $default_app_name = get_bloginfo('name');
        $default_logo_url = $this->get_site_icon_url();
        
        $app_name = get_option('frak_app_name', $default_app_name);
        $logo_url = get_option('frak_logo_url', $default_logo_url);
        $custom_config = get_option('frak_custom_config', '');
        $enable_tracking = get_option('frak_enable_purchase_tracking', 0);
        $enable_button = get_option('frak_enable_floating_button', 0);
        $show_reward = get_option('frak_show_reward', 0);
        $button_classname = get_option('frak_button_classname', '');
        
        if (empty($custom_config)) {
            $custom_config = $this->get_default_config($app_name, $logo_url);
        }
        
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

    private function get_default_config($app_name, $logo_url) {
        return <<<JS
window.FrakSetup = {
    config: {
        metadata: {
            name: "{$app_name}",
            lang: "en",
            currency: "eur",
            logoUrl: "{$logo_url}",
            homepageLink: window.location.origin,
        },
        customizations: {},
        domain: window.location.host,
    },
    modalConfig: {
        login: {
            allowSso: true,
            ssoMetadata: {},
        },
        metadata: {
            isDismissible: true,
        },
    },
    modalShareConfig: {
        link: window.location.href,
    },  
    modalWalletConfig: {
        metadata: {
            position: "left",
        },
        loggedIn: {
            action: {
                key: "sharing",
                options: {
                    link: window.location.href,
                },
            },
        },
    },
};
JS;
    }
}