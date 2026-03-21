<?php
/**
 * Template Name: Order success
 */
get_header();
?>

<main id="main">
<section class="section" style="padding-top: 100px;">
        <div class="container" style="max-width: 800px; text-align: center;">
            <div style="margin-bottom: 30px;">
                <div
                    style="background: rgba(16, 185, 129, 0.2); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; margin-bottom: 20px;">
                    <i class="fas fa-check" style="font-size: 40px; color: #10b981;"></i>
                </div>
                <h1 style="color: #10b981; margin-bottom: 10px;">Payment Successful!</h1>
                <p style="color: var(--text-gray); font-size: 1.1rem;">Your ticket has been secured.</p>
            </div>
            <div id="order-details" class="card" style="padding: 30px; text-align: left; overflow: hidden; position: relative;">
                <div style="margin-bottom: 20px; border-bottom: 1px solid var(--glass-border); padding-bottom: 20px;">
                    <h2 id="eventName">Event Name</h2>
                    <p id="orderId" style="font-size: 0.9rem; color: var(--text-gray);">Order ID: #12345</p>
                </div>
                <div style="margin-bottom: 30px;">
                    <h3 style="margin-bottom: 15px; color: var(--primary);">Your Ticket Proof</h3>
                    <p style="font-size: 0.9rem; color: var(--text-gray); margin-bottom: 15px;">
                        Below is the official ticket image provided by the seller. Please download and save it.
                    </p>
                    <div style="background: rgba(0,0,0,0.3); padding: 10px; border-radius: 12px; text-align: center;">
                        <img id="ticketImage" src="" alt="Ticket Proof"
                            style="max-width: 100%; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.5); display: none;">
                        <div id="noImageMsg" style="display: none; padding: 20px; color: #aaa;">
                            <i class="fas fa-image-slash fa-2x"></i>
                            <p>No image proof provided by seller.</p>
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="<?php echo esc_url(home_url('/buyer-dashboard/')); ?>" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-ticket-alt"></i> Go to My Tickets
                    </a>
                </div>
            </div>
            <div style="margin-top: 30px; color: var(--text-gray); font-size: 0.9rem;">
                <p>A confirmation email has been sent to your registered email address.</p>
            </div>
        </div>
    </section>
    
    
    
    </div>
</main>
<?php get_footer(); ?>
