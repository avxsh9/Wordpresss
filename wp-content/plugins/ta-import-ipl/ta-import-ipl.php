<?php
/**
 * Plugin Name: TA IPL Import (One-Time)
 * Description: Imports 20 IPL 2026 events with placeholder image. Auto-deactivates after running.
 * Version: 1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', function() {
    // Only run once
    if ( get_option( 'ta_ipl_imported_2026' ) ) return;

    $matches = array(
        array( "Sunrisers Hyderabad vs Royal Challengers Bengaluru", "2026-03-28", "19:30", "Bengaluru" ),
        array( "Kolkata Knight Riders vs Mumbai Indians",             "2026-03-29", "19:30", "Kolkata" ),
        array( "Chennai Super Kings vs Rajasthan Royals",             "2026-03-30", "19:30", "Chennai" ),
        array( "Gujarat Titans vs Punjab Kings",                      "2026-03-31", "19:30", "Ahmedabad" ),
        array( "Delhi Capitals vs Lucknow Super Giants",              "2026-04-01", "19:30", "Delhi" ),
        array( "Sunrisers Hyderabad vs Kolkata Knight Riders",        "2026-04-02", "19:30", "Hyderabad" ),
        array( "Punjab Kings vs Chennai Super Kings",                 "2026-04-03", "19:30", "Mohali" ),
        array( "Mumbai Indians vs Delhi Capitals",                    "2026-04-04", "15:30", "Mumbai" ),
        array( "Rajasthan Royals vs Gujarat Titans",                  "2026-04-04", "19:30", "Jaipur" ),
        array( "Lucknow Super Giants vs Sunrisers Hyderabad",         "2026-04-05", "15:30", "Lucknow" ),
        array( "Chennai Super Kings vs Royal Challengers Bengaluru",  "2026-04-05", "19:30", "Chennai" ),
        array( "Punjab Kings vs Kolkata Knight Riders",               "2026-04-06", "19:30", "Mohali" ),
        array( "Mumbai Indians vs Rajasthan Royals",                  "2026-04-07", "19:30", "Mumbai" ),
        array( "Gujarat Titans vs Delhi Capitals",                    "2026-04-08", "19:30", "Ahmedabad" ),
        array( "Lucknow Super Giants vs Kolkata Knight Riders",       "2026-04-09", "19:30", "Lucknow" ),
        array( "Royal Challengers Bengaluru vs Rajasthan Royals",     "2026-04-10", "19:30", "Bengaluru" ),
        array( "Sunrisers Hyderabad vs Punjab Kings",                 "2026-04-11", "15:30", "Hyderabad" ),
        array( "Delhi Capitals vs Chennai Super Kings",               "2026-04-11", "19:30", "Delhi" ),
        array( "Gujarat Titans vs Lucknow Super Giants",              "2026-04-12", "15:30", "Ahmedabad" ),
        array( "Royal Challengers Bengaluru vs Mumbai Indians",       "2026-04-12", "19:30", "Bengaluru" ),
    );

    // Upload placeholder image to media library
    $image_source = plugin_dir_path( __FILE__ ) . 'ipl-placeholder.png';
    $attach_id    = 0;

    if ( file_exists( $image_source ) ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $upload_dir  = wp_upload_dir();
        $filename    = 'ipl-2026-placeholder.png';
        $dest        = $upload_dir['path'] . '/' . $filename;
        copy( $image_source, $dest );

        $attachment = array(
            'guid'           => $upload_dir['url'] . '/' . $filename,
            'post_mime_type' => 'image/png',
            'post_title'     => 'IPL 2026 Placeholder',
            'post_content'   => '',
            'post_status'    => 'inherit',
        );
        $attach_id  = wp_insert_attachment( $attachment, $dest );
        $meta       = wp_generate_attachment_metadata( $attach_id, $dest );
        wp_update_attachment_metadata( $attach_id, $meta );
    }

    // Ensure Sports category exists
    if ( ! term_exists( 'Sports', 'event_cat' ) ) {
        wp_insert_term( 'Sports', 'event_cat' );
    }
    $term     = get_term_by( 'name', 'Sports', 'event_cat' );
    $sports_id = $term ? (int) $term->term_id : 0;

    foreach ( $matches as $m ) {
        // Skip if already exists
        $existing = get_posts( array(
            'post_type'   => 'events',
            'post_status' => 'publish',
            'title'       => $m[0],
            'numberposts' => 1,
        ) );
        if ( $existing ) continue;

        $post_id = wp_insert_post( array(
            'post_title'   => $m[0],
            'post_status'  => 'publish',
            'post_type'    => 'events',
            'post_content' => 'Watch ' . $m[0] . ' live at ' . $m[3] . ' on ' . date( 'd M Y', strtotime( $m[1] ) ) . '. Book your tickets on TickerAdda.',
        ) );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, 'event_date',     $m[1] );
            update_post_meta( $post_id, 'event_time',     $m[2] );
            update_post_meta( $post_id, 'event_location', $m[3] );

            if ( $attach_id ) {
                set_post_thumbnail( $post_id, $attach_id );
            }
            if ( $sports_id ) {
                wp_set_object_terms( $post_id, $sports_id, 'event_cat' );
            }
        }
    }

    // Mark as done so this never runs again
    update_option( 'ta_ipl_imported_2026', true );
}, 20 );
