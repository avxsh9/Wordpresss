<div class="mtm-card" data-id="<?php the_ID(); ?>">
    <div class="mtm-card-image">
        <?php if ( has_post_thumbnail() ) : ?>
            <?php the_post_thumbnail( 'large' ); ?>
        <?php else : ?>
            <img src="https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=500&h=300&q=80" alt="Movie Poster">
        <?php endif; ?>
        <div class="mtm-badge <?php echo strtolower( get_post_meta( get_the_ID(), 'mtm_status', true ) ); ?>">
            <?php echo esc_html( get_post_meta( get_the_ID(), 'mtm_status', true ) ); ?>
        </div>
    </div>
    <div class="mtm-card-content">
        <h3 class="mtm-card-title"><?php the_title(); ?></h3>
        <p class="mtm-card-location">
            <span class="dashicons dashicons-location"></span>
            <?php echo esc_html( get_post_meta( get_the_ID(), 'mtm_location', true ) ); ?>
        </p>
        <p class="mtm-card-datetime">
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php 
            $dt = get_post_meta( get_the_ID(), 'mtm_datetime', true );
            echo $dt ? date( 'M j, Y @ g:i A', strtotime( $dt ) ) : 'TBA'; 
            ?>
        </p>
        <div class="mtm-card-footer">
            <span class="mtm-price">₹<?php echo esc_html( get_post_meta( get_the_ID(), 'mtm_price', true ) ); ?></span>
            <a href="<?php the_permalink(); ?>" class="mtm-btn">Buy Now</a>
        </div>
    </div>
</div>
