<?php
require_once '/Users/avinash/Desktop/wordpress/wp-load.php';
$slug = 'hcv-vs-trs';
$post = get_page_by_path($slug, OBJECT, array('events', 'event_listing', 'post', 'page'));
if ($post) {
    echo "Found post: " . $post->post_title . "\n";
    echo "Post Type: " . $post->post_type . "\n";
    echo "Post ID: " . $post->ID . "\n";
} else {
    echo "Post not found for slug: " . $slug . "\n";
    // Search by title just in case
    $args = array(
        'title'     => $slug,
        'post_type' => 'any',
        'posts_per_page' => 1
    );
    $query = new WP_Query($args);
    if ($query->have_posts()) {
        $p = $query->posts[0];
        echo "Found by title search: " . $p->post_title . " (Type: " . $p->post_type . ")\n";
    } else {
        echo "No post found by title search either.\n";
    }
}
