<?php
/**
 * Test REST API connectivity
 */
require_once 'wp-load.php';

$url = rest_url('tickeradda/v2/auth/login');
echo "Testing URL: $url\n";

$response = wp_remote_post($url, array(
    'headers' => array('Content-Type' => 'application/json'),
    'body' => json_encode(array(
        'email' => 'test@example.com',
        'password' => 'wrongpassword'
    ))
));

if (is_wp_error($response)) {
    echo "Error: " . $response->get_error_message() . "\n";
} else {
    echo "Status: " . wp_remote_retrieve_response_code($response) . "\n";
    echo "Body: " . wp_remote_retrieve_body($response) . "\n";
}
