<?php

if (!defined('ABSPATH')) {
    exit;
}

class CSJ_Picksheet
{

    public function __construct()
    {
        // Add button to Admin Order Actions
        add_filter('woocommerce_admin_order_actions', array($this, 'add_picksheet_action'), 10, 2);

        // Handle Print request
        add_action('admin_init', array($this, 'generate_picksheet_view'));
    }

    public function add_picksheet_action($actions, $order)
    {
        if ($order->has_status(array('processing', 'completed'))) {
            $actions['csj_print_picksheet'] = array(
                'url' => wp_nonce_url(admin_url('admin.php?action=csj_print_picksheet&order_id=' . $order->get_id()), 'csj_print_picksheet'),
                'name' => __('Print Picksheet', 'csj-custom'),
                'action' => 'view',
            );
        }
        return $actions;
    }

    public function generate_picksheet_view()
    {
        if (isset($_GET['action']) && $_GET['action'] === 'csj_print_picksheet') {
            if (!isset($_GET['order_id']) || !isset($_GET['_wpnonce']))
                return;
            if (!wp_verify_nonce($_GET['_wpnonce'], 'csj_print_picksheet'))
                wp_die('Invalid Nonce');

            $order_id = absint($_GET['order_id']);
            $order = wc_get_order($order_id);

            if (!$order)
                wp_die('Invalid Order');

            // group items by category
            $grouped_items = array();
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                $cats = wc_get_product_category_list($product->get_id());
                $cat_name = strip_tags($cats);
                if (empty($cat_name))
                    $cat_name = 'Uncategorized';

                $grouped_items[$cat_name][] = $item;
            }

            // Render HTML for Print
            ?>
            <html>

            <head>
                <title>Picksheet #<?php echo $order_id; ?></title>
                <style>
                    body {
                        font-family: sans-serif;
                        padding: 20px;
                    }

                    .header {
                        text-align: center;
                        border-bottom: 2px solid #000;
                        padding-bottom: 10px;
                        margin-bottom: 20px;
                    }

                    .group {
                        margin-bottom: 20px;
                        border: 1px solid #ccc;
                        break-inside: avoid;
                    }

                    .group-title {
                        background: #eee;
                        padding: 5px 10px;
                        font-weight: bold;
                        border-bottom: 1px solid #ccc;
                    }

                    table {
                        width: 100%;
                        border-collapse: collapse;
                    }

                    th,
                    td {
                        text-align: left;
                        padding: 8px;
                        border-bottom: 1px solid #eee;
                    }

                    .barcode {
                        font-family: 'Courier New', monospace;
                        letter-spacing: 2px;
                    }
                </style>
            </head>

            <body onload="window.print()">
                <div class="header">
                    <h1>WAREHOUSE PICKSHEET</h1>
                    <h2>Order #<?php echo $order_id; ?></h2>
                    <p>User: <?php echo $order->get_formatted_billing_full_name(); ?></p>
                    <p>Date: <?php echo $order->get_date_created()->date('Y-m-d H:i'); ?></p>
                </div>

                <?php foreach ($grouped_items as $category => $items): ?>
                    <div class="group">
                        <div class="group-title">BIN/ZONE: <?php echo $category; ?></div>
                        <table>
                            <thead>
                                <tr>
                                    <th width="10%">Qty</th>
                                    <th width="50%">Product</th>
                                    <th width="20%">SKU</th>
                                    <th width="20%">Barcode</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item):
                                    $product = $item->get_product();
                                    ?>
                                    <tr>
                                        <td style="font-size:1.2em; font-weight:bold;"><?php echo $item->get_quantity(); ?></td>
                                        <td><?php echo $item->get_name(); ?></td>
                                        <td><?php echo $product ? $product->get_sku() : '-'; ?></td>
                                        <td class="barcode">||| |||| || |||</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>

                <div style="margin-top: 30px; text-align: center; font-size: 0.8em; color: #666;">
                    Generated by CSJ Custom Integration on <?php echo current_time('mysql'); ?>
                </div>
            </body>

            </html>
            <?php
            exit;
        }
    }
}
