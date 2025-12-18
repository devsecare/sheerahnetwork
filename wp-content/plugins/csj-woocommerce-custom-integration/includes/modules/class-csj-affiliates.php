<?php

if (!defined('ABSPATH')) {
    exit;
}

class CSJ_Affiliates
{


    public function __construct()
    {
        // Track referral on init
        add_action('init', array($this, 'track_referral'));

        // Award commission
        add_action('woocommerce_order_status_completed', array($this, 'award_commission'));

        // Add "Affiliates" tab to My Account
        add_action('init', array($this, 'add_endpoint'));
        add_filter('woocommerce_account_menu_items', array($this, 'add_menu_item'));
        add_action('woocommerce_account_affiliate-dashboard_endpoint', array($this, 'render_dashboard'));
    }

    public function track_referral()
    {
        if (isset($_GET['ref'])) {
            $affiliate_id = absint($_GET['ref']);
            // Verify user is an affiliate
            $user = get_userdata($affiliate_id);
            if ($user && in_array('csj_affiliate', (array) $user->roles)) {
                setcookie('csj_affiliate_ref', $affiliate_id, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
            }
        }
    }

    public function award_commission($order_id)
    {
        // Check cookie
        if (!isset($_COOKIE['csj_affiliate_ref']))
            return;

        $affiliate_id = absint($_COOKIE['csj_affiliate_ref']);

        // Prevent self-referral
        $order = wc_get_order($order_id);
        if ($order->get_user_id() == $affiliate_id)
            return;

        // Determine commission
        $total = $order->get_subtotal();
        $rate = (float) get_option('csj_affiliate_commission_rate', 10) / 100;
        $commission = $total * $rate;

        global $wpdb;
        $table = $wpdb->prefix . 'csj_affiliate_commissions';

        $wpdb->insert(
            $table,
            array(
                'affiliate_id' => $affiliate_id,
                'order_id' => $order_id,
                'commission_amount' => $commission,
                'status' => 'pending'
            )
        );
    }

    // -- My Account Dashboard --

    public function add_endpoint()
    {
        add_rewrite_endpoint('affiliate-dashboard', EP_ROOT | EP_PAGES);
    }

    public function add_menu_item($items)
    {
        // Only show to affiliates
        $user = wp_get_current_user();
        if (in_array('csj_affiliate', (array) $user->roles)) {
            $items['affiliate-dashboard'] = __('Affiliate Dashboard', 'csj-custom');
        }
        return $items;
    }

    public function render_dashboard()
    {
        $user_id = get_current_user_id();
        global $wpdb;
        $table = $wpdb->prefix . 'csj_affiliate_commissions';
        $commissions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE affiliate_id = %d ORDER BY created_at DESC", $user_id));

        echo '<h3>Affiliate Dashboard</h3>';
        echo '<p>Your Referral Link: <code>' . home_url('/?ref=' . $user_id) . '</code></p>';

        if ($commissions) {
            echo '<table class="woocommerce-orders-table shop_table shop_table_responsive my_account_orders account-orders-table">';
            echo '<thead><tr>';
            echo '<th>Order ID</th><th>Commission</th><th>Status</th><th>Date</th>';
            echo '</tr></thead><tbody>';
            foreach ($commissions as $row) {
                echo '<tr>';
                echo '<td>' . $row->order_id . '</td>';
                echo '<td>£' . $row->commission_amount . '</td>';
                echo '<td>' . ucfirst($row->status) . '</td>';
                echo '<td>' . $row->created_at . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No commissions yet.</p>';
        }
    }
}
