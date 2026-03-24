<?php
/**
 * Template Name: Events
 */
get_header();
?>

<main id="main">
    <section class="section" style="padding-top: 120px; padding-bottom: 40px; text-align: center;">
        <div class="container">
            <h1 class="gradient-text" style="font-size: 3rem; margin-bottom: 15px;">Discover Events</h1>
            <p style="color: var(--text-gray); max-width: 600px; margin: 0 auto 30px;">
                Browse verified tickets for the hottest concerts, matches, and shows.
            </p>
            <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px; padding: 15px; max-width: 600px; margin: 0 auto 30px; display: flex; align-items: center; gap: 15px; justify-content: center;">
                <i class="fas fa-gift" style="color: var(--primary); font-size: 1.5rem;"></i>
                <div style="text-align: left;">
                    <h4 style="margin: 0; color: #fff;">TicketAdda is Free for now!</h4>
                    <p style="margin: 0; font-size: 0.85rem; color: var(--text-gray);">Enjoy 0% service fees. You can contact the seller directly to buy tickets.</p>
                </div>
            </div>

            <div class="hero-categories" style="justify-content: center;">
                <button class="category-pill filter-btn" data-category="all">All</button>
                <button class="category-pill filter-btn" data-category="music">Music</button>
                <button class="category-pill filter-btn" data-category="sports">Sports</button>
                <button class="category-pill filter-btn" data-category="comedy">Comedy</button>
                <button class="category-pill filter-btn" data-category="theatre">Theatre</button>
                <button class="category-pill filter-btn" data-category="movies">Movies</button>
            </div>
        </div>
    </section>
    
    <section class="section" style="padding-top:0;">
        <div class="container">
            <div class="grid grid-3" id="eventsGrid">
                <div style="grid-column: 1/-1; text-align: center; color: var(--text-gray); padding: 60px;">
                    <i class="fas fa-spinner fa-spin fa-3x" style="color: var(--primary);"></i>
                    <p style="margin-top: 20px; font-size: 1.1rem;">Fetching live tickets...</p>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
// Expose the URL ?category= param for events.js to pick up and pre-filter
window.TA_INITIAL_CATEGORY = '<?php echo esc_js( sanitize_key( $_GET['category'] ?? 'all' ) ); ?>';
</script>

<?php get_footer(); ?>
