<?php

if (!defined('ABSPATH')) {
    exit;
}

class CSJ_Shipping
{


    public function __construct()
    {
        // Calculate split logic on order creation
        add_action('woocommerce_checkout_create_order', array($this, 'analyze_shipping_split'), 10, 2);

        // Display split notice in admin
        add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'display_split_notice'));
    }

    public function analyze_shipping_split($order, $data)
    {
        $total_weight = 0;
        $has_heavy_item = false;
        $has_forced_dpd_sku = false;
        $split_reason = [];

        $dpd_force_skus = array_filter(array_map('trim', explode(',', get_option('csj_shipping_dpd_skus', ''))));

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $weight = (float) $product->get_weight();
                $qty = $item->get_quantity();
                $total_weight += ($weight * $qty);

                // Example logic: if single item > 15kg, mark heavy
                if ($weight > 15) {
                    $has_heavy_item = true;
                }

                // Check for Forced DPD SKU
                if (!empty($dpd_force_skus) && in_array($product->get_sku(), $dpd_force_skus)) {
                    $has_forced_dpd_sku = true;
                }
            }
        }

        // Split Logic: If total weight > threshold OR mixed heavy/light items OR forced DPD SKU
        $threshold = (float) get_option('csj_shipping_split_weight', 20);
        $is_split_required = false;

        if ($total_weight > $threshold) {
            $is_split_required = true;
            $split_reason[] = "Total Weight: {$total_weight}kg (exceeds {$threshold}kg)";
        }
        if ($has_heavy_item) {
            $is_split_required = true;
            $split_reason[] = "Contains heavy item (>15kg)";
        }
        if ($has_forced_dpd_sku) {
            $is_split_required = true;
            $split_reason[] = "Contains DPD-forced SKU";
        }

        if ($is_split_required) {
            $order->update_meta_data('_csj_shipping_split_required', 'yes');
            $order->update_meta_data('_csj_shipping_split_reason', implode(', ', $split_reason));
        }
    }

    public function display_split_notice($order)
    {
        $split_required = $order->get_meta('_csj_shipping_split_required');
        if ($split_required === 'yes') {
            $reason = $order->get_meta('_csj_shipping_split_reason');
            echo '<div style="background:#fff4f4; border-left:4px solid #d63638; padding:10px; margin-top:10px;">';
            echo '<p style="color:#d63638; font-weight:bold; margin:0;">⚠️ Split Shipping Required</p>';
            echo '<p style="margin:5px 0 0;">Reason: ' . esc_html($reason) . '</p>';
            echo '<p style="margin:5px 0 0;">Action: Split between DPD (Heavy) and Royal Mail (Light).</p>';
            echo '</div>';
        }
    }
}
