<?php
require_once 'wp-load.php';

echo "Testing TickerAdda Connectivity...\n";

$key_id = get_option('ta_razorpay_key_id');
$key_secret = get_option('ta_razorpay_key_secret');

if (!$key_id || !$key_secret) {
    echo "ERROR: Razorpay keys are not set in Options table!\n";
} else {
    echo "Razorpay Key ID: " . substr($key_id, 0, 5) . "...\n";
    echo "Razorpay Key Secret: " . substr($key_secret, 0, 3) . "...\n";
}

$response = wp_remote_get('https://api.razorpay.com/v1/orders');
if (is_wp_error($response)) {
    echo "Connectivity Error: " . $response->get_error_message() . "\n";
} else {
    echo "Razorpay API Response Code: " . wp_remote_retrieve_response_code($response) . "\n";
    echo "Body Snippet: " . substr(wp_remote_retrieve_body($response), 0, 100) . "\n";
}

$ticket_id = 1; // Assuming 1 exists
global $wpdb;
$table = $wpdb->prefix . 'ta_tickets';
$ticket = $wpdb->get_row("SELECT * FROM $table LIMIT 1");
if ($ticket) {
    echo "DB Connection OK. Found ticket: " . $ticket->event_name . "\n";
} else {
    echo "DB Error or No Tickets found.\n";
}
