<?php
/**
 * Template Name: Contact
 */
get_header();
?>
<main id="main">
    <section class="section">
        <div class="container" style="max-width: 800px;">
            <h1 class="gradient-text" style="font-size: 3rem; margin-bottom: 20px; text-align: center;">Contact Us</h1>
            <p style="text-align: center; color: var(--text-gray); margin-bottom: 40px;">Have questions? We're here to help.</p>
            
            <div style="background: var(--card-bg); padding: 40px; border-radius: 20px; border: 1px solid var(--glass-border);">
                <div style="margin-bottom: 30px; display: flex; align-items: center; gap: 20px;">
                    <i class="fas fa-envelope fa-2x" style="color: var(--primary);"></i>
                    <div>
                        <h4 style="margin: 0;">Email Support</h4>
                        <p style="margin: 0; color: #888;">support@tickeradda.com</p>
                    </div>
                </div>
                <div style="margin-bottom: 10px; display: flex; align-items: center; gap: 20px;">
                    <i class="fab fa-instagram fa-2x" style="color: #e4405f;"></i>
                    <div>
                        <h4 style="margin: 0;">Instagram</h4>
                        <p style="margin: 0; color: #888;">@ticketadda.in</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php get_footer(); ?>
