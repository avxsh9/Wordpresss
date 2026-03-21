<?php
require_once('wp-load.php');

if (!function_exists('wp_insert_post')) {
    die('WordPress environment not found.');
}

// Only run if authorized
if (!isset($_GET['run']) || $_GET['run'] !== '1') {
    die('Unauthorized. Visit ?run=1 to execute.');
}

$page_title = 'My Tickets';
$page_slug = 'my-tickets';
$template = 'page-my-tickets.php';

// Check if page already exists
$existing_page = get_page_by_path($page_slug);

if (!$existing_page) {
    $page_data = array(
        'post_title'    => $page_title,
        'post_name'     => $page_slug,
        'post_content'  => '',
        'post_status'   => 'publish',
        'post_type'     => 'page',
    );

    $page_id = wp_insert_post($page_data);

    if ($page_id) {
        update_post_meta($page_id, '_wp_page_template', $template);
        echo "<h2 style='color:green;'>Page '{$page_title}' created successfully! (ID: {$page_id})</h2>";
        echo "<p>Template set to: {$template}</p>";
        echo "<p><a href='" . home_url('/my-tickets/') . "'>Visit page</a></p>";
    } else {
        echo "<h2 style='color:red;'>Error creating page.</h2>";
    }
} else {
    // If exists, make sure template is correct
    update_post_meta($existing_page->ID, '_wp_page_template', $template);
    echo "<h2 style='color:orange;'>Page '{$page_title}' already exists.</h2>";
    echo "<p>Template updated to: {$template}</p>";
    echo "<p><a href='" . home_url('/my-tickets/') . "'>Visit page</a></p>";
}

// Also check and update "Seller Dashboard" template just in case
$dash = get_page_by_path('seller-dashboard');
if ($dash) {
    update_post_meta($dash->ID, '_wp_page_template', 'page-seller-dashboard.php');
}

echo "<hr><p>Script execution finished.</p>";
