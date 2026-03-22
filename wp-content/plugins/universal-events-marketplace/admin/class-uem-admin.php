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
        add_menu_page( 'UEM Admin', 'UEM Admin', 'manage_options', 'uem-dashboard', array( $this, 'render_dashboard' ), 'dashicons-tickets', 6 );
        add_submenu_page( 'uem-dashboard', 'Master Events', 'Master Events', 'manage_options', 'uem-master-events', array( $this, 'render_master_events' ) );
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

    public function render_master_events() {
        global $wpdb;
        $events = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}uem_master_events ORDER BY event_date ASC" );
        ?>
        <div class="wrap">
            <h1>Manage Master Events</h1>
            <div style="background:#fff; padding:25px; margin-top:20px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
                <h2>Add New Event</h2>
                <form method="POST">
                    <?php wp_nonce_field( 'uem_save_master', 'uem_nonce' ); ?>
                    <table class="form-table">
                        <tr><th>Event Name</th><td><input type="text" name="event_name" required class="regular-text"></td></tr>
                        <tr><th>Category</th><td>
                            <select name="event_category" required>
                                <option value="movie">Movie</option>
                                <option value="sports">Sports</option>
                                <option value="concert">Concert</option>
                                <option value="others">Others</option>
                            </select>
                        </td></tr>
                        <tr><th>Poster URL</th><td><input type="url" name="event_poster" class="regular-text"></td></tr>
                        <tr><th>Venue</th><td><input type="text" name="venue" required class="regular-text"></td></tr>
                        <tr><th>Date & Time</th><td><input type="date" name="event_date" required> <input type="time" name="event_time" required></td></tr>
                        <tr><th>IMDb Rating</th><td><input type="text" name="imdb_rating" placeholder="8.5 (Movies Only)"></td></tr>
                    </table>
                    <?php submit_button( 'Create Master Event', 'primary', 'uem_create_master' ); ?>
                </form>
            </div>

            <h2 style="margin-top:40px;">Registered Master Events</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Category</th><th>Venue</th><th>Date</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $e) : ?>
                        <tr>
                            <td><?php echo $e->id; ?></td>
                            <td><strong><?php echo esc_html($e->event_name); ?></strong></td>
                            <td><?php echo esc_html(ucfirst($e->event_category)); ?></td>
                            <td><?php echo esc_html($e->venue); ?></td>
                            <td><?php echo date('M j, Y', strtotime($e->event_date)); ?></td>
                            <td><a href="?page=uem-master-events&delete=<?php echo $e->id; ?>" class="button button-link-delete" onclick="return confirm('Full delete?')">Delete</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_master_data() {
        if ( isset($_POST['uem_create_master']) && wp_verify_nonce($_POST['uem_nonce'], 'uem_save_master') ) {
            global $wpdb;
            $wpdb->insert( "{$wpdb->prefix}uem_master_events", array(
                'event_name'     => sanitize_text_field($_POST['event_name']),
                'event_category' => sanitize_text_field($_POST['event_category']),
                'event_poster'   => esc_url_raw($_POST['event_poster']),
                'venue'          => sanitize_text_field($_POST['venue']),
                'event_date'     => sanitize_text_field($_POST['event_date']),
                'event_time'     => sanitize_text_field($_POST['event_time']),
                'imdb_rating'    => sanitize_text_field($_POST['imdb_rating']),
            ) );
            wp_redirect( admin_url('admin.php?page=uem-master-events&success=1') );
            exit;
        }

        if ( isset($_GET['delete']) && current_user_can('manage_options') ) {
            global $wpdb;
            $wpdb->delete( "{$wpdb->prefix}uem_master_events", array( 'id' => absint($_GET['delete']) ) );
        }
    }
}
