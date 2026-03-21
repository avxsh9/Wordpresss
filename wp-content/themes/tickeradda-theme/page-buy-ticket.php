<?php
/**
 * Template Name: Buy ticket
 */
get_header();
?>

<main id="main">
<section class="section" style="padding-top: 120px;">
        <div class="container" style="max-width: 800px;">
            <a href="#" onclick="history.back()"
                style="color: var(--text-gray); text-decoration: none; margin-bottom: 20px; display: inline-block;">
                <i class="fas fa-arrow-left"></i> Back to Events
            </a>
            <h1 style="margin-bottom: 10px;">Review your order</h1>
            <p style="color: var(--text-gray); margin-bottom: 40px;">Please review the ticket details before proceeding
                to payment.</p>
            <div id="loadingState" style="text-align: center; padding: 50px;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p style="margin-top: 10px;">Loading ticket details...</p>
            </div>
            <div id="errorState"
                style="display: none; text-align: center; padding: 50px; background: rgba(220, 38, 38, 0.1); border-radius: 12px; border: 1px solid rgba(220, 38, 38, 0.3);">
                <i class="fas fa-exclamation-circle fa-2x" style="color: #dc2626;"></i>
                <h3 style="margin-top: 10px; color: #dc2626;">Ticket Not Found</h3>
                <p>This ticket may have been sold or removed.</p>
                <a href="<?php echo esc_url(home_url('/events/')); ?>" class="btn btn-primary" style="margin-top: 20px;">Browse Other
                    Events</a>
            </div>
            <div id="ticketDetails" style="display: none;">
                <div class="card" style="padding: 30px; margin-bottom: 30px;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                        <div>
                            <span class="badge" id="ticketTypeBadge"
                                style="background: rgba(255,255,255,0.1); font-size: 0.8rem; margin-bottom: 10px; display: inline-block;">TYPE</span>
                            <h2 id="eventName" style="margin: 0; font-size: 1.8rem;">Event Name</h2>
                            <p id="eventDate" style="color: var(--primary); margin-top: 5px; font-weight: 500;">Date</p>
                            <p id="eventLocation" style="color: var(--text-gray); margin-top: 5px; font-size: 0.9rem;">
                                Location</p>
                        </div>
                        <div style="text-align: right;">
                            <p style="color: var(--text-gray); font-size: 0.9rem;">Total Price</p>
                            <h2 id="ticketPrice" style="margin: 0; color: var(--text-color);">₹0</h2>
                        </div>
                    </div>
                    <div class="grid grid-2" style="padding: 20px; background: rgba(255,255,255,0.03); border-radius: 12px; margin-bottom: 20px;">
                        <div>
                            <p style="font-size: 0.8rem; color: var(--text-gray); margin-bottom: 5px;">SECTION</p>
                            <p id="ticketSection" style="font-weight: 600;">-</p>
                        </div>
                        <div>
                            <p style="font-size: 0.8rem; color: var(--text-gray); margin-bottom: 5px;">ROW</p>
                            <p id="ticketRow" style="font-weight: 600;">-</p>
                        </div>
                        <div>
                            <p style="font-size: 0.8rem; color: var(--text-gray); margin-bottom: 5px;">SEAT</p>
                            <p id="ticketSeat" style="font-weight: 600;">-</p>
                        </div>
                        <div>
                            <p style="font-size: 0.8rem; color: var(--text-gray); margin-bottom: 5px;">QUANTITY</p>
                            <p id="ticketQty" style="font-weight: 600;">1</p>
                        </div>
                    </div>
                    <div id="sellerInfoCard"
                        style="display: flex; align-items: center; gap: 15px; padding: 20px; background: rgba(255,255,255,0.03); border-radius: 12px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.05);">
                        <div
                            style="width: 50px; height: 50px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem;">
                            <span id="sellerInitials">?</span>
                        </div>
                        <div>
                            <h4 id="sellerName" style="margin: 0; font-size: 1.1rem; color: #fff;">Seller Name</h4>
                            <div id="sellerRating" style="margin-top: 5px; color: #f59e0b; font-size: 0.9rem;">
                                <i class="fas fa-star"></i> 4.5 <span style="color: var(--text-gray);">(0
                                    reviews)</span>
                            </div>
                        </div>
                        <div style="margin-left: auto;">
                            <span class="badge badge-success"><i class="fas fa-check-circle"></i> Verified</span>
                        </div>
                    </div>
                    <div
                        style="display: flex; gap: 10px; align-items: center; padding: 15px; background: rgba(59, 130, 246, 0.1); border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.2);">
                        <i class="fas fa-shield-alt" style="color: var(--primary); font-size: 1.2rem;"></i>
                        <div>
                            <h4 style="margin: 0; font-size: 1rem;">100% Buyer Guarantee</h4>
                            <p style="margin: 0; font-size: 0.8rem; color: var(--text-gray);">Your tickets will be valid
                                and authentic.</p>
                        </div>
                    </div>
                </div>
                <div class="card" style="padding: 30px;">
                    <h3>Payment Summary</h3>
                    <div style="margin-top: 20px; border-top: 1px solid var(--glass-border); padding-top: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Ticket Price</span>
                            <span id="summaryPrice">₹0</span>
                        </div>
                        <div
                            style="display: flex; justify-content: space-between; margin-bottom: 10px; color: var(--text-gray);">
                            <span>Platform Fee (5%)</span>
                            <span id="platformFee">₹0</span>
                        </div>
                        <div
                            style="display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 1px dashed var(--glass-border); font-size: 1.2rem; font-weight: bold;">
                            <span>Total</span>
                            <span id="summaryTotal" style="color: var(--primary);">₹0</span>
                        </div>
                    </div>
                    <button id="payBtn" class="btn btn-primary"
                        style="width: 100%; margin-top: 30px; padding: 15px; font-size: 1.1rem;">
                        Proceed to Pay
                    </button>
                    <p style="text-align: center; margin-top: 15px; font-size: 0.8rem; color: var(--text-gray);">
                        By clicking "Proceed to Pay", you agree to our Terms of Service.
                    </p>
                </div>
            </div>
        </div>
    </section>
</main>
<?php get_footer(); ?>
