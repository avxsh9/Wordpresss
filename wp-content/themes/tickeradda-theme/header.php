<?php 
// Disable severe caching plugins natively for dynamic tickeradda app
if ( ! defined('DONOTCACHEPAGE') ) define('DONOTCACHEPAGE', true);
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <!-- ANTIGRAVITY_DEBUG_HEADER -->
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title( '|', true, 'right' ); bloginfo( 'name' ); ?></title>
    <link rel="icon" type="image/png" href="<?php echo esc_url( get_template_directory_uri() . '/public/images/favicon.png' ); ?>">
    <?php wp_head(); ?>
    <script>
        // Override original fetch URLs to point to WordPress REST API
        const originalFetch = window.fetch;
        window.fetch = async function() {
            if (typeof TA === 'undefined') return originalFetch.apply(this, arguments);
            
            let args = Array.prototype.slice.call(arguments);
            let url = args[0];
            
            // Map original Node API paths to WP REST API paths
            if (typeof url === 'string' && url.includes('/api/')) {
                const base = TA.restUrl.endsWith('/') ? TA.restUrl : TA.restUrl + '/';
                const oldUrl = url;
                url = url.replace(/.*\/api\//, base);
                
                // Cache bust all GET requests explicitly
                if (!args[1] || !args[1].method || args[1].method.toUpperCase() === 'GET') {
                    url += (url.includes('?') ? '&' : '?') + '_t=' + Date.now();
                }
                args[0] = url;
                console.log(`[TA Interceptor] Mapping ${oldUrl} -> ${url}`);
            }
            
            // Inject Nonce and Credentials for REST calls
            if (typeof url === 'string' && url.includes(TA.restUrl)) {
                args[1] = args[1] || {};
                args[1].headers = args[1].headers || {};
                args[1].headers['X-WP-Nonce'] = TA.nonce;
                // WordPress handles auth via cookies automatically if credentials=same-origin
                const method = (args[1].method || 'GET').toUpperCase();
                if (method !== 'GET') {
                    args[1].credentials = 'same-origin';
                }
            }

            return originalFetch.apply(this, args);
        };
        
        // Polyfill localStorage user for original scripts
        if (TA.loggedIn) {
            localStorage.setItem('user', JSON.stringify(TA.user));
            // Provide a dummy token so scripts looking for it don't fail, actual auth is WP Cookie
            localStorage.setItem('token', 'wp_cookie_auth');
        } else {
            localStorage.removeItem('user');
            localStorage.removeItem('token');
        }
    </script>
</head>
<?php 
$has_banner = false;
if ( is_user_logged_in() ) {
    $user = wp_get_current_user();
    $roles = (array) $user->roles;
    if ( ! in_array( 'administrator', $roles, true ) ) {
        $kyc_status = get_user_meta( $user->ID, 'ta_kyc_status', true ) ?: 'not_submitted';
        if ( $kyc_status !== 'approved' ) $has_banner = true;
    }
}
?>
<body <?php body_class( $has_banner ? 'has-kyc-banner' : '' ); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <?php 
    // Global KYC Notice for unverified Sellers
    if ( is_user_logged_in() ) :
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        if ( ! in_array( 'administrator', $roles, true ) ) {
            $kyc_status = get_user_meta( $user->ID, 'ta_kyc_status', true ) ?: 'not_submitted';
            if ( $kyc_status !== 'approved' ) :
                $status_class = 'kyc-' . $kyc_status;
                $msg = $kyc_status === 'rejected' ? 'Your KYC was rejected. Please resubmit your documents to continue selling tickets.' : ($kyc_status === 'pending' ? 'Your KYC is currently under review. Selling is disabled until approved.' : 'You must complete your KYC verification before you can actively sell tickets.');
                $btn = $kyc_status === 'pending' ? 'View Status' : 'Complete KYC Now';
                ?>
                <div class="kyc-banner <?php echo esc_attr($status_class); ?>">
                    <div class="container banner-content">
                        <span><i class="fas fa-exclamation-triangle"></i> <?php echo esc_html($msg); ?></span>
                        <a href="<?php echo esc_url( home_url( '/kyc-verification/' ) ); ?>" class="banner-btn">
                            <?php echo esc_html($btn); ?> <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php
            endif;
        }
    endif; 
    ?>

    <nav class="navbar">
        <div class="container nav-content">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo">
                <img src="<?php echo esc_url( get_template_directory_uri() . '/public/images/logo.png' ); ?>" alt="TickerAdda">
            </a>
            <button class="menu-toggle" aria-label="Toggle Menu">
                <i class="fas fa-bars"></i>
            </button>
            <div class="nav-links" id="navLinks">
                <a href="<?php echo esc_url( home_url( '/events/' ) ); ?>" class="nav-link">Events</a>
                <a href="<?php echo esc_url( home_url( '/seller-dashboard/' ) ); ?>" class="nav-link" id="dashboardLink" style="<?php echo is_user_logged_in() ? 'display: inline-block;' : 'display: none;'; ?>">My Listings</a>
                <a href="<?php echo esc_url( home_url( '/buyer-dashboard-2/' ) ); ?>" class="nav-link" id="myTicketsLink" style="<?php echo is_user_logged_in() ? 'display: inline-block;' : 'display: none;'; ?>">My Requests</a>
                <a href="<?php echo esc_url( home_url( '/calculator/' ) ); ?>" class="nav-link">Calculator</a>
                <a href="<?php echo esc_url( home_url( '/seller-dashboard/' ) ); ?>" class="btn btn-primary" style="margin-right: 10px;">
                    <i class="fas fa-plus-circle"></i> Sell Tickets
                </a>
                <?php if ( is_user_logged_in() ) : 
                    $current_user = wp_get_current_user();
                    $display_name = $current_user->display_name ?: $current_user->user_login;
                    ?>
                    <div id="userBadge" class="user-header-group" style="display: flex; align-items: center; gap: 10px; margin-left: 10px;">
                        <a href="<?php echo esc_url( home_url( '/seller-dashboard/' ) ); ?>" class="btn btn-outline" style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 24px; height: 24px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: white;">
                                <?php echo esc_html( strtoupper( substr( $display_name, 0, 1 ) ) ); ?>
                            </div>
                            <span class="user-name-header"><?php echo esc_html( $display_name ); ?></span>
                        </a>
                        <a href="#" class="btn-logout" onclick="logout(); return false;" style="color: #ef4444; font-size: 0.9rem; text-decoration: none; padding: 5px 10px; border-radius: 6px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); transition: all 0.2s;">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                <?php else : ?>
                    <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="btn btn-outline" id="loginBtn" style="margin-left: 10px;">Sign In</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>

<!-- EXACT REPLICA OF loader.html -->
<div class="loader-container" id="global-loader" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
    <div class="loader" style="border: 4px solid #f3f3f3; border-top: 4px solid var(--primary); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite;"></div>
</div>
<style>
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>
