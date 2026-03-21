<?php
/**
 * Template Name: Sell Movie Ticket
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
            
            <h1 style="margin-bottom: 30px; text-align: center;">Sell Movie Ticket</h1>
            
            <div class="form-card" style="max-width: 600px;">
                <!-- TMDB Search Box Wrapper -->
                <div class="form-group" style="position: relative;">
                    <label class="form-label">Movie Name <span style="color:var(--color-danger);">*</span></label>
                    <div style="position: relative;">
                        <input class="form-input" type="text" id="movieSearchInput" placeholder="Type movie name to search..." style="padding-left: 40px;">
                        <i class="fas fa-search" style="position: absolute; left: 15px; top: 14px; color: var(--color-text-muted);"></i>
                        <i id="movieSearchSpinner" class="fas fa-spinner fa-spin" style="position: absolute; right: 15px; top: 14px; color: var(--color-primary); display: none;"></i>
                    </div>
                    
                    <!-- Dropdown for TMDB Results -->
                    <div id="movieSearchResults" style="display:none; position:absolute; top:100%; left:0; right:0; background:var(--color-bg-card); border:1px solid var(--color-border); border-top:none; border-radius:0 0 var(--radius-sm) var(--radius-sm); z-index:100; max-height:300px; overflow-y:auto; box-shadow:var(--shadow-card);">
                    </div>
                </div>

                <!-- Poster Preview (Hidden initially) -->
                <div id="moviePosterPreview" style="display:none; margin-bottom: 24px; text-align:center;">
                    <img id="moviePosterImg" src="" alt="Movie Poster" style="max-width:150px; border-radius:var(--radius-sm); border:1px solid var(--color-border); box-shadow:var(--shadow-card);">
                </div>

                <!-- The Actual Seller Form -->
                <form id="sellMovieForm">
                    <input type="hidden" id="movie_name" name="movie_name">
                    <input type="hidden" id="poster_url" name="poster_url">

                    <div class="form-group">
                        <label class="form-label">Language <span style="color:var(--color-danger);">*</span></label>
                        <select class="form-input" id="movieLanguage" name="language">
                            <option value="">Select Language...</option>
                            <option value="Hindi">Hindi</option>
                            <option value="English">English</option>
                            <option value="Tamil">Tamil</option>
                            <option value="Telugu">Telugu</option>
                            <option value="Malayalam">Malayalam</option>
                            <option value="Kannada">Kannada</option>
                            <option value="Punjabi">Punjabi</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Venue / Cinema <span style="color:var(--color-danger);">*</span></label>
                        <input class="form-input" type="text" id="movieVenue" name="venue" placeholder="e.g. PVR Imax, Lower Parel">
                    </div>

                    <div style="display: flex; gap: 20px; margin-bottom: 24px;">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label class="form-label">Quantity <span style="color:var(--color-danger);">*</span></label>
                            <input class="form-input" type="number" id="movieQty" name="quantity" min="1" value="1">
                        </div>
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label class="form-label">Price / Ticket (₹) <span style="color:var(--color-danger);">*</span></label>
                            <input class="form-input" type="number" id="moviePrice" name="price" placeholder="0.00">
                        </div>
                    </div>

                    <button type="submit" id="submitMovieBtn" class="btn btn-primary" style="width: 100%; justify-content: center; font-size: 16px; padding: 14px;">
                        List Movie Ticket
                    </button>
                </form>
            </div>
        </div>
    </section>
</main>

<style>
.tmdb-result-item {
    padding: 10px 15px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    border-bottom: 1px solid #334155;
    transition: background 0.2s;
}
.tmdb-result-item:hover {
    background: #334155;
}
.tmdb-result-item img {
    width: 30px;
    height: 45px;
    object-fit: cover;
    border-radius: 4px;
}
</style>

<?php get_footer(); ?>
