<?php
/**
 * Fix and create all marketplace pages
 * Run this via: php fix-all-pages.php (if wp-load.php is available)
 * Or upload to your server root and visit tickeradda.shop/fix-all-pages.php
 */

// Try to find wp-load.php
$wp_load = 'wp-load.php';
if (!file_exists($wp_load)) {
    $wp_load = '../wp-load.php';
}
if (!file_exists($wp_load)) {
    die("Error: wp-load.php not found. Please place this script in your WordPress root directory.");
}

require_once $wp_load;

echo "<pre>=== FIXING MARKETPLACE PAGES ===\n\n";

$pages = [
    'sports' => [
        'title'    => 'Sports',
        'template' => 'page-sports.php',
        'content'  => 'Sports events marketplace'
    ],
    'movies' => [
        'title'    => 'Movies',
        'template' => 'page-movies.php',
        'content'  => 'Movies marketplace'
    ],
    'theatre' => [
        'title'    => 'Theatre',
        'template' => 'page-theatre.php',
        'content'  => 'Theatre and Stage shows'
    ]
];

foreach ($pages as $slug => $data) {
    echo "Processing page: [{$slug}] ... ";
    $page = get_page_by_path($slug);
    
    if (!$page) {
        $page_id = wp_insert_post([
            'post_type'      => 'page',
            'post_title'     => $data['title'],
            'post_name'      => $slug,
            'post_status'    => 'publish',
            'post_content'   => $data['content'],
            'page_template'  => $data['template'],
        ]);
        if (is_wp_error($page_id)) {
            echo "✗ Error: " . $page_id->get_error_message() . "\n";
        } else {
            echo "✓ Created (ID: $page_id)\n";
        }
    } else {
        echo "✓ Exists (ID: {$page->ID})\n";
        // Ensure template is correct
        update_post_meta($page->ID, '_wp_page_template', $data['template']);
        // Ensure published
        if ($page->post_status !== 'publish') {
            wp_update_post(['ID' => $page->ID, 'post_status' => 'publish']);
            echo "  Updated status to publish.\n";
        }
    }
}

echo "\nFlushing rewrite rules... ";
flush_rewrite_rules();
echo "✓ Done\n";

echo "\n=== ALL PAGES ARE READY ===\n";
echo "Visit:\n";
echo "- https://ticketadda.shop/sports/\n";
echo "- https://ticketadda.shop/movies/\n";
echo "- https://ticketadda.shop/theatre/\n";
echo "</pre>";
