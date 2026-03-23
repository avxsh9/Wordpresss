<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TA_Events {

    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_action( 'init', array( $this, 'register_taxonomy' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_event_meta_boxes' ) );
        add_action( 'save_post_events', array( $this, 'save_event_meta' ) );
        
        // Admin column customisation
        add_filter( 'manage_events_posts_columns', array( $this, 'add_custom_admin_columns' ) );
        add_action( 'manage_events_posts_custom_column', array( $this, 'render_custom_admin_columns' ), 10, 2 );
        add_filter( 'manage_edit-events_sortable_columns', array( $this, 'sortable_admin_columns' ) );
        
        // One-time flush — incremented to v5 to re-purge after events limit fix
        if ( get_option( 'ta_event_slug_flushed_v5' ) !== 'yes' ) {
            add_action( 'init', function() {
                flush_rewrite_rules();
                do_action( 'litespeed_purge_all' );  // Always purge, even without LSCWP active
                update_option( 'ta_event_slug_flushed_v5', 'yes' );
            }, 20 );
        }
    }

    /**
     * Register Event Custom Post Type
     */
    public function register_cpt() {
        $labels = array(
            'name'               => 'Events',
            'singular_name'      => 'Event',
            'menu_name'          => 'Events',
            'name_admin_bar'     => 'Event',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Event',
            'new_item'           => 'New Event',
            'edit_item'          => 'Edit Event',
            'view_item'          => 'View Event',
            'all_items'          => 'All Events',
            'search_items'       => 'Search Events',
            'parent_item_colon'  => 'Parent Events:',
            'not_found'          => 'No events found.',
            'not_found_in_trash' => 'No events found in Trash.',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'events' ), // Reverting to 'events' for standard archive routing
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-calendar-alt',
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
            'show_in_rest'       => true,
        );

        register_post_type( 'events', $args );
    }

    /**
     * Register Event Category Taxonomy
     */
    public function register_taxonomy() {
        $labels = array(
            'name'              => 'Event Categories',
            'singular_name'     => 'Event Category',
            'search_items'      => 'Search Event Categories',
            'all_items'         => 'All Event Categories',
            'parent_item'       => 'Parent Event Category',
            'parent_item_colon' => 'Parent Event Category:',
            'edit_item'         => 'Edit Event Category',
            'update_item'       => 'Update Event Category',
            'add_new_item'      => 'Add New Event Category',
            'new_item_name'     => 'New Event Category Name',
            'menu_name'         => 'Categories',
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'event-category' ),
            'show_in_rest'      => true,
        );

        register_taxonomy( 'event_cat', array( 'events', 'movies', 'sports_events' ), $args );
        
        // Register default terms if they don't exist
        $this->ensure_default_categories();
    }

    private function ensure_default_categories() {
        $categories = array('Sports', 'Music', 'Movies', 'Comedy', 'Theatre', 'Other');
        foreach ($categories as $cat) {
            if (!term_exists($cat, 'event_cat')) {
                wp_insert_term($cat, 'event_cat');
            }
        }
    }

    /**
     * Admin Meta Boxes
     */
    public function add_event_meta_boxes() {
        add_meta_box(
            'ta_event_details',
            'Event Details',
            array( $this, 'render_event_details_meta_box' ),
            'events',
            'normal',
            'high'
        );
    }

    public function render_event_details_meta_box( $post ) {
        $date     = get_post_meta( $post->ID, 'event_date', true );
        $time     = get_post_meta( $post->ID, 'event_time', true );
        $location = get_post_meta( $post->ID, 'event_location', true );
        $poster   = get_post_meta( $post->ID, 'poster_url', true );
        $rating   = get_post_meta( $post->ID, 'movie_rating', true );
        $cert     = get_post_meta( $post->ID, 'movie_cert', true );
        $lang     = get_post_meta( $post->ID, 'language', true );
        $teams    = get_post_meta( $post->ID, 'teams', true );

        wp_nonce_field( 'ta_event_meta_save', 'ta_event_meta_nonce' );
        ?>
        <div style="padding: 15px; background: #fff; border-radius: 8px; border: 1px solid #ccd0d4;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <p>
                    <label style="font-weight:600;display:block;margin-bottom:8px;">Event Date:</label>
                    <input type="date" name="ta_event_date" value="<?php echo esc_attr( $date ); ?>" style="width:100%;">
                </p>
                <p>
                    <label style="font-weight:600;display:block;margin-bottom:8px;">Event Time:</label>
                    <input type="time" name="ta_event_time" value="<?php echo esc_attr( $time ); ?>" style="width:100%;">
                </p>
            </div>
            <p>
                <label style="font-weight:600;display:block;margin-bottom:8px;">Venue / Location:</label>
                <input type="text" name="ta_event_location" value="<?php echo esc_attr( $location ); ?>" style="width:100%;" placeholder="e.g. Jio World Garden, Mumbai">
            </p>
            <p>
                <label style="font-weight:600;display:block;margin-bottom:8px;">Poster URL (Direct Link):</label>
                <input type="url" name="ta_poster_url" value="<?php echo esc_attr( $poster ); ?>" style="width:100%;" placeholder="https://...">
            </p>
            
            <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
            <h4 style="margin-top:0;">Advanced Details (Movies / Sports)</h4>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                <p>
                    <label>Rating (e.g. 8.5/10):</label>
                    <input type="text" name="ta_movie_rating" value="<?php echo esc_attr( $rating ); ?>" style="width:100%;">
                </p>
                <p>
                    <label>Certificate (UA/A):</label>
                    <input type="text" name="ta_movie_cert" value="<?php echo esc_attr( $cert ); ?>" style="width:100%;">
                </p>
                <p>
                    <label>Language:</label>
                    <input type="text" name="ta_language" value="<?php echo esc_attr( $lang ); ?>" style="width:100%;">
                </p>
            </div>
            <p>
                <label>Teams (For Sports):</label>
                <input type="text" name="ta_teams" value="<?php echo esc_attr( $teams ); ?>" style="width:100%;" placeholder="Team A vs Team B">
            </p>
        </div>
        <?php
    }

    public function save_event_meta( $post_id ) {
        if ( ! isset( $_POST['ta_event_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ta_event_meta_nonce'], 'ta_event_meta_save' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        if ( isset( $_POST['ta_event_date'] ) ) {
            update_post_meta( $post_id, 'event_date', sanitize_text_field( $_POST['ta_event_date'] ) );
        }
        if ( isset( $_POST['ta_event_time'] ) ) {
            update_post_meta( $post_id, 'event_time', sanitize_text_field( $_POST['ta_event_time'] ) );
        }
        if ( isset( $_POST['ta_event_location'] ) ) {
            update_post_meta( $post_id, 'event_location', sanitize_text_field( $_POST['ta_event_location'] ) );
        }
        if ( isset( $_POST['ta_poster_url'] ) ) {
            update_post_meta( $post_id, 'poster_url', esc_url_raw( $_POST['ta_poster_url'] ) );
        }
        if ( isset( $_POST['ta_movie_rating'] ) ) {
            update_post_meta( $post_id, 'movie_rating', sanitize_text_field( $_POST['ta_movie_rating'] ) );
        }
        if ( isset( $_POST['ta_movie_cert'] ) ) {
            update_post_meta( $post_id, 'movie_cert', sanitize_text_field( $_POST['ta_movie_cert'] ) );
        }
        if ( isset( $_POST['ta_language'] ) ) {
            update_post_meta( $post_id, 'language', sanitize_text_field( $_POST['ta_language'] ) );
        }
        if ( isset( $_POST['ta_teams'] ) ) {
            update_post_meta( $post_id, 'teams', sanitize_text_field( $_POST['ta_teams'] ) );
        }
    }

    /**
     * Custom Admin Columns
     */
    public function add_custom_admin_columns( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $title ) {
            if ( $key === 'date' ) {
                $new_columns['event_thumbnail'] = 'Image';
                $new_columns['event_date_time'] = 'Event Date & Time';
                $new_columns['event_location']  = 'Venue';
            }
            $new_columns[$key] = $title;
        }
        return $new_columns;
    }

    public function render_custom_admin_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'event_thumbnail':
                if ( has_post_thumbnail( $post_id ) ) {
                    echo get_the_post_thumbnail( $post_id, array( 60, 60 ), array( 'style' => 'border-radius:6px; border: 1px solid #e1e3e5;' ) );
                } else {
                    echo '<span style="display:inline-block; width:60px; height:60px; background:#f0f0f1; border-radius:6px; border: 1px dashed #ccd0d4; text-align:center; line-height:60px; color:#9ca3af;"><i class="dashicons dashicons-format-image" style="font-size:24px; margin-top:18px;"></i></span>';
                }
                break;
            case 'event_date_time':
                $date = get_post_meta( $post_id, 'event_date', true );
                $time = get_post_meta( $post_id, 'event_time', true );
                if ( $date ) {
                    echo '<strong>' . date( 'd M Y', strtotime( $date ) ) . '</strong>';
                    echo $time ? '<br><span style="color:#646970; font-size:12px;">' . date( 'h:i A', strtotime( $time ) ) . '</span>' : '';
                } else {
                    echo '<span style="color:#d63638;">Not Set</span>';
                }
                break;
            case 'event_location':
                $location = get_post_meta( $post_id, 'event_location', true );
                echo $location ? esc_html( $location ) : '<span style="color:#9ca3af;">TBD</span>';
                break;
        }
    }

    public function sortable_admin_columns( $columns ) {
        $columns['event_date_time'] = 'event_date';
        return $columns;
    }

    /**
     * REST API Routes
     */
    public function register_routes() {
        register_rest_route( TA_REST_NS, '/events-list', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_events' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( TA_REST_NS, '/events-list/(?P<id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_event' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( TA_REST_NS, '/events-list/(?P<id>\d+)/tickets', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_event_tickets' ),
            'permission_callback' => '__return_true',
        ) );
    }

    public function get_events( $request ) {
        $category = $request->get_param('category');
        $search = $request->get_param('s');
        $type = $request->get_param('type'); // trending, popular, recent
        $per_page = $request->get_param('per_page') ? (int) $request->get_param('per_page') : 500;

        $args = array(
            'post_type'      => array( 'events', 'movies', 'sports_events' ),
            'posts_per_page' => $per_page,
            'post_status'    => 'publish',
            'orderby'        => 'meta_value',
            'meta_key'       => 'event_date',
            'order'          => 'ASC',
        );

        if ($category && $category !== 'all') {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'event_cat',
                    'field'    => 'slug',
                    'terms'    => $category,
                ),
            );
        }

        if ($search) {
            $args['s'] = $search;
        }

        if ($type === 'recent') {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        }

        // Logic for trending/popular can be based on most tickets or custom meta
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $events[] = $this->format_event(get_post());
            }
        }
        wp_reset_postdata();

        return rest_ensure_response($events);
    }

    public function get_event( $request ) {
        $id = $request['id'];
        $post = get_post($id);
        if (!$post || !in_array($post->post_type, array('events', 'movies', 'sports_events'))) {
            return new WP_Error('no_event', 'Event not found', array('status' => 404));
        }
        return rest_ensure_response($this->format_event($post));
    }

    public function get_event_tickets( $request ) {
        $id = $request['id'];
        $tickets_class = new TA_Tickets();
        return $tickets_class->get_approved_tickets($request, $id);
    }

    public function format_event($post) {
        if (!$post) return array();
        $post_id = $post->ID;

        // Poster / Thumbnail
        $thumbnail = get_the_post_thumbnail_url($post_id, 'large');
        if (!$thumbnail) {
            // Fallbacks for specific types
            if ($post->post_type === 'movies') {
                $thumbnail = get_post_meta($post_id, 'poster_url', true);
            } elseif ($post->post_type === 'sports_events') {
                $thumbnail = get_post_meta($post_id, 'match_poster', true);
            }
        }
        if (!$thumbnail) {
            $thumbnail = 'https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?auto=format&fit=crop&w=800&q=80';
        }

        // Pricing and Tickets
        global $wpdb;
        $table_name = $wpdb->prefix . 'ta_tickets';
        
        $min_price = $wpdb->get_var($wpdb->prepare(
            "SELECT MIN(price) FROM $table_name WHERE event_id = %d AND status IN ('approved', 'available')",
            $post_id
        )) ?: 0;

        $ticket_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE event_id = %d AND status IN ('approved', 'available')",
            $post_id
        )) ?: 0;

        // Category Name
        $cats = get_the_terms($post_id, 'event_cat');
        $category_name = !empty($cats) ? $cats[0]->name : 'Event';
        $category_slug = !empty($cats) ? $cats[0]->slug : 'event';

        $data = array(
            'id'          => $post_id,
            'name'        => get_the_title($post_id),
            'url'         => get_permalink($post_id),
            'image'       => $thumbnail,
            'location'    => get_post_meta($post_id, 'venue', true) ?: get_post_meta($post_id, 'location', true) ?: 'Venue TBD',
            'date'        => get_post_meta($post_id, 'date', true) ?: get_post_meta($post_id, 'event_date', true) ?: '',
            'time'        => get_post_meta($post_id, 'time', true) ?: get_post_meta($post_id, 'event_time', true) ?: '',
            'price'       => (float) $min_price,
            'ticketCount' => $ticket_count,
            'category'    => $category_name, // Fixed to use actual name
            'category_slug' => $category_slug,
            'post_type'    => $post->post_type,
        );

        // Add extra fields for specialized views
        if ($post->post_type === 'movies') {
            $data['movieRating']   = get_post_meta($post_id, 'movie_rating', true) ?: get_post_meta($post_id, 'imdb_rating', true) ?: '8.5/10';
            $data['movieCert']     = get_post_meta($post_id, 'movie_cert', true) ?: 'UA';
            $data['movieLanguage'] = get_post_meta($post_id, 'language', true) ?: 'Hindi';
        }
        if ($post->post_type === 'sports_events') {
            $data['teams'] = get_post_meta($post_id, 'teams', true) ?: '';
        }

        return $data;
    }
}
