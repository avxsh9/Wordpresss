<?php
/**
 * TAE_Shortcodes — Handles Frontend Display and Seller Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TAE_Shortcodes {
    public function __construct() {
        add_shortcode( 'events_list', array( $this, 'render_events' ) );
        add_shortcode( 'movies_list', array( $this, 'render_movies' ) );
        add_shortcode( 'sports_list', array( $this, 'render_sports' ) );
        add_shortcode( 'add_ticket_form', array( $this, 'render_add_form' ) );
        add_shortcode( 'seller_dashboard', array( $this, 'render_seller_dashboard' ) );
    }

    public function render_events( $atts ) {
        return $this->render_listings( $atts, 'all' );
    }

    public function render_movies( $atts ) {
        return $this->render_listings( $atts, 'movie' );
    }

    public function render_sports( $atts ) {
        return $this->render_listings( $atts, 'sports' );
    }

    private function render_listings( $atts, $type = 'all' ) {
        $atts = shortcode_atts( array( 'count' => 12 ), $atts );
        $args = array(
            'post_type'      => 'event_ticket',
            'posts_per_page' => $atts['count'],
            'post_status'    => 'publish',
        );

        if ( $type !== 'all' ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'event_type',
                    'field'    => 'slug',
                    'terms'    => $type,
                ),
            );
        }

        $query = new WP_Query( $args );
        ob_start();
        echo '<div class="tae-grid">';
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                include TAE_PLUGIN_DIR . 'templates/listing-card.php';
            }
        } else {
            echo '<p>No tickets available in this category.</p>';
        }
        echo '</div>';
        wp_reset_postdata();
        return ob_get_clean();
    }

    public function render_add_form() {
        if ( ! is_user_logged_in() ) return '<p>Please log in to add tickets.</p>';
        
        global $wpdb;
        $master_events = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}events_master WHERE status = 'active'" );
        
        ob_start();
        include TAE_PLUGIN_DIR . 'templates/add-ticket-form.php';
        return ob_get_clean();
    }

    public function render_seller_dashboard() {
        if ( ! is_user_logged_in() ) return '<p>Please log in to view your dashboard.</p>';
        
        $user_id = get_current_user_id();
        $query = new WP_Query( array(
            'post_type'   => 'event_ticket',
            'author'      => $user_id,
            'post_status' => array( 'publish', 'pending', 'draft' ),
        ) );

        ob_start();
        include TAE_PLUGIN_DIR . 'templates/seller-dashboard.php';
        wp_reset_postdata();
        return ob_get_clean();
    }
}
