<?php
/*
Plugin Name: Frak
Description: Adds Frak configuration to your WordPress site
Version: 1.0
Author: Frak-Labs
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load the main plugin class
require_once plugin_dir_path(__FILE__) . 'includes/class-frak-plugin.php';

// Initialize the plugin
function frak_init() {
    Frak_Plugin::instance();
}
add_action('plugins_loaded', 'frak_init');