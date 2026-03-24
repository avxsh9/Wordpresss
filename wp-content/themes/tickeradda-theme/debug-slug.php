<?php
/**
 * Template Name: Debug Slug
 */
get_header();
echo '<div class="container" style="padding:100px 0; color:white;">';
$slug = 'movies';
echo "<h1>Checking slug: $slug</h1>";

$args = array(
    'name'        => $slug,
    'post_type'   => 'any',
    'post_status' => 'any',
    'numberposts' => -1
);
$posts = get_posts($args);

if($posts) {
    foreach($posts as $post) {
        echo "<p>Found ID: $post->ID, Type: $post->post_type, Status: $post->post_status, Name: $post->post_name</p>";
    }
} else {
    echo "<p>No posts found with name $slug</p>";
}

// Check if it's a CPT archive slug
global $wp_post_types;
foreach($wp_post_types as $type => $obj) {
    if($obj->has_archive === $slug || (is_array($obj->rewrite) && $obj->rewrite['slug'] === $slug)) {
        echo "<p>Slug is reserved by CPT: $type (Archive: " . ($obj->has_archive ? 'Yes' : 'No') . ", Slug: " . $obj->rewrite['slug'] . ")</p>";
    }
}

echo '</div>';
get_footer();
