<?php
/**
 * Single Event Listing Template - Universal Events Marketplace
 */

get_header();

$listing_id = get_the_ID();
$master_id  = get_post_meta( $listing_id, 'uem_master_id', true );

global $wpdb;
$master = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}uem_master_events WHERE id = %d", $master_id ) );

if ( ! $master ) {
    echo '<div class="uem-container"><p>Event data not found.</p></div>';
    get_footer();
    exit;
}

// Get all sellers for this master event
$sellers_query = new WP_Query( array(
    'post_type'      => 'event_listing',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'meta_query'     => array(
        array( 'key' => 'uem_master_id', 'value' => $master_id ),
        array( 'key' => 'uem_status', 'value' => 'active' )
    )
) );
?>

<div class="uem-single-wrapper">
    <!-- Hero Section -->
    <div class="uem-hero">
        <img src="<?php echo esc_url($master->event_poster); ?>" class="uem-hero-img">
        <div class="uem-hero-content">
            <img src="<?php echo esc_url($master->event_poster); ?>" class="uem-hero-poster">
            <div class="uem-hero-text">
                <div class="uem-badge"><?php echo ucfirst($master->event_category); ?></div>
                <h1 class="uem-hero-title"><?php echo esc_html($master->event_name); ?></h1>
                <?php if ($master->imdb_rating) : ?>
                    <div style="background:#f5c518; color:#000; padding:5px 10px; border-radius:4px; font-weight:900; display:inline-block; margin-top:10px;">⭐ IMDb: <?php echo esc_html($master->imdb_rating); ?></div>
                <?php endif; ?>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
                    <div style="background:rgba(255,255,255,0.1); padding:10px 15px; border-radius:8px; backdrop-filter:blur(5px);">📍 <strong>Venue:</strong> <?php echo esc_html($master->venue); ?></div>
                    <div style="background:rgba(255,255,255,0.1); padding:10px 15px; border-radius:8px; backdrop-filter:blur(5px);">📅 <strong>Date:</strong> <?php echo date('M j, Y', strtotime($master->event_date)); ?></div>
                    <div style="background:rgba(255,255,255,0.1); padding:10px 15px; border-radius:8px; backdrop-filter:blur(5px);">⏰ <strong>Time:</strong> <?php echo date('g:i A', strtotime($master->event_time)); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="uem-container">
        <!-- Seller Availability Section -->
        <div class="uem-seller-card shadow-sm">
            <h3 style="margin-top:0;">Available Sellers & Tickets</h3>
            <?php if ( $sellers_query->have_posts() ) : ?>
                <table class="uem-table">
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
                            $s_price = get_post_meta( get_the_ID(), 'uem_price', true );
                            $s_seats = get_post_meta( get_the_ID(), 'uem_seats', true );
                            $seller  = get_the_author();
                            ?>
                            <tr>
                                <td><strong>👤 <?php echo esc_html( $seller ); ?></strong></td>
                                <td><?php echo esc_html( $s_seats ); ?></td>
                                <td style="font-weight:900; color:var(--uem-dark); font-size:1.2rem;">₹<?php echo esc_html( $s_price ); ?></td>
                                <td><a href="#" class="uem-btn-book">Book Ticket</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div style="padding:50px; text-align:center; background:#f8f9fa; border-radius:12px;">
                    <h4 style="margin:0; color:#5f6368;">No tickets available yet</h4>
                    <p style="margin:10px 0 0 0;">Check back later or try another event.</p>
                </div>
            <?php endif; wp_reset_postdata(); ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
