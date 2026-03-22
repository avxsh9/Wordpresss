<?php
/**
 * Plugin Name: TicketAdda Events Marketplace
 * Description: The definitive unified marketplace for Movie and Sports tickets.
 * Version: 2.0.0
 * Author: TicketAdda Senior Architect
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Define Constants
define( 'TAE_DIR', plugin_dir_path( __FILE__ ) );
define( 'TAE_URL', plugin_dir_url( __FILE__ ) );

// Core Includes
require_once TAE_DIR . 'includes/class-tae-db.php';
require_once TAE_DIR . 'includes/class-tae-cpt.php';
require_once TAE_DIR . 'includes/class-tae-api.php';
require_once TAE_DIR . 'includes/class-tae-shortcodes.php';
require_once TAE_DIR . 'includes/class-tae-engine.php';
require_once TAE_DIR . 'admin/class-tae-admin.php';

/**
 * Main Plugin Class
 */
class TicketAdda_Marketplace {
    public function __construct() {
        $this->init();
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    private function init() {
        new TAE_DB();
        new TAE_CPT();
        new TAE_API();
        new TAE_Shortcodes();
        new TAE_Engine();
        new TAE_Admin();
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'tae-main-style', TAE_URL . 'assets/css/style.css', array(), '2.0.0' );
        wp_enqueue_script( 'tae-main-js', TAE_URL . 'assets/js/script.js', array( 'jquery' ), '2.0.0', true );
        
        wp_localize_script( 'tae-main-js', 'tae_data', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'tae_nonce' ),
            'rest_url' => esc_url_raw( rest_url( 'ta/v2' ) ),
        ) );
    }

    public function enqueue_admin_assets() {
        wp_enqueue_style( 'tae-admin-style', TAE_URL . 'assets/css/admin-style.css', array(), '2.0.0' );
    }
}

// Start the Marketplace
new TicketAdda_Marketplace();
