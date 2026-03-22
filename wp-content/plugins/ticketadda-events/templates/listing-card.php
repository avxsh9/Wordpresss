<?php
$event_type_terms = get_the_terms( get_the_ID(), 'event_type' );
$type_slug = ! empty( $event_type_terms ) ? $event_type_terms[0]->slug : 'unknown';
$price = get_post_meta( get_the_ID(), 'tae_price', true );
$venue = get_post_meta( get_the_ID(), 'tae_venue', true );
$date  = get_post_meta( get_the_ID(), 'tae_date', true );
$time  = get_post_meta( get_the_ID(), 'tae_time', true );
$thumbnail = get_the_post_thumbnail_url( get_the_ID(), 'large' );

if ( $type_slug === 'sports' ) {
    $master_id = get_post_meta( get_the_ID(), 'tae_event_id', true );
    global $wpdb;
    $master = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}events_master WHERE id = %d", $master_id ) );
    if ( $master ) {
        $venue = $master->venue;
        $date  = $master->event_date;
        $time  = $master->event_time;
        if ( ! $thumbnail ) $thumbnail = $master->poster_url;
    }
}
?>
<div class="tae-card <?php echo esc_attr( $type_slug ); ?>">
    <div class="tae-card-image">
        <img src="<?php echo $thumbnail ?: 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=500&q=80'; ?>" alt="<?php the_title(); ?>">
        <div class="tae-type-badge"><?php echo ucfirst( $type_slug ); ?></div>
    </div>
    <div class="tae-card-body">
        <h3 class="tae-card-title"><?php the_title(); ?></h3>
        <p class="tae-card-info">
            <span class="dashicons dashicons-location"></span> <?php echo esc_html( $venue ); ?>
        </p>
        <p class="tae-card-info">
            <span class="dashicons dashicons-calendar-alt"></span> <?php echo date( 'M j, Y', strtotime($date) ); ?> @ <?php echo date( 'g:i A', strtotime($time) ); ?>
        </p>
        <div class="tae-card-footer">
            <span class="tae-card-price">₹<?php echo esc_html( $price ); ?></span>
            <a href="<?php the_permalink(); ?>" class="tae-btn">Buy Now</a>
        </div>
    </div>
</div>
