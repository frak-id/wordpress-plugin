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
                    <button type="button" id="autofill_app_name" class="button button-secondary" style="margin-left: 10px;">
                        Use Site Name
                    </button>
                    <p class="description">Current site name: <strong><?php echo esc_html(get_bloginfo('name')); ?></strong></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="frak_logo_url">Logo URL</label>
                </th>
                <td>
                    <input type="url" id="frak_logo_url" name="frak_logo_url" 
                           value="<?php echo esc_url($logo_url); ?>" class="regular-text">
                    <button type="button" id="autofill_logo_url" class="button button-secondary" style="margin-left: 10px;">
                        Use Site Icon
                    </button>
                    <?php
                    $site_icon_id = get_option('site_icon');
                    $custom_logo_id = get_theme_mod('custom_logo');
                    if ($site_icon_id || $custom_logo_id): ?>
                        <p class="description">
                            <?php if ($site_icon_id): ?>
                                Site icon available
                            <?php elseif ($custom_logo_id): ?>
                                Custom logo available
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <p class="description">No site icon or custom logo found. <a href="<?php echo admin_url('customize.php'); ?>" target="_blank">Set one in Customizer</a></p>
                    <?php endif; ?>
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
                    <p class="description">The button position is controlled by the <code>modalWalletConfig.metadata.position</code> setting in the advanced configuration below. It can be set to either "left" or "right".</p>
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

    // WordPress site information for autofill
    var wpSiteInfo = {
        name: <?php echo json_encode(get_bloginfo('name')); ?>,
        logoUrl: <?php 
            $site_icon_id = get_option('site_icon');
            $logo_url = '';
            if ($site_icon_id) {
                $logo_url = wp_get_attachment_image_url($site_icon_id, 'full');
            }
            if (!$logo_url) {
                $custom_logo_id = get_theme_mod('custom_logo');
                if ($custom_logo_id) {
                    $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
                }
            }
            echo json_encode($logo_url ?: '');
        ?>
    };

    function updateConfig() {
        var appName = $('#frak_app_name').val();
        var logoUrl = $('#frak_logo_url').val();
        var currentConfig = editor.codemirror.getValue();
        
        currentConfig = currentConfig.replace(
            /(metadata:\s*{\s*name:\s*")[^"]*(")/,
            '$1' + appName + '$2'
        );
        
        currentConfig = currentConfig.replace(
            /(logoUrl:\s*")[^"]*(")/,
            '$1' + logoUrl + '$2'
        );
        
        editor.codemirror.setValue(currentConfig);
    }

    function toggleFloatingButtonSettings() {
        var enabled = $('#frak_enable_floating_button').is(':checked');
        $('#frak_show_reward, #frak_button_classname').prop('disabled', !enabled);
    }

    // Autofill functionality
    $('#autofill_app_name').on('click', function() {
        if (wpSiteInfo.name) {
            $('#frak_app_name').val(wpSiteInfo.name).trigger('input');
        }
    });

    $('#autofill_logo_url').on('click', function() {
        if (wpSiteInfo.logoUrl) {
            $('#frak_logo_url').val(wpSiteInfo.logoUrl).trigger('input');
        }
    });

    $('#frak_app_name, #frak_logo_url').on('input', updateConfig);
    $('#frak_enable_floating_button').on('change', toggleFloatingButtonSettings);
    toggleFloatingButtonSettings();
});
</script>