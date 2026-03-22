<?php
/**
 * Plugin Name: Universal Events Marketplace
 * Description: A single unified marketplace for all event types.
 * Version: 1.0.0
 * Author: Senior WordPress Architect
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Define Constants
define( 'UEM_DIR', plugin_dir_path( __FILE__ ) );
define( 'UEM_URL', plugin_dir_url( __FILE__ ) );

// Core Includes
require_once UEM_DIR . 'includes/class-uem-db.php';
require_once UEM_DIR . 'includes/class-uem-cpt.php';
require_once UEM_DIR . 'includes/class-uem-api.php';
require_once UEM_DIR . 'includes/class-uem-shortcodes.php';
require_once UEM_DIR . 'includes/class-uem-engine.php';
require_once UEM_DIR . 'admin/class-uem-admin.php';

/**
 * Main Plugin Class
 */
class Universal_Events_Marketplace {
    public function __construct() {
        $this->init();
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    private function init() {
        new UEM_DB();
        new UEM_CPT();
        new UEM_API();
        new UEM_Shortcodes();
        new UEM_Engine();
        new UEM_Admin();
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'uem-main-style', UEM_URL . 'assets/css/style.css', array(), '1.0.0' );
        wp_enqueue_script( 'uem-main-js', UEM_URL . 'assets/js/script.js', array( 'jquery' ), '1.0.0', true );
        
        wp_localize_script( 'uem-main-js', 'uem_data', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'uem_nonce' ),
            'rest_url' => esc_url_raw( rest_url( 'uem/v1' ) ),
        ) );
    }
}

// Start the Marketplace
new Universal_Events_Marketplace();
