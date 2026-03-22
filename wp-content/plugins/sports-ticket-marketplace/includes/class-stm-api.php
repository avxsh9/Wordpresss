<?php
/**
 * STM_API — Handles REST API Endpoints for Sports
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class STM_API {
    const NS = 'tm/v1';

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( self::NS, '/sports', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_listings' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'per_page' => array( 'default' => 12, 'sanitize_callback' => 'absint' ),
                'stadium'  => array( 'sanitize_callback' => 'sanitize_text_field' ),
            ),
        ) );

        register_rest_route( self::NS, '/sports/(?P<id>\\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_listing' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( self::NS, '/sports', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'create_listing' ),
            'permission_callback' => array( $this, 'check_auth' ),
        ) );

        register_rest_route( self::NS, '/wishlist', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'toggle_wishlist' ),
            'permission_callback' => array( $this, 'check_auth' ),
        ) );
    }

    public function get_listings( $request ) {
        $args = array(
            'post_type'      => 'ipl_tickets',
            'posts_per_page' => $request->get_param( 'per_page' ),
            'post_status'    => 'publish',
        );

        $stadium = $request->get_param( 'stadium' );
        if ( $stadium ) {
            $args['meta_query'] = array(
                array(
                    'key'     => 'stm_location',
                    'value'   => $stadium,
                    'compare' => 'LIKE',
                ),
            );
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

    public function get_listing( $request ) {
        $post = get_post( $request->get_param( 'id' ) );
        if ( ! $post || $post->post_type !== 'ipl_tickets' ) {
            return new WP_Error( 'not_found', 'Listing not found', array( 'status' => 404 ) );
        }
        return rest_ensure_response( $this->format_listing( $post ) );
    }

    public function create_listing( $request ) {
        $params = $request->get_params();
        
        $post_id = wp_insert_post( array(
            'post_type'    => 'ipl_tickets',
            'post_title'   => sanitize_text_field( $params['title'] ),
            'post_status'  => 'pending',
            'post_author'  => get_current_user_id(),
        ) );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        $fields = array( 'stm_teams', 'stm_location', 'stm_datetime', 'stm_seat_info', 'stm_price', 'stm_quantity' );
        foreach ( $fields as $field ) {
            $key = str_replace( 'stm_', '', $field );
            if ( isset( $params[ $key ] ) ) {
                update_post_meta( $post_id, $field, sanitize_text_field( $params[ $key ] ) );
            }
        }
        update_post_meta( $post_id, 'stm_status', 'Available' );
        update_post_meta( $post_id, 'stm_seller_id', get_current_user_id() );

        return rest_ensure_response( array( 'success' => true, 'id' => $post_id ) );
    }

    public function check_auth() {
        return is_user_logged_in();
    }

    public function toggle_wishlist( $request ) {
        $post_id = $request->get_param( 'post_id' );
        $user_id = get_current_user_id();
        $status  = Marketplace_Engine::toggle_wishlist( $post_id, $user_id );
        return rest_ensure_response( array( 'success' => true, 'status' => $status ) );
    }

    private function format_listing( $post ) {
        return array(
            'id'        => $post->ID,
            'title'     => $post->post_title,
            'teams'     => get_post_meta( $post->ID, 'stm_teams', true ),
            'location'  => get_post_meta( $post->ID, 'stm_location', true ),
            'datetime'  => get_post_meta( $post->ID, 'stm_datetime', true ),
            'seat_info' => get_post_meta( $post->ID, 'stm_seat_info', true ),
            'price'     => (float) get_post_meta( $post->ID, 'stm_price', true ),
            'quantity'  => (int) get_post_meta( $post->ID, 'stm_quantity', true ),
            'status'    => get_post_meta( $post->ID, 'stm_status', true ),
            'seller_id' => (int) get_post_meta( $post->ID, 'stm_seller_id', true ),
            'thumbnail' => get_the_post_thumbnail_url( $post->ID, 'large' ),
        );
    }
}
