<?php
/**
 * TickerAdda Theme — functions.php
 * Strictly replicates the original HTML/CSS/JS frontend environment.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Disable Admin Bar for all users on frontend
add_filter('show_admin_bar', '__return_false');

// ── Theme Setup ────────────────────────────────────────────────────────────────
add_action( 'after_setup_theme', function() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
} );

// ── Enqueue Scripts & Styles ───────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', function() {
    $v = time(); // force reload for dev
    $uri = get_template_directory_uri();

    // Fonts & Icons (Original)
    wp_enqueue_style( 'google-outfit', 'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap', array(), null );
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), null );

    // SweetAlert2 (Original)
    wp_enqueue_script( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, false );

    // Razorpay (only on buy ticket page)
    if ( is_page_template( 'page-buy-ticket.php' ) ) {
        wp_enqueue_script( 'razorpay-checkout', 'https://checkout.razorpay.com/v1/checkout.js', array(), null, false );
    }

    // Main Styles
    wp_enqueue_style( 'ta-main', $uri . '/assets/css/main.css', array(), $v );
    wp_enqueue_style( 'ta-responsive', $uri . '/assets/css/responsive.css', array('ta-main'), $v );

    $template = get_page_template_slug();
    
    // Page specific CSS
    if ( in_array( $template, array('page-login.php', 'page-register.php', 'page-forgot-password.php') ) ) {
        wp_enqueue_style( 'ta-auth', $uri . '/assets/css/auth.css', array('ta-main'), $v );
    }
    if ( in_array( $template, array('page-seller-dashboard.php', 'page-kyc-verification.php', 'page-sell-ticket.php', 'page-listings.php', 'page-my-tickets.php') ) ) {
        wp_enqueue_style( 'ta-seller', $uri . '/assets/css/seller.css', array('ta-main'), $v );
    }
    if ( in_array( $template, array('page-buyer-dashboard.php', 'page-orders.php') ) ) {
        wp_enqueue_style( 'ta-buyer', $uri . '/assets/css/buyer.css', array('ta-main'), $v );
    }

    // Original Global JS (Load in head so TA is available for fetch interceptor)
    wp_enqueue_script( 'ta-common', $uri . '/assets/js/common.js', array(), $v, false );

    $js_map = array(
        'page-home.php'             => 'public/home.js',
        'page-events.php'           => 'public/events.js',
        'page-movies.php'           => 'public/movies.js',
        'page-sports.php'           => 'public/sports.js',
        'page-theatre.php'          => 'public/theatre.js',
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
        'page-my-tickets.php'       => 'seller/my-listings.js',
        'page-buyer-dashboard.php'  => 'buyer/dashboard.js',
        'page-orders.php'           => 'buyer/order-history.js',
    );

    if ( isset( $js_map[ $template ] ) ) {
        wp_enqueue_script( 'ta-page-js', $uri . '/assets/js/' . $js_map[ $template ], array( 'ta-common' ), $v, false );
    }

    if ( is_post_type_archive( 'events' ) ) {
        wp_enqueue_script( 'ta-events-archive-js', $uri . '/assets/js/public/events.js', array( 'ta-common' ), $v, true );
    }

    // Pass data to JS to override API URLs and verify nonces
    $current_user_data = null;
    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        $roles = $user->roles;
        $role  = 'buyer';
        if ( in_array( 'administrator', $roles, true ) ) $role = 'admin';
        elseif ( in_array( 'ta_both', $roles, true ) ) $role = 'both';
        elseif ( in_array( 'ta_seller', $roles, true ) )  $role = 'seller';

        $current_user_data = array(
            'id'        => $user->ID,
            'name'      => $user->display_name,
            'email'     => $user->user_email,
            'role'      => $role,
            'phone'     => get_user_meta( $user->ID, 'ta_phone', true ),
            'kycStatus' => get_user_meta( $user->ID, 'ta_kyc_status', true ) ?: 'not_submitted',
        );
    }

    wp_localize_script( 'ta-common', 'TA', array(
        'ajaxUrl'   => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
        'restUrl'   => esc_url_raw( rest_url( 'tickeradda/v2' ) ),
        'nonce'     => wp_create_nonce( 'wp_rest' ),
        'tmdbNonce' => wp_create_nonce( 'ta_tmdb_nonce' ),
        'sellMovieNonce' => wp_create_nonce( 'ta_sell_movie_nonce' ),
        'sellSportNonce' => wp_create_nonce( 'ta_sell_sport_nonce' ),
        'homeUrl'   => esc_url( home_url( '/' ) ),
        'themeUrl'  => get_template_directory_uri(),
        'user'      => $current_user_data,
        'loggedIn'  => is_user_logged_in(),
        'rzpKeyId'  => esc_html( get_option( 'ta_razorpay_key_id', 'rzp_test_SQ0ySa9NBL4rWx' ) ),
    ) );
} );

// ── Auth Redirects (WordPress Native) ─────────────────────────────────────────
add_action( 'template_redirect', function() {
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
    
    // Redirect unauthenticated users
    if ( in_array( $slug, $protected_templates, true ) && ! is_user_logged_in() ) {
        $login_url = home_url( '/login/' );
        if ( ! empty( $_GET ) ) {
            $login_url = add_query_arg( $_GET, $login_url );
        }
        wp_redirect( $login_url );
        exit;
    }

    // Redirect authenticated users away from auth pages
    $auth_templates = array( 'page-login.php', 'page-register.php', 'page-forgot-password.php' );
    if ( in_array( $slug, $auth_templates, true ) && is_user_logged_in() ) {
        wp_redirect( home_url( '/' ) );
        exit;
    }

    // Unified role access: All logged in users can access all templates
} );

// Allow Cookie Auth for REST API
add_filter( 'rest_authentication_errors', function( $result ) {
    if ( ! empty( $result ) ) return $result;
    if ( ! is_user_logged_in() ) return $result;
    return $result;
} );

// Clean WP Head
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
