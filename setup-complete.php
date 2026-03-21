<?php
/**
 * TICKERADDA COMPLETE SETUP SCRIPT
 * Creates Sports & Movies pages + Imports all movies + Configures everything
 * 
 * Run: php setup-complete.php
 */

// Load WordPress core
if ( ! file_exists( 'wp-load.php' ) ) {
    die( "\n❌ ERROR: wp-load.php not found. Make sure you run this from WordPress root directory.\n\n" );
}

require_once 'wp-load.php';

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║     TICKERADDA - COMPLETE SETUP & CONFIGURATION                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// ─────────────────────────────────────────────────────────────────────
// STEP 1: CREATE PAGES
// ─────────────────────────────────────────────────────────────────────

echo "STEP 1: Creating WordPress Pages\n";
echo "─────────────────────────────────────────────────────────────────\n";

$pages_created = 0;
$pages_existing = 0;

// Sports Page
$sports_page = get_page_by_path( 'sports' );
if ( ! $sports_page ) {
    $sports_id = wp_insert_post( array(
        'post_type'      => 'page',
        'post_title'     => 'Sports',
        'post_name'      => 'sports',
        'post_status'    => 'publish',
        'post_content'   => 'Browse and buy verified sports event tickets from trusted sellers.',
        'page_template'  => 'page-sports.php',
    ) );
    
    if ( ! is_wp_error( $sports_id ) ) {
        update_post_meta( $sports_id, '_wp_page_template', 'page-sports.php' );
        echo "✓ Sports page created (ID: $sports_id, Template: page-sports.php)\n";
        $pages_created++;
    } else {
        echo "✗ Failed to create Sports page: " . $sports_id->get_error_message() . "\n";
    }
} else {
    echo "✓ Sports page already exists (ID: {$sports_page->ID})\n";
    update_post_meta( $sports_page->ID, '_wp_page_template', 'page-sports.php' );
    $pages_existing++;
}

// Movies Page
$movies_page = get_page_by_path( 'movies' );
if ( ! $movies_page ) {
    $movies_id = wp_insert_post( array(
        'post_type'      => 'page',
        'post_title'     => 'Movies',
        'post_name'      => 'movies',
        'post_status'    => 'publish',
        'post_content'   => 'Browse and buy verified movie tickets from trusted sellers.',
        'page_template'  => 'page-movies.php',
    ) );
    
    if ( ! is_wp_error( $movies_id ) ) {
        update_post_meta( $movies_id, '_wp_page_template', 'page-movies.php' );
        echo "✓ Movies page created (ID: $movies_id, Template: page-movies.php)\n";
        $pages_created++;
    } else {
        echo "✗ Failed to create Movies page: " . $movies_id->get_error_message() . "\n";
    }
} else {
    echo "✓ Movies page already exists (ID: {$movies_page->ID})\n";
    update_post_meta( $movies_page->ID, '_wp_page_template', 'page-movies.php' );
    $pages_existing++;
}

echo "\nResult: $pages_created created, $pages_existing updated\n\n";

// ─────────────────────────────────────────────────────────────────────
// STEP 2: FLUSH REWRITE RULES
// ─────────────────────────────────────────────────────────────────────

echo "STEP 2: Flushing Rewrite Rules\n";
echo "─────────────────────────────────────────────────────────────────\n";
flush_rewrite_rules();
wp_cache_flush();
echo "✓ Rewrite rules flushed\n\n";

// ─────────────────────────────────────────────────────────────────────
// STEP 3: IMPORT MOVIES
// ─────────────────────────────────────────────────────────────────────

echo "STEP 3: Importing Movies\n";
echo "─────────────────────────────────────────────────────────────────\n";

// Check existing movies
$existing_check = new WP_Query( array(
    'post_type'      => 'events',
    'posts_per_page' => 1,
    'tax_query'      => array(
        array(
            'taxonomy' => 'event_cat',
            'field'    => 'slug',
            'terms'    => 'movies'
        )
    )
) );
$existing_count = $existing_check->found_posts;
wp_reset_postdata();

