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
        
        // Add webhook handlers for order status changes
        add_action('woocommerce_order_status_completed', array($this, 'send_order_webhook'));
        add_action('woocommerce_order_status_processing', array($this, 'send_order_webhook'));
        add_action('woocommerce_payment_complete', array($this, 'send_order_webhook'));
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
     * Send webhook when order status changes
     *
     * @param int $order_id Order ID.
     */
    public function send_order_webhook($order_id) {
        // Check if webhook secret is configured
        $webhook_secret = get_option('frak_webhook_secret');
        if (empty($webhook_secret)) {
            return;
        }
        
        // Get order
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Get order status and token
        $status = $order->get_status();
        $token = $order->get_order_key();
        
        // Send webhook
        $result = Frak_Webhook_Helper::send($order_id, $status, $token);
        
        // Log result
        if ($result['success']) {
            $order->add_order_note(__('Frak webhook sent successfully', 'frak'));
        } else {
            $order->add_order_note(sprintf(__('Frak webhook failed: %s', 'frak'), $result['error']));
        }
    }
}