<?php
/**
 * Plugin Name: TicketAdda Events Marketplace
 * Description: A powerful unified marketplace for Movie and Sports tickets.
 * Version: 1.0.0
 * Author: TicketAdda Architect
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Define Constants
define( 'TAE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TAE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Core Includes
require_once TAE_PLUGIN_DIR . 'includes/class-tae-db.php';
require_once TAE_PLUGIN_DIR . 'includes/class-tae-cpt.php';
require_once TAE_PLUGIN_DIR . 'includes/class-tae-api.php';
require_once TAE_PLUGIN_DIR . 'includes/class-tae-shortcodes.php';
require_once TAE_PLUGIN_DIR . 'includes/class-tae-engine.php';
require_once TAE_PLUGIN_DIR . 'admin/class-tae-admin.php';

/**
 * Main Plugin Class
 */
class TicketAdda_Events {
    public function __construct() {
        // Initialize components
        new TAE_DB();
        new TAE_CPT();
        new TAE_API();
        new TAE_Shortcodes();
        new TAE_Engine();
        new TAE_Admin();

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
    }

    public function enqueue_public_assets() {
        wp_enqueue_style( 'tae-public-style', TAE_PLUGIN_URL . 'assets/css/style.css', array(), '1.0.0' );
        wp_enqueue_script( 'tae-public-script', TAE_PLUGIN_URL . 'assets/js/script.js', array( 'jquery' ), '1.0.0', true );
        
        wp_localize_script( 'tae-public-script', 'tae_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'tae_nonce' ),
            'rest_url' => esc_url_raw( rest_url( 'ta/v1' ) ),
        ) );
    }
}

// Start the engine
new TicketAdda_Events();
