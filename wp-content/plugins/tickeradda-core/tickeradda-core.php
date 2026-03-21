<?php
/**
 * Plugin Name: TickerAdda Core
 * Plugin URI:  https://tickeradda.in
 * Description: Core plugin for TickerAdda ticket reselling marketplace. Handles REST API, database, roles, payments, KYC, and admin dashboards.
 * Version:     2.0.0
 * Author:      TickerAdda
 * Author URI:  https://tickeradda.in
 * License:     GPL-2.0+
 * Text Domain: tickeradda-core
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─── Constants ────────────────────────────────────────────────────────────────
define( 'TA_VERSION',     '2.0.0' );
define( 'TA_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'TA_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'TA_UPLOAD_DIR',  WP_CONTENT_DIR . '/uploads/tickeradda/' );
define( 'TA_UPLOAD_URL',  WP_CONTENT_URL . '/uploads/tickeradda/' );
define( 'TA_REST_NS',     'tickeradda/v2' );

// ─── Load Dependencies ────────────────────────────────────────────────────────
require_once TA_PLUGIN_DIR . 'security/class-security.php';
require_once TA_PLUGIN_DIR . 'database/class-database.php';
require_once TA_PLUGIN_DIR . 'includes/class-activator.php';
require_once TA_PLUGIN_DIR . 'includes/class-roles.php';
require_once TA_PLUGIN_DIR . 'includes/class-email.php';
require_once TA_PLUGIN_DIR . 'includes/class-razorpay.php';
require_once TA_PLUGIN_DIR . 'includes/class-pdf-invoice.php';
require_once TA_PLUGIN_DIR . 'includes/class-auth.php';
require_once TA_PLUGIN_DIR . 'includes/class-tickets.php';
require_once TA_PLUGIN_DIR . 'includes/class-events.php';
require_once TA_PLUGIN_DIR . 'includes/class-orders.php';
require_once TA_PLUGIN_DIR . 'includes/class-kyc.php';
require_once TA_PLUGIN_DIR . 'includes/class-reviews.php';
require_once TA_PLUGIN_DIR . 'includes/class-admin-panel.php';
require_once TA_PLUGIN_DIR . 'includes/class-movie-importer.php';
require_once TA_PLUGIN_DIR . 'includes/class-movies-sports.php';

// ─── Activation / Deactivation Hooks ─────────────────────────────────────────
register_activation_hook( __FILE__, array( 'TA_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'TA_Activator', 'deactivate' ) );

// ─── Boot the Plugin ─────────────────────────────────────────────────────────
add_action( 'plugins_loaded', 'tickeradda_init' );

function tickeradda_init() {
    // Initialize core components globally for hooks/CPTs
    $GLOBALS['ta_security'] = new TA_Security();
    $GLOBALS['ta_events']   = new TA_Events();
    $GLOBALS['ta_tickets'] = new TA_Tickets();
    $GLOBALS['ta_auth']    = new TA_Auth();
    $GLOBALS['ta_orders']  = new TA_Orders();
    $GLOBALS['ta_kyc']     = new TA_KYC();
    $GLOBALS['ta_reviews'] = new TA_Reviews();
    $GLOBALS['ta_movies_sports'] = new TA_Movies_Sports();

    // Init admin panel
    if ( is_admin() ) {
        new TA_Admin_Panel();
    }

    // Protect the uploads folder from direct access
    tickeradda_protect_upload_dir();

    // Schedule cleanup of expired OTPs (daily)
    if ( ! wp_next_scheduled( 'tickeradda_cleanup_otps' ) ) {
        wp_schedule_event( time(), 'daily', 'tickeradda_cleanup_otps' );
    }
}

// ─── Register all REST Routes ─────────────────────────────────────────────────
add_action( 'rest_api_init', 'tickeradda_register_rest_routes' );

// ─── Register all REST Routes ─────────────────────────────────────────────────
function tickeradda_register_rest_routes() {
    // Routes are registered using the global instances
    if ( isset($GLOBALS['ta_auth']) )    $GLOBALS['ta_auth']->register_routes();
    if ( isset($GLOBALS['ta_events']) )  $GLOBALS['ta_events']->register_routes();
    if ( isset($GLOBALS['ta_tickets']) ) $GLOBALS['ta_tickets']->register_routes();
    if ( isset($GLOBALS['ta_orders']) )  $GLOBALS['ta_orders']->register_routes();
    if ( isset($GLOBALS['ta_kyc']) )     $GLOBALS['ta_kyc']->register_routes();
    if ( isset($GLOBALS['ta_reviews']) ) $GLOBALS['ta_reviews']->register_routes();

    // Admin-only stats endpoint
    register_rest_route( TA_REST_NS, '/admin/stats', array(
        'methods'             => 'GET',
        'callback'            => 'tickeradda_admin_stats',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
    ) );
}

// ─── Admin Stats Callback ─────────────────────────────────────────────────────
function tickeradda_admin_stats( $request ) {
    global $wpdb;
    $t = TA_Database::tickets_table();
    $o = TA_Database::orders_table();

    $pending_tickets = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status = 'pending'" );
    $total_orders    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$o} WHERE status = 'completed'" );
    $gmv             = (float) $wpdb->get_var( "SELECT SUM(total_amount) FROM {$o} WHERE status = 'completed'" );

    $total_users  = count_users();
    $buyers       = isset( $total_users['avail_roles']['ta_buyer'] ) ? $total_users['avail_roles']['ta_buyer'] : 0;
    $sellers      = isset( $total_users['avail_roles']['ta_seller'] ) ? $total_users['avail_roles']['ta_seller'] : 0;

    return rest_ensure_response( array(
        'pending_tickets' => $pending_tickets,
        'total_orders'    => $total_orders,
        'gmv'             => round( $gmv, 2 ),
        'total_buyers'    => $buyers,
        'total_sellers'   => $sellers,
    ) );
}

// ─── Protect Uploads Directory ────────────────────────────────────────────────
function tickeradda_protect_upload_dir() {
    $upload_dir = TA_UPLOAD_DIR;
    if ( ! file_exists( $upload_dir ) ) {
        wp_mkdir_p( $upload_dir );
    }

    $htaccess = $upload_dir . '.htaccess';
    if ( ! file_exists( $htaccess ) ) {
        file_put_contents( $htaccess, "Options -Indexes\nDeny from all\n" );
    }
}

// ─── OTP Cleanup Cron ─────────────────────────────────────────────────────────
add_action( 'tickeradda_cleanup_otps', function() {
    global $wpdb;
    $table = TA_Database::otp_table();
    $wpdb->query( "DELETE FROM {$table} WHERE expires_at < NOW()" );
} );

// ─── Plugin Deactivation Cron Cleanup ────────────────────────────────────────
add_action( 'deactivate_tickeradda-core/tickeradda-core.php', function() {
    wp_clear_scheduled_hook( 'tickeradda_cleanup_otps' );
} );
