<?php
/**
 * TAE_API — Handles REST Endpoints for TicketAdda
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TAE_API {
    const NS = 'ta/v2';

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( self::NS, '/master-data', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_master_data' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( self::NS, '/tickets', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'create_ticket' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( self::NS, '/events', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_listings' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'per_page' => array( 'default' => 12, 'sanitize_callback' => 'absint' ),
                'cat'      => array( 'sanitize_callback' => 'sanitize_text_field' ),
            ),
        ) );
    }

    public function get_master_data( $request ) {
        global $wpdb;
        $type = $request->get_param( 'type' );
        $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tae_master_data WHERE type = %s AND status = 'active' ORDER BY name ASC", $type ) );
        return rest_ensure_response( $results );
    }

    public function create_ticket( $request ) {
        $params = $request->get_params();
        $user_id = get_current_user_id();
        $cat = sanitize_text_field( $params['event_category'] );
        $master_id = absint( $params['master_id'] );

        // Validate master data
        global $wpdb;
        $master = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tae_master_data WHERE id = %d", $master_id ) );
        if ( ! $master ) return new WP_Error( 'invalid_master', 'Invalid master event selection.', array('status' => 400) );

        $post_title = $master->name . ' - ' . ( $cat === 'movie' ? $params['venue'] : $master->venue );
        
        $post_id = wp_insert_post( array(
            'post_title'   => sanitize_text_field( $post_title ),
            'post_type'    => 'event_listing',
            'post_status'  => 'pending',
            'post_author'  => $user_id,
        ) );

        if ( is_wp_error( $post_id ) ) return $post_id;

        wp_set_object_terms( $post_id, $cat, 'event_category' );

        // Basic meta
        update_post_meta( $post_id, 'tae_master_id', $master_id );
        update_post_meta( $post_id, 'tae_price', (float)$params['price'] );
        update_post_meta( $post_id, 'tae_seats', sanitize_text_field($params['seats']) );
        update_post_meta( $post_id, 'tae_seller_id', $user_id );
        update_post_meta( $post_id, 'tae_status', 'active' );
        update_post_meta( $post_id, 'tae_event_type', $cat );

        if ( $cat === 'movie' ) {
            update_post_meta( $post_id, 'tae_date', sanitize_text_field($params['date']) );
            update_post_meta( $post_id, 'tae_time', sanitize_text_field($params['time']) );
            update_post_meta( $post_id, 'tae_venue', sanitize_text_field($params['venue']) );
        } else {
            // Lock to admin match data
            update_post_meta( $post_id, 'tae_date', $master->event_date );
            update_post_meta( $post_id, 'tae_time', $master->event_time );
            update_post_meta( $post_id, 'tae_venue', $master->venue );
        }

        return rest_ensure_response( array( 'success' => true, 'id' => $post_id ) );
    }

    public function get_listings( $request ) {
        $args = array(
            'post_type'      => 'event_listing',
            'post_status'    => 'publish',
            'posts_per_page' => $request->get_param( 'per_page' ),
            'meta_query'     => array(
                array( 'key' => 'tae_status', 'value' => 'active' )
            )
        );

        $cat = $request->get_param( 'cat' );
        if ( $cat ) {
            $args['tax_query'] = array( array( 'taxonomy' => 'event_category', 'field' => 'slug', 'terms' => $cat ) );
        }

        $query = new WP_Query( $args );
        $data  = array();

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $data[] = $this->format_listing( get_post() );
            }
        }
        wp_reset_postdata();

        return rest_ensure_response( $data );
    }

    private function format_listing( $post ) {
        $master_id = get_post_meta( $post->ID, 'tae_master_id', true );
        global $wpdb;
        $master = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tae_master_data WHERE id = %d", $master_id ) );
        
        $cat = get_the_terms( $post->ID, 'event_category' )[0]->slug;

        return array(
            'id'           => $post->ID,
            'title'        => $master->name,
            'price'        => (float) get_post_meta( $post->ID, 'tae_price', true ),
            'date'         => get_post_meta( $post->ID, 'tae_date', true ),
            'time'         => get_post_meta( $post->ID, 'tae_time', true ),
            'venue'        => get_post_meta( $post->ID, 'tae_venue', true ),
            'poster'       => $master->poster_url,
            'imdb'         => $cat === 'movie' ? $master->imdb_rating : '',
            'category'     => $cat,
            'permalink'    => get_permalink($post->ID),
        );
    }
}
