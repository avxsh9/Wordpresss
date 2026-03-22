<?php
/**
 * REST API Debugger
 */
define('WP_USE_THEMES', false);
require_once __DIR__ . '/wp-load.php';

$request = new WP_REST_Request('GET', '/tickeradda/v2/events');
$response = rest_do_request($request);
$data = $response->get_data();

header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT);
