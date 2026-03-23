<div class="uem-form-wrapper">
    <form id="uem-add-listing-form" class="uem-form">
        <h2>List Your Ticket</h2>
        <p>Select an official event from the list below to sell your tickets.</p>
        
        <div class="uem-form-group">
            <label>Select Event</label>
            <select name="master_id" id="uem-master-select" required>
                <option value="">-- Loading Events --</option>
            </select>
        </div>

        <div id="uem-event-info" class="uem-info-box" style="display:none;">
            <!-- Dynamic Event Info -->
        </div>

        <div class="uem-form-grid">
            <div class="uem-form-group">
                <label>Price (₹)</label>
                <input type="number" name="price" required placeholder="e.g. 500">
            </div>
            <div class="uem-form-group">
                <label>Seats / Area</label>
                <input type="text" name="seats" required placeholder="e.g. Row A, 12">
            </div>
        </div>

        <button type="submit" class="uem-btn-submit">Add Listing</button>
        <div id="uem-form-msg"></div>
    </form>
</div>
