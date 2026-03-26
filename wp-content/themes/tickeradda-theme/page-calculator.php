<?php
/**
 * Template Name: Calculator
 */
get_header();
?>

<main id="main">
<section class="section">
        <div class="container" style="max-width: 600px;">
            <div
                style="background: var(--card-bg); padding: 40px; border-radius: 24px; border: 1px solid var(--glass-border); box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                <div style="text-align: center; margin-bottom: 30px;">
                    <div
                        style="width: 60px; height: 60px; background: rgba(59, 130, 246, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; margin-bottom: 15px;">
                        <i class="fas fa-calculator" style="font-size: 24px; color: var(--primary);"></i>
                    </div>
                    <h1 style="font-size: 2rem; margin-bottom: 5px;">Payout Calculator</h1>
                    <p style="color: var(--text-gray);">Calculate your earnings before you sell.</p>
                </div>
                <div style="margin-bottom: 30px;">
                    <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                        <div style="flex: 1;">
                            <label style="display: block; color: var(--text-gray); margin-bottom: 8px;">Ticket Price
                                (₹)</label>
                            <input type="number" id="calcPrice" placeholder="0" min="0" max="1000000"
                                style="width: 100%; padding: 15px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 12px; color: white; font-size: 1.2rem; font-weight: 600;">
                        </div>
                        <div style="width: 100px;">
                            <label style="display: block; color: var(--text-gray); margin-bottom: 8px;">Qty</label>
                            <input type="number" id="calcQty" value="1" min="1"
                                style="width: 100%; padding: 15px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 12px; color: white; font-size: 1.2rem; font-weight: 600; text-align: center;">
                        </div>
                    </div>
                </div>
                <div style="background: rgba(0,0,0,0.2); border-radius: 16px; padding: 25px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <span style="color: var(--text-gray);">Total Sales</span>
                        <span style="font-weight: 600;" id="totalSales">₹0</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px; background: rgba(16, 185, 129, 0.1); padding: 10px; border-radius: 8px; border: 1px dashed #10b981;">
                        <span style="color: #10b981; font-weight: 600; font-size: 0.9rem;">Platform Fee (0%)</span>
                        <span style="color: #10b981; font-weight: 700;">₹0</span>
                    </div>
                    <div style="border-top: 1px solid rgba(255,255,255,0.1); margin: 15px 0;"></div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 1.1rem; color: var(--text-color);">Net Payout</span>
                        <span style="font-size: 1.8rem; font-weight: 700; color: #10b981;" id="netPayout">₹0</span>
                    </div>
                    <div style="text-align: center; margin-top: 15px; font-size: 0.85rem; color: #10b981;">
                        TicketAdda doesn't take any commission; zero fees applied right now! Enjoy.
                    </div>
                </div>
                <div style="text-align: center; margin-top: 30px;">
                    <a href="<?php echo esc_url(home_url('/sell-ticket/')); ?>" class="btn btn-primary"
                        style="width: 100%; justify-content: center;">
                        Start Selling Now
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    
    <script>
        // Inline script for simplicity, or move to external file
        document.addEventListener('DOMContentLoaded', () => {
            const priceInput = document.getElementById('calcPrice');
            const qtyInput = document.getElementById('calcQty');
            const totalSalesEl = document.getElementById('totalSales');
            const netPayoutEl = document.getElementById('netPayout');
            const MAX_TOTAL = 1000000; // ₹10 Lakh limit

            // Error message element
            let errEl = document.getElementById('calcError');
            if (!errEl) {
                errEl = document.createElement('div');
                errEl.id = 'calcError';
                errEl.style.cssText = 'display:none; color:#ef4444; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:8px; padding:10px 14px; margin-top:12px; font-size:0.9rem; text-align:center;';
                errEl.textContent = '⚠️ Maximum limit is ₹10,00,000 (10 Lakh). Please enter a lower amount.';
                document.getElementById('calcPrice').closest('div[style*="margin-bottom: 30px"]').appendChild(errEl);
            }

            function calculate() {
                const price = parseFloat(priceInput.value) || 0;
                const qty   = Math.max(1, parseFloat(qtyInput.value) || 1);
                const total = price * qty;

                if (total > MAX_TOTAL) {
                    errEl.style.display = 'block';
                    priceInput.style.borderColor = '#ef4444';
                    totalSalesEl.textContent = '—';
                    netPayoutEl.textContent   = '—';
                    return;
                }

                errEl.style.display = 'none';
                priceInput.style.borderColor = '';
                const fee    = 0; // ZERO COMMISSION
                const payout = total;
                totalSalesEl.textContent = `₹${total.toLocaleString('en-IN')}`;
                netPayoutEl.textContent  = `₹${payout.toLocaleString('en-IN')}`;
            }
            priceInput.addEventListener('input', calculate);
            qtyInput.addEventListener('input', calculate);
        });
    </script>
</main>
<?php get_footer(); ?>
