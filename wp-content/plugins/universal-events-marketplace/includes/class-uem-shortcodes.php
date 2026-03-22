<?php
/**
 * UEM_Shortcodes — Handles Frontend Display and Seller Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class UEM_Shortcodes {
    public function __construct() {
        add_shortcode( 'events_home', array( $this, 'render_grid' ) );
        add_shortcode( 'events_page', array( $this, 'render_grid_with_filters' ) );
        add_shortcode( 'add_event_form', array( $this, 'render_add_form' ) );
        add_shortcode( 'seller_dashboard', array( $this, 'render_seller_dashboard' ) );
    }

    public function render_grid( $atts ) {
        $a = shortcode_atts( array( 'count' => 8 ), $atts );
        ob_start();
        ?>
        <div class="uem-container">
            <div id="uem-listing-grid" class="uem-grid" data-per-page="<?php echo $a['count']; ?>">
                <!-- AJAX Load -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_grid_with_filters( $atts ) {
        $a = shortcode_atts( array( 'count' => 12 ), $atts );
        ob_start();
        $cats = get_terms( array( 'taxonomy' => 'event_category', 'hide_empty' => false ) );
        ?>
        <div class="uem-container">
            <div class="uem-filters">
                <select id="uem-filter-cat">
                    <option value="">All Categories</option>
                    <?php foreach ( $cats as $cat ) : ?>
                        <option value="<?php echo $cat->slug; ?>"><?php echo $cat->name; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" id="uem-filter-date">
                <input type="number" id="uem-filter-price" placeholder="Max Price (₹)">
                <button class="uem-btn-filter">Apply</button>
            </div>
            <div id="uem-listing-grid" class="uem-grid" data-per-page="<?php echo $a['count']; ?>">
                <!-- AJAX Load -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_add_form() {
        if ( ! is_user_logged_in() ) return '<p>Please log in to list tickets.</p>';
        ob_start();
        include UEM_DIR . 'templates/add-event-form.php';
        return ob_get_clean();
    }

    public function render_seller_dashboard() {
        if ( ! is_user_logged_in() ) return '<p>Please log in to view dashboard.</p>';
        $user_id = get_current_user_id();
        $query = new WP_Query( array(
            'post_type'   => 'event_listing',
            'author'      => $user_id,
            'post_status' => array( 'publish', 'pending', 'draft' ),
        ) );
        ob_start();
        include UEM_DIR . 'templates/seller-dashboard.php';
        wp_reset_postdata();
        return ob_get_clean();
    }
}
