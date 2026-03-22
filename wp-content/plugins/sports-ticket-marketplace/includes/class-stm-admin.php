<?php
/**
 * STM_Admin — Handles Admin Dashboard and Settings for Sports
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class STM_Admin {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );
    }

    public function register_admin_pages() {
        add_submenu_page(
            'edit.php?post_type=ipl_tickets',
            'Sports Marketplace Dashboard',
            'Dashboard',
            'manage_options',
            'stm-dashboard',
            array( $this, 'render_dashboard' )
        );
    }

    public function render_dashboard() {
        $total_listings = wp_count_posts( 'ipl_tickets' )->publish;
        $pending_listings = wp_count_posts( 'ipl_tickets' )->pending;
        
        // Mock analytics data
        $revenue = 340000;
        $tickets_sold = 820;

        ?>
        <div class="wrap stm-admin-dashboard">
            <h1 style="color:#004ba0;">Sports Marketplace Analytics</h1>
            
            <div class="stm-stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <div class="stm-stat-card" style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1); border-left:5px solid #004ba0;">
                    <h3 style="color:#004ba0;">Total Match Listings</h3>
                    <p style="font-size:2rem; font-weight:700; margin:0;"><?php echo $total_listings; ?></p>
                </div>
                <div class="stm-stat-card" style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1); border-left:5px solid #ffcc00;">
                    <h3 style="color:#ffcc00;">Pending Approval</h3>
                    <p style="font-size:2rem; font-weight:700; margin:0;"><?php echo $pending_listings; ?></p>
                </div>
                <div class="stm-stat-card" style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1); border-left:5px solid #2ecc71;">
                    <h3 style="color:#2ecc71;">Match Revenue</h3>
                    <p style="font-size:2rem; font-weight:700; margin:0;">₹<?php echo number_format($revenue); ?></p>
                </div>
                <div class="stm-stat-card" style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1); border-left:5px solid #e74c3c;">
                    <h3 style="color:#e74c3c;">Match Tickets Sold</h3>
                    <p style="font-size:2rem; font-weight:700; margin:0;"><?php echo $tickets_sold; ?></p>
                </div>
            </div>

            <h2 style="margin-top:40px;">Sports Marketplace Commission (%)</h2>
            <form method="post" action="options.php">
                <?php settings_fields( 'stm_settings' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Platform Fee (%)</th>
                        <td><input type="number" name="stm_commission" value="<?php echo esc_attr( get_option('stm_commission', 15) ); ?>" step="0.1"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
