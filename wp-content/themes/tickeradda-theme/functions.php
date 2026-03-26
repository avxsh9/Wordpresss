<?php
/**
 * TickerAdda Theme — functions.php
 * v2.0.0 — Self-healing category pages, bulletproof routing.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Disable Admin Bar for all users on frontend
add_filter( 'show_admin_bar', '__return_false' );

// ── Theme Support ──────────────────────────────────────────────────────────────
add_action( 'after_setup_theme', function() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
} );

// ── Category Pages: Self-Healing Setup ────────────────────────────────────────
// Runs on every page load. Creates the page if missing, fixes the template if wrong.
// This fires at priority 1 so it runs before routing.
add_action( 'init', function() {
    if ( wp_doing_ajax() ) return; // Skip on AJAX

    $categories = array(
        'movies'  => array( 'title' => 'Movies',         'template' => 'page-movies.php' ),
        'sports'  => array( 'title' => 'Sports Events',  'template' => 'page-sports.php' ),
        'theatre' => array( 'title' => 'Theatre & Plays','template' => 'page-theatre.php' ),
        'play'    => array( 'title' => 'Play',           'template' => 'page-play.php' ),
    );

    foreach ( $categories as $slug => $data ) {
        // Search by path (slug) across all statuses
        $page = get_page_by_path( $slug, OBJECT, 'page' );

        if ( ! $page ) {
            // Also try trashed pages
            $trashed = get_posts( array(
                'post_type'   => 'page',
                'post_status' => 'trash',
                'name'        => $slug,
                'numberposts' => 1,
            ) );
            if ( $trashed ) {
                // Untrash it and re-publish
                wp_untrash_post( $trashed[0]->ID );
                wp_update_post( array( 'ID' => $trashed[0]->ID, 'post_status' => 'publish', 'post_name' => $slug ) );
                update_post_meta( $trashed[0]->ID, '_wp_page_template', $data['template'] );
                continue;
            }

            // Create brand new page
            $id = wp_insert_post( array(
                'post_type'   => 'page',
                'post_title'  => $data['title'],
                'post_name'   => $slug,
                'post_status' => 'publish',
                'post_content'=> '',
            ) );
            if ( $id && ! is_wp_error( $id ) ) {
                update_post_meta( $id, '_wp_page_template', $data['template'] );
            }
        } else {
            // Page exists — ensure it's published with correct slug and template
            $needs_update = false;
            $update_args  = array( 'ID' => $page->ID );

            if ( $page->post_status !== 'publish' ) {
                $update_args['post_status'] = 'publish';
                $needs_update = true;
            }
            if ( $page->post_name !== $slug ) {
                $update_args['post_name'] = $slug;
                $needs_update = true;
            }
            if ( $needs_update ) {
                wp_update_post( $update_args );
            }
            // Always sync the template meta
            update_post_meta( $page->ID, '_wp_page_template', $data['template'] );
        }
    }

    // Flush rewrite rules only once after setup
    if ( ! get_option( 'ta_rewrites_flushed_v2' ) ) {
        flush_rewrite_rules();
        update_option( 'ta_rewrites_flushed_v2', '1' );
    }
}, 1 );

// ── Prevent slug collision: block WP from renaming our slugs to 'movies-2' etc ─
add_filter( 'wp_unique_post_slug', function( $slug, $post_ID, $post_status, $post_type ) {
    $protected = array( 'movies', 'sports', 'theatre', 'play' );
    if ( $post_type === 'page' && in_array( $slug, $protected, true ) ) {
        return $slug;
    }
    return $slug;
}, 10, 4 );

// ── Template Override: Load category templates by URI path ────────────────────
// Priority 999 — runs after all other template logic.
add_filter( 'template_include', function( $template ) {
    $uri_path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
    $uri_path = rtrim( $uri_path, '/' ) . '/'; // Normalize to always have trailing slash

    $map = array(
        '/movies/'  => 'page-movies.php',
        '/sports/'  => 'page-sports.php',
        '/theatre/' => 'page-theatre.php',
        '/play/'    => 'page-play.php',
    );

    foreach ( $map as $path => $tpl_file ) {
        // Only exact match. No partial matching so we don't break single event pages
        if ( $uri_path === $path ) {
            $full_path = get_template_directory() . '/' . $tpl_file;
            if ( file_exists( $full_path ) ) {
                global $wp_query;
                $wp_query->is_404  = false;
                $wp_query->is_page = true;
                status_header( 200 );
                return $full_path;
            }
        }
    }
    return $template;
}, 999 );

// ── Enqueue Scripts & Styles ───────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', function() {
    $v   = '2.0.7';
    $uri = get_template_directory_uri();

    // Fonts & Icons
    wp_enqueue_style( 'google-outfit', 'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap', array(), null );
    wp_enqueue_style( 'font-awesome',  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), null );

    // SweetAlert2
    wp_enqueue_script( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, false );

    // Razorpay only on buy ticket page
    if ( is_page_template( 'page-buy-ticket.php' ) ) {
        wp_enqueue_script( 'razorpay-checkout', 'https://checkout.razorpay.com/v1/checkout.js', array(), null, false );
    }

    // Main CSS
    wp_enqueue_style( 'ta-main',       $uri . '/assets/css/main.css',       array(),         $v );
    wp_enqueue_style( 'ta-responsive', $uri . '/assets/css/responsive.css', array('ta-main'), $v );

    // Page-specific CSS
    $template = get_page_template_slug();
    if ( in_array( $template, array( 'page-login.php', 'page-register.php', 'page-forgot-password.php' ) ) ) {
        wp_enqueue_style( 'ta-auth', $uri . '/assets/css/auth.css', array('ta-main'), $v );
    }
    if ( in_array( $template, array( 'page-seller-dashboard.php', 'page-kyc-verification.php', 'page-sell-ticket.php', 'page-listings.php', 'page-my-tickets.php' ) ) ) {
        wp_enqueue_style( 'ta-seller', $uri . '/assets/css/seller.css', array('ta-main'), $v );
    }
    if ( in_array( $template, array( 'page-buyer-dashboard.php', 'page-orders.php', 'page-my-tickets.php' ) ) ) {
        wp_enqueue_style( 'ta-buyer', $uri . '/assets/css/buyer.css', array('ta-main'), $v );
    }

    // Global JS (loads in <head> so TA object is available)
    wp_enqueue_script( 'ta-common', $uri . '/assets/js/common.js', array('jquery'), $v, false );

    // Template-to-JS mapping
    $js_map = array(
        'page-home.php'             => 'public/home.js',
        'page-events.php'           => 'public/events.js',
        'archive-events.php'        => 'public/events.js',
        'page-movies.php'           => 'public/movies.js',
        'page-sports.php'           => 'public/sports.js',
        'page-theatre.php'          => 'public/theatre.js',
        'page-play.php'             => 'public/play.js',
        'page-buy-ticket.php'       => 'public/buy-ticket.js',
        'page-order-success.php'    => 'public/order-success.js',
        'page-login.php'            => 'auth/login.js',
        'page-register.php'         => 'auth/signup.js',
        'page-forgot-password.php'  => 'auth/forgot-password.js',
        'page-seller-dashboard.php' => 'seller/seller-dashboard.js',
        'page-kyc-verification.php' => 'seller/kyc-verification.js',
        'page-sell-ticket.php'      => 'seller/sell-ticket.js',
        'page-sell-movie.php'       => 'public/sell-movie.js',
        'page-sell-sport.php'       => 'public/sell-sport.js',
        'page-listings.php'         => 'seller/my-listings.js',
        'page-my-tickets.php'       => 'buyer/buyer-dashboard.js',
        'page-buyer-dashboard.php'  => 'buyer/buyer-dashboard.js',
        'page-orders.php'           => 'buyer/order-history.js',
    );

    if ( isset( $js_map[ $template ] ) ) {
        wp_enqueue_script( 'ta-page-js', $uri . '/assets/js/' . $js_map[ $template ], array('ta-common'), $v, true );
    }

    // Force-enqueue JS for category pages by URI — ensures JS loads even on routing overrides
    $uri_path = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
    $slug_js_map = array(
        'movies'  => 'public/movies.js',
        'sports'  => 'public/sports.js',
        'theatre' => 'public/theatre.js',
        'play'    => 'public/play.js',
        'events'  => 'public/events.js',
    );
    foreach ( $slug_js_map as $cat_slug => $js_file ) {
        if ( $uri_path === $cat_slug || $template === 'page-' . $cat_slug . '.php' || is_page( $cat_slug ) ) {
            $handle = 'ta-' . $cat_slug . '-js';
            if ( ! wp_script_is( $handle, 'enqueued' ) && ! wp_script_is( 'ta-page-js', 'enqueued' ) ) {
                wp_enqueue_script( $handle, $uri . '/assets/js/' . $js_file, array('ta-common'), $v, true );
            }
        }
    }

    // Pass TA object to JS
    $current_user_data = null;
    if ( is_user_logged_in() ) {
        $user  = wp_get_current_user();
        $roles = $user->roles;
        $role  = 'both';
        if ( in_array( 'administrator', $roles, true ) ) $role = 'admin';
        $phone = get_user_meta( $user->ID, 'ta_phone', true );
        $current_user_data = array(
            'id'              => $user->ID,
            'name'            => $user->display_name,
            'email'           => $user->user_email,
            'role'            => $role,
            'phone'           => $phone,
            'isPhoneRequired' => empty( $phone ),
            'kycStatus'       => get_user_meta( $user->ID, 'ta_kyc_status', true ) ?: 'not_submitted',
        );
    }

    wp_localize_script( 'ta-common', 'TA', array(
        'ajaxUrl'        => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
        'restUrl'        => esc_url_raw( rest_url( 'tickeradda/v2' ) ),
        'nonce'          => wp_create_nonce( 'wp_rest' ),
        'tmdbNonce'      => wp_create_nonce( 'ta_tmdb_nonce' ),
        'sellMovieNonce' => wp_create_nonce( 'ta_sell_movie_nonce' ),
        'sellSportNonce' => wp_create_nonce( 'ta_sell_sport_nonce' ),
        'homeUrl'        => esc_url( home_url( '/' ) ),
        'themeUrl'       => get_template_directory_uri(),
        'user'           => $current_user_data,
        'loggedIn'       => is_user_logged_in(),
        'rzpKeyId'       => esc_html( get_option( 'ta_razorpay_key_id', 'rzp_test_SQ0ySa9NBL4rWx' ) ),
    ) );
} );

// ── Auth Redirects ────────────────────────────────────────────────────────────
add_action( 'template_redirect', function() {
    global $wp;
    $protected_templates = array(
        'page-seller-dashboard.php',
        'page-buyer-dashboard.php',
        'page-kyc-verification.php',
        'page-order-success.php',
        'page-buy-ticket.php',
        'page-sell-ticket.php',
        'page-sell-movie.php',
        'page-sell-sport.php',
        'page-listings.php',
        'page-my-tickets.php',
        'page-orders.php',
    );

    $slug = get_page_template_slug();
    if ( in_array( $slug, $protected_templates, true ) && ! is_user_logged_in() ) {
        $login_url   = home_url( '/login/' );
        $current_url = home_url( add_query_arg( array(), $wp->request ) );
        if ( ! empty( $_SERVER['QUERY_STRING'] ) ) $current_url .= '?' . $_SERVER['QUERY_STRING'];
        wp_redirect( add_query_arg( 'redirect_to', urlencode( $current_url ), $login_url ) );
        exit;
    }

    if ( is_page( 'dashboard' ) ) {
        wp_redirect( home_url( '/buyer-dashboard-2/' ) );
        exit;
    }

    $auth_templates = array( 'page-login.php', 'page-register.php', 'page-forgot-password.php' );
    if ( in_array( $slug, $auth_templates, true ) && is_user_logged_in() ) {
        wp_redirect( home_url( '/' ) );
        exit;
    }
} );

// ── REST API ──────────────────────────────────────────────────────────────────
add_filter( 'rest_authentication_errors', function( $result ) {
    if ( ! empty( $result ) ) return $result;
    return $result;
} );

// ── Performance: Remove unused WP head items ──────────────────────────────────
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

// Mobile Responsive CSS — injected inline to bypass file caching/sync issues
add_action( 'wp_head', function() {
    if ( is_admin() ) return;
    ?>
<style id="ta-mobile-responsive">
@media (max-width: 768px) {
    html, body { overflow-x: hidden !important; width: 100% !important; }
    .container { width: 100% !important; max-width: 100% !important; padding-left: 12px !important; padding-right: 12px !important; box-sizing: border-box !important; }
    .logo img, .site-logo img, .nav-logo img { height: 32px !important; width: auto !important; max-width: 120px !important; }

    /* Hero */
    .hero { min-height: auto !important; padding: 90px 0 30px !important; }
    .hero-content { width: 100% !important; max-width: 100% !important; padding: 0 12px !important; text-align: center !important; }
    .hero h1, .hero-title { font-size: clamp(22px, 7vw, 34px) !important; line-height: 1.2 !important; margin-bottom: 10px !important; }
    .hero p, .hero-subtitle { font-size: 13px !important; line-height: 1.5 !important; margin-bottom: 16px !important; }

    /* Search */
    .search-container { width: 100% !important; max-width: 100% !important; margin: 12px 0 !important; position: relative !important; }
    .search-container input { width: 100% !important; padding: 12px 100px 12px 16px !important; border-radius: 30px !important; font-size: 14px !important; box-sizing: border-box !important; }
    .search-container button { position: absolute !important; right: 5px !important; top: 5px !important; width: auto !important; padding: 8px 14px !important; border-radius: 25px !important; font-size: 12px !important; height: calc(100% - 10px) !important; }

    /* Category pills */
    .hero-categories, .category-tabs, .filter-tabs { display: flex !important; flex-wrap: nowrap !important; overflow-x: auto !important; gap: 8px !important; justify-content: flex-start !important; margin-top: 12px !important; scrollbar-width: none !important; -webkit-overflow-scrolling: touch !important; }
    .hero-categories::-webkit-scrollbar { display: none !important; }
    .category-pill, .category-pill-small { flex: 0 0 auto !important; white-space: nowrap !important; padding: 6px 14px !important; font-size: 12px !important; }

    /* Sections */
    .section { padding: 24px 0 !important; }
    .section-header { display: flex !important; flex-wrap: wrap !important; align-items: center !important; justify-content: space-between !important; gap: 8px !important; margin-bottom: 14px !important; }
    .section-title h2 { font-size: 18px !important; margin-bottom: 2px !important; }
    .section-title p { font-size: 12px !important; margin: 0 !important; }

    /* 2-Column Grid — stretch so equal row heights */
    .grid-4, .grid-3, .grid-2 { grid-template-columns: repeat(2, 1fr) !important; gap: 10px !important; align-items: stretch !important; }

    /* ── CARD: FLEX COLUMN, FULL HEIGHT, BUTTONS PINNED ── */
    .event-card-premium { min-height: 0 !important; height: 100% !important; border-radius: 14px !important; display: flex !important; flex-direction: column !important; overflow: hidden !important; }
    .movie-card { min-height: 0 !important; height: 100% !important; }

    /* Image */
    .event-card-image { height: auto !important; aspect-ratio: 2/3 !important; overflow: hidden !important; flex-shrink: 0 !important; }
    .event-card-image img { width: 100% !important; height: 100% !important; object-fit: cover !important; }

    /* Details flex grow */
    .event-card-details { padding: 8px 10px 4px !important; flex: 1 1 auto !important; display: flex !important; flex-direction: column !important; gap: 3px !important; }

    /* Title: clamp to 2 lines → uniform height across cards in same row */
    .movie-title, .event-card-title { font-size: 11px !important; overflow: hidden !important; display: -webkit-box !important; -webkit-line-clamp: 2 !important; -webkit-box-orient: vertical !important; -webkit-text-fill-color: #fff !important; margin-bottom: 2px !important; line-height: 1.3 !important; }

    /* Meta */
    .event-card-meta { margin-top: auto !important; gap: 3px !important; }
    .event-card-meta span { font-size: 10px !important; }

    /* Buttons: row, pinned */
    .event-card-premium .event-card-actions,
    .movie-card .event-card-actions,
    .event-card-actions { padding: 6px 8px 10px !important; display: flex !important; flex-direction: row !important; gap: 6px !important; flex-shrink: 0 !important; }
    .card-btn-primary { flex: 1 !important; width: auto !important; min-width: 0 !important; padding: 8px 4px !important; font-size: 9px !important; border-radius: 8px !important; text-align: center !important; }
    .card-btn-secondary { flex: 1 !important; width: auto !important; min-width: 0 !important; padding: 7px 4px !important; font-size: 9px !important; border-radius: 8px !important; justify-content: center !important; }

    /* Navigation */
    .navbar { padding: 10px 0 !important; }
    .menu-toggle { display: block !important; }
    .nav-links { position: absolute !important; top: 100% !important; left: 0 !important; width: 100% !important; background: rgba(5,5,5,0.97) !important; backdrop-filter: blur(20px) !important; flex-direction: column !important; padding: 16px !important; display: none !important; z-index: 999 !important; gap: 8px !important; }
    .nav-links.active { display: flex !important; }
    .nav-link { font-size: 15px !important; padding: 10px 0 !important; text-align: center !important; }
    .banner-content { flex-direction: column !important; gap: 6px !important; text-align: center !important; padding: 6px 12px !important; font-size: 12px !important; }

    /* Footer — full rework for mobile */
    .footer { padding: 30px 0 16px !important; }
    /* Main footer grid: brand full-width on top, then links in 2 columns */
    .footer-grid, .site-footer .footer-grid, .footer-content {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 20px 16px !important;
    }
    /* Brand/logo spans full width */
    .footer-brand, .footer-about {
        grid-column: 1 / -1 !important;
        text-align: center !important;
        margin-bottom: 8px !important;
    }
    .footer-brand img, .site-footer .footer-logo { height: 30px !important; width: auto !important; }
    .footer-brand p { font-size: 12px !important; margin-top: 6px !important; color: rgba(255,255,255,0.6) !important; }
    /* Column headings */
    .footer-col h4, .footer-col h3 { font-size: 12px !important; font-weight: 700 !important; margin-bottom: 10px !important; letter-spacing: 0.03em !important; color: #fff !important; }
    /* Column links */
    .footer-col ul { padding: 0 !important; margin: 0 !important; list-style: none !important; }
    .footer-col ul li { margin-bottom: 8px !important; }
    .footer-col ul li a { font-size: 12px !important; color: rgba(255,255,255,0.65) !important; text-decoration: none !important; }
    /* Social icons row */
    .footer-social, .social-links { display: flex !important; flex-wrap: wrap !important; gap: 10px !important; justify-content: center !important; margin-top: 6px !important; }
    /* Bottom bar */
    .footer-bottom, .site-footer .footer-bottom { text-align: center !important; font-size: 11px !important; padding-top: 16px !important; margin-top: 16px !important; border-top: 1px solid rgba(255,255,255,0.08) !important; }

    /* Sidebars */
    .movies-page-container, .sports-page-container, .theatre-page-container { flex-direction: column !important; display: flex !important; }
    .movies-sidebar, .sports-sidebar, .theatre-sidebar { width: 100% !important; position: static !important; margin-bottom: 16px !important; }

    /* Tables */
    .table-container { overflow-x: auto !important; -webkit-overflow-scrolling: touch !important; }

    /* ── HOME: "Your Safety" trust section — 3 cards, 1 column on mobile (no orphan gap) ── */
    .trust-section .grid-3,
    .trust-section .grid.grid-3 { grid-template-columns: 1fr !important; gap: 12px !important; }
    .feature-box.card, .feature-box {
        padding: 18px 16px !important;
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        gap: 14px !important;
        text-align: left !important;
        border-radius: 12px !important;
    }
    /* Icon container: fixed circle so SVG/icon stays contained */
    .feature-box .feature-icon,
    .feature-box > svg,
    .feature-box > i,
    .feature-box > .icon,
    .feature-box > img[src*="svg"],
    .feature-box > div:first-child {
        flex-shrink: 0 !important;
        width: 44px !important;
        height: 44px !important;
        min-width: 44px !important;
        border-radius: 50% !important;
        background: rgba(59,130,246,0.12) !important;
        display: flex !important; align-items: center !important; justify-content: center !important;
        overflow: hidden !important;
        font-size: 20px !important;
        margin-bottom: 0 !important;
    }
    .feature-box > div:first-child svg,
    .feature-box .feature-icon svg { width: 22px !important; height: 22px !important; flex-shrink: 0 !important; }
    .feature-box h3, .feature-box h4 { font-size: 14px !important; margin: 0 0 4px !important; }
    .feature-box p { font-size: 12px !important; margin: 0 !important; line-height: 1.4 !important; }
    .feature-box > div:last-child { flex: 1 !important; }

    /* ── EVENT/MOVIE DETAIL PAGE: Hero & CTAs ── */
    .event-hero-premium { padding: 90px 0 40px !important; min-height: auto !important; }
    .hero-info-bar { gap: 10px !important; flex-wrap: wrap !important; }
    .info-item { min-width: 70px !important; }
    .hero-title-main { font-size: clamp(20px, 6.5vw, 36px) !important; }
    .col-md-6 { display: flex !important; flex-direction: column !important; gap: 10px !important; width: 100% !important; }
    .col-md-6 .btn, .col-md-6 .btn-xxl { width: 100% !important; justify-content: center !important; font-size: 14px !important; padding: 13px 16px !important; box-sizing: border-box !important; }
    .event-grid-layout { grid-template-columns: 1fr !important; }
    .details-column { display: none !important; }

    /* ── TICKET LISTING CARDS ── */
    .ticket-listing-card { grid-template-columns: 1fr !important; gap: 10px !important; }
    .ticket-cta { border-left: none !important; padding-left: 0 !important; border-top: 1px solid rgba(255,255,255,0.05) !important; padding-top: 10px !important; display: flex !important; flex-direction: row !important; align-items: center !important; justify-content: space-between !important; gap: 8px !important; flex-wrap: wrap !important; }
}
</style>
    <?php
}, 5 );

// Final Responsive Overrides — ensures 2-column layout and professional look
add_action( 'wp_enqueue_scripts', function() {
    $css_path = get_template_directory() . '/assets/css/responsive-addon.css';
    $v = file_exists( $css_path ) ? filemtime( $css_path ) : '2.1.0';
    wp_enqueue_style( 'ta-responsive-addon', get_template_directory_uri() . '/assets/css/responsive-addon.css', array( 'ta-main', 'ta-responsive' ), $v );
}, 999 );


