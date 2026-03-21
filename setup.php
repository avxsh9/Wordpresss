<?php
/**
 * TickerAdda Setup Script
 * Place this in your WordPress root directory and visit it in browser to create missing pages.
 */
require_once 'wp-load.php';

if (!current_user_can('manage_options')) {
    die('Unauthorized. Please login as Admin first.');
}

$pages = [
    'sell-ticket'        => ['title' => 'Sell Ticket',        'template' => 'page-sell-ticket.php'],
    'seller-dashboard'  => ['title' => 'Seller Dashboard',  'template' => 'page-seller-dashboard.php'],
    'buyer-dashboard'   => ['title' => 'Buyer Dashboard',   'template' => 'page-buyer-dashboard.php'],
    'kyc-verification'  => ['title' => 'KYC Verification',  'template' => 'page-kyc.php'],
    'my-listings'       => ['title' => 'My Listings',       'template' => 'page-listings.php'],
    'my-orders'         => ['title' => 'My Orders',         'template' => 'page-orders.php'],
    'payouts'           => ['title' => 'Payouts',           'template' => 'page-payouts.php'],
    'calculator'        => ['title' => 'Calculator',        'template' => 'page-calculator.php'],
    'events'            => ['title' => 'Events',            'template' => 'page-events.php'],
    'buy-ticket'        => ['title' => 'Buy Ticket',       'template' => 'page-buy-ticket.php'],
    'order-success'     => ['title' => 'Order Success',    'template' => 'page-order-success.php'],
    'login'             => ['title' => 'Login',             'template' => 'page-login.php'],
    'register'          => ['title' => 'Register',          'template' => 'page-register.php'],
    'forgot-password'   => ['title' => 'Forgot Password',   'template' => 'page-forgot-password.php'],
    'about'             => ['title' => 'About Us',          'template' => 'page-about.php'],
    'careers'           => ['title' => 'Careers',           'template' => 'page-careers.php'],
    'contact'           => ['title' => 'Contact Us',        'template' => 'page-contact.php'],
    'terms'             => ['title' => 'Terms',             'template' => 'page-terms.php'],
    'privacy'           => ['title' => 'Privacy',           'template' => 'page-privacy.php'],
    'refund-policy'     => ['title' => 'Refund Policy',     'template' => 'page-refund-policy.php'],
    'aadhaar-compliance'=> ['title' => 'Aadhaar Compliance','template' => 'page-aadhaar-compliance.php'],
];

echo "<h1>TickerAdda Page Setup</h1><ul>";

foreach ($pages as $slug => $data) {
    if (!get_page_by_path($slug)) {
        $post_id = wp_insert_post([
            'post_title'    => $data['title'],
            'post_name'     => $slug,
            'post_status'   => 'publish',
            'post_type'     => 'page',
        ]);
        if ($post_id) {
            update_post_meta($post_id, '_wp_page_template', $data['template']);
            echo "<li><span style='color:green'>Created:</span> <strong>{$data['title']}</strong> (slug: $slug)</li>";
        }
    } else {
        echo "<li><span style='color:blue'>Already Exists:</span> <strong>{$data['title']}</strong></li>";
    }
}

echo "</ul><p><strong>Setup Complete!</strong> You can now delete this file.</p>";
