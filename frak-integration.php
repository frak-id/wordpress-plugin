<?php
/*
Plugin Name: Frak
Description: Adds Frak configuration to your WordPress site
Version: 0.5
Author: Frak-Labs
*/


// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Add menu item under Settings
function frak_add_admin_menu() {
    add_options_page(
        'Frak Settings',
        'Frak',
        'manage_options',
        'frak-settings',
        'frak_settings_page'
    );
}
add_action('admin_menu', 'frak_add_admin_menu');

// Function to get the static config file path
function frak_get_config_file_path() {
    $upload_dir = wp_upload_dir();
    $frak_dir = $upload_dir['basedir'] . '/frak';
    if (!file_exists($frak_dir)) {
        wp_mkdir_p($frak_dir);
    }
    return $frak_dir . '/config.js';
}

// Function to get the config file URL
function frak_get_config_file_url() {
    $upload_dir = wp_upload_dir();
    return $upload_dir['baseurl'] . '/frak/config.js';
}

// Function to save the static config file
function frak_save_config_file($config_content) {
    $file_path = frak_get_config_file_path();
    $result = file_put_contents($file_path, $config_content);
    
    if ($result !== false) {
        // Update the last modification timestamp
        update_option('frak_config_last_modified', time());
        return true;
    }
    return false;
}

// Add rewrite rules for the config file
function frak_add_rewrite_rules() {
    add_rewrite_rule(
        'frak/config\.js$',
        'index.php?frak_config=1',
        'top'
    );
}
add_action('init', 'frak_add_rewrite_rules');

// Handle the config file request
function frak_handle_config_request($wp) {
    if (isset($wp->query_vars['frak_config'])) {
        $file_path = frak_get_config_file_path();
        
        if (file_exists($file_path)) {
            $last_modified = get_option('frak_config_last_modified', 0);
            
            // Set proper headers for caching
            header('Content-Type: application/javascript');
            header('Cache-Control: public, max-age=31536000'); // 1 year
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $last_modified) . ' GMT');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            
            // Check if the browser has a cached version
            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
                $if_modified_since = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
                if ($if_modified_since >= $last_modified) {
                    header('HTTP/1.1 304 Not Modified');
                    exit;
                }
            }
            
            readfile($file_path);
            exit;
        }
    }
}
add_action('parse_request', 'frak_handle_config_request');

