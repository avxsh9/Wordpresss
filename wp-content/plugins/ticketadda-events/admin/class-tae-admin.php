<?php
/**
 * TAE_Admin — Handles Admin Dashboard, Sports Master Events, and Approvals
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TAE_Admin {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );
        add_action( 'admin_init', array( $this, 'handle_form_submissions' ) );
    }

    public function register_admin_pages() {
        add_menu_page(
            'TicketAdda',
            'TicketAdda',
            'manage_options',
            'tae-events',
            array( $this, 'render_main_page' ),
            'dashicons-tickets',
            6
        );

        add_submenu_page(
            'tae-events',
            'Sports Master Events',
            'Sports Events',
            'manage_options',
            'tae-sports-master',
            array( $this, 'render_sports_master_page' )
        );

        add_submenu_page(
            'tae-events',
            'Settings',
            'Settings',
            'manage_options',
            'tae-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function render_main_page() {
        $pending_count = wp_count_posts( 'event_ticket' )->pending;
        ?>
        <div class="wrap">
            <h1>TicketAdda Marketplace Dashboard</h1>
            <div class="mtm-stats-grid" style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top:20px;">
                <div style="background:#fff; padding:20px; border-radius:10px; border-left:4px solid #004ba0;">
                    <h3>Pending Approvals</h3>
                    <p style="font-size:2rem; font-weight:700;"><?php echo $pending_count; ?></p>
                    <a href="edit.php?post_status=pending&post_type=event_ticket">View All</a>
                </div>
                <div style="background:#fff; padding:20px; border-radius:10px; border-left:4px solid #2ecc71;">
                    <h3>Total Sales</h3>
                    <p style="font-size:2rem; font-weight:700;">₹4.2L</p>
                </div>
                <div style="background:#fff; padding:20px; border-radius:10px; border-left:4px solid #ffcc00;">
                    <h3>Active Listings</h3>
                    <p style="font-size:2rem; font-weight:700;"><?php echo wp_count_posts('event_ticket')->publish; ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_sports_master_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'events_master';
        $events = $wpdb->get_results( "SELECT * FROM $table_name WHERE event_type = 'sports' ORDER BY event_date ASC" );
        ?>
        <div class="wrap">
            <h1>Manage Sports (Master Events)</h1>
            <div style="background:#fff; padding:20px; margin-top:20px; border-radius:8px;">
                <h2>Add New Match</h2>
                <form method="POST">
                    <?php wp_nonce_field( 'tae_add_match', 'tae_nonce' ); ?>
                    <table class="form-table">
                        <tr>
                            <th>Match Name</th>
                            <td><input type="text" name="event_name" required style="width:100%;" placeholder="e.g. MI vs CSK (Wankhede)"></td>
                        </tr>
                        <tr>
                            <th>Venue</th>
                            <td><input type="text" name="venue" required style="width:100%;" placeholder="e.g. Wankhede Stadium, Mumbai"></td>
                        </tr>
                        <tr>
                            <th>Date & Time</th>
                            <td>
                                <input type="date" name="event_date" required>
                                <input type="time" name="event_time" required>
                            </td>
                        </tr>
                        <tr>
                            <th>Poster URL</th>
                            <td><input type="url" name="poster_url" style="width:100%;"></td>
                        </tr>
                    </table>
                    <p><input type="submit" name="tae_add_event_submit" class="button button-primary" value="Add Event"></p>
                </form>
            </div>

            <h2 style="margin-top:40px;">Existing Sports Events</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Event Name</th>
                        <th>Venue</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $events as $event ) : ?>
                        <tr>
                            <td><?php echo $event->id; ?></td>
                            <td><strong><?php echo esc_html( $event->event_name ); ?></strong></td>
                            <td><?php echo esc_html( $event->venue ); ?></td>
                            <td><?php echo esc_html( $event->event_date ); ?></td>
                            <td><?php echo esc_html( $event->event_time ); ?></td>
                            <td><a href="#" class="button button-small">Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_form_submissions() {
        if ( ! isset( $_POST['tae_add_event_submit'] ) || ! wp_verify_nonce( $_POST['tae_nonce'], 'tae_add_match' ) ) return;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'events_master';
        
        $wpdb->insert( $table_name, array(
            'event_name' => sanitize_text_field( $_POST['event_name'] ),
            'venue'      => sanitize_text_field( $_POST['venue'] ),
            'event_date' => sanitize_text_field( $_POST['event_date'] ),
            'event_time' => sanitize_text_field( $_POST['event_time'] ),
            'poster_url' => esc_url_raw( $_POST['poster_url'] ),
            'event_type' => 'sports',
            'time'       => current_time( 'mysql' ),
        ) );

        wp_redirect( admin_url( 'admin.php?page=tae-sports-master&success=1' ) );
        exit;
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Marketplace Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'tae_settings_group' ); ?>
                <table class="form-table">
                    <tr>
                        <th>Global Commission (%)</th>
                        <td><input type="number" name="tae_commission" value="<?php echo esc_attr( get_option('tae_commission', 10) ); ?>" step="0.1"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
