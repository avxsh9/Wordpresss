<?php
/**
 * Template Name: Sell Sport Ticket
 */
get_header();
?>

<main id="main">
    <section class="section">
        <div class="container" style="max-width: 800px; margin-top: 50px; margin-bottom: 50px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                <a href="<?php echo esc_url(home_url('/seller-dashboard/')); ?>" style="color: var(--color-text-muted); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            
            <h1 style="margin-bottom: 30px; text-align: center;">Sell Sports Ticket</h1>
            
            <div class="form-card" style="max-width: 600px;">
                <!-- The Actual Seller Form -->
                <form id="sellSportForm">
                    <div class="form-group">
                        <label class="form-label">Match Name / Tournament <span style="color:var(--color-danger);">*</span></label>
                        <input class="form-input" type="text" id="matchName" name="match_name" placeholder="e.g. IPL 2025">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Teams <span style="color:var(--color-danger);">*</span></label>
                        <input class="form-input" type="text" id="matchTeams" name="teams" placeholder="e.g. Mumbai Indians vs Chennai Super Kings">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Match Poster URL (Optional)</label>
                        <input class="form-input" type="url" id="matchPoster" name="match_poster" placeholder="https://...">
                        <small style="color:var(--color-text-muted); margin-top:5px; display:block;">Leave empty to use default sports cover image.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Stadium / Venue <span style="color:var(--color-danger);">*</span></label>
                        <input class="form-input" type="text" id="matchVenue" name="venue" placeholder="e.g. Wankhede Stadium, Mumbai">
                    </div>

                    <div style="display: flex; gap: 20px; margin-bottom: 24px;">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label class="form-label">Match Date <span style="color:var(--color-danger);">*</span></label>
                            <input class="form-input" type="date" id="matchDate" name="sport_date" style="color-scheme: dark;">
                        </div>
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label class="form-label">Match Time <span style="color:var(--color-danger);">*</span></label>
                            <input class="form-input" type="time" id="matchTime" name="sport_time" style="color-scheme: dark;">
                        </div>
                    </div>

                    <div style="display: flex; gap: 20px; margin-bottom: 24px;">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label class="form-label">Quantity <span style="color:var(--color-danger);">*</span></label>
                            <input class="form-input" type="number" id="matchQty" name="quantity" min="1" value="1">
                        </div>
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label class="form-label">Price / Ticket (₹) <span style="color:var(--color-danger);">*</span></label>
                            <input class="form-input" type="number" id="matchPrice" name="price" placeholder="0.00">
                        </div>
                    </div>

                    <button type="submit" id="submitSportBtn" class="btn btn-primary" style="width: 100%; justify-content: center; font-size: 16px; padding: 14px;">
                        List Sports Ticket
                    </button>
                </form>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>
