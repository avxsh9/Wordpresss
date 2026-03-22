<?php
/**
 * TAE_Engine — Handles auto-expiry and common logic
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TAE_Engine {
    public function __construct() {
        add_action( 'tae_expiry_check', array( $this, 'check_expired_listings' ) );
        if ( ! wp_next_scheduled( 'tae_expiry_check' ) ) {
            wp_schedule_event( time(), 'hourly', 'tae_expiry_check' );
        }
    }

    public function check_expired_listings() {
        $args = array(
            'post_type'      => 'event_listing',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array( 'key' => 'tae_status', 'value' => 'active' )
            )
        );

        $query = new WP_Query( $args );
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();
                $date = get_post_meta( $post_id, 'tae_date', true );
                $time = get_post_meta( $post_id, 'tae_time', true );

                if ( $date && $time ) {
                    $expiry_time = strtotime( "$date $time" );
                    if ( time() > $expiry_time ) {
                        update_post_meta( $post_id, 'tae_status', 'expired' );
                        // and set post status to draft if we want to hide it completely from WP_Query
                        wp_update_post( array( 'ID' => $post_id, 'post_status' => 'private' ) );
                    }
                }
            }
        }
        wp_reset_postdata();
    }
}
