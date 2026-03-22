<?php
/**
 * TAE_DB — Handles Custom Database Table for Master Events
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TAE_DB {
    public function __construct() {
        register_activation_hook( TAE_DIR . 'events-marketplace.php', array( $this, 'create_tables' ) );
        add_action( 'plugins_loaded', array( $this, 'check_db_update' ) );
    }

    public function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tae_master_data';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            poster_url varchar(255) DEFAULT '',
            imdb_rating varchar(10) DEFAULT '',
            venue varchar(255) DEFAULT '',
            event_date date DEFAULT NULL,
            event_time time DEFAULT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    public function check_db_update() {
        if ( get_option( 'tae_db_v2' ) !== '2.0.0' ) {
            $this->create_tables();
            update_option( 'tae_db_v2', '2.0.0' );
        }
    }
}
