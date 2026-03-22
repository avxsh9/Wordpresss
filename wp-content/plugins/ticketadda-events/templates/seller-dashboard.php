<div class="tae-dashboard-wrapper">
    <h2>Seller Dashboard</h2>
    <div class="tae-dashboard-stats" style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px;">
        <div style="background:#f8f9fa; padding:15px; border-radius:8px; border:1px solid #ddd;">
            <small>Active Listings</small>
            <div style="font-size:1.5rem; font-weight:700;"><?php echo $query->post_count; ?></div>
        </div>
        <div style="background:#f8f9fa; padding:15px; border-radius:8px; border:1px solid #ddd;">
            <small>Total Sales</small>
            <div style="font-size:1.5rem; font-weight:700;">₹0</div>
        </div>
        <div style="background:#f8f9fa; padding:15px; border-radius:8px; border:1px solid #ddd;">
            <small>Orders</small>
            <div style="font-size:1.5rem; font-weight:700;">0</div>
        </div>
    </div>

    <h3>My Listings</h3>
    <table class="tae-dashboard-table">
        <thead>
            <tr>
                <th>Event</th>
                <th>Type</th>
                <th>Price</th>
                <th>Qty</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post(); 
                $terms = get_the_terms( get_the_ID(), 'event_type' );
                $type = ! empty( $terms ) ? $terms[0]->name : 'Other';
                $price = get_post_meta( get_the_ID(), 'tae_price', true );
                $qty = get_post_meta( get_the_ID(), 'tae_quantity', true );
                $status = get_post_status();
                ?>
                <tr>
                    <td><strong><?php the_title(); ?></strong></td>
                    <td><?php echo esc_html( $type ); ?></td>
                    <td>₹<?php echo esc_html( $price ); ?></td>
                    <td><?php echo esc_html( $qty ); ?></td>
                    <td><span class="tae-status-badge <?php echo esc_attr( $status ); ?>"><?php echo ucfirst( $status ); ?></span></td>
                    <td><a href="<?php the_permalink(); ?>">View</a></td>
                </tr>
            <?php endwhile; else : ?>
                <tr><td colspan="6">No listings found. <a href="<?php echo home_url('/add-ticket'); ?>">Add your first ticket!</a></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