// Register settings
function frak_register_settings() {
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
add_action('admin_init', 'frak_register_settings');

// Add CSS and JS for the admin page
function frak_admin_enqueue_scripts($hook) {
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
add_action('admin_enqueue_scripts', 'frak_admin_enqueue_scripts');

// Create the settings page
function frak_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_POST['submit'])) {
        $app_name = sanitize_text_field($_POST['frak_app_name']);
        $logo_url = esc_url_raw($_POST['frak_logo_url']);
        $custom_config = stripslashes($_POST['frak_custom_config']);
        $enable_tracking = isset($_POST['frak_enable_purchase_tracking']) ? 1 : 0;
        $enable_button = isset($_POST['frak_enable_floating_button']) ? 1 : 0;
        $show_reward = isset($_POST['frak_show_reward']) ? 1 : 0;
        $button_classname = sanitize_text_field($_POST['frak_button_classname']);

        // Save everything
        update_option('frak_app_name', $app_name);
        update_option('frak_logo_url', $logo_url);
        update_option('frak_custom_config', $custom_config);
        update_option('frak_enable_purchase_tracking', $enable_tracking);
        update_option('frak_enable_floating_button', $enable_button);
        update_option('frak_show_reward', $show_reward);
        update_option('frak_button_classname', $button_classname);

        // Save the config to a static file
        frak_save_config_file($custom_config);
    }
    
    $app_name = get_option('frak_app_name', 'Your App Name');
    $logo_url = get_option('frak_logo_url', '');
    $custom_config = get_option('frak_custom_config', '');
    $enable_tracking = get_option('frak_enable_purchase_tracking', 0);
    $enable_button = get_option('frak_enable_floating_button', 0);
    $show_reward = get_option('frak_show_reward', 0);
    $button_classname = get_option('frak_button_classname', '');
    
    if (empty($custom_config)) {
        $custom_config = <<<JS
window.FrakSetup = {
    // Overall config of the Frak SDK
    config: {
        metadata: {
            name: "{$app_name}",
            lang: "en",
            currency: "eur",
            logoUrl: "{$logo_url}",
            homepageLink: window.location.origin,
        },
        customizations: {
            // Customize the i18n messages
            // i18n: {
            // },
        },
        domain: window.location.host,
    },
    // Config for the generic modal view
    modalConfig: {
        login: {
            allowSso: true,
            ssoMetadata: {},
        },
        metadata: {
            isDismissible: true,
        },
    },
    // Config for the sharing modal step
    modalShareConfig: {
        link: window.location.href,
    },  
    // Config for the embedded modal view
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
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="frak-links">
            <a href="https://docs.frak.id/components/frak-setup" target="_blank">📚 Documentation</a>
            <a href="https://business.frak.id/" target="_blank">🎯 Dashboard</a>
        </div>

        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="frak_app_name">App Name</label>
                    </th>
                    <td>
                        <input type="text" id="frak_app_name" name="frak_app_name" 
                               value="<?php echo esc_attr($app_name); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="frak_logo_url">Logo URL</label>
                    </th>
                    <td>
                        <input type="url" id="frak_logo_url" name="frak_logo_url" 
                               value="<?php echo esc_url($logo_url); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="frak_enable_purchase_tracking">WooCommerce Purchase Tracking</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="frak_enable_purchase_tracking" 
                                   name="frak_enable_purchase_tracking" value="1" 
                                   <?php checked($enable_tracking, 1); ?>>
                            Enable WooCommerce orders tracking
                        </label>
                    </td>
                </tr>
            </table>
            
            <h2>Floating Button Configuration</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="frak_enable_floating_button">Enable Floating Button</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="frak_enable_floating_button" 
                                   name="frak_enable_floating_button" value="1" 
                                   <?php checked($enable_button, 1); ?>>
                            Show floating button on all pages
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="frak_show_reward">Show Potential Reward</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="frak_show_reward" 
                                   name="frak_show_reward" value="1" 
                                   <?php checked($show_reward, 1); ?>
                                   <?php echo $enable_button ? '' : 'disabled'; ?>>
                            Display potential reward on the button
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="frak_button_classname">Custom Class Name</label>
                    </th>
                    <td>
                        <input type="text" id="frak_button_classname" 
                               name="frak_button_classname" 
                               value="<?php echo esc_attr($button_classname); ?>" 
                               class="regular-text"
                               <?php echo $enable_button ? '' : 'disabled'; ?>>
                        <p class="description">Add custom CSS classes to the button</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Button Position</th>
                    <td>
                        <p class="description">The button position is controlled by the <code>modalWalletConfig.metadata.position</code> setting in the advanced configuration above. It can be set to either "left" or "right".</p>
                    </td>
                </tr>
            </table>
            
            <h2>Advanced Configuration</h2>
            <p>Customize your Frak configuration below:</p>
            <textarea id="frak_custom_config" name="frak_custom_config" 
                      style="width: 100%; height: 400px; font-family: monospace;"
            ><?php echo $custom_config; ?></textarea>
            
            <?php submit_button('Save Settings'); ?>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($) {
        var editor = wp.codeEditor.initialize($('#frak_custom_config'), {
            codemirror: {
                mode: 'javascript',
                lineNumbers: true,
                matchBrackets: true,
                autoCloseBrackets: true,
                extraKeys: {"Ctrl-Space": "autocomplete"},
                theme: 'default'
            }
        });

        function updateConfig() {
            var appName = $('#frak_app_name').val();
            var logoUrl = $('#frak_logo_url').val();
            var currentConfig = editor.codemirror.getValue();
            
            // Update name
            currentConfig = currentConfig.replace(
                /(metadata:\s*{\s*name:\s*")[^"]*(")/,
                '$1' + appName + '$2'
            );
            
            // Update the logoUrl
            currentConfig = currentConfig.replace(
                /(logoUrl:\s*")[^"]*(")/,
                '$1' + logoUrl + '$2'
            );
            
            editor.codemirror.setValue(currentConfig);
        }

        // Handle floating button settings
        function toggleFloatingButtonSettings() {
            var enabled = $('#frak_enable_floating_button').is(':checked');
            $('#frak_show_reward, #frak_button_classname').prop('disabled', !enabled);
        }

        $('#frak_app_name, #frak_logo_url').on('input', updateConfig);
        $('#frak_enable_floating_button').on('change', toggleFloatingButtonSettings);
        toggleFloatingButtonSettings(); // Initial state
    });
    </script>
    <?php
}

// Add the Frak script to the front end
function frak_add_to_frontend() {
    if (is_admin()) {
        return;
    }
    
    // Add the static config file with cache busting
    $config_script = frak_get_config_file_url();
    if (!empty($config_script)) {
        wp_enqueue_script(
            'frak-config',
            $config_script,
            array(),
            get_option('frak_config_last_modified', 0),
            array(
                'strategy' => 'defer'
            )
        );
    }
    
    // The version of the components script correspond to the current day of the year, to ensure it's not cached more than 24hr
    $components_script_version = date('zo');
    // Add the Frak components script
    wp_enqueue_script(
        'frak-components',
        'https://cdn.jsdelivr.net/npm/@frak-labs/components@latest/cdn/components.js',
        array(),
        $components_script_version,
        array(
            'strategy' => 'defer'
        )
    );
}
add_action('wp_enqueue_scripts', 'frak_add_to_frontend', 20);

// Add floating button to the body
function frak_add_floating_button() {
    if (is_admin()) {
        return '';
    }

    if (!get_option('frak_enable_floating_button', 0)) {
        return '';
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
    
    return '<frak-button-wallet ' . implode(' ', $attributes) . '></frak-button-wallet>';
}

// Add floating button to footer
function frak_output_floating_button() {
    echo frak_add_floating_button();
}
add_action('wp_footer', 'frak_output_floating_button');

// Fallback function to inject scripts if wp_head/wp_footer are not available
function frak_fallback_injection() {
    // Only inject if we haven't already and if we're not in admin
    if (is_admin()) {
        return;
    }

    // Check if scripts are already in the output buffer to prevent duplicates
    $has_frak_script = wp_script_is('frak-config', 'done') && wp_script_is('frak-components', 'done');
    if ($has_frak_script) {
        return;
    }

    $to_add = '';

    // If we don't have the scripts, add them
    if (!$has_frak_script) {
        $config_script = frak_get_config_file_url();

        // Add config script if available
        if (!empty($config_script)) {
            $last_modified = get_option('frak_config_last_modified', 0);
            // Add a v=lastModified to the script tag to force cache busting
            $final_script_url = $config_script . '?ver=' . $last_modified;
            $to_add .= sprintf(
                '<script src="%s" defer></script>',
                esc_url($final_script_url)
            );
        }

        // Add Frak components script
        $components_script_version = date('zo');
        $final_components_script_url = 'https://cdn.jsdelivr.net/npm/@frak-labs/components@latest/cdn/components.js?ver=' . $components_script_version;
        $to_add .= sprintf(
            '<script src="%s" defer></script>',
            esc_url($final_components_script_url)
        );
    }

    // todo: Should find a way to add the button (but check with `ob_get_contents` are failing)

    // Add scripts to the output buffer
    echo $to_add;
}
add_action('shutdown', 'frak_fallback_injection');

// Add WooCommerce purchase tracking
function frak_add_purchase_tracking($order_id) {
    // Early exit if tracking is disabled
    if (!get_option('frak_enable_purchase_tracking', 0)) {
        return;
    }

    if (!$order_id) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    $customer_id = $order->get_user_id();
    $order_key = $order->get_order_key();
    $transaction_id = $order->get_transaction_id();

    echo "<script>
            const interactionToken = sessionStorage.getItem('frak-wallet-interaction-token');

            const payload = {
                customerId: " . json_encode($customer_id) . ",
                orderId: " . json_encode($order_id) . ",
                token: " . json_encode($order_key) . "
            };

            fetch('https://backend.frak.id/interactions/listenForPurchase', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'x-wallet-sdk-auth': interactionToken
                },
                body: JSON.stringify(payload)
            });
    </script>";
}

// Hook into WooCommerce
add_action('woocommerce_thankyou', 'frak_add_purchase_tracking', 10, 1);