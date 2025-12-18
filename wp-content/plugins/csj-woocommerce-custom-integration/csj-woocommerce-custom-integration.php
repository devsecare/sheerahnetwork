<?php
/**
 * Plugin Name: CSJ Woocommerce Custom Integration
 * Plugin URI:  https://ecareinfoway.com
 * Description: A comprehensive WordPress implementation handling Loyalty, Stockists, Affiliates, Custom Pricing, and Logistics customized for CSJ.
 * Version:     1.0.0
 * Author:      DV
 * Author URI:  https://ecareinfoway.com
 * Text Domain: csj-custom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Plugin Constants
define( 'CSJ_CUSTOM_VERSION', '1.0.0' );
define( 'CSJ_CUSTOM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CSJ_CUSTOM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Class
 */
class CSJ_Woocommerce_Custom_Integration {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the plugin.
	 */
	private function __construct() {
		// Load Includes
		$this->includes();

		// Init Modules
		add_action( 'plugins_loaded', array( $this, 'init_modules' ) );
        
        // Activation Hook
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
	}

	/**
	 * Load Core Files
	 */
	private function includes() {
		require_once CSJ_CUSTOM_PLUGIN_DIR . 'includes/class-csj-loader.php';
	}

	/**
	 * Initialize Modules (Instantiate Classes)
	 */
	public function init_modules() {
        // Check if WooCommerce is active
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', function() {
                echo '<div class="error"><p><strong>CSJ Woocommerce Custom Integration</strong> requires WooCommerce to be installed and active.</p></div>';
            });
            return;
        }

		CSJ_Loader::init();
	}
    
    /**
     * Plugin Activation
     */
    public function activate() {
        // Create custom tables
        require_once CSJ_CUSTOM_PLUGIN_DIR . 'includes/class-csj-activator.php';
        CSJ_Activator::activate();
    }
}

// Init Plugin
CSJ_Woocommerce_Custom_Integration::get_instance();
