<?php
/**
 * TAE_DB — Handles Custom Database Table Setup
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TAE_DB {
    public function __construct() {
        register_activation_hook( TAE_PLUGIN_DIR . 'ticketadda-events.php', array( $this, 'create_tables' ) );
        add_action( 'plugins_loaded', array( $this, 'check_db_update' ) );
    }

    public function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'events_master';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            event_name varchar(255) NOT NULL,
            venue varchar(255) NOT NULL,
            event_date date NOT NULL,
            event_time time NOT NULL,
            poster_url varchar(255) DEFAULT '',
            event_type varchar(50) DEFAULT 'sports' NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        // Create orders table as well
        $order_table = $wpdb->prefix . 'tae_orders';
        $sql_order = "CREATE TABLE $order_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_time datetime NOT NULL,
            ticket_id mediumint(9) NOT NULL,
            buyer_id mediumint(9) NOT NULL,
            seller_id mediumint(9) NOT NULL,
            amount decimal(10,2) NOT NULL,
            commission decimal(10,2) NOT NULL,
            status varchar(20) DEFAULT 'completed' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta( $sql_order );
    }

    public function check_db_update() {
        if ( get_option( 'tae_db_version' ) !== '1.0.0' ) {
            $this->create_tables();
            update_option( 'tae_db_version', '1.0.0' );
        }
    }
}
