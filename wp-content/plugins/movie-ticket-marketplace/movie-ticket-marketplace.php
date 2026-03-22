<?php
/**
 * Plugin Name: Movie Ticket Marketplace
 * Description: A premium marketplace for movie tickets.
 * Version: 1.0.0
 * Author: Antigravity
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Define Constants
define( 'MTM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MTM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load Classes
require_once MTM_PLUGIN_DIR . 'includes/class-marketplace-engine.php';
require_once MTM_PLUGIN_DIR . 'includes/class-mtm-cpt.php';
require_once MTM_PLUGIN_DIR . 'includes/class-mtm-api.php';
require_once MTM_PLUGIN_DIR . 'includes/class-mtm-shortcodes.php';
require_once MTM_PLUGIN_DIR . 'includes/class-mtm-admin.php';

// Initialize Plugin
class Movie_Ticket_Marketplace {
    public function __construct() {
        new MTM_CPT();
        new MTM_API();
        new MTM_Shortcodes();
        new MTM_Admin();

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'mtm-style', MTM_PLUGIN_URL . 'assets/css/style.css', array(), '1.0.0' );
        wp_enqueue_script( 'mtm-script', MTM_PLUGIN_URL . 'assets/js/script.js', array( 'jquery' ), '1.0.0', true );
        
        wp_localize_script( 'mtm-script', 'mtm_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'mtm_nonce' ),
            'rest_url' => esc_url_raw( rest_url( 'tm/v1' ) ),
        ) );
    }
}

new Movie_Ticket_Marketplace();
