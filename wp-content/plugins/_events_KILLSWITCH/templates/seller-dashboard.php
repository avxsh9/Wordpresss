<div class="uem-dashboard-wrapper">
    <h2>Seller Dashboard</h2>
    <div class="uem-dashboard-stats">
        <div class="stat-card">
            <small>Active Listings</small>
            <div class="number"><?php echo $query->post_count; ?></div>
        </div>
        <div class="stat-card">
            <small>Total Sales</small>
            <div class="number">₹0</div>
        </div>
    </div>

    <h3>My Listings</h3>
    <table class="uem-dashboard-table">
        <thead>
            <tr>
                <th>Event</th>
                <th>Category</th>
                <th>Price</th>
                <th>Seats</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post(); 
                $post_id = get_the_ID();
                $master_id = get_post_meta( $post_id, 'event_id', true );
                $master = get_post( $master_id );
                $title = $master ? $master->post_title : get_the_title();
                
                $price = get_post_meta( $post_id, 'uem_price', true );
                $seats = get_post_meta( $post_id, 'uem_seats', true );
                $status = get_post_meta( $post_id, 'uem_status', true );
                $cat_terms = get_the_terms( $post_id, 'event_category' );
                $cat = ! empty($cat_terms) ? $cat_terms[0]->name : 'Event';
                ?>
                <tr>
                    <td><strong><?php echo esc_html( $title ); ?></strong></td>
                    <td><?php echo esc_html( $cat ); ?></td>
                    <td>₹<?php echo esc_html( $price ); ?></td>
                    <td><?php echo esc_html( $seats ); ?></td>
                    <td><span class="uem-status-badge <?php echo esc_attr( $status ); ?>"><?php echo ucfirst( $status ); ?></span></td>
                </tr>
            <?php endwhile; else : ?>
                <tr><td colspan="5">No listings found. <a href="<?php echo home_url('/add-listing'); ?>">List one now!</a></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
