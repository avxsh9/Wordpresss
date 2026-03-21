<?php
/**
 * Template Name: Seller Dashboard
 */
get_header();
?>

<main id="main">
<section class="section" style="padding-top: 100px;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h1>Seller Dashboard</h1>
                <a href="<?php echo esc_url(home_url('/sell-ticket/')); ?>" class="btn btn-primary"><i class="fas fa-plus"></i> List New Ticket</a>
            </div>
            <div
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px;">
                <div
                    style="background: var(--card-bg); padding: 25px; border-radius: 12px; border: 1px solid var(--glass-border);">
                    <div style="color: var(--text-gray); margin-bottom: 10px;">Total Earnings</div>
                    <div style="font-size: 32px; font-weight: 700; color: #10B981;">₹0</div>
                </div>
                <div
                    style="background: var(--card-bg); padding: 25px; border-radius: 12px; border: 1px solid var(--glass-border);">
                    <div style="color: var(--text-gray); margin-bottom: 10px;">Active Listings</div>
                    <div style="font-size: 32px; font-weight: 700;">0</div>
                </div>
                <div
                    style="background: var(--card-bg); padding: 25px; border-radius: 12px; border: 1px solid var(--glass-border);">
                    <div style="color: var(--text-gray); margin-bottom: 10px;">Tickets Sold</div>
                    <div style="font-size: 32px; font-weight: 700;">0</div>
                </div>
            </div>
            <h2 style="margin-bottom: 20px;">Active Listings</h2>
            <div class="table-container" style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                    <thead>
                        <tr
                            style="text-align: left; border-bottom: 1px solid var(--glass-border); color: var(--text-gray);">
                            <th style="padding: 15px;">Event</th>
                            <th style="padding: 15px;">Date</th>
                            <th style="padding: 15px;">Quantity</th>
                            <th style="padding: 15px;">Price</th>
                            <th style="padding: 15px;">Status</th>
                            <th style="padding: 15px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="listingsTable">
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td colspan="6" style="padding: 20px; text-align: center; color: var(--text-gray);">No
                                listings found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
<?php get_footer(); ?>
