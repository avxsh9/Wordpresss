<?php
/**
 * UEM_API — Handles REST Endpoints for Universal Events
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class UEM_API {
    const NS = 'uem/v1';

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( self::NS, '/master-events', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_master_events' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( self::NS, '/tickets', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'create_ticket' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( self::NS, '/listings', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_listings' ),
            'permission_callback' => '__return_true',
        ) );
    }

    public function get_master_events() {
        global $wpdb;
        $results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}uem_master_events WHERE status = 'active' ORDER BY event_name ASC" );
        return rest_ensure_response( $results );
    }

    public function create_ticket( $request ) {
        $params = $request->get_params();
        $user_id = get_current_user_id();
        $master_id = absint( $params['master_id'] );

        // Validate master data
        global $wpdb;
        $master = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}uem_master_events WHERE id = %d", $master_id ) );
        if ( ! $master ) return new WP_Error( 'invalid_master', 'Event not found.', array('status' => 400) );

        $post_title = $master->event_name . ' (' . $master->venue . ')';
        
        $post_id = wp_insert_post( array(
            'post_title'   => sanitize_text_field( $post_title ),
            'post_type'    => 'event_listing',
            'post_status'  => 'pending',
            'post_author'  => $user_id,
        ) );

        if ( is_wp_error( $post_id ) ) return $post_id;

        wp_set_object_terms( $post_id, $master->event_category, 'event_category' );

        update_post_meta( $post_id, 'uem_master_id', $master_id );
        update_post_meta( $post_id, 'uem_price', (float)$params['price'] );
        update_post_meta( $post_id, 'uem_seats', sanitize_text_field($params['seats']) );
        update_post_meta( $post_id, 'uem_seller_id', $user_id );
        update_post_meta( $post_id, 'uem_status', 'active' );

        return rest_ensure_response( array( 'success' => true, 'id' => $post_id ) );
    }

    public function get_listings( $request ) {
        $args = array(
            'post_type'      => 'event_listing',
            'post_status'    => 'publish',
            'posts_per_page' => $request->get_param('per_page') ?: 12,
            'meta_query'     => array(
                array( 'key' => 'uem_status', 'value' => 'active' )
            )
        );

        if ( $request->get_param('cat') ) {
            $args['tax_query'] = array( array( 'taxonomy' => 'event_category', 'field' => 'slug', 'terms' => $request->get_param('cat') ) );
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
        $master_id = get_post_meta( $post->ID, 'uem_master_id', true );
        global $wpdb;
        $master = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}uem_master_events WHERE id = %d", $master_id ) );
        if ( ! $master ) return array();

        return array(
            'id'           => $post->ID,
            'title'        => $master->event_name,
            'price'        => (float) get_post_meta( $post->ID, 'uem_price', true ),
            'date'         => $master->event_date,
            'venue'        => $master->venue,
            'poster'       => $master->event_poster,
            'imdb'         => $master->imdb_rating,
            'category'     => $master->event_category,
            'permalink'    => get_permalink($post->ID),
        );
    }
}
