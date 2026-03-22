<?php
/**
 * UEM_CPT — Handles CPT and Taxonomy Registration
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class UEM_CPT {
    public function __construct() {
        add_action( 'init', array( $this, 'register_cpts' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post_event_listing', array( $this, 'save_meta_boxes' ) );
        add_filter( 'template_include', array( $this, 'template_redirect' ) );
    }

    public function register_cpts() {
        register_post_type( 'event_listing', array(
            'labels' => array(
                'name'               => 'Listings',
                'singular_name'      => 'Listing',
                'menu_name'          => 'UEM Listings',
                'add_new'            => 'Add New Listing',
                'add_new_item'       => 'Add New Listing',
                'edit_item'          => 'Edit Listing',
                'view_item'          => 'View Listing',
            ),
            'public'             => true,
            'has_archive'        => true,
            'show_in_rest'       => true,
            'supports'           => array( 'title', 'editor', 'thumbnail' ),
            'menu_icon'          => 'dashicons-tickets-alt',
            'rewrite'            => array( 'slug' => 'event-listing' ),
        ) );
    }

    public function register_taxonomies() {
        register_taxonomy( 'event_category', 'event_listing', array(
            'labels' => array(
                'name'              => 'Event Categories',
                'singular_name'     => 'Category',
                'search_items'      => 'Search Categories',
                'all_items'         => 'All Categories',
                'edit_item'         => 'Edit Category',
                'update_item'       => 'Update Category',
                'add_new_item'      => 'Add New Category',
                'menu_name'         => 'Categories',
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'event-cat' ),
        ) );

        if ( ! term_exists( 'movie', 'event_category' ) ) wp_insert_term( 'Movie', 'event_category', array( 'slug' => 'movie' ) );
        if ( ! term_exists( 'sports', 'event_category' ) ) wp_insert_term( 'Sports', 'event_category', array( 'slug' => 'sports' ) );
        if ( ! term_exists( 'concert', 'event_category' ) ) wp_insert_term( 'Concert', 'event_category', array( 'slug' => 'concert' ) );
    }

    public function register_meta_boxes() {
        add_meta_box( 'uem_listing_meta', 'Listing Details', array( $this, 'render_meta_box' ), 'event_listing', 'normal', 'high' );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'uem_save_listing', 'uem_listing_nonce' );
        $fields = array(
            'uem_master_id' => array( 'label' => 'Master Event ID', 'type' => 'number' ),
            'uem_price'     => array( 'label' => 'Price (₹)', 'type' => 'number' ),
            'uem_seats'     => array( 'label' => 'Seats / Stand Info', 'type' => 'text' ),
            'uem_seller_id' => array( 'label' => 'Seller ID', 'type' => 'number' ),
            'uem_status'    => array( 'label' => 'Status', 'type' => 'select', 'options' => array( 'active', 'sold', 'expired' ) ),
        );

        echo '<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 15px;">';
        foreach ( $fields as $id => $field ) {
            $val = get_post_meta( $post->ID, $id, true ) ?: '';
            echo '<div>';
            echo '<label style="display:block; font-weight:700; margin-bottom:5px;">' . esc_html( $field['label'] ) . '</label>';
            if ( $field['type'] === 'select' ) {
                echo '<select name="' . esc_attr( $id ) . '" style="width:100%;">';
                foreach ( $field['options'] as $opt ) {
                    echo '<option value="' . esc_attr( $opt ) . '" ' . selected( $val, $opt, false ) . '>' . esc_html( ucfirst($opt) ) . '</option>';
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
        if ( ! isset( $_POST['uem_listing_nonce'] ) || ! wp_verify_nonce( $_POST['uem_listing_nonce'], 'uem_save_listing' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        foreach ( $_POST as $key => $val ) {
            if ( strpos( $key, 'uem_' ) === 0 ) {
                update_post_meta( $post_id, $key, sanitize_text_field( $val ) );
            }
        }
    }

    public function template_redirect( $template ) {
        if ( is_singular( 'event_listing' ) ) {
            $new_template = UEM_DIR . 'templates/single-event_listing.php';
            if ( file_exists( $new_template ) ) return $new_template;
        }
        return $template;
    }
}
