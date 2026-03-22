<?php
/**
 * TAE_Shortcodes — Handles Frontend Display and Seller Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TAE_Shortcodes {
    public function __construct() {
        add_shortcode( 'events_home', array( $this, 'render_home' ) );
        add_shortcode( 'events_page', array( $this, 'render_page' ) );
        add_shortcode( 'add_event_form', array( $this, 'render_add_form' ) );
        add_shortcode( 'seller_dashboard', array( $this, 'render_seller_dashboard' ) );
    }

    public function render_home( $atts ) {
        return $this->render_grid( array( 'count' => 8, 'title' => 'Featured Listings' ) );
    }

    public function render_page( $atts ) {
        return $this->render_grid( array( 'count' => 12, 'show_filters' => true ) );
    }

    private function render_grid( $args ) {
        ob_start();
        $cats = get_terms( array( 'taxonomy' => 'event_category', 'hide_empty' => false ) );
        ?>
        <div class="tae-container">
            <?php if ( ! empty( $args['show_filters'] ) ) : ?>
                <div class="tae-filters">
                    <select id="tae-filter-cat">
                        <option value="">All Categories</option>
                        <?php foreach ( $cats as $cat ) : ?>
                            <option value="<?php echo $cat->slug; ?>"><?php echo $cat->name; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" id="tae-filter-date" placeholder="Filter by Date">
                    <input type="number" id="tae-filter-price" placeholder="Max Price (₹)">
                    <button class="tae-btn-filter">Apply Filters</button>
                </div>
            <?php endif; ?>

            <div id="tae-listing-grid" class="tae-grid" data-per-page="<?php echo $args['count']; ?>">
                <!-- AJAX Load -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_add_form() {
        if ( ! is_user_logged_in() ) return '<p>Please log in to list tickets.</p>';
        ob_start();
        include TAE_DIR . 'templates/add-event-form.php';
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
        include TAE_DIR . 'templates/seller-dashboard.php';
        wp_reset_postdata();
        return ob_get_clean();
    }
}
