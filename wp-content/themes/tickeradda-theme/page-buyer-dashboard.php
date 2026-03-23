<?php
/**
 * Template Name: Buyer Dashboard
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
            <div style="text-align:center; color:#aaa; padding:50px;">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem; margin-bottom:12px; display:block;"></i>
                Loading your requests...
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>
