<?php

if (!defined('ABSPATH')) {
    exit;
}

class CSJ_Loader
{

    public static function init()
    {
        self::load_modules();
        self::load_admin();
    }

    private static function load_modules()
    {
        $modules = array(
            'csj-loyalty' => 'CSJ_Loyalty',
            'csj-stockists' => 'CSJ_Stockists',
            'csj-affiliates' => 'CSJ_Affiliates',
            'csj-pricing' => 'CSJ_Pricing',
            'csj-shipping' => 'CSJ_Shipping',
            'csj-picksheet' => 'CSJ_Picksheet',
        );

        foreach ($modules as $file => $class) {
            $path = CSJ_CUSTOM_PLUGIN_DIR . 'includes/modules/class-' . $file . '.php';
            if (file_exists($path)) {
                require_once $path;
                if (class_exists($class)) {
                    new $class();
                }
            }
        }
    }

    private static function load_admin()
    {
        if (is_admin()) {
            $path = CSJ_CUSTOM_PLUGIN_DIR . 'includes/admin/class-csj-admin.php';
            if (file_exists($path)) {
                require_once $path;
                new CSJ_Admin();
            }
        }
    }
}
