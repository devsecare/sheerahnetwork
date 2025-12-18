<?php

if (!defined('ABSPATH')) {
    exit;
}

class CSJ_Activator
{

    public static function activate()
    {
        self::create_tables();
        self::add_roles();
        flush_rewrite_rules();
    }

    private static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // 1. Loyalty Log Table
        $table_loyalty = $wpdb->prefix . 'csj_loyalty_log';
        $sql_loyalty = "CREATE TABLE $table_loyalty (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            points int(11) NOT NULL,
            log_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            description text NOT NULL,
            reference_order_id mediumint(9) DEFAULT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // 2. Affiliate Commission Table
        $table_affiliates = $wpdb->prefix . 'csj_affiliate_commissions';
        $sql_affiliates = "CREATE TABLE $table_affiliates (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            affiliate_id mediumint(9) NOT NULL,
            order_id mediumint(9) NOT NULL,
            commission_amount decimal(10,2) NOT NULL,
            status varchar(20) DEFAULT 'pending' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_loyalty);
        dbDelta($sql_affiliates);
    }

    private static function add_roles()
    {
        add_role(
            'csj_affiliate',
            __('CSJ Affiliate'),
            array(
                'read' => true,
                'edit_posts' => false,
            )
        );
    }
}
