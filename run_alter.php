<?php
require_once 'wp-load.php';
global $wpdb;

$table = $wpdb->prefix . 'ta_tickets';

// Check if payment_proof_url exists
$has_payment_proof = $wpdb->get_results("SHOW COLUMNS FROM `{$table}` LIKE 'payment_proof_url'");
if (empty($has_payment_proof)) {
    $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN `payment_proof_url` VARCHAR(512) DEFAULT NULL AFTER `file_hash`");
    echo "Added payment_proof_url.\n";
}

// Check if agreement_accepted exists
$has_agreement = $wpdb->get_results("SHOW COLUMNS FROM `{$table}` LIKE 'agreement_accepted'");
if (empty($has_agreement)) {
    $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN `agreement_accepted` TINYINT(1) DEFAULT 0 AFTER `payment_proof_url`");
    echo "Added agreement_accepted.\n";
}

echo "Database updated.\n";
