<?php
/**
 * Test script to trigger automatic movie import
 */

// Load WordPress
require_once 'wp-load.php';

// Check if movies already exist
$movies_check = new WP_Query( array(
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

$existing_movies = $movies_check->found_posts;
wp_reset_postdata();

echo "=== TICKERADDA MOVIE AUTO-IMPORT ===" . PHP_EOL . PHP_EOL;
echo "Existing movies in database: " . $existing_movies . PHP_EOL;
echo PHP_EOL;

if ( $existing_movies > 0 ) {
    echo "✓ Movies already imported. Skipping auto-import." . PHP_EOL;
    die;
}

// Load the importer class
require_once WP_CONTENT_DIR . '/plugins/tickeradda-core/includes/class-movie-importer.php';

// Check if class exists
if ( ! class_exists( 'TA_Movie_Importer' ) ) {
    echo "✗ Error: TA_Movie_Importer class not found" . PHP_EOL;
    die;
}

echo "Starting automatic movie import..." . PHP_EOL;
echo "Expected: 42 movies" . PHP_EOL . PHP_EOL;

// Create importer instance
$importer = new TA_Movie_Importer();

// Call the import function via reflection
$reflection = new ReflectionClass( 'TA_Movie_Importer' );
$import_method = $reflection->getMethod( 'import_movies' );
$import_method->setAccessible( true );

// Run the import
$result = $import_method->invoke( $importer );

// Display results
echo "=== IMPORT COMPLETE ===" . PHP_EOL . PHP_EOL;
echo "Status: " . strtoupper( $result['status'] ) . PHP_EOL;
echo "Imported: " . $result['imported'] . " movies" . PHP_EOL;
echo "Skipped: " . $result['skipped'] . " (already exist)" . PHP_EOL;

if ( ! empty( $result['errors'] ) ) {
    echo PHP_EOL . "Errors:" . PHP_EOL;
    foreach ( $result['errors'] as $error ) {
        echo "  - " . $error . PHP_EOL;
    }
}

// Verify import
$verify = new WP_Query( array(
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

$final_count = $verify->found_posts;
wp_reset_postdata();

echo PHP_EOL . "=== VERIFICATION ===" . PHP_EOL;
echo "Total movies now in database: " . $final_count . PHP_EOL;

if ( $final_count > 0 ) {
    echo "✓ SUCCESS! Movies are ready to display" . PHP_EOL;
} else {
    echo "✗ Import may have failed - no movies found" . PHP_EOL;
}
