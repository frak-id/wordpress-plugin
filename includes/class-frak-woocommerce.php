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
}