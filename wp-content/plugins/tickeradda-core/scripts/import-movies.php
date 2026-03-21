<?php
/**
 * Bulk Movie Import Script
 * 
 * This script adds all movies from the TickerAdda catalog to the WordPress database.
 * To use: Place in wp-admin/admin.php?page=ta-import-movies or run via WP-CLI
 * 
 * Run via: wp eval-file wp-content/plugins/tickeradda-core/scripts/import-movies.php
 */

// Define movie data - extracted from catalog
$movies_data = array(
    array(
        'title'    => 'Vadh 2',
        'rating'   => '8.8/10',
        'votes'    => '4.8K Votes',
        'language' => 'Hindi',
        'cert'     => 'UA16+',
        'date'     => '2026-03-20',
    ),
    array(
        'title'    => 'The SpongeBob Movie: Search for SquarePants',
        'rating'   => '8/10',
        'votes'    => '310+ Votes',
        'language' => 'English',
        'cert'     => 'UA 7+',
        'date'     => '2026-03-21',
    ),
    array(
        'title'    => 'The Secret Agent',
        'rating'   => '8/10',
        'votes'    => '180+ Votes',
        'language' => 'Portuguese',
        'cert'     => 'A',
        'date'     => '2026-03-22',
    ),
    array(
        'title'    => 'Thaai Kizhavi',
        'rating'   => '9.2/10',
        'votes'    => '22.4K Votes',
        'language' => 'Tamil',
        'cert'     => 'U',
        'date'     => '2026-03-23',
    ),
    array(
        'title'    => 'Kissa Court Kachehari Ka',
        'rating'   => '7.7/10',
        'votes'    => '30+ Votes',
        'language' => 'Hindi',
        'cert'     => 'UA13+',
        'date'     => '2026-03-24',
    ),
    array(
        'title'    => 'Crime 101',
        'rating'   => '8.1/10',
        'votes'    => '3K+ Votes',
        'language' => 'English, Hindi',
        'cert'     => 'A',
        'date'     => '2026-03-25',
    ),
    array(
        'title'    => 'Ghangtaul',
        'rating'   => '9/10',
        'votes'    => '30+ Votes',
        'language' => 'Garhwali',
        'cert'     => 'UA 7+',
        'date'     => '2026-03-26',
    ),
    array(
        'title'    => 'Na Jaane Kaun Aa Gaya',
        'rating'   => '8.8/10',
        'votes'    => '290+ Votes',
        'language' => 'Hindi',
        'cert'     => 'UA16+',
        'date'     => '2026-03-27',
    ),
    array(
        'title'    => '2 Way',
        'rating'   => '8.7/10',
        'votes'    => 'Early Ratings',
        'language' => 'Hindi',
        'cert'     => 'UA16+',
        'date'     => '2026-03-28',
    ),
    array(
        'title'    => 'Miss Khiladi-The Perfect Player',
        'rating'   => '5.8/10',
        'votes'    => '40+ Votes',
        'language' => 'Hindi',
        'cert'     => 'A',
        'date'     => '2026-03-29',
    ),
    array(
        'title'    => 'Roller Coaster 7D - Combo',
        'rating'   => '5.8/10',
        'votes'    => '1K+ Votes',
        'language' => 'English 7D',
        'cert'     => '',
        'date'     => '2026-03-30',
    ),
    array(
        'title'    => 'Bhaiaji Superhit',
        'rating'   => '5.2/10',
        'votes'    => '4K+ Votes',
        'language' => 'Hindi',
        'cert'     => 'UA',
        'date'     => '2026-03-31',
    ),
    array(
        'title'    => 'Oslo: A Tale of Promise',
        'rating'   => '8.2/10',
        'votes'    => '45+ Votes',
        'language' => 'English',
        'cert'     => 'U',
        'date'     => '2026-04-01',
    ),
    array(
        'title'    => 'The Bride',
        'rating'   => '7.1/10',
        'votes'    => '650+ Votes',
        'language' => 'English',
        'cert'     => 'A',
        'date'     => '2026-04-02',
    ),
    array(
        'title'    => 'Iron Lung',
        'rating'   => '7.4/10',
        'votes'    => '450+ Votes',
        'language' => 'English',
        'cert'     => 'A',
        'date'     => '2026-04-03',
    ),
    array(
        'title'    => 'Viyaah Kartaare Da',
        'rating'   => '9.1/10',
        'votes'    => '1.7K+ Votes',
        'language' => 'Punjabi',
        'cert'     => 'UA 7+',
        'date'     => '2026-04-04',
    ),
    array(
        'title'    => 'Wuthering Heights',
        'rating'   => '7.3/10',
        'votes'    => '2.9K Votes',
        'language' => 'English',
        'cert'     => 'A',
        'date'     => '2026-04-05',
    ),
    array(
        'title'    => 'Demon Slayer: Kimetsu no Yaiba Infinity Castle',
        'rating'   => '9.5/10',
        'votes'    => '150+ Votes',
        'language' => 'Japanese, English, Hindi',
        'cert'     => 'UA13+',
        'date'     => '2026-04-06',
    ),
    array(
        'title'    => 'Tere Naam',
        'rating'   => '9.7/10',
        'votes'    => '2.9K Votes',
        'language' => 'Hindi',
        'cert'     => 'UA',
        'date'     => '2026-04-07',
    ),
    array(
        'title'    => 'F1: The Movie',
        'rating'   => '9.5/10',
        'votes'    => '166K+ Votes',
        'language' => 'English, Hindi, Tamil, Telugu',
        'cert'     => 'UA16+',
        'date'     => '2026-04-08',
    ),
    array(
        'title'    => 'Sinners',
        'rating'   => '8.4/10',
        'votes'    => '14.8K Votes',
        'language' => 'English',
        'cert'     => 'A',
        'date'     => '2026-04-09',
    ),
    array(
        'title'    => 'Avatar: Fire and Ash',
        'rating'   => '8/10',
        'votes'    => '136K+ Votes',
        'language' => 'English, Kannada',
        'cert'     => 'UA16+',
        'date'     => '2026-04-10',
    ),
    array(
        'title'    => 'Marty Supreme',
        'rating'   => '8.4/10',
        'votes'    => '5.1K Votes',
        'language' => 'English',
        'cert'     => 'A',
        'date'     => '2026-04-11',
    ),
    array(
        'title'    => 'Zootopia 2',
        'rating'   => '9.1/10',
        'votes'    => '22.8K Votes',
        'language' => 'English, Hindi, Tamil, Telugu',
        'cert'     => 'UA 7+',
        'date'     => '2026-04-12',
    ),
    array(
        'title'    => 'Goat',
        'rating'   => '9.1/10',
        'votes'    => '2.3K Votes',
        'language' => 'English, Hindi',
        'cert'     => 'U',
        'date'     => '2026-04-13',
    ),
    array(
        'title'    => 'Do Deewane Seher Mein',
        'rating'   => '8.3/10',
        'votes'    => '5K+ Votes',
        'language' => 'Hindi',
        'cert'     => 'UA13+',
        'date'     => '2026-04-14',
    ),
    array(
        'title'    => 'Nukkad Naatak',
        'rating'   => '9/10',
        'votes'    => '330+ Votes',
        'language' => 'Hindi',
        'cert'     => 'UA16+',
        'date'     => '2026-04-15',
    ),
    array(
        'title'    => 'Scream 7',
        'rating'   => '7.4/10',
        'votes'    => '1.2K Votes',
        'language' => 'English',
        'cert'     => 'A',
        'date'     => '2026-04-16',
    ),
    array(
        'title'    => 'Bambukat 2',
        'rating'   => '9.2/10',
        'votes'    => '1.9K Votes',
        'language' => 'Punjabi',
        'cert'     => 'U',
        'date'     => '2026-04-17',
    ),
    array(
        'title'    => 'Dhurandhar The Revenge',
        'rating'   => '8.6/10',
        'votes'    => '639K+ Likes',
        'language' => 'Hindi, Telugu, Tamil',
        'cert'     => 'A',
        'date'     => '2026-04-18',
    ),
    array(
        'title'    => 'The Kerala Story 2: Goes Beyond',
        'rating'   => '9.2/10',
        'votes'    => '25.5K+ Votes',
        'language' => 'Hindi, Telugu',
        'cert'     => 'UA16+',
        'date'     => '2026-04-19',
    ),
    array(
        'title'    => 'Ishqan De Lekhe',
        'rating'   => '9.9/10',
        'votes'    => '2.1K Votes',
        'language' => 'Punjabi',
        'cert'     => 'UA13+',
        'date'     => '2026-04-20',
    ),
    array(
        'title'    => 'Adventure of Jetcat 7D - Combo',
        'rating'   => '1.8/10',
        'votes'    => '4 Votes',
        'language' => 'English 7D',
        'cert'     => '',
        'date'     => '2026-04-21',
    ),
    array(
        'title'    => 'Adventure of Iceberg 7D - Combo',
        'rating'   => '2.8/10',
        'votes'    => '4 Votes',
        'language' => 'English 7D',
        'cert'     => '',
        'date'     => '2026-04-22',
    ),
    array(
        'title'    => 'O\' Romeo',
        'rating'   => '7.6/10',
        'votes'    => '159K+ Likes',
        'language' => 'Hindi',
        'cert'     => 'A',
        'date'     => '2026-04-23',
    ),
    array(
        'title'    => 'Hoppers',
        'rating'   => '9.2/10',
        'votes'    => '4.5K Votes',
        'language' => 'English, Hindi',
        'cert'     => 'UA 7+',
        'date'     => '2026-04-24',
    ),
    array(
        'title'    => 'Dhurandhar',
        'rating'   => '9.3/10',
        'votes'    => '554K+ Votes',
        'language' => 'Hindi',
        'cert'     => 'A',
        'date'     => '2026-04-25',
    ),
    array(
        'title'    => 'Assi',
        'rating'   => '9.3/10',
        'votes'    => '8.8K Votes',
        'language' => 'Hindi',
        'cert'     => 'A',
        'date'     => '2026-04-26',
    ),
    array(
        'title'    => 'Mardaani 3',
        'rating'   => '8.9/10',
        'votes'    => '34K+ Votes',
        'language' => 'Hindi',
        'cert'     => 'UA16+',
        'date'     => '2026-04-27',
    ),
    array(
        'title'    => 'Border 2',
        'rating'   => '8.4/10',
        'votes'    => '113K+ Votes',
        'language' => 'Hindi',
        'cert'     => 'UA13+',
        'date'     => '2026-04-28',
    ),
    array(
        'title'    => 'Ramyaa',
        'rating'   => '3.7/10',
        'votes'    => '40+ Votes',
        'language' => 'Hindi',
        'cert'     => 'UA16+',
        'date'     => '2026-04-29',
    ),
    array(
        'title'    => 'Boong',
        'rating'   => '9.5/10',
        'votes'    => '2.1K Votes',
        'language' => 'Manipuri',
        'cert'     => 'U',
        'date'     => '2026-04-30',
    ),
    array(
        'title'    => 'Reminders of Him',
        'rating'   => '8.4/10',
        'votes'    => '110+ Votes',
        'language' => 'English',
        'cert'     => 'UA16+',
        'date'     => '2026-05-01',
    ),
    array(
        'title'    => 'Tu Yaa Main',
        'rating'   => '8.6/10',
        'votes'    => '6.7K Votes',
        'language' => 'Hindi',
        'cert'     => 'UA16+',
        'date'     => '2026-05-02',
    ),
    array(
        'title'    => 'Hamnet',
        'rating'   => '9.1/10',
        'votes'    => '1K Votes',
        'language' => 'Hindi',
        'cert'     => 'UA16+',
        'date'     => '2026-05-03',
    ),
);

