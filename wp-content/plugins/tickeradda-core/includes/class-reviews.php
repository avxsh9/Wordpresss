<?php
/**
 * TA_Reviews — REST API for buyer-to-seller reviews
 * Namespace: tickeradda/v2
 *
 * Routes:
 *  POST /reviews             — Buyer: submit review for completed order
 *  GET  /reviews/seller/(?id) — Public: list reviews for seller
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class TA_Reviews {

    public function register_routes() {
        $ns = TA_REST_NS;

        register_rest_route( $ns, '/reviews', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'create_review' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( $ns, '/reviews/seller/(?P<id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_seller_reviews' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * POST /reviews
     * Body: { orderId, rating, comment }
     */
    public function create_review( WP_REST_Request $request ) {
        global $wpdb;
        $user_id  = get_current_user_id();
        $order_id = TA_Security::clean_int( $request->get_param( 'orderId' ) );
        $rating   = TA_Security::clean_int( $request->get_param( 'rating' ) );
        $comment  = TA_Security::clean( $request->get_param( 'comment' ) );

        if ( ! $order_id || ! $rating ) {
            return new WP_Error( 'missing_fields', 'Order ID and rating are required.', array( 'status' => 400 ) );
        }

        if ( $rating < 1 || $rating > 5 ) {
            return new WP_Error( 'invalid_rating', 'Rating must be between 1 and 5.', array( 'status' => 400 ) );
        }

        // Verify order exists, belongs to user, and is completed
        $o_table = TA_Database::orders_table();
        $t_table = TA_Database::tickets_table();
        $order = $wpdb->get_row( $wpdb->prepare(
            "SELECT o.*, t.seller_id FROM {$o_table} o 
             JOIN {$t_table} t ON o.ticket_id = t.id
             WHERE o.id = %d AND o.buyer_id = %d AND o.status = 'completed'",
            $order_id, $user_id
        ) );

        if ( ! $order ) {
            return new WP_Error( 'invalid_order', 'Order not found or not eligible for review.', array( 'status' => 404 ) );
        }

        // Check if review already exists
        $r_table = TA_Database::reviews_table();
        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$r_table} WHERE order_id = %d", $order_id ) );
        if ( $existing ) {
            return new WP_Error( 'duplicate_review', 'You have already reviewed this order.', array( 'status' => 400 ) );
        }

        // Insert review
        $inserted = $wpdb->insert( $r_table, array(
            'reviewer_id' => $user_id,
            'seller_id'   => $order->seller_id,
            'order_id'    => $order_id,
            'rating'      => $rating,
            'comment'     => $comment
        ), array( '%d', '%d', '%d', '%d', '%s' ) );

        if ( ! $inserted ) {
            return new WP_Error( 'db_error', 'Failed to save review.', array( 'status' => 500 ) );
        }

        // Update seller overall rating meta
        $this->update_seller_rating_meta( $order->seller_id );

        return rest_ensure_response( array(
            'msg' => 'Review submitted successfully!',
            'review_id' => $wpdb->insert_id
        ) );
    }

    /**
     * GET /reviews/seller/(?id)
     */
    public function get_seller_reviews( WP_REST_Request $request ) {
        global $wpdb;
        $seller_id = TA_Security::clean_int( $request->get_param( 'id' ) );
        $r_table   = TA_Database::reviews_table();

        $reviews = $wpdb->get_results( $wpdb->prepare(
            "SELECT r.*, u.display_name as reviewer_name 
             FROM {$r_table} r
             LEFT JOIN {$wpdb->users} u ON r.reviewer_id = u.ID
             WHERE r.seller_id = %d
             ORDER BY r.created_at DESC",
            $seller_id
        ) );

        return rest_ensure_response( array_map( function( $r ) {
            return array(
                'id'           => (int) $r->id,
                'reviewerName' => esc_html( $r->reviewer_name ?? 'Anonymous' ),
                'rating'       => (int) $r->rating,
                'comment'      => esc_html( $r->comment ?? '' ),
                'createdAt'    => $r->created_at
            );
        }, $reviews ) );
    }

    /**
     * Helper to update seller's average rating in usermeta
     */
    private function update_seller_rating_meta( $seller_id ) {
        global $wpdb;
        $r_table = TA_Database::reviews_table();
        
        $stats = $wpdb->get_row( $wpdb->prepare(
            "SELECT AVG(rating) as avg_rating, COUNT(*) as count 
             FROM {$r_table} WHERE seller_id = %d",
            $seller_id
        ) );

        if ( $stats ) {
            update_user_meta( $seller_id, 'ta_average_rating', round( (float) $stats->avg_rating, 1 ) );
            update_user_meta( $seller_id, 'ta_ratings_count', (int) $stats->count );
        }
    }
}
