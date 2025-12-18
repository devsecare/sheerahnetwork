<?php

if (!defined('ABSPATH')) {
    exit;
}

class CSJ_Pricing
{

    public function __construct()
    {
        // Add custom fields to Product Data
        add_action('woocommerce_product_options_pricing', array($this, 'add_tier_pricing_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_tier_pricing_fields'));

        // Filter price
        add_filter('woocommerce_product_get_price', array($this, 'get_tier_price'), 10, 2);
        add_filter('woocommerce_product_get_regular_price', array($this, 'get_tier_price'), 10, 2);
    }

    public function add_tier_pricing_fields()
    {
        echo '<div class="options_group">';

        woocommerce_wp_text_input(array(
            'id' => '_price_professional_tier_1',
            'label' => __('Professional Price (Tier 1)', 'csj-custom'),
            'data_type' => 'price',
        ));

        woocommerce_wp_text_input(array(
            'id' => '_price_professional_tier_2',
            'label' => __('Sponsored Price (Tier 2)', 'csj-custom'),
            'data_type' => 'price',
        ));

        echo '</div>';
    }

    public function save_tier_pricing_fields($post_id)
    {
        $tier_1 = isset($_POST['_price_professional_tier_1']) ? wc_clean($_POST['_price_professional_tier_1']) : '';
        $tier_2 = isset($_POST['_price_professional_tier_2']) ? wc_clean($_POST['_price_professional_tier_2']) : '';

        update_post_meta($post_id, '_price_professional_tier_1', $tier_1);
        update_post_meta($post_id, '_price_professional_tier_2', $tier_2);
    }

    public function get_tier_price($price, $product)
    {
        if (is_admin() && !defined('DOING_AJAX'))
            return $price;

        $user = wp_get_current_user();
        $roles = (array) $user->roles;

        // Tier 1: Professional
        if (in_array('csj_professional', $roles)) {
            $tier_price = get_post_meta($product->get_id(), '_price_professional_tier_1', true);
            if ($tier_price)
                return $tier_price;
        }

        // Tier 2: Sponsored
        if (in_array('csj_sponsored', $roles)) {
            $tier_price = get_post_meta($product->get_id(), '_price_professional_tier_2', true);
            if ($tier_price)
                return $tier_price;
        }

        return $price;
    }
}
