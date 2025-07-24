<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="frak-links">
        <a href="https://docs.frak.id/components/frak-setup" target="_blank">📚 Documentation</a>
        <a href="https://business.frak.id/" target="_blank">🎯 Dashboard</a>
    </div>

    <form method="post" action="" enctype="multipart/form-data">
        <!-- Generic Website Info Section -->
        <div class="frak-section">
            <h2>Website Information</h2>
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
                        <label for="frak_logo_file">Upload Logo</label>
                    </th>
                    <td>
                        <input type="file" id="frak_logo_file" name="frak_logo_file" accept="image/*">
                        <p class="description">Upload a logo image (JPG, PNG, GIF, SVG - Max 2MB)</p>
                        <?php if ($logo_url): ?>
                            <div class="frak-logo-preview" style="margin-top: 10px;">
                                <img src="<?php echo esc_url($logo_url); ?>" alt="Current logo" style="max-height: 80px; max-width: 200px;">
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Customisations Section -->
        <div class="frak-section">
            <h2>Customisations</h2>
            
            <!-- Floating Button Subsection -->
            <div class="frak-subsection">
                <h3>Floating Button</h3>
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
                            <label for="frak_floating_button_position">Button Position</label>
                        </th>
                        <td>
                            <select id="frak_floating_button_position" name="frak_floating_button_position"
                                    <?php echo $enable_button ? '' : 'disabled'; ?>>
                                <option value="right" <?php selected($floating_button_position, 'right'); ?>>Right</option>
                                <option value="left" <?php selected($floating_button_position, 'left'); ?>>Left</option>
                            </select>
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
                </table>
            </div>
            
            <!-- Modal Customization Subsection -->
            <div class="frak-subsection">
                <h3>Modal Customization</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="frak_modal_language">Modal Language</label>
                        </th>
                        <td>
                            <select id="frak_modal_language" name="frak_modal_language">
                                <option value="default" <?php selected($modal_language, 'default'); ?>>Default</option>
                                <option value="en" <?php selected($modal_language, 'en'); ?>>English</option>
                                <option value="fr" <?php selected($modal_language, 'fr'); ?>>Français</option>
                            </select>
                            <p class="description">Default language for the Frak modal</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Custom Translations</th>
                        <td>
                            <p class="description" style="margin-bottom: 10px;">Override default text in the modal (leave empty to use defaults)</p>
                            
                            <!-- Sharing Modal Customization -->
                            <div class="frak-i18n-group">
                                <h4 style="margin: 15px 0 10px 0;">Sharing Modal</h4>
                                <table class="frak-i18n-table">
                                    <tr>
                                        <td style="vertical-align: top; padding-bottom: 15px;">
                                            <label for="frak_modal_i18n_sharing_title">Sharing Modal Title:</label>
                                            <p class="description" style="margin-top: 5px;">The title that appears when users share your content on social media or messaging apps</p>
                                        </td>
                                        <td style="padding-bottom: 15px;">
                                            <input type="text" id="frak_modal_i18n_sharing_title" 
                                                   name="frak_modal_i18n[sharing.title]" 
                                                   value="<?php echo isset($modal_i18n['sharing.title']) ? esc_attr($modal_i18n['sharing.title']) : ''; ?>" 
                                                   class="large-text"
                                                   placeholder="Example: 'Share this amazing product with your friends!'">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: top; padding-bottom: 15px;">
                                            <label for="frak_modal_i18n_sharing_text">Sharing Message Text:</label>
                                            <p class="description" style="margin-top: 5px;">The default message that will be shared along with your product link</p>
                                        </td>
                                        <td style="padding-bottom: 15px;">
                                            <textarea id="frak_modal_i18n_sharing_text" 
                                                      name="frak_modal_i18n[sharing.text]" 
                                                      class="large-text" 
                                                      rows="3"
                                                      placeholder="Example: 'Check out this amazing product I found!'"><?php echo isset($modal_i18n['sharing.text']) ? esc_textarea($modal_i18n['sharing.text']) : ''; ?></textarea>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Wallet Login Modal Customization -->
                            <div class="frak-i18n-group">
                                <h4 style="margin: 15px 0 10px 0;">Wallet Login Modal</h4>
                                <table class="frak-i18n-table">
                                    <tr>
                                        <td style="vertical-align: top; padding-bottom: 15px;">
                                            <label for="frak_modal_i18n_login_primary_action">Wallet Login Button Text:</label>
                                            <p class="description" style="margin-top: 5px;">The text displayed on the main action button in the wallet login modal</p>
                                        </td>
                                        <td style="padding-bottom: 15px;">
                                            <input type="text" id="frak_modal_i18n_login_primary_action" 
                                                   name="frak_modal_i18n[sdk.wallet.login.primaryAction]" 
                                                   value="<?php echo isset($modal_i18n['sdk.wallet.login.primaryAction']) ? esc_attr($modal_i18n['sdk.wallet.login.primaryAction']) : ''; ?>" 
                                                   class="large-text"
                                                   placeholder="Example: 'Create your wallet in 2 seconds!'">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: top; padding-bottom: 15px;">
                                            <label for="frak_modal_i18n_login_text_sharing">Login Text for Sharing:</label>
                                            <p class="description" style="margin-top: 5px;">Message shown to users when they need to login to share content and earn rewards.<br>
                                            You can use <strong>**bold text**</strong>, <em>*italic text*</em>, and <code>{{ estimatedReward }}</code> to show the reward amount.</p>
                                        </td>
                                        <td style="padding-bottom: 15px;">
                                            <textarea id="frak_modal_i18n_login_text_sharing" 
                                                      name="frak_modal_i18n[sdk.wallet.login.text_sharing]" 
                                                      class="large-text" 
                                                      rows="3"
                                                      placeholder="Example: 'Share, Refer, Earn up to **{{ estimatedReward }}** per successful referral'"><?php echo isset($modal_i18n['sdk.wallet.login.text_sharing']) ? esc_textarea($modal_i18n['sdk.wallet.login.text_sharing']) : ''; ?></textarea>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: top; padding-bottom: 15px;">
                                            <label for="frak_modal_i18n_login_text_referred">Welcome Text for Referred Users:</label>
                                            <p class="description" style="margin-top: 5px;">Message shown to users who clicked on a shared link.<br>
                                            You can use <strong>**bold text**</strong>, <em>*italic text*</em>, and <code>{{ estimatedReward }}</code> to show the reward amount.</p>
                                        </td>
                                        <td style="padding-bottom: 15px;">
                                            <textarea id="frak_modal_i18n_login_text_referred" 
                                                      name="frak_modal_i18n[sdk.wallet.login.text_referred]" 
                                                      class="large-text" 
                                                      rows="3"
                                                      placeholder="Example: 'Welcome! Receive **{{ estimatedReward }}** when you make a purchase'"><?php echo isset($modal_i18n['sdk.wallet.login.text_referred']) ? esc_textarea($modal_i18n['sdk.wallet.login.text_referred']) : ''; ?></textarea>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <!-- Purchase Tracking Section -->
        <div class="frak-section">
            <h2>Purchase Tracking</h2>
            
            <?php
            // Check if WooCommerce is active
            $woocommerce_active = class_exists('WooCommerce');
            ?>
            
            <!-- WooCommerce Tracking -->
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="frak_enable_purchase_tracking">WooCommerce Integration</label>
                    </th>
                    <td>
                        <?php if ($woocommerce_active): ?>
                            <label>
                                <input type="checkbox" id="frak_enable_purchase_tracking" 
                                       name="frak_enable_purchase_tracking" value="1" 
                                       <?php checked($enable_tracking, 1); ?>>
                                Enable WooCommerce orders tracking
                            </label>
                            <p class="description" style="color: green;">✓ WooCommerce plugin detected</p>
                        <?php else: ?>
                            <label style="color: #999;">
                                <input type="checkbox" disabled>
                                Enable WooCommerce orders tracking
                            </label>
                            <p class="description" style="color: #666;">WooCommerce plugin not detected. Install and activate WooCommerce to enable this feature.</p>
                        <?php endif; ?>
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
            
            <!-- Webhook Configuration -->
            <div class="frak-subsection">
                <h3>Webhook Configuration</h3>
                
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
                
                <h4>Webhook Information</h4>
                <p><strong>Domain:</strong> <?php echo esc_html(parse_url(home_url(), PHP_URL_HOST)); ?></p>
                <p><strong>Product ID:</strong> <code><?php echo esc_html($product_id); ?></code></p>
                <p><strong>Webhook URL:</strong> <code><?php echo esc_html($webhook_url); ?></code></p>
                
                <h4>Webhook Statistics</h4>
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
                
                <h4>Recent Webhook Attempts</h4>
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
        </div>
        
        <?php submit_button('Save Settings'); ?>
    </form>
</div>