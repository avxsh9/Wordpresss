<?php
/**
 * UEM_API — Handles REST Endpoints for Universal Events
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class UEM_API {
    const NS = 'uem/v1';
    // const NS = 'uem/v1'; // This constant is no longer directly used for routes

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        $ns = UEM_NAMESPACE;

        register_rest_route( $ns, '/events', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_master_events' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $ns, '/tickets/approved', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_listings' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $ns, '/tickets/recent', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_listings' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $ns, '/tickets', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'create_ticket' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( $ns, '/events/(?P<id>\d+)', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_single_event' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $ns, '/events/(?P<id>\d+)/tickets', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_event_tickets' ),
            'permission_callback' => '__return_true',
        ) );
    }

    public function get_single_event( $request ) {
        $id = absint( $request['id'] );
        $post = get_post( $id );
        if ( ! $post || $post->post_type !== 'events' ) {
            return new WP_Error( 'not_found', 'Event not found', array( 'status' => 404 ) );
        }
        $cat_terms = get_the_terms( $id, 'event_category' );
        return rest_ensure_response( array(
            'id'       => $id,
            'name'     => $post->post_title,
            'location' => get_post_meta( $id, 'event_location', true ),
            'date'     => get_post_meta( $id, 'event_date', true ),
            'time'     => get_post_meta( $id, 'event_time', true ),
            'image'    => get_post_meta( $id, 'poster_url', true ) ?: get_the_post_thumbnail_url( $id, 'full' ),
            'category' => ! empty($cat_terms) ? $cat_terms[0]->slug : 'other',
        ) );
    }

    public function get_event_tickets( $request ) {
        $event_id = absint( $request['id'] );
        $args = array(
            'post_type'      => 'event_listing',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array( 'key' => 'event_id', 'value' => $event_id ),
                array( 'key' => 'uem_status', 'value' => 'active' )
            )
        );
        $query = new WP_Query( $args );
        $data = array();
        foreach ( $query->posts as $post ) {
            $data[] = $this->format_listing( $post );
        }
        return rest_ensure_response( $data );
    }

    public function get_master_events( $request ) {
        $args = array(
            'post_type'      => 'events',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        if ( $request->get_param('category') ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'event_category',
                    'field'    => 'slug',
                    'terms'    => $request->get_param('category')
                )
            );
        }

        $query = new WP_Query( $args );
        $data = array();
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $id = get_the_ID();
                $data[] = array(
                    'id'       => $id,
                    'name'     => get_the_title(),
                    'location' => get_post_meta( $id, 'event_location', true ),
                    'date'     => get_post_meta( $id, 'event_date', true ),
                    'image'    => get_post_meta( $id, 'poster_url', true ) ?: get_the_post_thumbnail_url($id, 'full'),
                    'url'      => get_permalink($id),
                    'price'    => '--', // Master events don't have a single price
                );
            }
        }
        wp_reset_postdata();
        return rest_ensure_response( $data );
    }

    public function create_ticket( $request ) {
        $params = $request->get_params();
        $user_id = get_current_user_id();
        $master_id = absint( $params['master_id'] );

        // Validate master event
        $master = get_post( $master_id );
        if ( ! $master || $master->post_type !== 'events' ) {
            return new WP_Error( 'invalid_master', 'Event not found.', array('status' => 400) );
        }

        $post_title = $master->post_title . ' - Ticket';
        
        $post_id = wp_insert_post( array(
            'post_title'   => sanitize_text_field( $post_title ),
            'post_type'    => 'event_listing',
            'post_status'  => 'publish', // Fixed: Should be publish for visibility
            'post_author'  => $user_id,
        ) );

        if ( is_wp_error( $post_id ) ) return $post_id;

        $cat = get_the_terms( $master_id, 'event_category' );
        if ( ! empty($cat) ) wp_set_object_terms( $post_id, $cat[0]->term_id, 'event_category' );

        update_post_meta( $post_id, 'event_id', $master_id );
        update_post_meta( $post_id, 'uem_price', (float)$params['price'] );
        update_post_meta( $post_id, 'uem_quantity', (int)($params['quantity'] ?? 1) );
        update_post_meta( $post_id, 'uem_section', sanitize_text_field($params['section'] ?? '') );
        update_post_meta( $post_id, 'uem_row', sanitize_text_field($params['row'] ?? '') );
        update_post_meta( $post_id, 'uem_seats', sanitize_text_field($params['seat_number'] ?? $params['seats'] ?? '') );
        update_post_meta( $post_id, 'uem_seller_id', $user_id );
        update_post_meta( $post_id, 'uem_status', 'pending' ); // New listings are pending admin approval

        // Handle File Upload
        if ( ! empty( $_FILES['ticketFile'] ) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );

            $attachment_id = media_handle_upload( 'ticketFile', $post_id );
            if ( ! is_wp_error( $attachment_id ) ) {
                update_post_meta( $post_id, 'uem_ticket_img', wp_get_attachment_url( $attachment_id ) );
                set_post_thumbnail( $post_id, $attachment_id );
            }
        }

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
        $master_id = get_post_meta( $post->ID, 'event_id', true );
        $master = get_post( $master_id );
        if ( ! $master || $master->post_type !== 'events' ) return array();

        $cat_terms = get_the_terms( $master->ID, 'event_category' );
        $cat = ! empty($cat_terms) ? $cat_terms[0]->slug : 'others';
        
        $seller_id = get_post_meta( $post->ID, 'uem_seller_id', true );
        $seller = get_userdata( $seller_id );

        return array(
            'id'           => $post->ID,
            'name'         => $master->post_title, // Home JS expects 'name'
            'title'        => $master->post_title,
            'price'        => (float) get_post_meta( $post->ID, 'uem_price', true ),
            'date'         => get_post_meta( $master->ID, 'event_date', true ),
            'eventDate'    => get_post_meta( $master->ID, 'event_date', true ),
            'eventTime'    => get_post_meta( $master->ID, 'event_time', true ),
            'location'     => get_post_meta( $master->ID, 'event_location', true ), // Home JS expects 'location'
            'venue'        => get_post_meta( $master->ID, 'event_location', true ),
            'image'        => get_post_meta( $master->ID, 'poster_url', true ) ?: get_the_post_thumbnail_url($master->ID, 'full'), // Home JS expects 'image'
            'poster'       => get_post_meta( $master->ID, 'poster_url', true ) ?: get_the_post_thumbnail_url($master->ID, 'full'),
            'imdb'         => get_post_meta( $master->ID, 'imdb_rating', true ),
            'category'     => $cat,
            'type'         => $cat,
            'url'          => get_permalink($master->ID), // Direct to event page
            'permalink'    => get_permalink($post->ID),
            'quantity'     => (int) get_post_meta( $post->ID, 'uem_quantity', true ) ?: 1,
            'ticketCount'  => (int) get_post_meta( $post->ID, 'uem_quantity', true ) ?: 1,
            'section'      => get_post_meta( $post->ID, 'uem_section', true ) ?: 'General',
            'row'          => get_post_meta( $post->ID, 'uem_row', true ) ?: 'N/A',
            'seats'        => get_post_meta( $post->ID, 'uem_seats', true ),
            'ticket_img'   => get_post_meta( $post->ID, 'uem_ticket_img', true ),
            'sellerName'   => $seller ? $seller->display_name : 'Verified Seller',
            'avgRating'    => 5.0,
            'ratingsCount' => 10,
        );
    }
}