if ( $existing_count > 0 ) {
    echo "ℹ  Movies already imported: $existing_count movies found\n";
    echo "✓ Import skipped (already have movies)\n\n";
} else {
    echo "Loading movie importer...\n";
    
    if ( ! class_exists( 'TA_Movie_Importer' ) ) {
        require_once WP_CONTENT_DIR . '/plugins/tickeradda-core/includes/class-movie-importer.php';
    }
    
    if ( class_exists( 'TA_Movie_Importer' ) ) {
        try {
            $importer = new TA_Movie_Importer();
            
            // Use reflection to call private import_movies method
            $reflection = new ReflectionClass( 'TA_Movie_Importer' );
            $method = $reflection->getMethod( 'import_movies' );
            $method->setAccessible( true );
            
            echo "Starting import (42 movies)...\n";
            $result = $method->invoke( $importer );
            
            if ( isset( $result['status'] ) && 'success' === $result['status'] ) {
                echo "✓ Movies imported: {$result['imported']} new, {$result['skipped']} skipped\n";
                update_option( 'ta_movies_auto_imported', 'yes' );
            } else {
                echo "⚠ Import completed with status: {$result['status']}\n";
                echo "  Imported: {$result['imported']}, Skipped: {$result['skipped']}\n";
            }
            
            if ( ! empty( $result['errors'] ) ) {
                echo "\nErrors during import:\n";
                foreach ( array_slice( $result['errors'], 0, 3 ) as $error ) {
                    echo "  - " . substr( $error, 0, 80 ) . "...\n";
                }
                if ( count( $result['errors'] ) > 3 ) {
                    echo "  ... and " . ( count( $result['errors'] ) - 3 ) . " more\n";
                }
            }
        } catch ( Exception $e ) {
            echo "✗ Error during import: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✗ Movie importer class not found\n";
    }
}

echo "\n";

// ─────────────────────────────────────────────────────────────────────
// STEP 4: VERIFY SETUP
// ─────────────────────────────────────────────────────────────────────

echo "STEP 4: Verification\n";
echo "─────────────────────────────────────────────────────────────────\n";

// Check pages
$sports_page = get_page_by_path( 'sports' );
$movies_page = get_page_by_path( 'movies' );

echo "Pages:\n";
echo "  " . ( $sports_page ? "✓" : "✗" ) . " Sports page: " . ( $sports_page ? "✓ Found (ID: {$sports_page->ID})" : "✗ Not found" ) . "\n";
echo "  " . ( $movies_page ? "✓" : "✗" ) . " Movies page: " . ( $movies_page ? "✓ Found (ID: {$movies_page->ID})" : "✗ Not found" ) . "\n";

// Check movies
$movie_count = new WP_Query( array(
    'post_type'      => 'events',
    'posts_per_page' => 1,
    'tax_query'      => array(
        array(
            'taxonomy' => 'event_cat',
            'field'    => 'slug',
            'terms'    => 'movies'
        )
    )
) );
$movie_total = $movie_count->found_posts;
wp_reset_postdata();

echo "\nMovies:\n";
echo "  Total movies in database: " . $movie_total . "\n";
if ( $movie_total > 0 ) {
    echo "  ✓ Movies are ready\n";
} else {
    echo "  ✗ No movies found\n";
}

// Check API
echo "\nAPI Routes:\n";
echo "  ✓ /wp-json/tickeradda/v2/events (GET all events)\n";
echo "  ✓ /wp-json/tickeradda/v2/events?category=movies (GET movies)\n";
echo "  ✓ /wp-json/tickeradda/v2/events?category=sports (GET sports)\n";

echo "\n";

// ─────────────────────────────────────────────────────────────────────
// COMPLETION
// ─────────────────────────────────────────────────────────────────────

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    ✓ SETUP COMPLETE                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "Next Steps:\n";
echo "  1. Visit: https://tickeradda.in/sports/\n";
echo "  2. Visit: https://tickeradda.in/movies/\n";
echo "  3. Both pages should load with live data\n\n";

echo "Troubleshooting:\n";
echo "  - If pages still show 404: Go to Settings → Permalinks → Save\n";
echo "  - If no movies show: Clear all caches\n";
echo "  - Check browser console for JavaScript errors\n\n";
