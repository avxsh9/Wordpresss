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

        register_taxonomy( 'event_cat', array( 'events' ), $args );
        
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
        wp_nonce_field( 'ta_event_meta_save', 'ta_event_meta_nonce' );
        ?>
        <div style="padding: 15px; background: #fff; border-radius: 8px; border: 1px solid #ccd0d4; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <p style="margin:0;">
                    <label for="ta_event_date" style="font-weight:600;display:block;margin-bottom:8px; color: #1d2327;">Event Date:</label>
                    <input type="date" id="ta_event_date" name="ta_event_date" value="<?php echo esc_attr( $date ); ?>" style="width:100%; height: 35px; border-radius: 4px; border: 1px solid #8c8f94;">
                </p>
                <p style="margin:0;">
                    <label for="ta_event_time" style="font-weight:600;display:block;margin-bottom:8px; color: #1d2327;">Event Time:</label>
                    <input type="time" id="ta_event_time" name="ta_event_time" value="<?php echo esc_attr( $time ); ?>" style="width:100%; height: 35px; border-radius: 4px; border: 1px solid #8c8f94;">
                </p>
            </div>
            <p style="margin:0;">
                <label for="ta_event_location" style="font-weight:600;display:block;margin-bottom:8px; color: #1d2327;">Venue / Location:</label>
                <input type="text" id="ta_event_location" name="ta_event_location" value="<?php echo esc_attr( $location ); ?>" placeholder="e.g. Jio World Garden, Mumbai" style="width:100%; height: 35px; border-radius: 4px; border: 1px solid #8c8f94;">
            </p>
            <p style="margin-top:20px; font-size: 13px; color: #646970; border-top: 1px solid #f0f0f1; padding-top: 15px;">
                <i class="dashicons dashicons-info" style="vertical-align: middle; font-size: 18px;"></i> Tip: High-quality event images make your listings stand out.
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
        register_rest_route( TA_REST_NS, '/events', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_events' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( TA_REST_NS, '/events/(?P<id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_event' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( TA_REST_NS, '/events/(?P<id>\d+)/tickets', array(
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
            'post_type'      => 'events',
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
        $events = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $events[] = $this->format_event(get_post());
            }
        }
        wp_reset_postdata();

        // Tell LiteSpeed Cache not to cache REST API responses for events
        do_action( 'litespeed_tag_add', 'ta_events_nostore' );
        do_action( 'litespeed_control_set_nocache', 'events-api' );

        return rest_ensure_response($events);
    }

    public function get_event($request) {
        $id = $request['id'];
        $post = get_post($id);
        if (!$post || $post->post_type !== 'events') {
            return new WP_Error('no_event', 'Event not found', array('status' => 404));
        }
        return rest_ensure_response($this->format_event($post));
    }

    public function get_event_tickets($request) {
        $id = $request['id'];
        $tickets_class = new TA_Tickets();
        
        // We need to filter tickets by event_id. 
        // Assuming we'll update TA_Tickets to support event_id filter.
        return $tickets_class->get_approved_tickets($request, $id);
    }

    private function format_event($post) {
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        $image = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'large') : $this->get_placeholder($post->ID);
        
        $categories = wp_get_post_terms($post->ID, 'event_cat', array('fields' => 'slugs'));
        $date = get_post_meta($post->ID, 'event_date', true);
        $category = !empty($categories) ? $categories[0] : 'other';
        
        $response = array(
            'id'          => $post->ID,
            'name'        => $post->post_title,
            'date'        => $date ?: '',
            'time'        => get_post_meta($post->ID, 'event_time', true) ?: '',
            'location'    => get_post_meta($post->ID, 'event_location', true) ?: 'Venue TBD',
            'image'       => $image,
            'description' => $post->post_content,
            'category'    => $category,
            'url'         => get_permalink($post->ID),
            'ticketCount' => $this->get_ticket_count($post->ID)
        );
        
        // Add movie-specific metadata if this is a movie
        if ($category === 'movies') {
            $response['movieRating']  = get_post_meta($post->ID, 'movie_rating', true) ?: '8.0/10';
            $response['movieCert']    = get_post_meta($post->ID, 'movie_certificate', true) ?: 'UA';
            $response['movieLanguage'] = get_post_meta($post->ID, 'movie_language', true) ?: 'Hindi';
            $response['movieVotes']   = get_post_meta($post->ID, 'movie_votes', true) ?: 'New';
        }
        
        return $response;
    }

    private function get_ticket_count($event_id) {
        global $wpdb;
        $table = TA_Database::tickets_table();
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE event_id = %d AND status = 'approved'", $event_id));
    }

    private function get_placeholder($post_id) {
        // Simple logic based on category or random high quality image
        return 'https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?auto=format&fit=crop&w=800&q=80';
    }
}
