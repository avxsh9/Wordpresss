<?php
/**
 * Template Name: Payouts
 */
get_header();
?>

<main id="main">
    <section class="section">
        <div class="container">
            <div style="margin-bottom: 20px;">
                <a href="<?php echo home_url('/seller-dashboard/'); ?>" style="color: var(--color-text-muted); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="section-header" style="margin-bottom: 2rem;">
                <h1 class="gradient-text">Earnings & Payouts</h1>
                <p style="color: var(--color-text-muted);">Manage your sales revenue and track payout status.</p>
            </div>

            <div class="grid grid-2" style="margin-bottom: 40px;">
                <div class="card">
                    <div style="color: var(--text-gray); margin-bottom: 10px;">Total Earnings (Gross)</div>
                    <div style="font-size: 28px; font-weight: 700; color: #fff;" id="totalRevenue">₹0</div>
                </div>
                <div class="card" style="border-bottom: 3px solid #10B981;">
                    <div style="color: var(--text-gray); margin-bottom: 10px;">Net Settlement</div>
                    <div style="font-size: 32px; font-weight: 700; color: #10B981;" id="netSettlement">₹0</div>
                </div>
            </div>

            <h2 style="margin-bottom: 20px;">Payment Settlements</h2>
            <div class="table-container" style="overflow-x: auto; background: var(--card-bg); border-radius: 12px; border: 1px solid var(--glass-border);">
                <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--glass-border); color: var(--text-gray);">
                            <th style="padding: 15px;">Date</th>
                            <th style="padding: 15px;">Event</th>
                            <th style="padding: 15px;">Amount</th>
                            <th style="padding: 15px;">Net Payout</th>
                            <th style="padding: 15px;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="payoutsTable">
                        <tr>
                            <td colspan="6" style="padding: 40px; text-align: center; color: var(--text-gray);">
                                <i class="fas fa-spinner fa-spin"></i> Loading transactions...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background: rgba(59, 130, 246, 0.1); border-radius: 12px; border-left: 4px solid var(--primary);">
                <i class="fas fa-info-circle"></i> Settlements are processed within 48 hours after the event has successfully ended.
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    const token = localStorage.getItem('token');
    if (!token) return;

    try {
        const res = await fetch('/api/tickets/my-tickets', {
            headers: { 'x-auth-token': token }
        });
        const tickets = await res.json();
        const soldTickets = tickets.filter(t => t.status === 'sold');
        
        let totalRev = 0;
        let totalFee = 0;
        let html = '';

        if (soldTickets.length === 0) {
            html = '<tr><td colspan="5" style="padding: 40px; text-align: center; color: var(--text-gray);">No settlements found yet. Sell your first ticket to see payouts!</td></tr>';
        } else {
            soldTickets.forEach(t => {
                const amount = t.price * t.quantity;
                const net = amount;
                
                totalRev += amount;

                html += `
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 15px; color: #aaa;">${new Date(t.createdAt).toLocaleDateString()}</td>
                    <td style="padding: 15px; font-weight: 500;">${t.event}</td>
                    <td style="padding: 15px;">₹${amount}</td>
                    <td style="padding: 15px; color: #10B981; font-weight: 600;">₹${net}</td>
                    <td style="padding: 15px;"><span class="badge badge-warning">Processing</span></td>
                </tr>`;
            });
        }

        document.getElementById('totalRevenue').textContent = '₹' + totalRev.toLocaleString();
        document.getElementById('netSettlement').textContent = '₹' + (totalRev).toLocaleString();
        document.getElementById('payoutsTable').innerHTML = html;

    } catch (err) {
        console.error(err);
        document.getElementById('payoutsTable').innerHTML = '<tr><td colspan="6" style="padding: 20px; text-align: center; color: #ef4444;">Failed to load payouts.</td></tr>';
    }
});
</script>
<?php get_footer(); ?>