/**
 * Import movies to WordPress database
 */
function ta_import_movies() {
    global $movies_data;
    
    if ( empty( $movies_data ) ) {
        return array( 'status' => 'error', 'message' => 'No movies to import' );
    }

    $imported = 0;
    $skipped = 0;
    $errors = array();

    // Get or create Movies category
    $movies_cat = get_term_by( 'slug', 'movies', 'event_cat' );
    if ( ! $movies_cat ) {
        $movies_cat = wp_insert_term( 'Movies', 'event_cat', array( 'slug' => 'movies' ) );
        if ( is_wp_error( $movies_cat ) ) {
            return array( 'status' => 'error', 'message' => 'Could not create Movies category: ' . $movies_cat->get_error_message() );
        }
        $movies_cat_id = $movies_cat['term_id'];
    } else {
        $movies_cat_id = $movies_cat->term_id;
    }

    foreach ( $movies_data as $movie ) {
        // Check if movie already exists
        $existing = new WP_Query( array(
            'post_type'      => 'events',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'   => 'movie_title_import_id',
                    'value' => sanitize_title( $movie['title'] ),
                    'compare' => '='
                )
            )
        ) );

        if ( $existing->have_posts() ) {
            $skipped++;
            continue;
        }

        // Create movie post
        $post_id = wp_insert_post( array(
            'post_type'    => 'events',
            'post_title'   => $movie['title'],
            'post_status'  => 'publish',
            'post_content' => 'Rating: ' . $movie['rating'] . ' | ' . $movie['votes'] . ' | Languages: ' . $movie['language'] . ' | Certificate: ' . $movie['cert'],
        ) );

        if ( is_wp_error( $post_id ) ) {
            $errors[] = 'Could not create ' . $movie['title'] . ': ' . $post_id->get_error_message();
            continue;
        }

        // Set the category
        wp_set_post_terms( $post_id, $movies_cat_id, 'event_cat' );

        // Set event metadata
        update_post_meta( $post_id, 'event_date', $movie['date'] );
        update_post_meta( $post_id, 'event_time', '10:00' );
        update_post_meta( $post_id, 'event_location', $movie['language'] );
        
        // Add custom movie metadata
        update_post_meta( $post_id, 'movie_rating', $movie['rating'] );
        update_post_meta( $post_id, 'movie_votes', $movie['votes'] );
        update_post_meta( $post_id, 'movie_language', $movie['language'] );
        update_post_meta( $post_id, 'movie_certificate', $movie['cert'] );
        update_post_meta( $post_id, 'movie_title_import_id', sanitize_title( $movie['title'] ) );

        $imported++;
    }

    return array(
        'status'   => 'success',
        'imported' => $imported,
        'skipped'  => $skipped,
        'errors'   => $errors
    );
}

// Run import if this is called from WP-CLI or admin
if ( function_exists( 'wp_insert_post' ) ) {
    $result = ta_import_movies();
    echo json_encode( $result, JSON_PRETTY_PRINT );
} else {
    die( 'WordPress not loaded' );
}
