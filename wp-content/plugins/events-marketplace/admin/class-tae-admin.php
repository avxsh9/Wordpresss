<?php
/**
 * TAE_Admin — Handles Admin Dashboard and Master Event Management
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TAE_Admin {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );
        add_action( 'admin_init', array( $this, 'handle_master_data' ) );
        add_action( 'init', array( $this, 'register_settings' ) );
    }

    public function register_settings() {
        register_setting( 'tae_settings_group', 'tae_commission' );
    }

    public function register_admin_pages() {
        add_menu_page( 'TicketAdda', 'TicketAdda', 'manage_options', 'tae-dashboard', array( $this, 'render_dashboard' ), 'dashicons-tickets', 6 );
        add_submenu_page( 'tae-dashboard', 'Master Movies', 'Master Movies', 'manage_options', 'tae-master-movies', array( $this, 'render_master_movies' ) );
        add_submenu_page( 'tae-dashboard', 'Master Matches', 'Master Matches', 'manage_options', 'tae-master-matches', array( $this, 'render_master_matches' ) );
        add_submenu_page( 'tae-dashboard', 'Settings', 'Settings', 'manage_options', 'tae-settings', array( $this, 'render_settings' ) );
    }

    public function render_dashboard() {
        $pending = wp_count_posts( 'event_listing' )->pending;
        ?>
        <div class="wrap">
            <h1>TicketAdda Events Dashboard</h1>
            <div class="tae-admin-stats">
                <div class="stat-card">
                    <h3>Pending Approvals</h3>
                    <div class="number"><?php echo $pending; ?></div>
                    <a href="edit.php?post_status=pending&post_type=event_listing">Review Listings</a>
                </div>
                <div class="stat-card">
                    <h3>Active Listings</h3>
                    <div class="number"><?php echo wp_count_posts('event_listing')->publish; ?></div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_master_movies() {
        global $wpdb;
        $movies = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}tae_master_data WHERE type = 'movie' ORDER BY id DESC" );
        ?>
        <div class="wrap">
            <h1>Manage Master Movies</h1>
            <div class="tae-admin-form-box">
                <h2>Add New Movie</h2>
                <form method="POST">
                    <?php wp_nonce_field( 'tae_master_movie', 'tae_nonce' ); ?>
                    <input type="hidden" name="event_type" value="movie">
                    <table class="form-table">
                        <tr><th>Movie Name</th><td><input type="text" name="name" required class="regular-text"></td></tr>
                        <tr><th>Poster URL</th><td><input type="url" name="poster_url" class="regular-text"></td></tr>
                        <tr><th>IMDb Rating</th><td><input type="text" name="imdb_rating" placeholder="e.g. 8.5" class="small-text"></td></tr>
                    </table>
                    <?php submit_button( 'Save Movie', 'primary', 'tae_save_master' ); ?>
                </form>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>Movie Name</th><th>IMDb</th><th>Poster</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($movies as $m): ?>
                        <tr><td><?php echo $m->id; ?></td><td><strong><?php echo esc_html($m->name); ?></strong></td><td><?php echo esc_html($m->imdb_rating); ?></td><td><img src="<?php echo esc_url($m->poster_url); ?>" width="50"></td><td><a href="?page=tae-master-movies&delete=<?php echo $m->id; ?>" class="button button-link-delete">Delete</a></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_master_matches() {
        global $wpdb;
        $matches = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}tae_master_data WHERE type = 'sports' ORDER BY event_date ASC" );
        ?>
        <div class="wrap">
            <h1>Manage Master Matches (Sports)</h1>
            <div class="tae-admin-form-box">
                <h2>Add New match</h2>
                <form method="POST">
                    <?php wp_nonce_field( 'tae_master_match', 'tae_nonce' ); ?>
                    <input type="hidden" name="event_type" value="sports">
                    <table class="form-table">
                        <tr><th>Match Name</th><td><input type="text" name="name" required class="regular-text" placeholder="e.g. MI vs CSK"></td></tr>
                        <tr><th>Venue</th><td><input type="text" name="venue" required class="regular-text"></td></tr>
                        <tr><th>Date & Time</th><td><input type="date" name="event_date" required> <input type="time" name="event_time" required></td></tr>
                        <tr><th>Poster URL</th><td><input type="url" name="poster_url" class="regular-text"></td></tr>
                    </table>
                    <?php submit_button( 'Save Match', 'primary', 'tae_save_master' ); ?>
                </form>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>Match</th><th>Venue</th><th>Date</th><th>Poster</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($matches as $m): ?>
                        <tr><td><?php echo $m->id; ?></td><td><strong><?php echo esc_html($m->name); ?></strong></td><td><?php echo esc_html($m->venue); ?></td><td><?php echo esc_html($m->event_date); ?></td><td><img src="<?php echo esc_url($m->poster_url); ?>" width="50"></td><td><a href="?page=tae-master-matches&delete=<?php echo $m->id; ?>" class="button button-link-delete">Delete</a></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_master_data() {
        if ( ! isset( $_POST['tae_save_master'] ) || ! wp_verify_nonce( $_POST['tae_nonce'], 'tae_master_' . $_POST['event_type'] ) ) {
            if ( isset($_GET['delete']) && current_user_can('manage_options') ) {
                global $wpdb;
                $wpdb->delete( "{$wpdb->prefix}tae_master_data", array( 'id' => absint($_GET['delete']) ) );
            }
            return;
        }

        global $wpdb;
        $wpdb->insert( "{$wpdb->prefix}tae_master_data", array(
            'name'        => sanitize_text_field( $_POST['name'] ),
            'type'        => sanitize_text_field( $_POST['event_type'] ),
            'poster_url'  => esc_url_raw( $_POST['poster_url'] ),
            'imdb_rating' => sanitize_text_field( $_POST['imdb_rating'] ?? '' ),
            'venue'       => sanitize_text_field( $_POST['venue'] ?? '' ),
            'event_date'  => sanitize_text_field( $_POST['event_date'] ?? null ),
            'event_time'  => sanitize_text_field( $_POST['event_time'] ?? null ),
        ) );

        $redirect = admin_url( 'admin.php?page=tae-master-' . ($_POST['event_type'] === 'movie' ? 'movies' : 'matches') );
        wp_redirect( $redirect );
        exit;
    }

    public function render_settings() {
        ?>
        <div class="wrap">
            <h1>Marketplace Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'tae_settings_group' ); ?>
                <table class="form-table">
                    <tr><th>Global Commission (%)</th><td><input type="number" name="tae_commission" value="<?php echo esc_attr(get_option('tae_commission', 10)); ?>"></td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
