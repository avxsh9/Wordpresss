<div class="stm-sell-form-wrapper">
    <form id="stm-sell-form" class="stm-form">
        <h2>List Your Sports/IPL Ticket</h2>
        <div class="stm-form-grid">
            <div class="stm-form-group">
                <label>Match Title</label>
                <input type="text" name="title" required placeholder="e.g. IPL 2025 Match 1">
            </div>
            <div class="stm-form-group">
                <label>Teams</label>
                <input type="text" name="teams" required placeholder="e.g. MI vs CSK">
            </div>
            <div class="stm-form-group">
                <label>Stadium / Venue</label>
                <input type="text" name="location" required placeholder="e.g. Wankhede Stadium, Mumbai">
            </div>
            <div class="stm-form-group">
                <label>Date & Time</label>
                <input type="datetime-local" name="datetime" required>
            </div>
            <div class="stm-form-group">
                <label>Seat/Stand Info</label>
                <input type="text" name="seat_info" required placeholder="e.g. North Stand, Row A">
            </div>
            <div class="stm-form-group">
                <label>Price (₹)</label>
                <input type="number" name="price" required placeholder="0">
            </div>
            <div class="stm-form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" required value="1">
            </div>
        </div>
        <button type="submit" class="stm-btn-submit">List Match Ticket</button>
        <div id="stm-form-msg"></div>
    </form>
</div>
