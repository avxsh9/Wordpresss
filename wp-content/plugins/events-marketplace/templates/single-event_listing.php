<?php
/**
 * Single Event Listing Template
 */

get_header();

$listing_id = get_the_ID();
$master_id  = get_post_meta( $listing_id, 'tae_master_id', true );
$cat_terms  = get_the_terms( $listing_id, 'event_category' );
$cat        = ! empty( $cat_terms ) ? $cat_terms[0]->slug : 'movie';

global $wpdb;
$master = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tae_master_data WHERE id = %d", $master_id ) );

if ( ! $master ) {
    echo '<div class="tae-container"><p>Event data not found.</p></div>';
    get_footer();
    exit;
}

// Get all sellers for this master event
$sellers_query = new WP_Query( array(
    'post_type'      => 'event_listing',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'meta_query'     => array(
        array( 'key' => 'tae_master_id', 'value' => $master_id ),
        array( 'key' => 'tae_status', 'value' => 'active' )
    )
) );
?>

<div class="tae-single-wrapper">
    <!-- Hero Section -->
    <div class="tae-hero">
        <img src="<?php echo esc_url($master->poster_url); ?>" class="tae-hero-bg">
        <div class="tae-hero-content">
            <img src="<?php echo esc_url($master->poster_url); ?>" class="tae-hero-poster">
            <div class="tae-hero-text">
                <div class="tae-badge <?php echo esc_attr($cat); ?>"><?php echo ucfirst($cat); ?></div>
                <h1 class="tae-hero-title"><?php echo esc_html($master->name); ?></h1>
                <?php if ($cat === 'movie' && $master->imdb_rating) : ?>
                    <div class="tae-imdb-large">⭐ IMDb: <?php echo esc_html($master->imdb_rating); ?></div>
                <?php endif; ?>
                
                <div class="tae-hero-meta" style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
                    <div><strong>Venue:</strong> <?php echo esc_html( $cat === 'movie' ? get_post_meta($listing_id, 'tae_venue', true) : $master->venue ); ?></div>
                    <div><strong>Date:</strong> <?php echo esc_html( $cat === 'movie' ? get_post_meta($listing_id, 'tae_date', true) : $master->event_date ); ?></div>
                    <div><strong>Time:</strong> <?php echo esc_html( $cat === 'movie' ? get_post_meta($listing_id, 'tae_time', true) : $master->event_time ); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tae-container">
        <!-- Seller Availability Section -->
        <div class="tae-details-card">
            <h3>Available Sellers & Tickets</h3>
            <?php if ( $sellers_query->have_posts() ) : ?>
                <table class="tae-seller-table">
                    <thead>
                        <tr>
                            <th>Seller</th>
                            <th>Seats / Area</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ( $sellers_query->have_posts() ) : $sellers_query->the_post(); 
                            $s_price = get_post_meta( get_the_ID(), 'tae_price', true );
                            $s_seats = get_post_meta( get_the_ID(), 'tae_seats', true );
                            $seller  = get_the_author();
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html( $seller ); ?></strong></td>
                                <td><?php echo esc_html( $s_seats ); ?></td>
                                <td style="font-weight:900; color:var(--tae-dark);">₹<?php echo esc_html( $s_price ); ?></td>
                                <td><a href="#" class="tae-btn-book">Book Now</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div style="padding:40px; text-align:center; background:#f8f9fa; border-radius:12px;">
                    <h4 style="margin:0; color:#5f6368;">No Sellers Available Right Now</h4>
                    <p style="margin:10px 0 0 0;">Tickets might be sold out or upcoming soon.</p>
                </div>
            <?php endif; wp_reset_postdata(); ?>
        </div>
    </div>
</div>

<style>
.tae-imdb-large { background: #f5c518; color: #000; display: inline-block; padding: 5px 15px; border-radius: 6px; font-weight: 800; font-size: 1.2rem; }
.tae-hero-meta div { font-size: 1.1rem; background: rgba(255,255,255,0.1); padding: 10px 20px; border-radius: 8px; backdrop-filter: blur(5px); }
</style>

<?php get_footer(); ?>
