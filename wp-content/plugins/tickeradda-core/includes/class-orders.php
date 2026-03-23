<?php
/**
 * TA_Orders — REST API for manual trust-based marketplace orders
 *
 * Routes:
 *  POST  /orders/claim            — Buyer: record interest, notify seller
 *  POST  /orders/(?P<id>\d+)/confirm-sale — Seller: confirm sale to buyer
 *  GET   /orders/my-orders        — Buyer: own orders
 *  GET   /admin/orders            — Admin: all orders
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class TA_Orders {

    public function register_routes() {
        $ns = TA_REST_NS;

        register_rest_route( $ns, '/orders/claim', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'claim_ticket' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( $ns, '/orders/(?P<id>\d+)/confirm-sale', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'confirm_sale' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( $ns, '/orders/my-orders', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_my_orders' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( $ns, '/admin/orders', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_all_orders' ),
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ) );
    }

    // ── POST /orders/claim ────────────────────────────────────────────────────
    public function claim_ticket( WP_REST_Request $request ) {
        global $wpdb;
        $user_id   = get_current_user_id();
        $ticket_id = TA_Security::clean_int( $request->get_param( 'ticketId' ) );
        $qty       = max( 1, TA_Security::clean_int( $request->get_param( 'quantity' ) ) );

        $tickets_table = TA_Database::tickets_table();
        $ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tickets_table} WHERE id = %d", $ticket_id ) );

        if ( ! $ticket ) {
            return new WP_Error( 'not_found', 'Ticket not found.', array( 'status' => 404 ) );
        }

        if ( ! in_array( $ticket->status, array( 'approved', 'available', 'active' ), true ) ) {
            return new WP_Error( 'not_available', 'This ticket is not available for purchase.', array( 'status' => 400 ) );
        }

        if ( (int) $ticket->seller_id === $user_id ) {
            return new WP_Error( 'self_purchase', 'You cannot buy your own ticket.', array( 'status' => 403 ) );
        }

        if ( $qty > (int) $ticket->quantity ) {
            return new WP_Error( 'qty_exceeded', "Only {$ticket->quantity} tickets available.", array( 'status' => 400 ) );
        }

        // Check if an active pending/completed order already exists for this buyer to prevent duplicate claims
        $orders_table = TA_Database::orders_table();
        $existing = $wpdb->get_row( $wpdb->prepare(
            "SELECT id FROM {$orders_table} WHERE ticket_id = %d AND buyer_id = %d AND status IN ('pending', 'completed')",
            $ticket_id, $user_id
        ) );
        if ( $existing ) {
            return new WP_Error( 'already_claimed', 'You have already placed a request for this ticket.', array( 'status' => 400 ) );
        }

        $subtotal = (float) $ticket->price * $qty;
        
        $wpdb->insert( $orders_table, array(
            'buyer_id'          => $user_id,
            'ticket_id'         => $ticket_id,
            'quantity'          => $qty,
            'subtotal'          => $subtotal,
            'platform_fee'      => 0,
            'total_amount'      => $subtotal,
            'status'            => 'pending',
            'razorpay_order_id' => '', // Not needed anymore
        ), array( '%d', '%d', '%d', '%f', '%f', '%f', '%s', '%s' ) );

        $order_id = $wpdb->insert_id;

        // Send Email to Seller
        $buyer_user = get_user_by( 'id', $user_id );
        $buyer_phone = get_user_meta( $user_id, 'ta_phone', true );
        $seller_user = get_user_by( 'id', $ticket->seller_id );

        if ( $seller_user ) {
            TA_Email::send_buyer_interested(
                $seller_user->user_email,
                esc_html( $seller_user->display_name ),
                esc_html( $buyer_user->display_name ),
                esc_html( $buyer_user->user_email ),
                esc_html( $buyer_phone ),
                $ticket,
                $order_id
            );
        }

        return rest_ensure_response( array(
            'id'       => $order_id,
            'status'   => 'success',
            'message'  => 'Purchase request submitted successfully. The seller has been notified.'
        ) );
    }

    // ── POST /orders/(?P<id>\d+)/confirm-sale ─────────────────────────────────
    public function confirm_sale( WP_REST_Request $request ) {
        global $wpdb;
        $user_id   = get_current_user_id();
        $order_id  = TA_Security::clean_int( $request->get_param( 'id' ) );
        
        $orders_table  = TA_Database::orders_table();
        $tickets_table = TA_Database::tickets_table();

        // Must join to verify the current user is the seller
        $order = $wpdb->get_row( $wpdb->prepare(
            "SELECT o.*, t.seller_id FROM {$orders_table} o JOIN {$tickets_table} t ON o.ticket_id = t.id WHERE o.id = %d",
            $order_id
        ) );

        if ( ! $order ) {
            return new WP_Error( 'not_found', 'Order not found.', array( 'status' => 404 ) );
        }

        if ( (int) $order->seller_id !== $user_id && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'forbidden', 'You do not have permission to confirm this order.', array( 'status' => 403 ) );
        }

        $wpdb->update( $orders_table, array( 'status' => 'completed' ), array( 'id' => $order_id ), array( '%s' ), array( '%d' ) );
        
        // Mark ticket as sold
        $wpdb->update( $tickets_table, array( 'status' => 'sold' ), array( 'id' => $order->ticket_id ), array( '%s' ), array( '%d' ) );

        // Send email to buyer
        $ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tickets_table} WHERE id = %d", $order->ticket_id ) );
        $buyer  = get_user_by( 'id', $order->buyer_id );
        $seller = get_user_by( 'id', $order->seller_id );
        $seller_phone = get_user_meta( $order->seller_id, 'ta_phone', true );

        if ( $buyer && $seller ) {
            TA_Email::send_sale_confirmed(
                $buyer->user_email,
                esc_html( $buyer->display_name ),
                esc_html( $seller->display_name ),
                esc_html( $seller->user_email ),
                esc_html( $seller_phone ),
                $ticket,
                $order
            );
        }

        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Sale confirmed successfully.'
        ) );
    }

    // ── GET /orders/my-orders ─────────────────────────────────────────────────
    public function get_my_orders( WP_REST_Request $request ) {
        global $wpdb;
        $user_id       = get_current_user_id();
        $orders_table  = TA_Database::orders_table();
        $tickets_table = TA_Database::tickets_table();

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT o.*, t.event_name, t.event_date, t.event_time, t.price as ticket_price,
             t.seller_id, t.venue, t.section, t.seat_number as seat, t.file_url,
             u.display_name as seller_name, u.user_email as seller_email,
             bu.display_name as buyer_name, bu.user_email as buyer_email,
             um.meta_value as seller_phone,
             um2.meta_value as seller_avg_rating,
             um3.meta_value as seller_kyc_status
             FROM {$orders_table} o
             LEFT JOIN {$tickets_table} t ON o.ticket_id = t.id
             LEFT JOIN {$wpdb->users} u ON t.seller_id = u.ID
             LEFT JOIN {$wpdb->users} bu ON o.buyer_id = bu.ID
             LEFT JOIN {$wpdb->usermeta} um ON t.seller_id = um.user_id AND um.meta_key = 'ta_phone'
             LEFT JOIN {$wpdb->usermeta} um2 ON t.seller_id = um2.user_id AND um2.meta_key = 'ta_average_rating'
             LEFT JOIN {$wpdb->usermeta} um3 ON t.seller_id = um3.user_id AND um3.meta_key = 'ta_kyc_status'
             WHERE o.buyer_id = %d 
             ORDER BY o.created_at DESC",
            $user_id
        ) );

        $orders = array_map( array( $this, 'format_order' ), $rows );

        foreach ( $orders as &$ord ) {
            if ( $ord['status'] !== 'completed' ) {
                $ord['ticket']['fileUrl'] = null;
            }
        }

        return rest_ensure_response( $orders );
    }

    // ── GET /admin/orders ─────────────────────────────────────────────────────
    public function get_all_orders( WP_REST_Request $request ) {
        global $wpdb;
        $orders_table  = TA_Database::orders_table();
        $tickets_table = TA_Database::tickets_table();

        $rows = $wpdb->get_results(
            "SELECT o.*, t.event_name, t.price as ticket_price,
             bu.display_name as buyer_name, bu.user_email as buyer_email,
             su.display_name as seller_name
             FROM {$orders_table} o
             LEFT JOIN {$tickets_table} t ON o.ticket_id = t.id
             LEFT JOIN {$wpdb->users} bu ON o.buyer_id = bu.ID
             LEFT JOIN {$wpdb->users} su ON t.seller_id = su.ID
             ORDER BY o.created_at DESC LIMIT 500"
        );

        return rest_ensure_response( array_map( array( $this, 'format_order' ), $rows ) );
    }

    // ── Format helper ─────────────────────────────────────────────────────────
    private function format_order( $o ) {
        return array(
            'id'              => (int) $o->id,
            '_id'             => (int) $o->id,
            'buyerId'         => (int) $o->buyer_id,
            'buyerName'       => esc_html( $o->buyer_name ?? '' ),
            'buyerEmail'      => esc_html( $o->buyer_email ?? '' ),
            'ticketId'        => (int) $o->ticket_id,
            'eventName'       => esc_html( $o->event_name ?? '' ),
            'venue'           => esc_html( $o->venue ?? '' ),
            'section'         => esc_html( $o->section ?? '' ),
            'seat'            => esc_html( $o->seat ?? '' ),
            'eventDate'       => $o->event_date ?? '',
            'eventTime'       => $o->event_time ?? '',
            'sellerName'      => esc_html( $o->seller_name ?? '' ),
            'sellerEmail'     => esc_html( $o->seller_email ?? '' ),
            'sellerPhone'     => esc_html( $o->seller_phone ?? '' ),
            'sellerKycStatus' => esc_html( $o->seller_kyc_status ?? '' ),
            'sellerAvgRating' => (float) ( $o->seller_avg_rating ?? 0 ),
            'quantity'        => (int) ( $o->quantity ?? 1 ),
            'subtotal'        => (float) ( $o->subtotal ?? 0 ),
            'platformFee'     => (float) ( $o->platform_fee ?? 0 ),
            'totalAmount'     => (float) $o->total_amount,
            'status'          => $o->status,
            'createdAt'       => $o->created_at,
            'ticket' => array(
                '_id'     => (int) $o->ticket_id,
                'id'      => (int) $o->ticket_id,
                'event'   => esc_html( $o->event_name ?? '' ),
                'fileUrl' => $o->file_url ? $o->file_url : null
            )
        );
    }
}
