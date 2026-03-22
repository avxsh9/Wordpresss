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
                $master_id = get_post_meta( $post_id, 'uem_master_id', true );
                
                global $wpdb;
                $master = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}uem_master_events WHERE id = %d", $master_id ) );

                if ( $master ) {
                    $expiry_time = strtotime( "{$master->event_date} {$master->event_time}" );
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
