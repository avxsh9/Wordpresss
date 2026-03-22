<?php
/**
 * MTM_CPT — Handles Custom Post Type and Meta Boxes
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MTM_CPT {
    public function __construct() {
        add_action( 'init', array( $this, 'register_cpts' ) );
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post_movie_tickets', array( $this, 'save_meta_boxes' ) );
    }

    public function register_cpts() {
        register_post_type( 'movie_tickets', array(
            'labels' => array(
                'name'               => 'Movie Tickets',
                'singular_name'      => 'Movie Ticket',
                'menu_name'          => 'Movie Tickets',
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
            'rewrite'            => array( 'slug' => 'movie-tickets' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 6,
            'menu_icon'          => 'dashicons-tickets-alt',
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
            'show_in_rest'       => true,
        ) );
    }

    public function register_meta_boxes() {
        add_meta_box(
            'mtm_ticket_details',
            'Ticket Details',
            array( $this, 'render_meta_box' ),
            'movie_tickets',
            'normal',
            'high'
        );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'mtm_save_meta', 'mtm_meta_nonce' );

        $fields = array(
            'mtm_event_type' => array( 'label' => 'Event Type', 'type' => 'text', 'default' => 'Movie' ),
            'mtm_location'   => array( 'label' => 'Location (City, Venue)', 'type' => 'text' ),
            'mtm_datetime'   => array( 'label' => 'Date & Time', 'type' => 'datetime-local' ),
            'mtm_seat_info'  => array( 'label' => 'Seat Info', 'type' => 'text' ),
            'mtm_price'      => array( 'label' => 'Price (₹)', 'type' => 'number' ),
            'mtm_quantity'   => array( 'label' => 'Quantity', 'type' => 'number', 'default' => 1 ),
            'mtm_status'     => array( 'label' => 'Status', 'type' => 'select', 'options' => array( 'Available', 'Sold', 'Expired' ) ),
            'mtm_seller_id'  => array( 'label' => 'Seller ID', 'type' => 'number' ),
        );

        echo '<div class="mtm-meta-container" style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 15px;">';
        foreach ( $fields as $id => $field ) {
            $val = get_post_meta( $post->ID, $id, true ) ?: ( $field['default'] ?? '' );
            echo '<div class="mtm-field-group">';
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
        if ( ! isset( $_POST['mtm_meta_nonce'] ) || ! wp_verify_nonce( $_POST['mtm_meta_nonce'], 'mtm_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        $fields = array( 'mtm_event_type', 'mtm_location', 'mtm_datetime', 'mtm_seat_info', 'mtm_price', 'mtm_quantity', 'mtm_status', 'mtm_seller_id' );
        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }
    }
}
