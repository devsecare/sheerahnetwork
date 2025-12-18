<?php

if (!defined('ABSPATH')) {
    exit;
}

class CSJ_Stockists
{

    public function __construct()
    {
        add_action('init', array($this, 'register_post_type'));
        add_shortcode('csj_stockist_locator', array($this, 'render_locator'));
        add_action('add_meta_boxes', array($this, 'add_custom_meta_boxes'));
        add_action('save_post', array($this, 'save_custom_meta'));
    }

    public function register_post_type()
    {
        $labels = array(
            'name' => _x('Stockists', 'Post Type General Name', 'csj-custom'),
            'singular_name' => _x('Stockist', 'Post Type Singular Name', 'csj-custom'),
            'menu_name' => __('Stockists', 'csj-custom'),
            'name_admin_bar' => __('Stockist', 'csj-custom'),
            'add_new' => __('Add New', 'csj-custom'),
            'add_new_item' => __('Add New Stockist', 'csj-custom'),
            'new_item' => __('New Stockist', 'csj-custom'),
            'edit_item' => __('Edit Stockist', 'csj-custom'),
            'view_item' => __('View Stockist', 'csj-custom'),
            'all_items' => __('All Stockists', 'csj-custom'),
            'search_items' => __('Search Stockists', 'csj-custom'),
        );
        $args = array(
            'label' => __('Stockist', 'csj-custom'),
            'description' => __('Stockist Locations', 'csj-custom'),
            'labels' => $labels,
            'supports' => array('title'),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-location',
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => false,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => 'post',
        );
        register_post_type('csj_stockist', $args);
    }

    public function add_custom_meta_boxes()
    {
        add_meta_box(
            'csj_stockist_details',
            __('Location Details', 'csj-custom'),
            array($this, 'render_meta_box'),
            'csj_stockist',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post)
    {
        wp_nonce_field('csj_save_stockist_details', 'csj_stockist_nonce');
        $address = get_post_meta($post->ID, '_csj_address', true);
        $lat = get_post_meta($post->ID, '_csj_lat', true);
        $lng = get_post_meta($post->ID, '_csj_lng', true);
        $phone = get_post_meta($post->ID, '_csj_phone', true);
        ?>
        <p>
            <label for="csj_address"><?php _e('Full Address', 'csj-custom'); ?></label><br>
            <textarea id="csj_address" name="csj_address" style="width:100%;"><?php echo esc_textarea($address); ?></textarea>
        </p>
        <p>
            <label for="csj_phone"><?php _e('Phone Number', 'csj-custom'); ?></label><br>
            <input type="text" id="csj_phone" name="csj_phone" value="<?php echo esc_attr($phone); ?>" style="width:100%;">
        </p>
        <div style="display:flex; gap: 10px;">
            <p style="flex:1;">
                <label for="csj_lat"><?php _e('Latitude', 'csj-custom'); ?></label><br>
                <input type="text" id="csj_lat" name="csj_lat" value="<?php echo esc_attr($lat); ?>" style="width:100%;">
            </p>
            <p style="flex:1;">
                <label for="csj_lng"><?php _e('Longitude', 'csj-custom'); ?></label><br>
                <input type="text" id="csj_lng" name="csj_lng" value="<?php echo esc_attr($lng); ?>" style="width:100%;">
            </p>
        </div>
        <?php
    }

    public function save_custom_meta($post_id)
    {
        if (!isset($_POST['csj_stockist_nonce']) || !wp_verify_nonce($_POST['csj_stockist_nonce'], 'csj_save_stockist_details'))
            return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (!current_user_can('edit_post', $post_id))
            return;

        if (isset($_POST['csj_address']))
            update_post_meta($post_id, '_csj_address', sanitize_textarea_field($_POST['csj_address']));
        if (isset($_POST['csj_phone']))
            update_post_meta($post_id, '_csj_phone', sanitize_text_field($_POST['csj_phone']));
        if (isset($_POST['csj_lat']))
            update_post_meta($post_id, '_csj_lat', sanitize_text_field($_POST['csj_lat']));
        if (isset($_POST['csj_lng']))
            update_post_meta($post_id, '_csj_lng', sanitize_text_field($_POST['csj_lng']));
    }

    public function render_locator($atts)
    {
        $api_key = get_option('csj_maps_api_key');

        // Enqueue Google Maps if API key is present
        if ($api_key) {
            wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($api_key), array(), null, true);
        }

        // Simple placeholder for the frontend Map
        // In real implementation, this would enqueue Google Maps JS and fetch stockist data via JSON
        ob_start();
        ?>
        <div id="csj-stockist-map"
            style="width:100%; height:500px; background:#e0e0e0; display:flex; align-items:center; justify-content:center;">
            <?php if (!$api_key): ?>
                <p>Please configure Google Maps API Key in CSJ Settings.</p>
            <?php else: ?>
                <p>Google Maps Locator Loading...</p>
            <?php endif; ?>
        </div>
        <div id="csj-stockist-list">
            <!-- List populated via JS -->
        </div>
        <?php
        return ob_get_clean();
    }
}
