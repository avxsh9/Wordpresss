<?php
/**
 * Plugin Name: Sports Ticket Marketplace
 * Description: A premium marketplace for sports and IPL tickets.
 * Version: 1.0.0
 * Author: Antigravity
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Define Constants
define( 'STM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'STM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load Classes
require_once STM_PLUGIN_DIR . 'includes/class-marketplace-engine.php';
require_once STM_PLUGIN_DIR . 'includes/class-stm-cpt.php';
require_once STM_PLUGIN_DIR . 'includes/class-stm-api.php';
require_once STM_PLUGIN_DIR . 'includes/class-stm-shortcodes.php';
require_once STM_PLUGIN_DIR . 'includes/class-stm-admin.php';

// Initialize Plugin
class Sports_Ticket_Marketplace {
    public function __construct() {
        new STM_CPT();
        new STM_API();
        new STM_Shortcodes();
        new STM_Admin();

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'stm-style', STM_PLUGIN_URL . 'assets/css/style.css', array(), '1.0.0' );
        wp_enqueue_script( 'stm-script', STM_PLUGIN_URL . 'assets/js/script.js', array( 'jquery' ), '1.0.0', true );
        
        wp_localize_script( 'stm-script', 'stm_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'stm_nonce' ),
            'rest_url' => esc_url_raw( rest_url( 'tm/v1' ) ),
        ) );
    }
}

new Sports_Ticket_Marketplace();
