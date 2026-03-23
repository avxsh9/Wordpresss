<?php
/**
 * UEM_Admin — Handles Admin Dashboard and Master Event Management
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class UEM_Admin {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );
        add_action( 'admin_init', array( $this, 'handle_master_data' ) );
    }

    public function register_admin_pages() {
        add_menu_page( 'UEM Dashboard', 'UEM Dashboard', 'manage_options', 'uem-dashboard', array( $this, 'render_dashboard' ), 'dashicons-performance', 6 );
    }

    public function render_dashboard() {
        $pending = wp_count_posts( 'event_listing' )->pending;
        ?>
        <div class="wrap">
            <h1>Universal Events Marketplace Dashboard</h1>
            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top:20px;">
                <div style="background:#fff; padding:20px; border-radius:10px; border-left:4px solid #1a73e8;">
                    <h3>Pending Approvals</h3>
                    <div style="font-size:2rem; font-weight:700;"><?php echo $pending; ?></div>
                    <a href="edit.php?post_status=pending&post_type=event_listing">Review Listings</a>
                </div>
                <div style="background:#fff; padding:20px; border-radius:10px; border-left:4px solid #2ecc71;">
                    <h3>Active Listings</h3>
                    <div style="font-size:2rem; font-weight:700;"><?php echo wp_count_posts('event_listing')->publish; ?></div>
                </div>
            </div>
        </div>
        <?php
    }

    public function handle_master_data() {
        // Handled by standard CPT interface now
    }
}
