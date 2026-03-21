<?php
/**
 * Template Name: Orders
 */
get_header();
?>

<main id="main">
    <section class="section" style="padding-top: 50px;">
        <div class="container">
            <div style="margin-bottom: 20px;">
                <a href="<?php echo home_url('/buyer-dashboard/'); ?>" style="color: var(--color-text-muted); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="section-header" style="margin-bottom: 2rem;">
                <h1 class="gradient-text">Order History</h1>
                <p style="color: var(--color-text-muted);">View your past purchases and download tickets.</p>
            </div>
            
            <div id="my-orders-container" class="grid grid-3">
                <div style="text-align: center; color: #aaa; padding: 40px; grid-column: 1/-1;">
                    <i class="fas fa-spinner fa-spin"></i> Loading your orders...
                </div>
            </div>
        </div>
    </section>
</main>

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
    transition: transform 0.2s;
}
</style>

<!-- Order history is loaded via assets/js/buyer/order-history.js -->
<?php get_footer(); ?>
