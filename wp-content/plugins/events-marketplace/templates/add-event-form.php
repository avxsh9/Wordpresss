<div class="tae-form-wrapper">
    <form id="tae-add-event-form" class="tae-form">
        <h2>List Your Ticket</h2>
        <div class="tae-form-group">
            <label>Event Category</label>
            <select name="event_category" id="tae-form-cat" required>
                <option value="">Select Category</option>
                <option value="movie">Movie</option>
                <option value="sports">Sports / Match</option>
            </select>
        </div>

        <div id="tae-master-selector" class="tae-form-group" style="display:none;">
            <label id="tae-master-label">Select Event</label>
            <select name="master_id" id="tae-master-id" required>
                <option value="">-- Loading --</option>
            </select>
        </div>

        <div id="tae-dynamic-fields" style="display:none;">
            <!-- Movie Specific -->
            <div id="tae-movie-fields" class="tae-cat-fields">
                <div class="tae-form-group">
                    <label>Cinema / Venue</label>
                    <input type="text" name="venue" placeholder="e.g. IMAX, Mumbai">
                </div>
                <div class="tae-form-grid">
                    <div class="tae-form-group"><label>Date</label><input type="date" name="date"></div>
                    <div class="tae-form-group"><label>Time</label><input type="time" name="time"></div>
                </div>
            </div>

            <!-- Common Fields -->
            <div class="tae-form-grid">
                <div class="tae-form-group"><label>Seat Info</label><input type="text" name="seats" placeholder="Row F, Seat 12"></div>
                <div class="tae-form-group"><label>Price (₹)</label><input type="number" name="price" placeholder="0"></div>
            </div>
            
            <button type="submit" class="tae-btn-submit">Add Listing</button>
            <div id="tae-form-msg"></div>
        </div>
    </form>
</div>
