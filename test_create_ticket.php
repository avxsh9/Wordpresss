<?php
require_once dirname(__FILE__) . '/wp-load.php';

// Mock a file upload
$_FILES['paymentProof'] = [
    'name' => 'test-proof.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => dirname(__FILE__) . '/test-proof.jpg',
    'error' => UPLOAD_ERR_OK,
    'size' => 1024
];

// Create a mock image file
file_put_contents(dirname(__FILE__) . '/test-proof.jpg', 'mock image data');

// Setup request
$request = new WP_REST_Request( 'POST', '/tickeradda/v2/tickets' );
$request->set_param('event', 'Test Seamless Event');
$request->set_param('category', 'music');
$request->set_param('price', '150.00');
$request->set_param('quantity', '2');
$request->set_param('eventDate', '2026-05-10');
$request->set_param('eventTime', '18:00');
$request->set_param('venue', 'Local Stadium');
$request->set_param('agreement', '1');

// Execute logic directly
$tickets_api = new TA_Tickets();

// Fake admin user login
$admin_user = get_user_by('login', 'admin');
if (!$admin_user) {
    // try finding by ID 1
    $admin_user = get_user_by('id', 1);
}
if ($admin_user) {
    wp_set_current_user($admin_user->ID);
} else {
    echo "NO USER FOUND.\n";
    exit;
}

// Bypass KYC if possible, or assume admin passes KYC bypass via 'manage_options'
$response = $tickets_api->create_ticket($request);

if ( is_wp_error($response) ) {
    echo "ERROR: " . $response->get_error_code() . " - " . $response->get_error_message() . "\n";
} else {
    echo "SUCCESS:\n";
    print_r($response->get_data());
}
