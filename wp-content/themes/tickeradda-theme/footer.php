<!-- EXACT REPLICA OF footer.html -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-brand" style="text-align:center;">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo" style="display:inline-block;">
                    <img src="<?php echo esc_url( get_template_directory_uri() . '/public/images/logo.png' ); ?>" alt="TickerAdda" style="height: 50px;">
                </a>
                <p style="text-align:center;">India's safest ticket marketplace. Buy and sell with 100% confidence.</p>
            </div>
            <div class="footer-col">
                <h4>Discover</h4>
                <ul>
                    <li><a href="<?php echo esc_url( home_url( '/events/' ) ); ?>">All Events</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About Us</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Categories</h4>
                <ul>
                    <li><a href="<?php echo esc_url( home_url( '/events/?category=music' ) ); ?>">Concerts</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/events/?category=sports' ) ); ?>">Sports</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/events/?category=comedy' ) ); ?>">Comedy</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/events/?category=theatre' ) ); ?>">Theatre</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Support</h4>
                <ul>
                    <li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Help Center</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/seller-dashboard/' ) ); ?>">Sell Tickets</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact Us</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>">Terms</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">Privacy</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Connect</h4>
                <ul>
                    <li><a href="https://www.instagram.com/ticketadda.in/" target="_blank"><i class="fab fa-instagram"></i> Instagram</a></li>
                    <li><a href="https://x.com/ticketadda" target="_blank"><i class="fa-brands fa-twitter"></i> Twitter</a></li>
                    <li><a href="https://www.linkedin.com/company/ticketadda/" target="_blank"><i class="fab fa-linkedin"></i> LinkedIn</a></li>

                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?php echo date('Y'); ?> TickerAdda Technologies Pvt Ltd. All rights reserved.
        </div>
    </div>
</footer>

<script>
// Prevent original common.js from fetching navbar and footer again since we included them natively in PHP
document.addEventListener('DOMContentLoaded', () => {
    // If the original common.js creates an error because placeholders are missing, we provide dummy endpoints.
    // However, the original code checks `if (navPlaceholder)` so it won't execute if missing.
    // Since we're rendering them natively, we don't include `<div id="navbar-placeholder">`!
});
</script>
<?php wp_footer(); ?>
</body>
</html>
