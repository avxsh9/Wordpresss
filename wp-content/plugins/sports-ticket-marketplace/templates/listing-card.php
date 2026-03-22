<div class="stm-card" data-id="<?php the_ID(); ?>">
    <div class="stm-card-image">
        <?php if ( has_post_thumbnail() ) : ?>
            <?php the_post_thumbnail( 'large' ); ?>
        <?php else : ?>
            <img src="https://images.unsplash.com/photo-1540747913346-19e32dc3e97e?auto=format&fit=crop&w=800&q=80" alt="Sports Match">
        <?php endif; ?>
        <div class="stm-badge <?php echo strtolower( get_post_meta( get_the_ID(), 'stm_status', true ) ); ?>">
            <?php echo esc_html( get_post_meta( get_the_ID(), 'stm_status', true ) ); ?>
        </div>
    </div>
    <div class="stm-card-content">
        <h3 class="stm-card-title"><?php the_title(); ?></h3>
        <p class="stm-card-teams">
            <strong>Teams:</strong> <?php echo esc_html( get_post_meta( get_the_ID(), 'stm_teams', true ) ); ?>
        </p>
        <p class="stm-card-location">
            <span class="dashicons dashicons-location"></span>
            <?php echo esc_html( get_post_meta( get_the_ID(), 'stm_location', true ) ); ?>
        </p>
        <p class="stm-card-datetime">
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php 
            $dt = get_post_meta( get_the_ID(), 'stm_datetime', true );
            echo $dt ? date( 'M j, Y @ g:i A', strtotime( $dt ) ) : 'TBA'; 
            ?>
        </p>
        <div class="stm-card-footer">
            <span class="stm-price">₹<?php echo esc_html( get_post_meta( get_the_ID(), 'stm_price', true ) ); ?></span>
            <a href="<?php the_permalink(); ?>" class="stm-btn">Get Tickets</a>
        </div>
    </div>
</div>
