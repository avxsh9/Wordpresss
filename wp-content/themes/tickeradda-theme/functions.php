<?php
/**
 * TickerAdda Theme — functions.php
 * Strictly replicates the original HTML/CSS/JS frontend environment.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( isset( $_GET['test_deploy'] ) ) die('DEPLOYMENT_VERIFIED_1.0.7');

// Disable Admin Bar for all users on frontend
add_filter('show_admin_bar', '__return_false');

// ── Slug Diagnosis ────────────────────────────────────────────────────────────
add_action( 'init', function() {
    if ( ! isset( $_GET['debug_slugs'] ) ) return;
    
    global $wpdb;
    $slugs = array('movies', 'sports', 'theatre', 'play');
    echo "<div style='background:#000;color:#0f0;padding:20px;font-family:monospace;'>";
    echo "<h2>Slug Conflict Report</h2>";

    foreach ($slugs as $slug) {
        echo "<h3>Checking slug: $slug</h3>";
        $posts = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_type, post_status FROM $wpdb->posts WHERE post_name = %s", $slug));
        if ($posts) {
            foreach ($posts as $p) {
                echo "POST: ID {$p->ID} | Title: {$p->post_title} | Type: {$p->post_type} | Status: {$p->post_status}<br>";
            }
        } else { echo "No posts found.<br>"; }
        
        $terms = $wpdb->get_results($wpdb->prepare("SELECT t.term_id, t.name, tt.taxonomy FROM $wpdb->terms t INNER JOIN $wpdb->term_taxonomy tt ON t.term_id = tt.term_id WHERE t.slug = %s", $slug));
        if ($terms) {
            foreach ($terms as $t) {
                echo "TERM: ID {$t->term_id} | Name: {$t->name} | Taxonomy: {$t->taxonomy}<br>";
            }
        } else { echo "No terms found.<br>"; }
        echo "<hr>";
    }
    echo "</div>";
    die();
} );

// ── Theme Setup ────────────────────────────────────────────────────────────────
add_action( 'after_setup_theme', function() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
} );

// ── Enqueue Scripts & Styles ───────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', function() {
    $v = '1.0.6'; // Persistent version across all scripts
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
    if ( in_array( $template, array('page-buyer-dashboard.php', 'page-orders.php', 'page-my-tickets.php') ) ) {
        wp_enqueue_style( 'ta-buyer', $uri . '/assets/css/buyer.css', array('ta-main'), $v );
    }

    // Original Global JS (Load in head so TA is available for fetch interceptor)
    wp_enqueue_script( 'ta-common', $uri . '/assets/js/common.js', array('jquery'), $v, false );

    $js_map = array(
        'page-home.php'             => 'public/home.js',
        'page-events.php'           => 'public/events.js',
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
        wp_enqueue_script( 'ta-page-js', $uri . '/assets/js/' . $js_map[ $template ], array( 'ta-common' ), $v, true );
    }

    // Direct check for slug-based routing (Ensures JS loads on forced templates)
    $path = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
    $parts = explode('/', $path);
    $uri_slug = end($parts);
    
    if ( $uri_slug === 'movies' || is_page('movies') ) {
        wp_enqueue_script( 'ta-movies-js-forced', $uri . '/assets/js/public/movies.js', array( 'ta-common' ), $v, true );
    }
    if ( $uri_slug === 'sports' || is_page('sports') ) {
        wp_enqueue_script( 'ta-sports-js-forced', $uri . '/assets/js/public/sports.js', array( 'ta-common' ), $v, true );
    }
    if ( $uri_slug === 'theatre' || is_page('theatre') ) {
        wp_enqueue_script( 'ta-theatre-js-forced', $uri . '/assets/js/public/theatre.js', array( 'ta-common' ), $v, true );
    }
    if ( $uri_slug === 'play' || is_page('play') ) {
        wp_enqueue_script( 'ta-play-js-forced', $uri . '/assets/js/public/play.js', array( 'ta-common' ), $v, true );
    }

    // Pass data...
    $current_user_data = null;
    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
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
        $login_url = home_url( '/login/' );
        $current_url = home_url( add_query_arg( array(), $wp->request ) );
        if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        $login_url = add_query_arg( 'redirect_to', urlencode( $current_url ), $login_url );
        wp_redirect( $login_url );
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

// ── REST API Config ───────────────────────────────────────────────────────────
add_filter( 'rest_authentication_errors', function( $result ) {
    if ( ! empty( $result ) ) return $result;
    return $result;
} );

// ── Category Page Setup ───────────────────────────────────────────────────────
add_action( 'init', function() {
    $pages_to_create = array(
        'movies'  => array( 'title' => 'Movies',  'template' => 'page-movies.php' ),
        'sports'  => array( 'title' => 'Sports',  'template' => 'page-sports.php' ),
        'theatre' => array( 'title' => 'Theatre', 'template' => 'page-theatre.php' ),
        'play'    => array( 'title' => 'Play',    'template' => 'page-play.php' ),
    );

    foreach ( $pages_to_create as $slug => $data ) {
        if ( ! get_page_by_path( $slug ) ) {
            $page_id = wp_insert_post( array(
                'post_title'   => $data['title'],
                'post_name'    => $slug,
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ) );
            if ( $page_id ) update_post_meta( $page_id, '_wp_page_template', $data['template'] );
        } else {
            $page = get_page_by_path( $slug );
            update_post_meta( $page->ID, '_wp_page_template', $data['template'] );
            // Ensure slug is exactly what we want (not movies-2)
            if ( $page->post_name !== $slug ) {
                wp_update_post( array( 'ID' => $page->ID, 'post_name' => $slug ) );
            }
        }
    }
} );

// ── Aggressive Template Override ─────────────────────────────────────────────
add_filter( 'template_include', function( $template ) {
    $uri = $_SERVER['REQUEST_URI'];
    
    $map = array(
        '/movies/'  => 'page-movies.php',
        '/sports/'  => 'page-sports.php',
        '/theatre/' => 'page-theatre.php',
        '/play/'    => 'page-play.php',
    );

    foreach ( $map as $path => $file_name ) {
        if ( strpos( $uri, $path ) !== false ) {
            $file = get_template_directory() . '/' . $file_name;
            if ( file_exists( $file ) ) {
                global $wp_query;
                $wp_query->is_404 = false;
                $wp_query->is_page = true;
                status_header( 200 );
                return $file;
            }
        }
    }
    return $template;
}, 999 );

// ── Slug Collision Protection ─────────────────────────────────────────────────
add_filter( 'wp_unique_post_slug', function( $slug, $post_ID, $post_status, $post_type ) {
    if ( $post_type === 'page' && in_array( $slug, ['movies', 'sports', 'theatre', 'play'] ) ) {
        return $slug; 
    }
    return $slug;
}, 10, 4 );

// Cleanup
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
