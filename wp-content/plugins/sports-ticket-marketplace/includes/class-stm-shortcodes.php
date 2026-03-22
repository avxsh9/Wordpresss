<?php
/**
 * STM_Shortcodes — Handles Frontend Display for Sports
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class STM_Shortcodes {
    public function __construct() {
        add_shortcode( 'sports_listings', array( $this, 'render_listings' ) );
        add_shortcode( 'sell_sports_ticket', array( $this, 'render_sell_form' ) );
    }

    public function render_listings( $atts ) {
        $atts = shortcode_atts( array(
            'count' => 12,
        ), $atts );

        $query = new WP_Query( array(
            'post_type'      => 'ipl_tickets',
            'posts_per_page' => $atts['count'],
            'post_status'    => 'publish',
        ) );

        ob_start();
        echo '<div class="stm-grid">';
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                include STM_PLUGIN_DIR . 'templates/listing-card.php';
            }
        } else {
            echo '<p class="stm-no-results">No matches found. Be the first to sell!</p>';
        }
        echo '</div>';
        wp_reset_postdata();
        return ob_get_clean();
    }

    public function render_sell_form() {
        if ( ! is_user_logged_in() ) {
            return '<div class="stm-notice">Please <a href="' . wp_login_url() . '">login</a> to list your tickets.</div>';
        }
        ob_start();
        include STM_PLUGIN_DIR . 'templates/sell-form.php';
        return ob_get_clean();
    }
}
