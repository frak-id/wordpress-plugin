jQuery(document).ready(function($) {
    var editor;
    
    // Initialize code editor if available
    if (typeof wp.codeEditor !== 'undefined' && $('#frak_custom_config').length) {
        var editorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
        editorSettings.codemirror = _.extend({}, editorSettings.codemirror, {
            indentUnit: 4,
            tabSize: 4,
            mode: 'javascript',
            lineNumbers: true,
            matchBrackets: true,
            autoCloseBrackets: true,
            extraKeys: {"Ctrl-Space": "autocomplete"},
            theme: 'default'
        });
        editor = wp.codeEditor.initialize($('#frak_custom_config'), editorSettings);
    }
    
    // Update config function
    function updateConfig() {
        if (!editor) return;
        
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
    
    // Toggle floating button settings
    function toggleFloatingButtonSettings() {
        var enabled = $('#frak_enable_floating_button').is(':checked');
        $('#frak_show_reward, #frak_button_classname').prop('disabled', !enabled);
    }
    
    // Bind events
    $('#frak_app_name, #frak_logo_url').on('input', updateConfig);
    $('#frak_enable_floating_button').on('change', toggleFloatingButtonSettings);
    toggleFloatingButtonSettings();

    // Generate webhook secret
    $('#generate-webhook-secret').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to regenerate the webhook secret? This will break the integration if you have already configured it on Frak.')) {
            return;
        }
        
        $.post(frak_ajax.ajax_url, {
            action: 'frak_generate_webhook_secret',
            nonce: frak_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#frak_webhook_secret').val(response.data.secret);
                showNotice(response.data.message, 'success');
            } else {
                showNotice('Error generating webhook secret', 'error');
            }
        });
    });

    // Test webhook
    $('#test-webhook').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        $button.prop('disabled', true).text('Testing...');
        
        $.post(frak_ajax.ajax_url, {
            action: 'frak_test_webhook',
            nonce: frak_ajax.nonce
        }, function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
            } else {
                showNotice(response.data.message, 'error');
            }
        }).always(function() {
            $button.prop('disabled', false).text('Test Webhook');
        });
    });

    // Clear webhook logs
    $('#clear-webhook-logs').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to clear all webhook logs?')) {
            return;
        }
        
        $.post(frak_ajax.ajax_url, {
            action: 'frak_clear_webhook_logs',
            nonce: frak_ajax.nonce
        }, function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            }
        });
    });

    // Open webhook setup popup
    $('#open-webhook-popup').on('click', function(e) {
        e.preventDefault();
        
        var productId = $(this).data('product-id');
        var webhookSecret = $('#frak_webhook_secret').val();
        
        if (!webhookSecret) {
            alert('Please generate a webhook secret first');
            return;
        }
        
        var createUrl = new URL('https://business.frak.id');
        createUrl.pathname = '/embedded/purchase-tracker';
        createUrl.searchParams.append('pid', productId);
        createUrl.searchParams.append('s', webhookSecret);
        createUrl.searchParams.append('p', 'custom');
        
        var openedWindow = window.open(
            createUrl.href,
            'frak-business',
            'menubar=no,status=no,scrollbars=no,fullscreen=no,width=500,height=800'
        );
        
        if (openedWindow) {
            openedWindow.focus();
            
            // Check when window is closed and refresh status
            var timer = setInterval(function() {
                if (openedWindow.closed) {
                    clearInterval(timer);
                    setTimeout(function() {
                        checkWebhookStatus();
                    }, 1000);
                }
            }, 500);
        }
    });

    // Check webhook status
    function checkWebhookStatus() {
        $.post(frak_ajax.ajax_url, {
            action: 'frak_check_webhook_status',
            nonce: frak_ajax.nonce
        }, function(response) {
            if (response.success) {
                var $status = $('.frak-webhook-status');
                if (response.data.status) {
                    $status.removeClass('status-inactive').addClass('status-active').text('Active');
                    showNotice('Webhook is now active!', 'success');
                } else {
                    $status.removeClass('status-active').addClass('status-inactive').text('Inactive');
                }
            }
        });
    }

    // Show admin notice
    function showNotice(message, type) {
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after($notice);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
});