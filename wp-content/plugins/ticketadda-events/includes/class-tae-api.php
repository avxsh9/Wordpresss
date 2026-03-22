<?php
/**
 * TAE_API — Handles Unified REST API for TicketAdda
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TAE_API {
    const NS = 'ta/v1';

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( self::NS, '/events', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_events' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'per_page'   => array( 'default' => 12, 'sanitize_callback' => 'absint' ),
                'event_type' => array( 'sanitize_callback' => 'sanitize_text_field' ),
            ),
        ) );

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
    }

    public function get_events( $request ) {
        $args = array(
            'post_type'      => 'event_ticket',
            'posts_per_page' => $request->get_param( 'per_page' ),
            'post_status'    => 'publish',
        );

        $type = $request->get_param( 'event_type' );
        if ( $type ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'event_type',
                    'field'    => 'slug',
                    'terms'    => $type,
                ),
            );
        }

        $query = new WP_Query( $args );
        $data  = array();

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $data[] = $this->format_ticket( get_post() );
            }
        }
        wp_reset_postdata();

        return rest_ensure_response( $data );
    }

    public function get_master_events( $request ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'events_master';
        $results = $wpdb->get_results( "SELECT * FROM $table_name WHERE status = 'active' ORDER BY event_date ASC" );
        return rest_ensure_response( $results );
    }

    public function create_ticket( $request ) {
        $params = $request->get_params();
        $user_id = get_current_user_id();

        // Validate event type
        $event_type = sanitize_text_field( $params['event_type'] ?? 'movie' );
        
        $post_data = array(
            'post_type'    => 'event_ticket',
            'post_status'  => 'pending',
            'post_author'  => $user_id,
        );

        if ( $event_type === 'movie' ) {
            $post_data['post_title'] = sanitize_text_field( $params['title'] );
        } else {
            // Sports: Link to master event
            $master_id = absint( $params['event_id'] );
            global $wpdb;
            $master = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}events_master WHERE id = %d", $master_id ) );
            if ( ! $master ) return new WP_Error( 'invalid_event', 'Invalid sports event', array( 'status' => 400 ) );
            $post_data['post_title'] = $master->event_name;
        }

        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) ) return $post_id;

        // Set Taxonomy
        wp_set_object_terms( $post_id, $event_type, 'event_type' );

        // Save Meta
        update_post_meta( $post_id, 'tae_event_type', $event_type );
        update_post_meta( $post_id, 'tae_price', (float) $params['price'] );
        update_post_meta( $post_id, 'tae_quantity', (int) $params['quantity'] );
        update_post_meta( $post_id, 'tae_seat_info', sanitize_text_field( $params['seat_info'] ) );
        update_post_meta( $post_id, 'tae_seller_id', $user_id );
        update_post_meta( $post_id, 'tae_status', 'Active' );

        if ( $event_type === 'movie' ) {
            update_post_meta( $post_id, 'tae_venue', sanitize_text_field( $params['venue'] ) );
            update_post_meta( $post_id, 'tae_date', sanitize_text_field( $params['date'] ) );
            update_post_meta( $post_id, 'tae_time', sanitize_text_field( $params['time'] ) );
        } else {
            update_post_meta( $post_id, 'tae_event_id', absint( $params['event_id'] ) );
        }

        return rest_ensure_response( array( 'success' => true, 'id' => $post_id ) );
    }

    private function format_ticket( $post ) {
        $event_type = get_the_terms( $post->ID, 'event_type' )[0]->slug;
        $data = array(
            'id'        => $post->ID,
            'title'     => $post->post_title,
            'price'     => (float) get_post_meta( $post->ID, 'tae_price', true ),
            'quantity'  => (int) get_post_meta( $post->ID, 'tae_quantity', true ),
            'seat_info' => get_post_meta( $post->ID, 'tae_seat_info', true ),
            'type'      => $event_type,
            'thumbnail' => get_the_post_thumbnail_url( $post->ID, 'large' ),
        );

        if ( $event_type === 'movie' ) {
            $data['venue'] = get_post_meta( $post->ID, 'tae_venue', true );
            $data['date']  = get_post_meta( $post->ID, 'tae_date', true );
            $data['time']  = get_post_meta( $post->ID, 'tae_time', true );
        } else {
            $master_id = get_post_meta( $post->ID, 'tae_event_id', true );
            global $wpdb;
            $master = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}events_master WHERE id = %d", $master_id ) );
            if ( $master ) {
                $data['venue'] = $master->venue;
                $data['date']  = $master->event_date;
                $data['time']  = $master->event_time;
                $data['poster']= $master->poster_url;
                if ( ! $data['thumbnail'] ) $data['thumbnail'] = $master->poster_url;
            }
        }

        return $data;
    }
}
