<div class="mtm-sell-form-wrapper">
    <form id="mtm-sell-form" class="mtm-form">
        <h2>List Your Movie Ticket</h2>
        <div class="mtm-form-grid">
            <div class="mtm-form-group">
                <label>Movie Title</label>
                <input type="text" name="title" required placeholder="e.g. Inception">
            </div>
            <div class="mtm-form-group">
                <label>Location</label>
                <input type="text" name="location" required placeholder="e.g. PVR, Mumbai">
            </div>
            <div class="mtm-form-group">
                <label>Date & Time</label>
                <input type="datetime-local" name="datetime" required>
            </div>
            <div class="mtm-form-group">
                <label>Seat Details</label>
                <input type="text" name="seat_info" required placeholder="e.g. Row F, Seat 12">
            </div>
            <div class="mtm-form-group">
                <label>Price (₹)</label>
                <input type="number" name="price" required placeholder="0">
            </div>
            <div class="mtm-form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" required value="1">
            </div>
        </div>
        <button type="submit" class="mtm-btn-submit">List Ticket</button>
        <div id="mtm-form-msg"></div>
    </form>
</div>
