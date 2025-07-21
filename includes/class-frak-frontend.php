<?php

class Frak_Frontend {

    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 20);
        add_action('wp_footer', array($this, 'add_floating_button'));
    }

    public function enqueue_scripts() {
        // Check if we have a configuration
        $has_config = !empty(get_option('frak_custom_config', '')) || 
                      !empty(get_option('frak_app_name', ''));
        
        if ($has_config) {
            // Load configuration from endpoint with cache busting
            wp_enqueue_script(
                'frak-config',
                Frak_Config_Endpoint::get_config_url(),
                array(),
                null,
                false // Load in head for early initialization
            );
        }
        
        // Load the Frak SDK
        wp_enqueue_script(
            'frak-sdk',
            'https://cdn.jsdelivr.net/npm/@frak-labs/components@latest/cdn/components.js',
            $has_config ? array('frak-config') : array(),
            null,
            array('strategy' => 'defer')
        );
    }

    public function add_floating_button() {
        if (!get_option('frak_enable_floating_button', 0)) {
            return;
        }

        $show_reward = get_option('frak_show_reward', 0);
        $classname = get_option('frak_button_classname', '');
        
        $attributes = array();
        if ($show_reward) {
            $attributes[] = 'use-reward';
        }
        if (!empty($classname)) {
            $attributes[] = 'classname="' . esc_attr($classname) . '"';
        }
        
        echo '<frak-button-wallet ' . implode(' ', $attributes) . '></frak-button-wallet>';
    }
}