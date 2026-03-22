<?php
/**
 * UEM_DB — Handles Custom Database Table for Master Events
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class UEM_DB {
    public function __construct() {
        register_activation_hook( UEM_DIR . 'universal-events-marketplace.php', array( $this, 'create_tables' ) );
        add_action( 'plugins_loaded', array( $this, 'check_db_update' ) );
    }

    public function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'uem_master_events';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_name varchar(255) NOT NULL,
            event_category varchar(50) NOT NULL,
            event_poster varchar(255) DEFAULT '',
            venue varchar(255) NOT NULL,
            event_date date NOT NULL,
            event_time time NOT NULL,
            imdb_rating varchar(10) DEFAULT '',
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    public function check_db_update() {
        if ( get_option( 'uem_db_version' ) !== '1.0.0' ) {
            $this->create_tables();
            update_option( 'uem_db_version', '1.0.0' );
        }
    }
}
