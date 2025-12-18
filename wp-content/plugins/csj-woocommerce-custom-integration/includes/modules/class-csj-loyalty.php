<?php

if (!defined('ABSPATH')) {
    exit;
}

class CSJ_Loyalty
{

    public function __construct()
    {
        // Award points on order complete
        add_action('woocommerce_order_status_completed', array($this, 'award_points'));

        // Display points in My Account
        add_action('woocommerce_account_dashboard', array($this, 'display_points_dashboard'));

        // Apply points discount at checkout (logic hook)
        add_action('woocommerce_cart_calculate_fees', array($this, 'apply_points_discount'));

        // Add Redemption Field to Checkout
        add_action('woocommerce_review_order_before_submit', array($this, 'checkout_points_redemption_field'));

        // Process Redemption Data
        add_action('woocommerce_checkout_create_order', array($this, 'save_points_redemption'));
        add_action('woocommerce_reduce_order_stock', array($this, 'deduct_points_on_purchase'));
    }

    private function get_points_ratio()
    {
        return (float) get_option('csj_loyalty_points_ratio', 1);
    }

    private function get_redemption_rate()
    {
        return (float) get_option('csj_loyalty_redemption_rate', 0.01);
    }

    public function award_points($order_id)
    {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();

        if (!$user_id)
            return;

        // Check if points already awarded to avoid duplicates
        if (get_post_meta($order_id, '_csj_points_awarded', true))
            return;

        $total_spent = $order->get_total();
        $points_earned = floor($total_spent * $this->get_points_ratio());

        $this->log_points($user_id, $points_earned, "Points earned from Order #$order_id", $order_id);

        update_post_meta($order_id, '_csj_points_awarded', $points_earned);
    }

    public function log_points($user_id, $points, $description, $order_id = null)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'csj_loyalty_log';

        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'points' => $points,
                'log_date' => current_time('mysql'),
                'description' => $description,
                'reference_order_id' => $order_id
            )
        );

        // Update user meta total for quick access
        $current_points = (int) get_user_meta($user_id, '_csj_current_points', true);
        update_user_meta($user_id, '_csj_current_points', $current_points + $points);
    }

    public function display_points_dashboard()
    {
        $user_id = get_current_user_id();
        $points = (int) get_user_meta($user_id, '_csj_current_points', true);
        echo '<div class="csj-loyalty-dashboard">';
        echo '<h3>PawPoints Logic</h3>';
        echo '<p>You have <strong>' . $points . '</strong> PawPoints.</p>';
        echo '</div>';
    }

    public function checkout_points_redemption_field()
    {
        $user_id = get_current_user_id();
        if (!$user_id)
            return;

        $points = (int) get_user_meta($user_id, '_csj_current_points', true);
        if ($points <= 0)
            return;

        $max_discount = $points * self::REDEMPTION_RATE;

        echo '<div id="csj_points_redemption_field">';
        woocommerce_form_field('csj_redeem_points', array(
            'type' => 'number',
            'class' => array('form-row-wide'),
            'label' => sprintf(__('Redeem PawPoints (Max: %s points for £%s off)', 'csj-custom'), $points, $max_discount),
            'custom_attributes' => array('max' => $points, 'min' => 0, 'step' => 10),
        ));
        echo '</div>';
    }

    public function apply_points_discount($cart)
    {
        if (is_admin() && !defined('DOING_AJAX'))
            return;

        // Note: Real implementation needs AJAX handler to update cart session when field changes
        // This is a simplified placeholder hook. 
        // In a real scenario, we'd check WC()->session->get('csj_redeemed_points')
    }

    public function save_points_redemption($order)
    {
        if (isset($_POST['csj_redeem_points']) && $_POST['csj_redeem_points'] > 0) {
            $points_to_redeem = absint($_POST['csj_redeem_points']);
            $user_id = $order->get_user_id();
            $user_points = (int) get_user_meta($user_id, '_csj_current_points', true);

            if ($points_to_redeem <= $user_points) {
                update_post_meta($order->get_id(), '_csj_points_redeemed', $points_to_redeem);
                // Calculate discount amount and add as fee or negative line item
                // (Not fully implemented in this safe placeholder)
            }
        }
    }

    public function deduct_points_on_purchase($order_id)
    {
        $order = wc_get_order($order_id);
        $points_redeemed = get_post_meta($order_id, '_csj_points_redeemed', true);

        if ($points_redeemed) {
            $this->log_points($order->get_user_id(), -$points_redeemed, "Redeemed on Order #$order_id", $order_id);
        }
    }
}
