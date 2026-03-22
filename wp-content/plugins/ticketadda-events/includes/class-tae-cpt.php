<?php
/**
 * TAE_CPT — Handles Custom Post Type and Taxonomy Registration
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TAE_CPT {
    public function __construct() {
        add_action( 'init', array( $this, 'register_cpts' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post_event_ticket', array( $this, 'save_meta_boxes' ) );
    }

    public function register_cpts() {
        register_post_type( 'event_ticket', array(
            'labels' => array(
                'name'               => 'Tickets',
                'singular_name'      => 'Ticket',
                'menu_name'          => 'Event Tickets',
                'add_new'            => 'Add New',
                'add_new_item'       => 'Add New Ticket',
                'edit_item'          => 'Edit Ticket',
                'view_item'          => 'View Ticket',
                'all_items'          => 'All Tickets',
                'search_items'       => 'Search Tickets',
                'not_found'          => 'No tickets found.',
                'not_found_in_trash' => 'No tickets in Trash.',
            ),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'tickets' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 6,
            'menu_icon'          => 'dashicons-tickets-alt',
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
            'show_in_rest'       => true,
        ) );
    }

    public function register_taxonomies() {
        register_taxonomy( 'event_type', 'event_ticket', array(
            'labels' => array(
                'name'              => 'Event Types',
                'singular_name'     => 'Event Type',
                'search_items'      => 'Search Event Types',
                'all_items'         => 'All Event Types',
                'edit_item'         => 'Edit Event Type',
                'update_item'       => 'Update Event Type',
                'add_new_item'      => 'Add New Event Type',
                'new_item_name'     => 'New Event Type Name',
                'menu_name'         => 'Event Types',
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'event-type' ),
        ) );

        // Seed default terms
        if ( ! term_exists( 'movie', 'event_type' ) ) wp_insert_term( 'Movie', 'event_type', array( 'slug' => 'movie' ) );
        if ( ! term_exists( 'sports', 'event_type' ) ) wp_insert_term( 'Sports', 'event_type', array( 'slug' => 'sports' ) );
        if ( ! term_exists( 'others', 'event_type' ) ) wp_insert_term( 'Others', 'event_type', array( 'slug' => 'others' ) );
    }

    public function register_meta_boxes() {
        add_meta_box(
            'tae_ticket_details',
            'Ticket Details',
            array( $this, 'render_meta_box' ),
            'event_ticket',
            'normal',
            'high'
        );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'tae_save_meta', 'tae_meta_nonce' );

        $fields = array(
            'tae_event_id'   => array( 'label' => 'Master Event ID (for Sports)', 'type' => 'number' ),
            'tae_venue'      => array( 'label' => 'Venue (for Movies)', 'type' => 'text' ),
            'tae_date'       => array( 'label' => 'Date', 'type' => 'date' ),
            'tae_time'       => array( 'label' => 'Time', 'type' => 'time' ),
            'tae_seat_info'  => array( 'label' => 'Seat / Stand Info', 'type' => 'text' ),
            'tae_price'      => array( 'label' => 'Price (₹)', 'type' => 'number' ),
            'tae_quantity'   => array( 'label' => 'Quantity', 'type' => 'number', 'default' => 1 ),
            'tae_seller_id'  => array( 'label' => 'Seller ID', 'type' => 'number' ),
            'tae_status'     => array( 'label' => 'Status', 'type' => 'select', 'options' => array( 'Active', 'Sold', 'Expired' ) ),
        );

        echo '<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 15px;">';
        foreach ( $fields as $id => $field ) {
            $val = get_post_meta( $post->ID, $id, true ) ?: ( $field['default'] ?? '' );
            echo '<div>';
            echo '<label style="display:block; font-weight:bold; margin-bottom:5px;">' . esc_html( $field['label'] ) . '</label>';
            if ( $field['type'] === 'select' ) {
                echo '<select name="' . esc_attr( $id ) . '" style="width:100%;">';
                foreach ( $field['options'] as $opt ) {
                    echo '<option value="' . esc_attr( $opt ) . '" ' . selected( $val, $opt, false ) . '>' . esc_html( $opt ) . '</option>';
                }
                echo '</select>';
            } else {
                echo '<input type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $id ) . '" value="' . esc_attr( $val ) . '" style="width:100%;">';
            }
            echo '</div>';
        }
        echo '</div>';
    }

    public function save_meta_boxes( $post_id ) {
        if ( ! isset( $_POST['tae_meta_nonce'] ) || ! wp_verify_nonce( $_POST['tae_meta_nonce'], 'tae_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        foreach ( $_POST as $key => $val ) {
            if ( strpos( $key, 'tae_' ) === 0 ) {
                update_post_meta( $post_id, $key, sanitize_text_field( $val ) );
            }
        }
    }
}
