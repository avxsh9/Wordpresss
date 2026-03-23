<?php
/**
 * Template Name: Seller Dashboard
 */
get_header();
?>

<main id="main">
    <section class="section" style="padding-top: 100px;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 30px;">
                <h1>Seller Dashboard</h1>
                <a href="<?php echo esc_url(home_url('/sell-ticket/')); ?>" class="btn btn-primary"><i class="fas fa-plus"></i> List New Ticket</a>
            </div>
            
            <div class="seller-stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Earnings</div>
                    <div class="stat-value success">₹0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Active Listings</div>
                    <div class="stat-value">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Tickets Sold</div>
                    <div class="stat-value">0</div>
                </div>
            </div>

            <h2 style="margin-bottom: 20px;">Active Listings</h2>
            <div class="table-container">
                <table class="seller-table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="listingsTable">
                        <tr>
                            <td colspan="6" style="padding: 40px; text-align: center; color: #666;">No listings found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
<?php get_footer(); ?>
