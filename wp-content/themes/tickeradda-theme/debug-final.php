<?php
require_once('../../../wp-load.php');
global $wpdb;

$slugs = array('movies', 'sports', 'theatre', 'play');
echo "<h2>Slug Conflict Report</h2>";

foreach ($slugs as $slug) {
    echo "<h3>Checking slug: $slug</h3>";
    
    // Check Posts
    $posts = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_type, post_status FROM $wpdb->posts WHERE post_name = %s", $slug));
    if ($posts) {
        foreach ($posts as $p) {
            echo "POST: ID {$p->ID} | Title: {$p->post_title} | Type: {$p->post_type} | Status: {$p->post_status}<br>";
        }
    } else {
        echo "No posts found with this slug.<br>";
    }
    
    // Check Terms
    $terms = $wpdb->get_results($wpdb->prepare("SELECT t.term_id, t.name, tt.taxonomy FROM $wpdb->terms t INNER JOIN $wpdb->term_taxonomy tt ON t.term_id = tt.term_id WHERE t.slug = %s", $slug));
    if ($terms) {
        foreach ($terms as $t) {
            echo "TERM: ID {$t->term_id} | Name: {$t->name} | Taxonomy: {$t->taxonomy}<br>";
        }
    } else {
        echo "No terms found with this slug.<br>";
    }
    echo "<hr>";
}
