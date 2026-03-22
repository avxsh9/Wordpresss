<?php
/**
 * MTM_Admin — Handles Admin Dashboard and Settings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MTM_Admin {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );
    }

    public function register_admin_pages() {
        add_submenu_page(
            'edit.php?post_type=movie_tickets',
            'Marketplace Dashboard',
            'Dashboard',
            'manage_options',
            'mtm-dashboard',
            array( $this, 'render_dashboard' )
        );
    }

    public function render_dashboard() {
        $total_listings = wp_count_posts( 'movie_tickets' )->publish;
        $pending_listings = wp_count_posts( 'movie_tickets' )->pending;
        
        // Mock analytics data
        $revenue = 125000;
        $tickets_sold = 450;

        ?>
        <div class="wrap mtm-admin-dashboard">
            <h1>Movie Marketplace Analytics</h1>
            
            <div class="mtm-stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <div class="mtm-stat-card" style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1); border-left:5px solid #e50914;">
                    <h3>Total Listings</h3>
                    <p style="font-size:2rem; font-weight:700; margin:0;"><?php echo $total_listings; ?></p>
                </div>
                <div class="mtm-stat-card" style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1); border-left:5px solid #f39c12;">
                    <h3>Pending Approval</h3>
                    <p style="font-size:2rem; font-weight:700; margin:0;"><?php echo $pending_listings; ?></p>
                </div>
                <div class="mtm-stat-card" style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1); border-left:5px solid #2ecc71;">
                    <h3>Total Revenue</h3>
                    <p style="font-size:2rem; font-weight:700; margin:0;">₹<?php echo number_format($revenue); ?></p>
                </div>
                <div class="mtm-stat-card" style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1); border-left:5px solid #3498db;">
                    <h3>Tickets Sold</h3>
                    <p style="font-size:2rem; font-weight:700; margin:0;"><?php echo $tickets_sold; ?></p>
                </div>
            </div>

            <h2 style="margin-top:40px;">Commission Settings</h2>
            <form method="post" action="options.php">
                <?php settings_fields( 'mtm_settings' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Admin Commission (%)</th>
                        <td><input type="number" name="mtm_commission" value="<?php echo esc_attr( get_option('mtm_commission', 10) ); ?>" step="0.1"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
