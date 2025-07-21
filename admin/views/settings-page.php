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
        
        <?php
        // Get webhook data
        $webhook_secret = get_option('frak_webhook_secret', '');
        $product_id = Frak_Webhook_Helper::get_product_id();
        $webhook_url = Frak_Webhook_Helper::get_webhook_url();
        $webhook_status = Frak_Webhook_Helper::get_webhook_status();
        $webhook_logs = Frak_Webhook_Helper::get_webhook_logs(10);
        $webhook_stats = Frak_Webhook_Helper::get_webhook_stats();
        ?>
        
        <div class="frak-webhook-section">
            <h2>Webhook Management</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="frak_webhook_secret">Webhook Secret</label>
                    </th>
                    <td>
                        <input type="text" id="frak_webhook_secret" name="frak_webhook_secret" 
                               value="<?php echo esc_attr($webhook_secret); ?>" class="regular-text" readonly>
                        <button type="button" id="generate-webhook-secret" class="button">
                            <?php echo $webhook_secret ? 'Regenerate' : 'Generate'; ?>
                        </button>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Webhook Status</th>
                    <td>
                        <span class="frak-webhook-status <?php echo $webhook_status ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $webhook_status ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Manage Webhook</th>
                    <td>
                        <button type="button" id="open-webhook-popup" class="button" 
                                data-product-id="<?php echo esc_attr($product_id); ?>">
                            Create Webhook
                        </button>
                        <a href="https://business.frak.id/product/<?php echo esc_attr($product_id); ?>" 
                           target="_blank" class="button">
                            Manage on Frak
                        </a>
                        <button type="button" id="test-webhook" class="button">
                            Test Webhook
                        </button>
                    </td>
                </tr>
            </table>
            
            <h3>Webhook Information</h3>
            <p><strong>Domain:</strong> <?php echo esc_html(parse_url(home_url(), PHP_URL_HOST)); ?></p>
            <p><strong>Product ID:</strong> <code><?php echo esc_html($product_id); ?></code></p>
            <p><strong>Webhook URL:</strong> <code><?php echo esc_html($webhook_url); ?></code></p>
            
            <h3>Webhook Statistics</h3>
            <div class="frak-stats-grid">
                <div class="frak-stat-box">
                    <h3><?php echo esc_html($webhook_stats['total_attempts']); ?></h3>
                    <p>Total Attempts</p>
                </div>
                <div class="frak-stat-box">
                    <h3><?php echo esc_html($webhook_stats['successful']); ?></h3>
                    <p>Successful</p>
                </div>
                <div class="frak-stat-box">
                    <h3><?php echo esc_html($webhook_stats['failed']); ?></h3>
                    <p>Failed</p>
                </div>
                <div class="frak-stat-box">
                    <h3><?php echo esc_html($webhook_stats['success_rate']); ?>%</h3>
                    <p>Success Rate</p>
                </div>
                <div class="frak-stat-box">
                    <h3><?php echo esc_html($webhook_stats['avg_response_time']); ?>ms</h3>
                    <p>Avg Response Time</p>
                </div>
            </div>
            
            <h3>Recent Webhook Attempts</h3>
            <?php if (!empty($webhook_logs)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Order ID</th>
                            <th>Status</th>
                            <th>HTTP Code</th>
                            <th>Response Time</th>
                            <th>Result</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($webhook_logs as $log) : ?>
                            <tr>
                                <td><?php echo esc_html($log['timestamp']); ?></td>
                                <td>
                                    <?php if ($log['order_id'] > 0) : ?>
                                        <a href="<?php echo admin_url('post.php?post=' . $log['order_id'] . '&action=edit'); ?>" target="_blank">
                                            #<?php echo esc_html($log['order_id']); ?>
                                        </a>
                                    <?php else : ?>
                                        Test
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($log['status']); ?></td>
                                <td>
                                    <span class="<?php echo $log['http_code'] >= 200 && $log['http_code'] < 300 ? 'text-success' : 'text-error'; ?>">
                                        <?php echo esc_html($log['http_code']); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($log['execution_time']); ?>ms</td>
                                <td>
                                    <?php if ($log['success']) : ?>
                                        <span style="color: green;">✓ Success</span>
                                    <?php else : ?>
                                        <span style="color: red;">✗ Failed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['error']) : ?>
                                        <span title="<?php echo esc_attr($log['error']); ?>">
                                            <?php echo esc_html(substr($log['error'], 0, 50) . (strlen($log['error']) > 50 ? '...' : '')); ?>
                                        </span>
                                    <?php else : ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p>
                    <button type="button" id="clear-webhook-logs" class="button">Clear Logs</button>
                </p>
            <?php else : ?>
                <p>No webhook attempts recorded yet. Webhook logs will appear here after orders are placed or tests are run.</p>
            <?php endif; ?>
        </div>
        
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
