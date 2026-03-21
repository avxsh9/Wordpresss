<?php
/**
 * Template Name: My Tickets
 */
get_header();
?>

<style>
.listings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 25px;
}
.listing-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 24px;
    transition: transform 0.2s, box-shadow 0.2s;
}
.listing-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    border-color: rgba(255, 255, 255, 0.2);
}
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding-bottom: 15px;
}
.card-header h3 {
    margin: 0;
    font-size: 1.2rem;
    color: #fff;
    line-height: 1.4;
}
.card-body p {
    margin: 8px 0;
    color: #ccc;
    display: flex;
    justify-content: space-between;
}
.card-body strong {
    color: #888;
    font-weight: 500;
}
.card-footer {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px dashed rgba(255, 255, 255, 0.1);
    font-size: 0.85rem;
    color: #666;
    text-align: right;
}
</style>

<main id="main">
    <section class="section" style="padding-top: 50px;">
        <div class="container">
            <div style="margin-bottom: 20px;">
                <a href="<?php echo home_url('/seller-dashboard/'); ?>" style="color: var(--color-text-muted); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 class="gradient-text">My Tickets</h1>
                    <p style="color: var(--color-text-muted);">Track your ticket listings and sales.</p>
                </div>
                <button onclick="loadListings()" class="btn btn-outline" style="cursor: pointer;">
                    <i class="fas fa-sync-alt"></i> Refresh Status
                </button>
            </div>
            <div id="my-listings-container" class="grid grid-3">
                <div style="text-align: center; color: #aaa; padding: 40px; grid-column: 1/-1;">
                    <i class="fas fa-spinner fa-spin"></i> Loading your tickets...
                </div>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof loadListings === 'function') {
        loadListings();
    } else {
        const s = document.createElement('script');
        s.src = '<?php echo esc_url(get_template_directory_uri()); ?>/assets/js/seller/my-listings.js';
        document.body.appendChild(s);
    }
});
</script>

<?php get_footer(); ?>
