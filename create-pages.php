<?php
/**
 * Create Sports and Movies pages if they don't exist
 */

require_once dirname( __FILE__ ) . '/wp-load.php';

echo "=== CREATING SPORTS & MOVIES PAGES ===" . PHP_EOL . PHP_EOL;

// Create Sports Page
$sports_page = get_page_by_path( 'sports' );
if ( ! $sports_page ) {
    echo "Creating Sports page..." . PHP_EOL;
    $sports_id = wp_insert_post( array(
        'post_type'      => 'page',
        'post_title'     => 'Sports',
        'post_name'      => 'sports',
        'post_status'    => 'publish',
        'post_content'   => 'Sports events marketplace',
        'page_template'  => 'page-sports.php',
    ) );
    
    if ( is_wp_error( $sports_id ) ) {
        echo "✗ Error creating Sports page: " . $sports_id->get_error_message() . PHP_EOL;
    } else {
        echo "✓ Sports page created (ID: " . $sports_id . ")" . PHP_EOL;
    }
} else {
    echo "✓ Sports page already exists (ID: " . $sports_page->ID . ")" . PHP_EOL;
    // Update template if not set
    $template = get_page_template_slug( $sports_page->ID );
    if ( $template !== 'page-sports.php' ) {
        echo "  Updating template to page-sports.php..." . PHP_EOL;
        update_post_meta( $sports_page->ID, '_wp_page_template', 'page-sports.php' );
    }
}

echo PHP_EOL;

// Create Movies Page
$movies_page = get_page_by_path( 'movies' );
if ( ! $movies_page ) {
    echo "Creating Movies page..." . PHP_EOL;
    $movies_id = wp_insert_post( array(
        'post_type'      => 'page',
        'post_title'     => 'Movies',
        'post_name'      => 'movies',
        'post_status'    => 'publish',
        'post_content'   => 'Movies marketplace',
        'page_template'  => 'page-movies.php',
    ) );
    
    if ( is_wp_error( $movies_id ) ) {
        echo "✗ Error creating Movies page: " . $movies_id->get_error_message() . PHP_EOL;
    } else {
        echo "✓ Movies page created (ID: " . $movies_id . ")" . PHP_EOL;
    }
} else {
    echo "✓ Movies page already exists (ID: " . $movies_page->ID . ")" . PHP_EOL;
    // Update template if not set
    $template = get_page_template_slug( $movies_page->ID );
    if ( $template !== 'page-movies.php' ) {
        echo "  Updating template to page-movies.php..." . PHP_EOL;
        update_post_meta( $movies_page->ID, '_wp_page_template', 'page-movies.php' );
    }
}

echo PHP_EOL;

// Flush rewrite rules
echo "Flushing rewrite rules..." . PHP_EOL;
flush_rewrite_rules();

echo PHP_EOL . "=== DONE ===" . PHP_EOL;
echo "Visit: http://tickeradda.in/sports/ or http://tickeradda.in/movies/" . PHP_EOL;
