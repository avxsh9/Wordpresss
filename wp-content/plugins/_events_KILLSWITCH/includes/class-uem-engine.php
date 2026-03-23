<?php
/**
 * UEM_Engine — Handles auto-expiry and common logic
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class UEM_Engine {
    public function __construct() {
        add_action( 'uem_expiry_check', array( $this, 'check_expired_listings' ) );
        if ( ! wp_next_scheduled( 'uem_expiry_check' ) ) {
            wp_schedule_event( time(), 'hourly', 'uem_expiry_check' );
        }
    }

    public function check_expired_listings() {
        $args = array(
            'post_type'      => 'event_listing',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array( 'key' => 'uem_status', 'value' => 'active' )
            )
        );

        $query = new WP_Query( $args );
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();
                $master_id = get_post_meta( $post_id, 'event_id', true );
                $master = get_post( $master_id );

                if ( $master && $master->post_type === 'events' ) {
                    $event_id = $master->ID; // Define $event_id for clarity as used in the instruction's snippet
                    $event_date = get_post_meta( $event_id, 'event_date', true );
                    $event_time = get_post_meta( $event_id, 'event_time', true );
                    $location   = get_post_meta( $event_id, 'event_location', true );
                    $expiry_time = strtotime( "{$event_date} {$event_time}" );
                    
                    if ( time() > $expiry_time ) {
                        update_post_meta( $post_id, 'uem_status', 'expired' );
                        wp_update_post( array( 'ID' => $post_id, 'post_status' => 'private' ) );
                    }
                }
            }
        }
        wp_reset_postdata();
    }
}
