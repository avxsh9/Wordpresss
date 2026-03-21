<?php
/**
 * Improved Migration Script
 */
require_once 'wp-load.php';

// Temporary security check - user must add ?run=1 to the URL
if ( !isset($_GET['run']) || $_GET['run'] != '1' ) {
    die( 'Unauthorized. Append ?run=1 to the URL to execute.' );
}

global $wpdb;
$table = $wpdb->prefix . 'ta_tickets';

echo "<h2>Starting Database Migration</h2>";

$columns_to_check = array(
    'event_id'   => 'BIGINT(20) UNSIGNED AFTER id',
    'event_name' => 'VARCHAR(255) NOT NULL AFTER event_id',
    'type'       => "VARCHAR(50) NOT NULL DEFAULT 'other' AFTER event_name",
    'seat_number' => 'VARCHAR(50) AFTER row_label',
    'event_time' => 'VARCHAR(10) NOT NULL AFTER event_date'
);

foreach ( $columns_to_check as $col_name => $col_def ) {
    $exists = $wpdb->get_results( "SHOW COLUMNS FROM `{$table}` LIKE '{$col_name}'" );

    if ( empty( $exists ) ) {
        $result = $wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `{$col_name}` {$col_def}" );
        if ( $col_name === 'event_id' ) {
            $wpdb->query( "ALTER TABLE `{$table}` ADD INDEX (`event_id`)" );
        }
        
        if ( $result === false ) {
            echo "<p style='color:red;'>Error adding column '{$col_name}': " . $wpdb->last_error . "</p>";
        } else {
            echo "<p style='color:green;'>Column '{$col_name}' added successfully.</p>";
        }
    } else {
        echo "<p style='color:blue;'>Column '{$col_name}' already exists.</p>";
    }
}

echo "<h3>Migrating Orders Table</h3>";
$orders_table = $wpdb->prefix . 'ta_orders';
$orders_columns = array(
    'is_ticket_sent' => 'TINYINT(1) NOT NULL DEFAULT 0 AFTER status',
    'razorpay_order_id' => 'VARCHAR(100) AFTER is_ticket_sent',
    'razorpay_payment_id' => 'VARCHAR(100) AFTER razorpay_order_id',
    'razorpay_signature' => 'VARCHAR(255) AFTER razorpay_payment_id'
);

foreach ( $orders_columns as $col_name => $col_def ) {
    $exists = $wpdb->get_results( "SHOW COLUMNS FROM `{$orders_table}` LIKE '{$col_name}'" );
    if ( empty( $exists ) ) {
        $result = $wpdb->query( "ALTER TABLE `{$orders_table}` ADD COLUMN `{$col_name}` {$col_def}" );
        if ( $result === false ) {
            echo "<p style='color:red;'>Error adding column '{$col_name}' to orders: " . $wpdb->last_error . "</p>";
        } else {
            echo "<p style='color:green;'>Column '{$col_name}' added to orders successfully.</p>";
        }
    } else {
        echo "<p style='color:blue;'>Column '{$col_name}' already exists in orders.</p>";
    }
}

echo "<h3>Migration Finished</h3>";
?>
