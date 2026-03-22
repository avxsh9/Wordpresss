<?php
/**
 * STM_CPT — Handles Custom Post Type and Meta Boxes for Sports
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class STM_CPT {
    public function __construct() {
        add_action( 'init', array( $this, 'register_cpts' ) );
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post_ipl_tickets', array( $this, 'save_meta_boxes' ) );
    }

    public function register_cpts() {
        register_post_type( 'ipl_tickets', array(
            'labels' => array(
                'name'               => 'Sports Tickets',
                'singular_name'      => 'Sports Ticket',
                'menu_name'          => 'Sports Tickets',
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
            'rewrite'            => array( 'slug' => 'sports-tickets' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 7,
            'menu_icon'          => 'dashicons-awards',
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
            'show_in_rest'       => true,
        ) );
    }

    public function register_meta_boxes() {
        add_meta_box(
            'stm_ticket_details',
            'Match Details',
            array( $this, 'render_meta_box' ),
            'ipl_tickets',
            'normal',
            'high'
        );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'stm_save_meta', 'stm_meta_nonce' );

        $fields = array(
            'stm_event_type' => array( 'label' => 'Event Type', 'type' => 'text', 'default' => 'IPL / Sports' ),
            'stm_teams'      => array( 'label' => 'Teams (e.g. MI vs CSK)', 'type' => 'text' ),
            'stm_location'   => array( 'label' => 'Stadium / Venue', 'type' => 'text' ),
            'stm_datetime'   => array( 'label' => 'Match Date & Time', 'type' => 'datetime-local' ),
            'stm_seat_info'  => array( 'label' => 'Stand / Seat Info', 'type' => 'text' ),
            'stm_price'      => array( 'label' => 'Price (₹)', 'type' => 'number' ),
            'stm_quantity'   => array( 'label' => 'Quantity', 'type' => 'number', 'default' => 1 ),
            'stm_status'     => array( 'label' => 'Status', 'type' => 'select', 'options' => array( 'Available', 'Sold', 'Expired' ) ),
            'stm_seller_id'  => array( 'label' => 'Seller ID', 'type' => 'number' ),
        );

        echo '<div class="stm-meta-container" style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 15px;">';
        foreach ( $fields as $id => $field ) {
            $val = get_post_meta( $post->ID, $id, true ) ?: ( $field['default'] ?? '' );
            echo '<div class="stm-field-group">';
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
        if ( ! isset( $_POST['stm_meta_nonce'] ) || ! wp_verify_nonce( $_POST['stm_meta_nonce'], 'stm_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        $fields = array( 'stm_event_type', 'stm_teams', 'stm_location', 'stm_datetime', 'stm_seat_info', 'stm_price', 'stm_quantity', 'stm_status', 'stm_seller_id' );
        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }
    }
}
