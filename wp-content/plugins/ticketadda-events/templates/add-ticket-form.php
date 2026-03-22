<div class="tae-form-wrapper">
    <form id="tae-add-ticket-form" class="tae-form">
        <h2>List Your Ticket</h2>
        <div class="tae-form-group">
            <label>Event Type</label>
            <select name="event_type" id="tae-type-selector" required>
                <option value="movie">Movie</option>
                <option value="sports">Sports / IPL</option>
            </select>
        </div>

        <!-- Movie Specific Fields -->
        <div id="tae-movie-fields" class="tae-type-fields">
            <div class="tae-form-group">
                <label>Movie Name</label>
                <input type="text" name="title" placeholder="e.g. Inception">
            </div>
            <div class="tae-form-group">
                <label>Venue (Cinema)</label>
                <input type="text" name="venue" placeholder="e.g. PVR, Mumbai">
            </div>
            <div class="tae-form-group">
                <label>Date</label>
                <input type="date" name="date">
            </div>
            <div class="tae-form-group">
                <label>Time</label>
                <input type="time" name="time">
            </div>
        </div>

        <!-- Sports Specific Fields (Admin Controlled) -->
        <div id="tae-sports-fields" class="tae-type-fields" style="display:none;">
            <div class="tae-form-group">
                <label>Select Match (Master Events)</label>
                <select name="event_id">
                    <option value="">-- Choose Match --</option>
                    <?php foreach ( $master_events as $event ) : ?>
                        <option value="<?php echo $event->id; ?>"><?php echo esc_html( $event->event_name ); ?> (<?php echo $event->event_date; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Common Fields -->
        <div class="tae-form-grid">
            <div class="tae-form-group">
                <label>Seat/Stand Info</label>
                <input type="text" name="seat_info" required placeholder="e.g. Row F, Seat 12">
            </div>
            <div class="tae-form-group">
                <label>Price (₹)</label>
                <input type="number" name="price" required placeholder="0">
            </div>
            <div class="tae-form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" required value="1">
            </div>
        </div>

        <button type="submit" class="tae-btn-submit">List Ticket Now</button>
        <div id="tae-form-msg"></div>
    </form>
</div>
