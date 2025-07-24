<?php

class Frak_WooCommerce {

    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('woocommerce_thankyou', array($this, 'track_purchase'));
        
        // Add webhook handler for all order status changes
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 4);
    }

    public function track_purchase($order_id) {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $customer_id = $order->get_user_id();
        $order_key = $order->get_order_key();

        wp_register_script('frak-purchase-tracking', false);
        wp_enqueue_script('frak-purchase-tracking');
        
        $tracking_script = $this->get_tracking_script($customer_id, $order_id, $order_key);
        wp_add_inline_script('frak-purchase-tracking', $tracking_script);
    }

    private function get_tracking_script($customer_id, $order_id, $order_key) {
        $payload = array(
            'customerId' => $customer_id,
            'orderId' => $order_id,
            'token' => $order_key
        );

        return "
        (function() {
            try {
                const interactionToken = sessionStorage.getItem('frak-wallet-interaction-token');
                if (interactionToken) {
                    fetch('https://backend.frak.id/interactions/listenForPurchase', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'x-wallet-sdk-auth': interactionToken
                        },
                        body: JSON.stringify(" . json_encode($payload) . ")
                    }).catch(error => {
                        console.error('Frak purchase tracking error:', error);
                    });
                }
            } catch (error) {
                console.error('Frak purchase tracking error:', error);
            }
        })();";
    }
    
    /**
     * Handle order status changes
     *
     * @param int    $order_id Order ID.
     * @param string $old_status Old status.
     * @param string $new_status New status.
     * @param object $order Order object.
     */
    public function handle_order_status_change($order_id, $old_status, $new_status, $order) {
        // Check if webhook secret is configured
        $webhook_secret = get_option('frak_webhook_secret');
        if (empty($webhook_secret)) {
            return;
        }
        
        // Define status mapping (WooCommerce status => Frak status)
        $status_map = array(
            'completed'  => 'confirmed',
            'processing' => 'pending',
            'on-hold'    => 'pending',
            'pending'    => 'pending',
            'cancelled'  => 'cancelled',
            'refunded'   => 'refunded',
            'failed'     => 'cancelled',
        );
        
        // Define statuses to skip
        $skip_statuses = array(
            'checkout-draft', // Draft orders during checkout
            'auto-draft',     // Auto-draft orders
        );
        
        // Skip if status is in skip list
        if (in_array($new_status, $skip_statuses)) {
            $order->add_order_note(sprintf(__('Frak: Skipping webhook for status: %s', 'frak'), $new_status));
            return;
        }
        
        // Map the status, default to 'pending' if not mapped
        $webhook_status = isset($status_map[$new_status]) ? $status_map[$new_status] : 'pending';
        
        // Get order token
        $token = $order->get_order_key();
        
        // Log the webhook attempt
        $order->add_order_note(sprintf(__('Frak: Sending webhook with status: %s', 'frak'), $webhook_status));
        
        // Send webhook
        $result = Frak_Webhook_Helper::send($order_id, $webhook_status, $token);
        
        // Log result
        if ($result['success']) {
            $order->add_order_note(__('Frak: Webhook sent successfully', 'frak'));
        } else {
            $order->add_order_note(sprintf(__('Frak: Webhook failed: %s', 'frak'), $result['error']));
        }
    }
}