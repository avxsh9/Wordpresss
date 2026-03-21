<?php
/**
 * Movie Import Admin Interface
 * 
 * Provides a WordPress admin page to bulk import movies
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TA_Movie_Importer {
    
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 100 );
        add_action( 'admin_post_ta_import_movies_action', array( $this, 'handle_import' ) );
        // Handle scheduled import events
        add_action( 'ta_import_movies', array( $this, 'run_automatic_import' ) );
    }

    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=events',
            'Import Movies',
            'Import Movies',
            'manage_options',
            'ta-import-movies',
            array( $this, 'render_import_page' )
        );
    }

    public function render_import_page() {
        ?>
        <div class="wrap">
            <h1>Import Movies to TickerAdda</h1>
            
            <div style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px; max-width: 800px;">
                <p style="font-size: 16px; color: #333; margin-bottom: 20px;">
                    This will import <strong>42 movies</strong> from the TickerAdda catalog to your WordPress database.
                </p>

                <div style="background: #eef7ff; padding: 15px; border-left: 4px solid #0073aa; margin-bottom: 20px; border-radius: 4px;">
                    <p style="margin: 0; color: #0073aa;">
                        <strong>Note:</strong> Movies that already exist in the database will be skipped.
                    </p>
                </div>

                <div style="background: #fff8e5; padding: 15px; border-left: 4px solid #ffb81c; margin-bottom: 30px; border-radius: 4px;">
                    <p style="margin: 0; color: #7a7a7a;">
                        <strong>Included movies:</strong> Vadh 2, The SpongeBob Movie, The Secret Agent, Thaai Kizhavi, Crime 101, and 37 others...
                    </p>
                </div>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'ta_import_movies_nonce', 'ta_import_movies_nonce' ); ?>
                    <input type="hidden" name="action" value="ta_import_movies_action">
                    
                    <button type="submit" class="button button-primary button-large" style="font-size: 16px; padding: 12px 30px;">
                        <i class="dashicons dashicons-upload" style="margin-right: 5px;"></i> Import All Movies
                    </button>
                </form>
            </div>

            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-top: 30px; max-width: 800px;">
                <h3>Movie List (42 Movies)</h3>
                <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <ul style="columns: 2; column-gap: 30px; margin: 0; padding: 10px;">
                        <li>Vadh 2</li>
                        <li>The SpongeBob Movie: Search for SquarePants</li>
                        <li>The Secret Agent</li>
                        <li>Thaai Kizhavi</li>
                        <li>Kissa Court Kachehari Ka</li>
                        <li>Crime 101</li>
                        <li>Ghangtaul</li>
                        <li>Na Jaane Kaun Aa Gaya</li>
                        <li>2 Way</li>
                        <li>Miss Khiladi-The Perfect Player</li>
                        <li>Roller Coaster 7D - Combo</li>
                        <li>Bhaiaji Superhit</li>
                        <li>Oslo: A Tale of Promise</li>
                        <li>The Bride</li>
                        <li>Iron Lung</li>
                        <li>Viyaah Kartaare Da</li>
                        <li>Wuthering Heights</li>
                        <li>Demon Slayer: Kimetsu no Yaiba Infinity Castle</li>
                        <li>Tere Naam</li>
                        <li>F1: The Movie</li>
                        <li>Sinners</li>
                        <li>Avatar: Fire and Ash</li>
                        <li>Marty Supreme</li>
                        <li>Zootopia 2</li>
                        <li>Goat</li>
                        <li>Do Deewane Seher Mein</li>
                        <li>Nukkad Naatak</li>
                        <li>Scream 7</li>
                        <li>Bambukat 2</li>
                        <li>Dhurandhar The Revenge</li>
                        <li>The Kerala Story 2: Goes Beyond</li>
                        <li>Ishqan De Lekhe</li>
                        <li>Adventure of Jetcat 7D - Combo</li>
                        <li>Adventure of Iceberg 7D - Combo</li>
                        <li>O' Romeo</li>
                        <li>Hoppers</li>
                        <li>Dhurandhar</li>
                        <li>Assi</li>
                        <li>Mardaani 3</li>
                        <li>Border 2</li>
                        <li>Ramyaa</li>
                        <li>Boong</li>
                        <li>Reminders of Him</li>
                        <li>Tu Yaa Main</li>
                        <li>Hamnet</li>
                    </ul>
                </div>
            </div>
        </div>

        <style>
            .ta-import-success {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
                padding: 15px;
                border-radius: 4px;
                margin-top: 20px;
                max-width: 800px;
            }
            .ta-import-error {
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
                padding: 15px;
                border-radius: 4px;
                margin-top: 20px;
                max-width: 800px;
            }
        </style>

        <?php

        // Check if import was successful
        if ( isset( $_GET['ta_import_result'] ) ) {
            $result = sanitize_text_field( wp_unslash( $_GET['ta_import_result'] ) );
            
            if ( 'success' === $result ) {
                $imported = isset( $_GET['imported'] ) ? intval( $_GET['imported'] ) : 0;
                $skipped = isset( $_GET['skipped'] ) ? intval( $_GET['skipped'] ) : 0;
                echo '<div class="ta-import-success">';
                echo '<strong>✓ Import Complete!</strong><br>';
                echo 'Imported: <strong>' . esc_html( $imported ) . ' movies</strong><br>';
                echo 'Skipped: <strong>' . esc_html( $skipped ) . ' (already exist)</strong>';
                echo '</div>';
            } elseif ( 'error' === $result ) {
                echo '<div class="ta-import-error">';
                echo '<strong>✗ Import Failed</strong><br>';
                if ( isset( $_GET['message'] ) ) {
                    echo esc_html( wp_unslash( $_GET['message'] ) );
                }
                echo '</div>';
            }
        }
    }

    public function handle_import() {
        // Verify nonce
        if ( ! isset( $_POST['ta_import_movies_nonce'] ) || ! wp_verify_nonce( $_POST['ta_import_movies_nonce'], 'ta_import_movies_nonce' ) ) {
            wp_die( 'Security check failed' );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have permission to perform this action' );
        }

        // Run the import function
        $result = $this->import_movies();

        // Redirect back to admin page with results
        $redirect_args = array(
            'page'             => 'ta-import-movies',
            'ta_import_result' => isset( $result['status'] ) && 'success' === $result['status'] ? 'success' : 'error'
        );

        if ( isset( $result['imported'] ) ) {
            $redirect_args['imported'] = intval( $result['imported'] );
        }
        if ( isset( $result['skipped'] ) ) {
            $redirect_args['skipped'] = intval( $result['skipped'] );
        }
        if ( isset( $result['message'] ) ) {
            $redirect_args['message'] = urlencode( $result['message'] );
        }

        wp_redirect( add_query_arg( $redirect_args, admin_url( 'edit.php?post_type=events' ) ) );
        exit;
    }

    /**
     * Import movies from the predefined list
     */
    private function import_movies() {
        // Movie data (same as in import-movies.php)
        $movies_data = $this->get_movies_data();
        
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
                'post_type'      => 'movies',
                'posts_per_page' => 1,
                's'              => $movie['title'],
                'exact'          => true,
            ) );

            if ( $existing->have_posts() ) {
                $skipped++;
                wp_reset_postdata();
                continue;
            }

            // Create movie post
            $post_id = wp_insert_post( array(
                'post_type'    => 'movies',
                'post_title'   => $movie['title'],
                'post_status'  => 'publish',
                'post_content' => 'Rating: ' . $movie['rating'] . ' | ' . $movie['votes'] . '<br>Languages: ' . $movie['language'] . '<br>Certificate: ' . $movie['cert'],
            ) );

            if ( is_wp_error( $post_id ) ) {
                $errors[] = 'Could not create ' . $movie['title'] . ': ' . $post_id->get_error_message();
                continue;
            }

            // Set the category
            wp_set_post_terms( $post_id, $movies_cat_id, 'event_cat' );

            // Set event metadata - date/time optional, sellers will fill them with cinema hall
            // Dates removed - sellers add these when listing tickets
            update_post_meta( $post_id, 'event_location', 'Multiple Cinemas' );
            // Date and time fields are intentionally blank for sellers to fill
            
            // Add custom movie metadata
            update_post_meta( $post_id, 'movie_rating', $movie['rating'] );
            update_post_meta( $post_id, 'movie_votes', $movie['votes'] );
            update_post_meta( $post_id, 'movie_language', $movie['language'] );
            update_post_meta( $post_id, 'movie_certificate', $movie['cert'] );

            // Set featured image (poster) - try to download and set
            if ( ! empty( $movie['poster'] ) ) {
                $this->set_featured_image_from_url( $post_id, $movie['poster'], $movie['title'] );
            }

            $imported++;
        }

        return array(
            'status'   => count( $errors ) > 0 ? 'warning' : 'success',
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors
        );
    }

    /**
     * Get the list of movies to import with poster images
     * NOTE: All 'date' fields removed - sellers will fill dates when listing tickets
     */
    private function get_movies_data() {
        return array(
            array( 'title' => 'Vadh 2', 'rating' => '8.8/10', 'votes' => '4.8K Votes', 'language' => 'Hindi', 'cert' => 'UA16+', 'poster' => 'https://images.unsplash.com/photo-1541961017774-22349e0babe4?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'The SpongeBob Movie: Search for SquarePants', 'rating' => '8/10', 'votes' => '310+ Votes', 'language' => 'English', 'cert' => 'UA 7+', 'poster' => 'https://images.unsplash.com/photo-1495562669820-29d440260313?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'The Secret Agent', 'rating' => '8/10', 'votes' => '180+ Votes', 'language' => 'Portuguese', 'cert' => 'A', 'poster' => 'https://images.unsplash.com/photo-1611162617305-c69b3fa7fbe0?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Thaai Kizhavi', 'rating' => '9.2/10', 'votes' => '22.4K Votes', 'language' => 'Tamil', 'cert' => 'U', 'poster' => 'https://images.unsplash.com/photo-1598899134739-24c46f58b8c0?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Kissa Court Kachehari Ka', 'rating' => '7.7/10', 'votes' => '30+ Votes', 'language' => 'Hindi', 'cert' => 'UA13+', 'poster' => 'https://images.unsplash.com/photo-1485579149c0-123dd979885c?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Crime 101', 'rating' => '8.1/10', 'votes' => '3K+ Votes', 'language' => 'English, Hindi', 'cert' => 'A', 'poster' => 'https://images.unsplash.com/photo-1487180144351-b8472da7d491?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Ghangtaul', 'rating' => '9/10', 'votes' => '30+ Votes', 'language' => 'Garhwali', 'cert' => 'UA 7+', 'poster' => 'https://images.unsplash.com/photo-1505686994434-e3cc5abf1330?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Na Jaane Kaun Aa Gaya', 'rating' => '8.8/10', 'votes' => '290+ Votes', 'language' => 'Hindi', 'cert' => 'UA16+', 'poster' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => '2 Way', 'rating' => '8.7/10', 'votes' => 'Early Ratings', 'language' => 'Hindi', 'cert' => 'UA16+', 'poster' => 'https://images.unsplash.com/photo-1518676590629-3dcbd9c5a5c9?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Miss Khiladi-The Perfect Player', 'rating' => '5.8/10', 'votes' => '40+ Votes', 'language' => 'Hindi', 'cert' => 'A', 'poster' => 'https://images.unsplash.com/photo-1485846234645-a62644f84728?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Roller Coaster 7D - Combo', 'rating' => '5.8/10', 'votes' => '1K+ Votes', 'language' => 'English 7D', 'cert' => '', 'poster' => 'https://images.unsplash.com/photo-1594909391200-c4e440f306c7?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Bhaiaji Superhit', 'rating' => '5.2/10', 'votes' => '4K+ Votes', 'language' => 'Hindi', 'cert' => 'UA', 'poster' => 'https://images.unsplash.com/photo-1511379938547-c1f69b13d835?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Oslo: A Tale of Promise', 'rating' => '8.2/10', 'votes' => '45+ Votes', 'language' => 'English', 'cert' => 'U', 'poster' => 'https://images.unsplash.com/photo-1499232295841-8e62f1c4f0f6?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'The Bride', 'rating' => '7.1/10', 'votes' => '650+ Votes', 'language' => 'English', 'cert' => 'A', 'poster' => 'https://images.unsplash.com/photo-1478720568477-152d9b164e26?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Iron Lung', 'rating' => '7.4/10', 'votes' => '450+ Votes', 'language' => 'English', 'cert' => 'A', 'poster' => 'https://images.unsplash.com/photo-1505686994434-e3cc5abf1330?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Viyaah Kartaare Da', 'rating' => '9.1/10', 'votes' => '1.7K+ Votes', 'language' => 'Punjabi', 'cert' => 'UA 7+', 'poster' => 'https://images.unsplash.com/photo-1533613220915-609f7a6ba338?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Wuthering Heights', 'rating' => '7.3/10', 'votes' => '2.9K Votes', 'language' => 'English', 'cert' => 'A', 'poster' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Demon Slayer: Kimetsu no Yaiba Infinity Castle', 'rating' => '9.5/10', 'votes' => '150+ Votes', 'language' => 'Japanese, English, Hindi', 'cert' => 'UA13+', 'poster' => 'https://images.unsplash.com/photo-1518676590629-3dcbd9c5a5c9?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Tere Naam', 'rating' => '9.7/10', 'votes' => '2.9K Votes', 'language' => 'Hindi', 'cert' => 'UA', 'poster' => 'https://images.unsplash.com/photo-1541961017774-22349e0babe4?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'F1: The Movie', 'rating' => '9.5/10', 'votes' => '166K+ Votes', 'language' => 'English, Hindi, Tamil, Telugu', 'cert' => 'UA16+', 'poster' => 'https://images.unsplash.com/photo-1594909391200-c4e440f306c7?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Sinners', 'rating' => '8.4/10', 'votes' => '14.8K Votes', 'language' => 'English', 'cert' => 'A', 'poster' => 'https://images.unsplash.com/photo-1487180144351-b8472da7d491?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Avatar: Fire and Ash', 'rating' => '8/10', 'votes' => '136K+ Votes', 'language' => 'English, Kannada', 'cert' => 'UA16+', 'poster' => 'https://images.unsplash.com/photo-1505686994434-e3cc5abf1330?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Marty Supreme', 'rating' => '8.4/10', 'votes' => '5.1K Votes', 'language' => 'English', 'cert' => 'A', 'poster' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Zootopia 2', 'rating' => '9.1/10', 'votes' => '22.8K Votes', 'language' => 'English, Hindi, Tamil, Telugu', 'cert' => 'UA 7+', 'poster' => 'https://images.unsplash.com/photo-1478720568477-152d9b164e26?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Goat', 'rating' => '9.1/10', 'votes' => '2.3K Votes', 'language' => 'English, Hindi', 'cert' => 'U', 'poster' => 'https://images.unsplash.com/photo-1541961017774-22349e0babe4?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Do Deewane Seher Mein', 'rating' => '8.3/10', 'votes' => '5K+ Votes', 'language' => 'Hindi', 'cert' => 'UA13+', 'poster' => 'https://images.unsplash.com/photo-1485846234645-a62644f84728?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Nukkad Naatak', 'rating' => '9/10', 'votes' => '330+ Votes', 'language' => 'Hindi', 'cert' => 'UA16+', 'poster' => 'https://images.unsplash.com/photo-1518676590629-3dcbd9c5a5c9?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Scream 7', 'rating' => '7.4/10', 'votes' => '1.2K Votes', 'language' => 'English', 'cert' => 'A', 'poster' => 'https://images.unsplash.com/photo-1511379938547-c1f69b13d835?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Bambukat 2', 'rating' => '9.2/10', 'votes' => '1.9K Votes', 'language' => 'Punjabi', 'cert' => 'U', 'poster' => 'https://images.unsplash.com/photo-1499232295841-8e62f1c4f0f6?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Dhurandhar The Revenge', 'rating' => '8.6/10', 'votes' => '639K+ Likes', 'language' => 'Hindi, Telugu, Tamil', 'cert' => 'A', 'poster' => 'https://images.unsplash.com/photo-1478720568477-152d9b164e26?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'The Kerala Story 2: Goes Beyond', 'rating' => '9.2/10', 'votes' => '25.5K+ Votes', 'language' => 'Hindi, Telugu', 'cert' => 'UA16+', 'poster' => 'https://images.unsplash.com/photo-1541961017774-22349e0babe4?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Ishqan De Lekhe', 'rating' => '9.9/10', 'votes' => '2.1K Votes', 'language' => 'Punjabi', 'cert' => 'UA13+', 'poster' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Adventure of Jetcat 7D - Combo', 'rating' => '1.8/10', 'votes' => '4 Votes', 'language' => 'English 7D', 'cert' => '', 'poster' => 'https://images.unsplash.com/photo-1533613220915-609f7a6ba338?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Adventure of Iceberg 7D - Combo', 'rating' => '2.8/10', 'votes' => '4 Votes', 'language' => 'English 7D', 'cert' => '', 'poster' => 'https://images.unsplash.com/photo-1505686994434-e3cc5abf1330?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'O\' Romeo', 'rating' => '7.6/10', 'votes' => '159K+ Likes', 'language' => 'Hindi', 'cert' => 'A', 'poster' => 'https://images.unsplash.com/photo-1487180144351-b8472da7d491?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Hoppers', 'rating' => '9.2/10', 'votes' => '4.5K Votes', 'language' => 'English, Hindi', 'cert' => 'UA 7+', 'poster' => 'https://images.unsplash.com/photo-1485846234645-a62644f84728?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Dhurandhar', 'rating' => '9.3/10', 'votes' => '554K+ Votes', 'language' => 'Hindi', 'cert' => 'A', 'poster' => 'https://images.unsplash.com/photo-1594909391200-c4e440f306c7?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Assi', 'rating' => '9.3/10', 'votes' => '8.8K Votes', 'language' => 'Hindi', 'cert' => 'A', 'poster' => 'https://images.unsplash.com/photo-1511379938547-c1f69b13d835?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Mardaani 3', 'rating' => '8.9/10', 'votes' => '34K+ Votes', 'language' => 'Hindi', 'cert' => 'UA16+', 'poster' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Border 2', 'rating' => '8.4/10', 'votes' => '113K+ Votes', 'language' => 'Hindi', 'cert' => 'UA13+', 'poster' => 'https://images.unsplash.com/photo-1478720568477-152d9b164e26?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Ramyaa', 'rating' => '3.7/10', 'votes' => '40+ Votes', 'language' => 'Hindi', 'cert' => 'UA16+', 'poster' => 'https://images.unsplash.com/photo-1541961017774-22349e0babe4?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Boong', 'rating' => '9.5/10', 'votes' => '2.1K Votes', 'language' => 'Manipuri', 'cert' => 'U', 'poster' => 'https://images.unsplash.com/photo-1487180144351-b8472da7d491?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Reminders of Him', 'rating' => '8.4/10', 'votes' => '110+ Votes', 'language' => 'English', 'cert' => 'UA16+', 'poster' => 'https://images.unsplash.com/photo-1505686994434-e3cc5abf1330?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Tu Yaa Main', 'rating' => '8.6/10', 'votes' => '6.7K Votes', 'language' => 'Hindi', 'cert' => 'UA16+', 'poster' => 'https://images.unsplash.com/photo-1533613220915-609f7a6ba338?auto=format&fit=crop&w=500&h=750&q=80' ),
            array( 'title' => 'Hamnet', 'rating' => '9.1/10', 'votes' => '1K Votes', 'language' => 'Hindi', 'cert' => 'UA16+', 'poster' => 'https://images.unsplash.com/photo-1499232295841-8e62f1c4f0f6?auto=format&fit=crop&w=500&h=750&q=80' ),
        );
    }

    /**
     * Set featured image from URL
     */
    private function set_featured_image_from_url( $post_id, $image_url, $title ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $image_url = esc_url_raw( $image_url );
        $response = wp_remote_get( $image_url );
        
        if ( ! is_wp_error( $response ) ) {
            $image_data = wp_remote_retrieve_body( $response );
            $filename = sanitize_file_name( $title . '.jpg' );
            $upload_dir = wp_upload_dir();
            $file_path = $upload_dir['path'] . '/' . $filename;

            file_put_contents( $file_path, $image_data );

            $attachment = array(
                'post_mime_type' => 'image/jpeg',
                'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
                'post_content'   => '',
                'post_status'    => 'inherit'
            );

            $attach_id = wp_insert_attachment( $attachment, $file_path, $post_id );
            if ( ! is_wp_error( $attach_id ) ) {
                $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
                wp_update_attachment_metadata( $attach_id, $attach_data );
                set_post_thumbnail( $post_id, $attach_id );
            }
        }
    }

    /**
     * Run automatic import (for scheduled events)
     */
    public function run_automatic_import() {
        // Only run once per installation
        if ( get_option( 'ta_movies_auto_imported' ) ) {
            return;
        }
        
        $result = $this->import_movies();
        
        if ( isset( $result['status'] ) && 'success' === $result['status'] ) {
            update_option( 'ta_movies_auto_imported', 'yes' );
            error_log( '[TickerAdda] Auto-imported ' . $result['imported'] . ' movies' );
        } else {
            error_log( '[TickerAdda] Movie auto-import error: ' . ( $result['message'] ?? 'Unknown error' ) );
        }
    }
}

// Initialize the importer
new TA_Movie_Importer();

