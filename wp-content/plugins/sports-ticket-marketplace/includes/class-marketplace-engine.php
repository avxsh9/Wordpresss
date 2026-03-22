<?php
/**
 * Marketplace_Engine — Shared logic for both plugins
 * Handles Orders, Commissions, and Auto-expiry
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Marketplace_Engine' ) ) {

    class Marketplace_Engine {
        
        public function __construct() {
            add_action( 'init', array( $this, 'init_shared_logic' ) );
            add_action( 'mtm_cron_expiry', array( $this, 'handle_ticket_expiry' ) );
        }

        public function init_shared_logic() {
            $this->create_db_tables();
            if ( ! wp_next_scheduled( 'mtm_cron_expiry' ) ) {
                wp_schedule_event( time(), 'hourly', 'mtm_cron_expiry' );
            }
        }

        private function create_db_tables() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'tm_orders';
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                ticket_id mediumint(9) NOT NULL,
                buyer_id mediumint(9) NOT NULL,
                seller_id mediumint(9) NOT NULL,
                amount decimal(10,2) NOT NULL,
                commission decimal(10,2) NOT NULL,
                status varchar(20) DEFAULT 'Pending' NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }

        public function handle_ticket_expiry() {
            $ticket_types = array( 'movie_tickets', 'ipl_tickets' );
            foreach ( $ticket_types as $type ) {
                $args = array(
                    'post_type'      => $type,
                    'posts_per_page' => -1,
                    'post_status'    => 'publish',
                    'meta_query'     => array(
                        array(
                            'key'     => $type === 'movie_tickets' ? 'mtm_datetime' : 'stm_datetime',
                            'value'   => date('Y-m-d H:i:s'),
                            'compare' => '<',
                            'type'    => 'DATETIME'
                        ),
                        array(
                            'key'     => $type === 'movie_tickets' ? 'mtm_status' : 'stm_status',
                            'value'   => 'Available',
                            'compare' => '='
                        )
                    )
                );

                $query = new WP_Query( $args );
                if ( $query->have_posts() ) {
                    while ( $query->have_posts() ) {
                        $query->the_post();
                        update_post_meta( get_the_ID(), $type === 'movie_tickets' ? 'mtm_status' : 'stm_status', 'Expired' );
                    }
                }
                wp_reset_postdata();
            }
        }

        public static function calculate_commission( $amount, $type = 'movie' ) {
            $percent = ( $type === 'movie' ) ? get_option('mtm_commission', 10) : get_option('stm_commission', 15);
            return ( $amount * $percent ) / 100;
        }

        public static function record_order( $ticket_id, $buyer_id, $amount, $type = 'movie' ) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'tm_orders';
            
            $post = get_post( $ticket_id );
            $seller_id = $post->post_author;
            $commission = self::calculate_commission( $amount, $type );

            $wpdb->insert( $table_name, array(
                'time'       => current_time( 'mysql' ),
                'ticket_id'  => $ticket_id,
                'buyer_id'   => $buyer_id,
                'seller_id'  => $seller_id,
                'amount'     => $amount,
                'commission' => $commission,
                'status'     => 'Completed',
            ) );

            // Update ticket status to Sold
            $status_key = ( $type === 'movie' ) ? 'mtm_status' : 'stm_status';
            update_post_meta( $ticket_id, $status_key, 'Sold' );

            return $wpdb->insert_id;
        }

        // --- Wishlist System ---
        public static function toggle_wishlist( $post_id, $user_id ) {
            $wishlist = get_user_meta( $user_id, 'tm_wishlist', true ) ?: array();
            if ( ( $key = array_search( $post_id, $wishlist ) ) !== false ) {
                unset( $wishlist[$key] );
                $status = 'removed';
            } else {
                $wishlist[] = $post_id;
                $status = 'added';
            }
            update_user_meta( $user_id, 'tm_wishlist', array_values( $wishlist ) );
            return $status;
        }

        public static function is_in_wishlist( $post_id, $user_id ) {
            $wishlist = get_user_meta( $user_id, 'tm_wishlist', true ) ?: array();
            return in_array( $post_id, $wishlist );
        }

        // --- Featured Listing system ---
        public static function make_featured( $post_id, $type = 'movie' ) {
            $key = ( $type === 'movie' ) ? 'mtm_is_featured' : 'stm_is_featured';
            update_post_meta( $post_id, $key, '1' );
        }
    }

    new Marketplace_Engine();
}
