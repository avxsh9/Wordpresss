<?php
/**
 * TA_Orders — REST API for orders and Razorpay payment
 *
 * Routes:
 *  POST  /payment/create-order   — Buyer: create Razorpay order + internal pending order
 *  POST  /payment/verify         — Buyer: verify Razorpay signature, complete order
 *  GET   /orders/my-orders       — Buyer: own orders
 *  GET   /orders/(?P<id>\d+)/invoice  — Buyer: download PDF invoice
 *  GET   /admin/orders           — Admin: all orders
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class TA_Orders {

    public function register_routes() {
        $ns = TA_REST_NS;

        register_rest_route( $ns, '/payment/create-order', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'create_razorpay_order' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( $ns, '/payment/verify', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'verify_payment' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( $ns, '/orders/my-orders', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_my_orders' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( $ns, '/orders/(?P<id>\d+)/invoice', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'download_invoice' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( $ns, '/payment/test-config', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'test_payment_config' ),
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ) );

        register_rest_route( $ns, '/payment/test-upload-dir', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'test_upload_dir' ),
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ) );

        register_rest_route( $ns, '/payment/test-email', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'test_email' ),
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ) );

        register_rest_route( $ns, '/admin/orders', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_all_orders' ),
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ) );
        
        register_rest_route( $ns, '/admin/orders/(?P<id>\d+)/send-ticket', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'send_ticket' ),
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ) );

        register_rest_route( $ns, '/admin/orders/(?P<id>\d+)/mark-paid', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'mark_order_paid' ),
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ) );
    }

    // ── POST /payment/create-order ────────────────────────────────────────────
    public function create_razorpay_order( WP_REST_Request $request ) {
        global $wpdb;
        $user_id   = get_current_user_id();
        $ticket_id = TA_Security::clean_int( $request->get_param( 'ticketId' ) );
        $qty       = max( 1, TA_Security::clean_int( $request->get_param( 'quantity' ) ) );

        $tickets_table = TA_Database::tickets_table();
        $ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tickets_table} WHERE id = %d", $ticket_id ) );

        if ( ! $ticket ) {
            return new WP_Error( 'not_found', 'Ticket not found.', array( 'status' => 404 ) );
        }

        if ( ! in_array( $ticket->status, array( 'approved', 'available' ), true ) ) {
            return new WP_Error( 'not_available', 'This ticket is not available for purchase.', array( 'status' => 400 ) );
        }

        if ( (int) $ticket->seller_id === $user_id ) {
            return new WP_Error( 'self_purchase', 'You cannot buy your own ticket.', array( 'status' => 403 ) );
        }

        if ( $qty > (int) $ticket->quantity ) {
            return new WP_Error( 'qty_exceeded', "Only {$ticket->quantity} tickets available.", array( 'status' => 400 ) );
        }

        $subtotal     = (float) $ticket->price * $qty;
        $platform_fee = ceil( $subtotal * 0.05 );  // 5% platform fee
        $total        = $subtotal + $platform_fee;

        // Create Razorpay order
        error_log( "[TickerAdda] Creating Razorpay order for total: $total" );
        $rzp_order = TA_Razorpay::create_order( $total, 'ta_' . time() );
        if ( is_wp_error( $rzp_order ) ) {
            error_log( "[TickerAdda] Razorpay error: " . $rzp_order->get_error_message() );
            return $rzp_order;
        }
        error_log( "[TickerAdda] Razorpay order created: " . $rzp_order['id'] );

        // Create internal pending order
        $orders_table = TA_Database::orders_table();
        $wpdb->insert( $orders_table, array(
            'buyer_id'          => $user_id,
            'ticket_id'         => $ticket_id,
            'quantity'          => $qty,
            'subtotal'          => $subtotal,
            'platform_fee'      => $platform_fee,
            'total_amount'      => $total,
            'status'            => 'pending',
            'razorpay_order_id' => $rzp_order['id'],
        ), array( '%d', '%d', '%d', '%f', '%f', '%f', '%s', '%s' ) );

        $internal_order_id = $wpdb->insert_id;

        return rest_ensure_response( array(
            'id'           => $rzp_order['id'],
            'orderId'      => $internal_order_id,
            'amount'       => $rzp_order['amount'],   // paise
            'currency'     => $rzp_order['currency'],
            'key_id'       => get_option( 'ta_razorpay_key_id', '' ),
            'subtotal'     => $subtotal,
            'platform_fee' => $platform_fee,
            'total'        => $total,
        ) );
    }

    // ── POST /payment/verify ───────────────────────────────────────────────────
    public function verify_payment( WP_REST_Request $request ) {
        global $wpdb;
        $user_id           = get_current_user_id();
        $rzp_order_id      = TA_Security::clean( $request->get_param( 'razorpay_order_id' ) );
        $rzp_payment_id    = TA_Security::clean( $request->get_param( 'razorpay_payment_id' ) );
        $rzp_signature     = $request->get_param( 'razorpay_signature' );

        // Verified signature
        if ( ! TA_Razorpay::verify_signature( $rzp_order_id, $rzp_payment_id, $rzp_signature ) ) {
            return new WP_Error( 'invalid_signature', 'Payment verification failed. Invalid signature.', array( 'status' => 400 ) );
        }

        $orders_table  = TA_Database::orders_table();
        $tickets_table = TA_Database::tickets_table();

        $order = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$orders_table} WHERE razorpay_order_id = %s AND buyer_id = %d",
            $rzp_order_id, $user_id
        ) );

        if ( ! $order ) {
            return new WP_Error( 'order_not_found', 'Order not found.', array( 'status' => 404 ) );
        }

        // Update order
        $wpdb->update( $orders_table, array(
            'status'                => 'completed',
            'razorpay_payment_id'   => $rzp_payment_id,
            'razorpay_signature'    => sanitize_text_field( $rzp_signature ),
        ), array( 'id' => $order->id ), array( '%s', '%s', '%s' ), array( '%d' ) );

        // Mark ticket as sold
        $wpdb->update( $tickets_table, array( 'status' => 'sold' ), array( 'id' => $order->ticket_id ), array( '%s' ), array( '%d' ) );

        // Get ticket and buyer details for email
        $ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tickets_table} WHERE id = %d", $order->ticket_id ) );
        $buyer  = get_user_by( 'id', $user_id );
        $seller = get_user_by( 'id', $ticket->seller_id );
        $order  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$orders_table} WHERE id = %d", $order->id ) );

        // Send emails (async-safe: wrapped in try/catch so payment is not blocked)
        try {
            // Build PDF invoice
            error_log( "[TickerAdda] Generating invoice for order: " . $order->id );
            $pdf        = TA_PDF_Invoice::generate( $order, $buyer, $ticket );
            $order_id_s = str_pad( $order->id, 6, '0', STR_PAD_LEFT );

            $invoice_dir = TA_UPLOAD_DIR . 'invoices/';
            if ( ! file_exists( $invoice_dir ) ) {
                error_log( "[TickerAdda] Creating invoice directory: $invoice_dir" );
                wp_mkdir_p( $invoice_dir );
            }
            $attach_path = $invoice_dir . "invoice-{$order_id_s}.html";
            
            if ( file_put_contents( $attach_path, $pdf ) === false ) {
                error_log( "[TickerAdda] Failed to save invoice at: $attach_path" );
            } else {
                error_log( "[TickerAdda] Invoice saved at: $attach_path" );
            }

            // ONLY send invoice in first email, NO ticket
            $attachments = array( $attach_path );

            // Email to buyer
            error_log( "[TickerAdda] Sending confirmation to: " . $buyer->user_email );
            $buyer_sent = TA_Email::send_order_confirmation(
                $buyer->user_email,
                esc_html( $buyer->display_name ),
                $order, $ticket,
                $attachments
            );
            error_log( "[TickerAdda] Buyer confirmation email sent: " . ($buyer_sent ? 'YES' : 'NO') );

            // Email to seller
            if ( $seller ) {
                TA_Email::send_ticket_sold(
                    $seller->user_email,
                    esc_html( $seller->display_name ),
                    esc_html( $buyer->display_name ),
                    esc_html( $buyer->user_email ),
                    esc_html( get_user_meta( $buyer->ID, 'ta_phone', true ) ),
                    $ticket, $order
                );
            }
        } catch ( Exception $e ) {
            error_log( 'TickerAdda email error: ' . $e->getMessage() );
        }

        return rest_ensure_response( array(
            'msg'     => 'Payment verified successfully!',
            'status'  => 'success',
            'orderId' => (int) $order->id,
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
             um2.meta_value as seller_avg_rating
             FROM {$orders_table} o
             LEFT JOIN {$tickets_table} t ON o.ticket_id = t.id
             LEFT JOIN {$wpdb->users} u ON t.seller_id = u.ID
             LEFT JOIN {$wpdb->users} bu ON o.buyer_id = bu.ID
             LEFT JOIN {$wpdb->usermeta} um ON t.seller_id = um.user_id AND um.meta_key = 'ta_phone'
             LEFT JOIN {$wpdb->usermeta} um2 ON t.seller_id = um2.user_id AND um2.meta_key = 'ta_average_rating'
             WHERE o.buyer_id = %d AND o.status = 'completed'
             ORDER BY o.created_at DESC",
            $user_id
        ) );

        $orders = array_map( array( $this, 'format_order' ), $rows );

        // Hide file_url if ticket not yet sent by admin
        foreach ( $orders as &$ord ) {
            if ( empty( $ord['isTicketSent'] ) ) {
                $ord['ticket']['fileUrl'] = null;
            }
        }

        return rest_ensure_response( $orders );
    }

    // ── POST /admin/orders/(?id)/send-ticket ─────────────────────────────────
    public function send_ticket( WP_REST_Request $request ) {
        global $wpdb;
        $order_id = TA_Security::clean_int( $request->get_param( 'id' ) );
        $o_table  = TA_Database::orders_table();
        $t_table  = TA_Database::tickets_table();

        $order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$o_table} WHERE id = %d", $order_id ) );
        if ( ! $order ) return new WP_Error( 'not_found', 'Order not found.', array( 'status' => 404 ) );
        
        if ( (int) $order->is_ticket_sent === 1 ) {
            return rest_ensure_response( array( 'msg' => 'Ticket already sent.' ) );
        }

        $ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t_table} WHERE id = %d", $order->ticket_id ) );
        $buyer  = get_user_by( 'id', $order->buyer_id );

        if ( ! $ticket || ! $buyer ) {
            return new WP_Error( 'not_found', 'Ticket or Buyer not found.', array( 'status' => 404 ) );
        }

        // Attach ticket file
        $attachments = array();
        $ticket_path = TA_UPLOAD_DIR . $ticket->file_url;
        if ( ! empty( $ticket->file_url ) && file_exists( $ticket_path ) ) {
            $attachments[] = $ticket_path;
        }

        // Add invoice too just in case
        $order_id_s  = str_pad( $order->id, 6, '0', STR_PAD_LEFT );
        $invoice_path = TA_UPLOAD_DIR . "invoices/invoice-{$order_id_s}.html";
        if ( file_exists( $invoice_path ) ) {
            $attachments[] = $invoice_path;
        }

        $sent = TA_Email::send_ticket_delivery(
            $buyer->user_email,
            esc_html( $buyer->display_name ),
            $order,
            $ticket,
            $attachments
        );

        if ( $sent ) {
            $wpdb->update( $o_table, array( 'is_ticket_sent' => 1 ), array( 'id' => $order_id ) );
            return rest_ensure_response( array( 'msg' => 'Ticket sent to buyer successfully!' ) );
        } else {
            return new WP_Error( 'email_fail', 'Failed to send email. Check SMTP settings.', array( 'status' => 500 ) );
        }
    }

    // ── GET /orders/(?P<id>\d+)/invoice ──────────────────────────────────────
    public function download_invoice( WP_REST_Request $request ) {
        global $wpdb;
        $user_id      = get_current_user_id();
        $order_id     = TA_Security::clean_int( $request->get_param( 'id' ) );
        $orders_table = TA_Database::orders_table();

        $order = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$orders_table} WHERE id = %d AND buyer_id = %d",
            $order_id, $user_id
        ) );

        if ( ! $order ) {
            return new WP_Error( 'forbidden', 'Order not found or not authorized.', array( 'status' => 403 ) );
        }

        $tickets_table = TA_Database::tickets_table();
        $ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tickets_table} WHERE id = %d", $order->ticket_id ) );
        $buyer  = get_user_by( 'id', $user_id );
        $seller = $ticket ? get_user_by( 'id', $ticket->seller_id ) : null;

        $html = TA_PDF_Invoice::generate( $order, $buyer, $ticket, $seller );

        // Try to serve as PDF; otherwise as HTML
        $id_str = str_pad( $order_id, 6, '0', STR_PAD_LEFT );
        header( 'Content-Type: text/html; charset=UTF-8' );
        header( "Content-Disposition: attachment; filename=\"Invoice-{$id_str}.html\"" );
        echo $html;
        exit;
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

    public function test_payment_config( WP_REST_Request $request ) {
        $key_id     = get_option( 'ta_razorpay_key_id', '' );
        $key_secret = get_option( 'ta_razorpay_key_secret', '' );

        return rest_ensure_response( array(
            'key_id' => $key_id ? substr($key_id, 0, 8) . '...' : 'NOT_SET',
            'secret_length' => strlen($key_secret),
            'secret_start'  => $key_secret ? substr($key_secret, 0, 3) . '...' : 'NOT_SET',
            'is_test'       => strpos($key_id, 'rzp_test_') === 0,
            'is_live'       => strpos($key_id, 'rzp_live_') === 0,
        ) );
    }

    public function test_upload_dir( WP_REST_Request $request ) {
        $upload_dir = TA_UPLOAD_DIR;
        $invoice_dir = $upload_dir . 'invoices/';
        
        $upload_exists = file_exists( $upload_dir );
        $invoice_exists = file_exists( $invoice_dir );
        
        $is_writable = is_writable( dirname($upload_dir) );
        
        $test_file = $upload_dir . 'test_write.txt';
        $write_success = false;
        if ( $upload_exists ) {
            $write_success = ( file_put_contents( $test_file, 'test' ) !== false );
            if ( $write_success ) unlink( $test_file );
        }
        
        return rest_ensure_response( array(
            'TA_UPLOAD_DIR'  => $upload_dir,
            'exists'         => $upload_exists,
            'invoice_exists' => $invoice_exists,
            'parent_writable' => $is_writable,
            'write_test'     => $write_success,
            'WP_CONTENT_DIR' => defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : 'UNDEFINED',
            'ABSPATH'        => defined('ABSPATH') ? ABSPATH : 'UNDEFINED'
        ) );
    }

    public function test_email( WP_REST_Request $request ) {
        $to      = get_option( 'admin_email' );
        $subject = "TickerAdda Email Test - " . date('Y-m-d H:i:s');
        $message = "<h1>Email Working!</h1><p>If you see this, your WordPress email (wp_mail) is functioning properly.</p>";
        
        $sent = TA_Email::send( $to, $subject, $message );
        
        return rest_ensure_response( array(
            'sent'      => $sent,
            'to'        => $to,
            'subject'   => $subject,
            'hint'      => $sent ? 'Email was sent successfully.' : 'Email failed. Please configure an SMTP plugin like "WP Mail SMTP".'
        ) );
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
            'sellerAvgRating' => (float) ( $o->seller_avg_rating ?? 0 ),
            'quantity'        => (int) ( $o->quantity ?? 1 ),
            'subtotal'        => (float) ( $o->subtotal ?? 0 ),
            'platformFee'     => (float) ( $o->platform_fee ?? 0 ),
            'totalAmount'     => (float) $o->total_amount,
            'status'          => $o->status,
            'isTicketSent'    => (int) ( $o->is_ticket_sent ?? 0 ),
            'razorpayOrderId' => $o->razorpay_order_id,
            'razorpayPayId'   => $o->razorpay_payment_id,
            'invoiceUrl'      => rest_url( TA_REST_NS . '/orders/' . $o->id . '/invoice' ),
            'createdAt'       => $o->created_at,
            'ticket' => array(
                '_id' => (int) $o->ticket_id,
                'id' => (int) $o->ticket_id,
                'event' => esc_html( $o->event_name ?? '' ),
                'fileUrl' => $o->file_url ? $o->file_url : null
            )
        );
    }

    public function mark_order_paid( WP_REST_Request $request ) {
        global $wpdb;
        $order_id = TA_Security::clean_int( $request->get_param( 'id' ) );
        $o_table  = TA_Database::orders_table();
        $t_table  = TA_Database::tickets_table();

        $order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$o_table} WHERE id = %d", $order_id ) );
        if ( ! $order ) return new WP_Error( 'not_found', 'Order not found.', array( 'status' => 404 ) );

        $wpdb->update( $o_table, array( 'status' => 'completed' ), array( 'id' => $order_id ) );
        $wpdb->update( $t_table, array( 'status' => 'sold' ),      array( 'id' => $order->ticket_id ) );

        return rest_ensure_response( array( 'success' => true, 'msg' => 'Order marked as paid.' ) );
    }
}
