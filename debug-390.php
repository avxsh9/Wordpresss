<?php
require_once 'wp-load.php';
$post_id = 390;
$post = get_post($post_id);
if (!$post) {
    echo "Post 390 not found\n";
    exit;
}
echo "Post Type: " . $post->post_type . "\n";
echo "Post Status: " . $post->post_status . "\n";
echo "Metadata:\n";
print_r(get_post_meta($post_id));

// Check tickets table
global $wpdb;
$table = $wpdb->prefix . 'ta_tickets';
$tickets = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE event_id = %d", $post_id));
echo "\nTickets for this event:\n";
print_r($tickets);
