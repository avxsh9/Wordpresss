<?php
/**
 * TA_Movies_Sports
 *
 * Registers:
 *  - CPT: movies        (slug /movies/{post-slug})
 *  - CPT: sports_events (slug /sports/{post-slug})
 *  - Meta boxes for both CPTs
 *  - REST: /wp-json/custom/v1/movies
 *  - REST: /wp-json/custom/v1/sports
 *  - AJAX: ta_tmdb_search      (TMDB poster proxy, transient-cached)
 *  - AJAX: ta_submit_movie_ticket
 *  - AJAX: ta_submit_sport_ticket
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TA_Movies_Sports {

    const REST_NS = 'custom/v1';

    public function __construct() {
        // CPT registration
        add_action( 'init', array( $this, 'register_cpts' ) );

        // Meta boxes
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post_movies',        array( $this, 'save_movie_meta' ) );
        add_action( 'save_post_sports_events', array( $this, 'save_sport_meta' ) );

        // REST API
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );

        // AJAX – TMDB proxy (logged in + public)
        add_action( 'wp_ajax_ta_tmdb_search',        array( $this, 'ajax_tmdb_search' ) );
        add_action( 'wp_ajax_nopriv_ta_tmdb_search',  array( $this, 'ajax_tmdb_search' ) );

        // AJAX – Movie ticket form submission
        add_action( 'wp_ajax_ta_submit_movie_ticket',       array( $this, 'ajax_submit_movie' ) );
        add_action( 'wp_ajax_nopriv_ta_submit_movie_ticket', array( $this, 'ajax_submit_movie' ) );

        // AJAX – Sport ticket form submission
        add_action( 'wp_ajax_ta_submit_sport_ticket',       array( $this, 'ajax_submit_sport' ) );
        add_action( 'wp_ajax_nopriv_ta_submit_sport_ticket', array( $this, 'ajax_submit_sport' ) );

        // One-time permalink flush and data migration
        if ( get_option( 'ta_movies_sports_flushed_v2' ) !== 'yes' ) {
            add_action( 'init', function() {
                flush_rewrite_rules();

                // Migrate existing 'events' to matching CPTs based on taxonomy
                global $wpdb;
                
                $movie_term = get_term_by( 'slug', 'movies', 'event_cat' );
                if ( $movie_term && ! is_wp_error( $movie_term ) ) {
                    $movie_posts = get_objects_in_term( $movie_term->term_id, 'event_cat' );
                    if ( ! empty( $movie_posts ) ) {
                        $ids = implode(',', array_map('intval', $movie_posts));
                        $wpdb->query( "UPDATE {$wpdb->posts} SET post_type = 'movies' WHERE ID IN ($ids) AND post_type = 'events'" );
                    }
                }

                $sports_term = get_term_by( 'slug', 'sports', 'event_cat' );
                if ( $sports_term && ! is_wp_error( $sports_term ) ) {
                    $sports_posts = get_objects_in_term( $sports_term->term_id, 'event_cat' );
                    if ( ! empty( $sports_posts ) ) {
                        $ids = implode(',', array_map('intval', $sports_posts));
                        $wpdb->query( "UPDATE {$wpdb->posts} SET post_type = 'sports_events' WHERE ID IN ($ids) AND post_type = 'events'" );
                    }
                }

                update_option( 'ta_movies_sports_flushed_v2', 'yes' );
            }, 30 );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CPT Registration
    // ─────────────────────────────────────────────────────────────────────────

    public function register_cpts() {
        // ── Movies ──
        register_post_type( 'movies', array(
            'labels' => array(
                'name'               => 'Movies',
                'singular_name'      => 'Movie',
                'menu_name'          => 'Movies',
                'add_new'            => 'Add New',
                'add_new_item'       => 'Add New Movie',
                'edit_item'          => 'Edit Movie',
                'view_item'          => 'View Movie',
                'all_items'          => 'All Movies',
                'search_items'       => 'Search Movies',
                'not_found'          => 'No movies found.',
                'not_found_in_trash' => 'No movies in Trash.',
            ),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'movies' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 6,
            'menu_icon'          => 'dashicons-video-alt2',
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
            'show_in_rest'       => true,
        ) );

        // ── Sports Events ──
        register_post_type( 'sports_events', array(
            'labels' => array(
                'name'               => 'Sports Events',
                'singular_name'      => 'Sports Event',
                'menu_name'          => 'Sports Events',
                'add_new'            => 'Add New',
                'add_new_item'       => 'Add New Sports Event',
                'edit_item'          => 'Edit Sports Event',
                'view_item'          => 'View Sports Event',
                'all_items'          => 'All Sports Events',
                'search_items'       => 'Search Sports Events',
                'not_found'          => 'No sports events found.',
                'not_found_in_trash' => 'No sports events in Trash.',
            ),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'sports' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 7,
            'menu_icon'          => 'dashicons-awards',
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
            'show_in_rest'       => true,
        ) );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Meta Boxes
    // ─────────────────────────────────────────────────────────────────────────

    public function register_meta_boxes() {
        add_meta_box(
            'ta_movie_details',
            'Movie Details',
            array( $this, 'render_movie_meta_box' ),
            'movies',
            'normal',
            'high'
        );

        add_meta_box(
            'ta_sport_details',
            'Sport Match Details',
            array( $this, 'render_sport_meta_box' ),
            'sports_events',
            'normal',
            'high'
        );
    }

    /** Movie meta box HTML */
    public function render_movie_meta_box( $post ) {
        wp_nonce_field( 'ta_movie_meta_save', 'ta_movie_meta_nonce' );

        $fields = array(
            'movie_name'  => array( 'label' => 'Movie Name',      'type' => 'text',   'placeholder' => 'e.g. Dhurandhar' ),
            'poster_url'  => array( 'label' => 'Poster URL',       'type' => 'url',    'placeholder' => 'https://...' ),
            'language'    => array( 'label' => 'Language',         'type' => 'text',   'placeholder' => 'e.g. Hindi, English' ),
            'movie_rating' => array( 'label' => 'Rating (e.g. 8.5/10)', 'type' => 'text', 'placeholder' => '8.5/10' ),
            'movie_cert'   => array( 'label' => 'Certificate (U/UA/A)', 'type' => 'text', 'placeholder' => 'UA' ),
            'venue'       => array( 'label' => 'Cinema / Venue',   'type' => 'text',   'placeholder' => 'e.g. PVR Cinemas, Mumbai' ),
            'price'       => array( 'label' => 'Price per Ticket (₹)', 'type' => 'number', 'placeholder' => '0' ),
            'quantity'    => array( 'label' => 'Quantity',         'type' => 'number', 'placeholder' => '1' ),
        );
        $this->render_meta_fields( $post, $fields );

        // Poster preview
        $poster = get_post_meta( $post->ID, 'poster_url', true );
        if ( $poster ) {
            echo '<div style="margin-top:15px;"><img src="' . esc_url( $poster ) . '" style="max-height:200px;border-radius:8px;border:1px solid #ddd;"></div>';
        }
    }

    /** Sport meta box HTML */
    public function render_sport_meta_box( $post ) {
        wp_nonce_field( 'ta_sport_meta_save', 'ta_sport_meta_nonce' );

        $fields = array(
            'match_name'   => array( 'label' => 'Match Name',        'type' => 'text',   'placeholder' => 'e.g. IPL 2025 — Match 12' ),
            'teams'        => array( 'label' => 'Teams',              'type' => 'text',   'placeholder' => 'e.g. MI vs CSK' ),
            'match_poster' => array( 'label' => 'Match Poster URL',   'type' => 'url',    'placeholder' => 'https://...' ),
            'sport_date'   => array( 'label' => 'Match Date',         'type' => 'date',   'placeholder' => '' ),
            'sport_time'   => array( 'label' => 'Match Time',         'type' => 'time',   'placeholder' => '' ),
            'venue'        => array( 'label' => 'Stadium / Venue',    'type' => 'text',   'placeholder' => 'e.g. Wankhede Stadium, Mumbai' ),
            'price'        => array( 'label' => 'Price per Ticket (₹)', 'type' => 'number', 'placeholder' => '0' ),
            'quantity'     => array( 'label' => 'Quantity',           'type' => 'number', 'placeholder' => '1' ),
        );
        $this->render_meta_fields( $post, $fields );
    }

    /** Shared helper: render grid of input fields */
    private function render_meta_fields( $post, $fields ) {
        echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;padding:15px;">';
        foreach ( $fields as $key => $field ) {
            $val = get_post_meta( $post->ID, $key, true );
            echo '<div>';
            echo '<label for="ta_' . esc_attr( $key ) . '" style="font-weight:600;display:block;margin-bottom:6px;color:#1d2327;">' . esc_html( $field['label'] ) . '</label>';
            echo '<input type="' . esc_attr( $field['type'] ) . '" id="ta_' . esc_attr( $key ) . '" name="ta_' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" style="width:100%;height:36px;border-radius:4px;border:1px solid #8c8f94;padding:0 8px;">';
            echo '</div>';
        }
        echo '</div>';
    }

    /** Save movie meta */
    public function save_movie_meta( $post_id ) {
        if ( ! isset( $_POST['ta_movie_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ta_movie_meta_nonce'], 'ta_movie_meta_save' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $fields = array( 'movie_name', 'poster_url', 'language', 'movie_rating', 'movie_cert', 'venue', 'price', 'quantity' );
        foreach ( $fields as $field ) {
            if ( isset( $_POST[ 'ta_' . $field ] ) ) {
                $val = sanitize_text_field( wp_unslash( $_POST[ 'ta_' . $field ] ) );
                if ( $field === 'poster_url' ) {
                    $val = esc_url_raw( wp_unslash( $_POST[ 'ta_' . $field ] ) );
                }
                update_post_meta( $post_id, $field, $val );
            }
        }
    }

    /** Save sport meta */
    public function save_sport_meta( $post_id ) {
        if ( ! isset( $_POST['ta_sport_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ta_sport_meta_nonce'], 'ta_sport_meta_save' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $fields = array( 'match_name', 'teams', 'match_poster', 'sport_date', 'sport_time', 'venue', 'price', 'quantity' );
        foreach ( $fields as $field ) {
            if ( isset( $_POST[ 'ta_' . $field ] ) ) {
                $val = sanitize_text_field( wp_unslash( $_POST[ 'ta_' . $field ] ) );
                if ( $field === 'match_poster' ) {
                    $val = esc_url_raw( wp_unslash( $_POST[ 'ta_' . $field ] ) );
                }
                update_post_meta( $post_id, $field, $val );
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REST API Routes
    // ─────────────────────────────────────────────────────────────────────────

    public function register_routes() {
        register_rest_route( self::REST_NS, '/movies', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_movies' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'per_page' => array( 'default' => 500, 'sanitize_callback' => 'absint' ),
                's'        => array( 'default' => '',  'sanitize_callback' => 'sanitize_text_field' ),
                'language' => array( 'default' => '',  'sanitize_callback' => 'sanitize_text_field' ),
            ),
        ) );

        register_rest_route( self::REST_NS, '/sports', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_sports' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'per_page' => array( 'default' => 500, 'sanitize_callback' => 'absint' ),
                's'        => array( 'default' => '',  'sanitize_callback' => 'sanitize_text_field' ),
            ),
        ) );
    }

    /** GET /wp-json/custom/v1/movies */
    public function get_movies( $request ) {
        $args = array(
            'post_type'      => 'movies',
            'posts_per_page' => $request->get_param( 'per_page' ),
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $search = $request->get_param( 's' );
        if ( $search ) {
            $args['s'] = $search;
        }

        // Language filter
        $lang = strtolower( $request->get_param( 'language' ) );
        if ( $lang ) {
            $args['meta_query'] = array(
                array(
                    'key'     => 'language',
                    'value'   => $lang,
                    'compare' => 'LIKE',
                ),
            );
        }

        $query   = new WP_Query( $args );
        $movies  = array();

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $movies[] = $this->format_movie( get_post() );
            }
        }
        wp_reset_postdata();

        return rest_ensure_response( $movies );
    }

    /** GET /wp-json/custom/v1/sports */
    public function get_sports( $request ) {
        $args = array(
            'post_type'      => 'sports_events',
            'posts_per_page' => $request->get_param( 'per_page' ),
            'post_status'    => 'publish',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => 'sport_date',
                    'value'   => date('Y-m-d'),
                    'compare' => '>=',
                    'type'    => 'DATE'
                ),
                array(
                    'key'     => 'sport_date',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key'     => 'sport_date',
                    'value'   => '',
                    'compare' => '='
                )
            ),
            'orderby'        => 'meta_value',
            'meta_key'       => 'sport_date',
            'order'          => 'ASC',
        );

        $search = $request->get_param( 's' );
        if ( $search ) {
            $args['s'] = $search;
        }

        $query  = new WP_Query( $args );
        $sports = array();

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $sports[] = $this->format_sport( get_post() );
            }
        }
        wp_reset_postdata();

        return rest_ensure_response( $sports );
    }

    /** Format a movie post for the REST response */
    private function format_movie( $post ) {
        $poster = get_post_meta( $post->ID, 'poster_url', true );
        // Fallback to WP featured image
        if ( ! $poster && has_post_thumbnail( $post->ID ) ) {
            $poster = get_the_post_thumbnail_url( $post->ID, 'large' );
        }
        if ( ! $poster ) {
            $poster = 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=500&h=750&q=80';
        }

        return array(
            'id'          => $post->ID,
            'title'       => $post->post_title,
            'movie_name'  => get_post_meta( $post->ID, 'movie_name', true ) ?: $post->post_title,
            'poster_url'  => $poster,
            'language'    => get_post_meta( $post->ID, 'language', true ) ?: '',
            'movie_rating'=> get_post_meta( $post->ID, 'movie_rating', true ) ?: '8.0/10',
            'movie_cert'  => get_post_meta( $post->ID, 'movie_cert', true ) ?: 'UA',
            'venue'       => get_post_meta( $post->ID, 'venue', true ) ?: '',
            'price'       => (float) get_post_meta( $post->ID, 'price', true ),
            'quantity'    => (int) get_post_meta( $post->ID, 'quantity', true ),
            'url'         => get_permalink( $post->ID ),
            'description' => wp_strip_all_tags( $post->post_content ),
        );
    }

    /** Format a sports_events post for the REST response */
    private function format_sport( $post ) {
        $poster = get_post_meta( $post->ID, 'match_poster', true );
        if ( ! $poster && has_post_thumbnail( $post->ID ) ) {
            $poster = get_the_post_thumbnail_url( $post->ID, 'large' );
        }
        if ( ! $poster ) {
            $poster = 'https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?auto=format&fit=crop&w=800&q=80';
        }

        $date = get_post_meta( $post->ID, 'sport_date', true );
        $time = get_post_meta( $post->ID, 'sport_time', true );

        return array(
            'id'          => $post->ID,
            'title'       => $post->post_title,
            'match_name'  => get_post_meta( $post->ID, 'match_name', true ) ?: $post->post_title,
            'teams'       => get_post_meta( $post->ID, 'teams', true ) ?: '',
            'match_poster'=> $poster,
            'date'        => $date ?: '',
            'time'        => $time ?: '',
            'venue'       => get_post_meta( $post->ID, 'venue', true ) ?: '',
            'price'       => (float) get_post_meta( $post->ID, 'price', true ),
            'quantity'    => (int) get_post_meta( $post->ID, 'quantity', true ),
            'url'         => get_permalink( $post->ID ),
            'description' => wp_strip_all_tags( $post->post_content ),
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: TMDB Poster Search (proxied server-side, transient-cached 1 hour)
    // ─────────────────────────────────────────────────────────────────────────

    public function ajax_tmdb_search() {
        check_ajax_referer( 'ta_tmdb_nonce', 'nonce' );

        $query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
        if ( strlen( $query ) < 2 ) {
            wp_send_json_success( array() );
        }

        $tmdb_key = get_option( 'ta_tmdb_api_key', '' );
        if ( ! $tmdb_key ) {
            wp_send_json_error( array( 'message' => 'TMDB API key not configured. Please set it in Settings → TickerAdda Settings.' ) );
        }

        $cache_key = 'ta_tmdb_' . md5( strtolower( $query ) );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            wp_send_json_success( $cached );
        }

        $url = add_query_arg( array(
            'api_key'       => $tmdb_key,
            'query'         => rawurlencode( $query ),
            'language'      => 'en-US',
            'include_adult' => 'false',
            'page'          => 1,
        ), 'https://api.themoviedb.org/3/search/movie' );

        $response = wp_remote_get( $url, array( 'timeout' => 8 ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => 'TMDB request failed: ' . $response->get_error_message() ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $results = isset( $body['results'] ) ? $body['results'] : array();

        $movies = array();
        foreach ( array_slice( $results, 0, 8 ) as $m ) {
            $poster = ! empty( $m['poster_path'] )
                ? 'https://image.tmdb.org/t/p/w500' . $m['poster_path']
                : '';
            $movies[] = array(
                'id'         => $m['id'],
                'title'      => $m['title'],
                'poster_url' => $poster,
                'year'       => isset( $m['release_date'] ) ? substr( $m['release_date'], 0, 4 ) : '',
            );
        }

        // Cache for 1 hour
        set_transient( $cache_key, $movies, HOUR_IN_SECONDS );

        wp_send_json_success( $movies );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: Seller Form — Submit Movie Ticket
    // ─────────────────────────────────────────────────────────────────────────

    public function ajax_submit_movie() {
        check_ajax_referer( 'ta_sell_movie_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'You must be logged in to sell tickets.' ) );
        }

        // Sanitize inputs
        $movie_name = sanitize_text_field( wp_unslash( $_POST['movie_name'] ?? '' ) );
        $poster_url = esc_url_raw( wp_unslash( $_POST['poster_url'] ?? '' ) );
        $language   = sanitize_text_field( wp_unslash( $_POST['language'] ?? '' ) );
        $venue      = sanitize_text_field( wp_unslash( $_POST['venue'] ?? '' ) );
        $price      = floatval( $_POST['price'] ?? 0 );
        $quantity   = absint( $_POST['quantity'] ?? 1 );

        if ( ! $movie_name ) {
            wp_send_json_error( array( 'message' => 'Movie name is required.' ) );
        }
        if ( ! $language ) {
            wp_send_json_error( array( 'message' => 'Language is required. Please fill it in manually.' ) );
        }
        if ( ! $venue ) {
            wp_send_json_error( array( 'message' => 'Venue is required.' ) );
        }
        if ( $price <= 0 ) {
            wp_send_json_error( array( 'message' => 'Price must be greater than ₹0.' ) );
        }
        if ( $quantity < 1 ) {
            wp_send_json_error( array( 'message' => 'Quantity must be at least 1.' ) );
        }

        $post_id = wp_insert_post( array(
            'post_type'    => 'movies',
            'post_title'   => $movie_name,
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
            'post_content' => '',
        ) );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array( 'message' => 'Could not create listing: ' . $post_id->get_error_message() ) );
        }

        update_post_meta( $post_id, 'movie_name',  $movie_name );
        update_post_meta( $post_id, 'poster_url',  $poster_url );
        update_post_meta( $post_id, 'language',    $language );
        update_post_meta( $post_id, 'venue',       $venue );
        update_post_meta( $post_id, 'price',       $price );
        update_post_meta( $post_id, 'quantity',    $quantity );
        update_post_meta( $post_id, '_seller_id',  get_current_user_id() );

        wp_send_json_success( array(
            'message' => 'Your movie ticket listing has been published!',
            'post_id' => $post_id,
            'url'     => get_permalink( $post_id ),
        ) );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: Seller Form — Submit Sport Ticket
    // ─────────────────────────────────────────────────────────────────────────

    public function ajax_submit_sport() {
        check_ajax_referer( 'ta_sell_sport_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'You must be logged in to sell tickets.' ) );
        }

        $match_name   = sanitize_text_field( wp_unslash( $_POST['match_name'] ?? '' ) );
        $teams        = sanitize_text_field( wp_unslash( $_POST['teams'] ?? '' ) );
        $match_poster = esc_url_raw( wp_unslash( $_POST['match_poster'] ?? '' ) );
        $date         = sanitize_text_field( wp_unslash( $_POST['sport_date'] ?? '' ) );
        $time         = sanitize_text_field( wp_unslash( $_POST['sport_time'] ?? '' ) );
        $venue        = sanitize_text_field( wp_unslash( $_POST['venue'] ?? '' ) );
        $price        = floatval( $_POST['price'] ?? 0 );
        $quantity     = absint( $_POST['quantity'] ?? 1 );

        if ( ! $match_name ) {
            wp_send_json_error( array( 'message' => 'Match name is required.' ) );
        }
        if ( ! $teams ) {
            wp_send_json_error( array( 'message' => 'Teams field is required (e.g. MI vs CSK).' ) );
        }
        if ( ! $date || ! $time || ! $venue ) {
            wp_send_json_error( array( 'message' => 'Date, time, and venue are required.' ) );
        }
        if ( $price <= 0 ) {
            wp_send_json_error( array( 'message' => 'Price must be greater than ₹0.' ) );
        }
        if ( $quantity < 1 ) {
            wp_send_json_error( array( 'message' => 'Quantity must be at least 1.' ) );
        }

        $post_id = wp_insert_post( array(
            'post_type'    => 'sports_events',
            'post_title'   => $match_name . ( $teams ? ' — ' . $teams : '' ),
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
            'post_content' => '',
        ) );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array( 'message' => 'Could not create listing: ' . $post_id->get_error_message() ) );
        }

        update_post_meta( $post_id, 'match_name',   $match_name );
        update_post_meta( $post_id, 'teams',         $teams );
        update_post_meta( $post_id, 'match_poster',  $match_poster );
        update_post_meta( $post_id, 'sport_date',    $date );
        update_post_meta( $post_id, 'sport_time',    $time );
        update_post_meta( $post_id, 'venue',         $venue );
        update_post_meta( $post_id, 'price',         $price );
        update_post_meta( $post_id, 'quantity',      $quantity );
        update_post_meta( $post_id, '_seller_id',    get_current_user_id() );

        wp_send_json_success( array(
            'message' => 'Your sports ticket listing has been published!',
            'post_id' => $post_id,
            'url'     => get_permalink( $post_id ),
        ) );
    }
}
