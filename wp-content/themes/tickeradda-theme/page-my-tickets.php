<?php
/**
 * Template Name: My Tickets
 * Shows the buyer's requested tickets (orders) and their status.
 */
get_header();
?>

<main id="main">
    <div class="buyer-dash-wrap">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:30px;">
            <div>
                <h1 class="gradient-text">My Requests</h1>
                <p class="sub">Tickets you've requested — seller contact details appear here after you claim.</p>
            </div>
            <button class="refresh-btn" onclick="loadMyOrders()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div id="orders-grid" class="orders-grid">
            <div style="text-align:center;color:#aaa;padding:50px;">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem;display:block;margin-bottom:12px;"></i>
                Loading your tickets...
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // buyer-dashboard.js is enqueued globally — just call loadMyOrders()
    if (typeof loadMyOrders === 'function') {
        loadMyOrders();
    } else {
        // Fallback: load the script if not already present
        const s = document.createElement('script');
        s.src = '<?php echo esc_url(get_template_directory_uri()); ?>/assets/js/buyer/buyer-dashboard.js?ver=<?php echo time(); ?>';
        s.onload = () => { if (typeof loadMyOrders === 'function') loadMyOrders(); };
        document.body.appendChild(s);
    }
});
</script>

<?php get_footer(); ?>
