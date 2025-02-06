<?php
/*
Plugin Name: Frak
Description: Adds Frak configuration to your WordPress site
Version: 0.2
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

// Register settings
function frak_register_settings() {
    register_setting('frak_settings', 'frak_app_name');
    register_setting('frak_settings', 'frak_logo_url');
    register_setting('frak_settings', 'frak_custom_config', array(
        'sanitize_callback' => function($input) {
            return stripslashes($input);
        }
    ));
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

        // Save everything
        update_option('frak_app_name', $app_name);
        update_option('frak_logo_url', $logo_url);
        update_option('frak_custom_config', $custom_config);
    }
    
    $app_name = get_option('frak_app_name', 'Your App Name');
    $logo_url = get_option('frak_logo_url', '');
    $custom_config = get_option('frak_custom_config', '');
    
    if (empty($custom_config)) {
        $custom_config = <<<JS
window.FrakSetup = {
    config: {
        metadata: {
            name: "{$app_name}",
        },
        domain: window.location.host,
    },
    modalConfig: {
        login: {
            allowSso: true,
            ssoMetadata: {
                logoUrl: "{$logo_url}",
                homepageLink: window.location.origin,
            },
        },
        metadata: {
            header: {
                icon: "{$logo_url}",
            },
            isDismissible: true,
            lang: "en",
        },
    },
    modalShareConfig: {
        link: window.location.href,
    }
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
            
            // Update both logoUrl instances
            currentConfig = currentConfig.replace(
                /(ssoMetadata:\s*{\s*logoUrl:\s*")[^"]*(")/,
                '$1' + logoUrl + '$2'
            );
            
            // Update icon
            currentConfig = currentConfig.replace(
                /(header:\s*{\s*icon:\s*")[^"]*(")/,
                '$1' + logoUrl + '$2'
            );
            
            editor.codemirror.setValue(currentConfig);
        }

        $('#frak_app_name, #frak_logo_url').on('input', updateConfig);
    });
    </script>
    <?php
}

// Add the Frak script to the front end
function frak_add_to_frontend() {
    if (is_admin()) {
        return;
    }
    
    $custom_config = get_option('frak_custom_config');
    if (!empty($custom_config)) {
        // Remove whitespace from the config
        $custom_config = preg_replace('/\s+/', ' ', $custom_config);
        $custom_config = preg_replace('/\s*([{}:,=+\-*\/()])\s*/', '$1', $custom_config);

        // Add it to the header
        echo "<script>\n";
        echo stripslashes($custom_config);
        echo "\n</script>\n";
    }
    ?>
    <script src="https://cdn.jsdelivr.net/npm/@frak-labs/components@latest/cdn/components.js" defer="defer"></script>
    <?php
}
add_action('wp_head', 'frak_add_to_frontend');