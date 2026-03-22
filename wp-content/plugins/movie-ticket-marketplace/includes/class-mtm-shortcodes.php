<?php
/**
 * MTM_Shortcodes — Handles Frontend Display
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MTM_Shortcodes {
    public function __construct() {
        add_shortcode( 'movie_listings', array( $this, 'render_listings' ) );
        add_shortcode( 'sell_movie_ticket', array( $this, 'render_sell_form' ) );
    }

    public function render_listings( $atts ) {
        $atts = shortcode_atts( array(
            'count' => 12,
        ), $atts );

        $query = new WP_Query( array(
            'post_type'      => 'movie_tickets',
            'posts_per_page' => $atts['count'],
            'post_status'    => 'publish',
        ) );

        ob_start();
        echo '<div class="mtm-grid">';
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                include MTM_PLUGIN_DIR . 'templates/listing-card.php';
            }
        } else {
            echo '<p class="mtm-no-results">No movies found. Be the first to sell!</p>';
        }
        echo '</div>';
        wp_reset_postdata();
        return ob_get_clean();
    }

    public function render_sell_form() {
        if ( ! is_user_logged_in() ) {
            return '<div class="mtm-notice">Please <a href="' . wp_login_url() . '">login</a> to list your tickets.</div>';
        }
        ob_start();
        include MTM_PLUGIN_DIR . 'templates/sell-form.php';
        return ob_get_clean();
    }
}
