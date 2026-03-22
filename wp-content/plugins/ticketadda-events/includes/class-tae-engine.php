<?php
/**
 * TAE_Engine — Handles background logic, commissions, and promotions
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TAE_Engine {
    public function __construct() {
        add_action( 'tae_expiry_check', array( $this, 'expire_old_tickets' ) );
        if ( ! wp_next_scheduled( 'tae_expiry_check' ) ) {
            wp_schedule_event( time(), 'hourly', 'tae_expiry_check' );
        }
    }

    public function expire_old_tickets() {
        $args = array(
            'post_type'      => 'event_ticket',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => 'tae_status',
                    'value'   => 'Active',
                    'compare' => '='
                )
            )
        );

        $query = new WP_Query( $args );
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();
                $type = get_the_terms( $post_id, 'event_type' )[0]->slug;
                
                $event_date = '';
                $event_time = '';

                if ( $type === 'movie' ) {
                    $event_date = get_post_meta( $post_id, 'tae_date', true );
                    $event_time = get_post_meta( $post_id, 'tae_time', true );
                } else {
                    $master_id = get_post_meta( $post_id, 'tae_event_id', true );
                    global $wpdb;
                    $master = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}events_master WHERE id = %d", $master_id ) );
                    if ( $master ) {
                        $event_date = $master->event_date;
                        $event_time = $master->event_time;
                    }
                }

                if ( $event_date && $event_time ) {
                    $expiry = strtotime( "$event_date $event_time" );
                    if ( time() > $expiry ) {
                        update_post_meta( $post_id, 'tae_status', 'Expired' );
                    }
                }
            }
        }
        wp_reset_postdata();
    }

    public static function process_order( $ticket_id, $buyer_id, $amount ) {
        global $wpdb;
        $post = get_post( $ticket_id );
        
        $commission_pct = (float) get_option( 'tae_commission', 10 );
        $commission_amt = ( $amount * $commission_pct ) / 100;

        $wpdb->insert( $wpdb->prefix . 'tae_orders', array(
            'order_time' => current_time( 'mysql' ),
            'ticket_id'  => $ticket_id,
            'buyer_id'   => $buyer_id,
            'seller_id'  => $post->post_author,
            'amount'     => $amount,
            'commission' => $commission_amt,
            'status'     => 'completed'
        ) );

        update_post_meta( $ticket_id, 'tae_status', 'Sold' );
    }

    public static function toggle_wishlist( $post_id, $user_id ) {
        $favs = get_user_meta( $user_id, 'tae_wishlist', true ) ?: array();
        if ( in_array( $post_id, $favs ) ) {
            $favs = array_diff( $favs, array( $post_id ) );
            $status = 'removed';
        } else {
            $favs[] = $post_id;
            $status = 'added';
        }
        update_user_meta( $user_id, 'tae_wishlist', $favs );
        return $status;
    }
}
