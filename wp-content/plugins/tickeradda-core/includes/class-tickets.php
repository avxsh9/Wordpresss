<?php
/**
 * TA_Tickets — REST API for ticket CRUD
 * Namespace: tickeradda/v1
 *
 * Routes:
 *  POST   /tickets                — Seller: create ticket listing
 *  GET    /tickets/approved       — Public: browse approved tickets
 *  GET    /tickets/(?P<id>\d+)    — Public: single ticket
 *  GET    /tickets/my-tickets     — Seller: own listings
 *  GET    /tickets/pending        — Admin: pending approval queue
 *  GET    /tickets/history        — Admin: approved/rejected history
 *  PUT    /tickets/(?P<id>\d+)/status  — Admin: approve/reject
 *  GET    /tickets/secure-image/(?P<id>\d+) — Auth: serve ticket image
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class TA_Tickets {

    public function register_routes() {
        $ns = TA_REST_NS;

        register_rest_route( $ns, '/tickets', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'create_ticket' ),
            'permission_callback' => array( $this, 'is_seller_or_admin' ),
        ) );

        register_rest_route( $ns, '/tickets/approved', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_approved_tickets' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $ns, '/tickets/recent', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_recent_tickets' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $ns, '/tickets/my-tickets', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_my_tickets' ),
            'permission_callback' => array( $this, 'is_seller_or_admin' ),
        ) );

        register_rest_route( $ns, '/tickets/pending', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_pending_tickets' ),
            'permission_callback' => array( $this, 'is_admin' ),
        ) );

        register_rest_route( $ns, '/tickets/history', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_ticket_history' ),
            'permission_callback' => array( $this, 'is_admin' ),
        ) );

        register_rest_route( $ns, '/tickets/(?P<id>\d+)/status', array(
            'methods'             => 'PUT',
            'callback'            => array( $this, 'update_ticket_status' ),
            'permission_callback' => array( $this, 'is_admin' ),
        ) );

        register_rest_route( $ns, '/tickets/secure-image/(?P<id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'serve_ticket_image' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( $ns, '/tickets/(?P<id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_ticket' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $ns, '/events/(?P<id>\d+)/tickets', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_event_tickets' ),
            'permission_callback' => '__return_true',
        ) );
    }

    // ── Permission Callbacks ──────────────────────────────────────────────────
    public function is_admin() {
        return current_user_can( 'manage_options' );
    }

    public function is_seller_or_admin() {
        if ( ! is_user_logged_in() ) return false;
        $user = wp_get_current_user();
        return in_array( 'ta_seller', (array) $user->roles, true )
            || current_user_can( 'manage_options' );
    }

    // ── POST /tickets ─────────────────────────────────────────────────────────
    public function create_ticket( WP_REST_Request $request ) {
        $user_id = get_current_user_id();

        // Check KYC (seller must be KYC approved)
        $kyc_status = get_user_meta( $user_id, 'ta_kyc_status', true );
        if ( $kyc_status !== 'approved' && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'kyc_required', 'KYC verification required before listing tickets.', array( 'status' => 403 ) );
        }

        $event_id    = TA_Security::clean_int( $request->get_param( 'event_id' ) );
        $event_name  = TA_Security::clean( $request->get_param( 'event' ) );
        $type        = TA_Security::clean( $request->get_param( 'category' ) ); // The select box 'category' maps to 'type'
        $price       = TA_Security::clean_float( $request->get_param( 'price' ) );
        $quantity    = TA_Security::clean_int( $request->get_param( 'quantity' ) );
        $event_date  = TA_Security::clean( $request->get_param( 'eventDate' ) );
        $event_time  = TA_Security::clean( $request->get_param( 'eventTime' ) );
        $section     = TA_Security::clean( $request->get_param( 'section' ) );
        $row_label   = TA_Security::clean( $request->get_param( 'row' ) );
        $seat_number = TA_Security::clean( $request->get_param( 'seat_number' ) );
        $venue       = TA_Security::clean( $request->get_param( 'venue' ) );
        $description = TA_Security::clean( $request->get_param( 'description' ) );

        if ( empty( $event_name ) || empty( $price ) || empty( $quantity ) || empty( $event_date ) || empty( $event_time ) ) {
            return new WP_Error( 'missing_fields', 'Event name, price, quantity, event date and time are required.', array( 'status' => 400 ) );
        }

        if ( $price <= 0 || $quantity <= 0 ) {
            return new WP_Error( 'invalid_values', 'Price and quantity must be positive numbers.', array( 'status' => 400 ) );
        }

        // Handle file upload
        $file_url  = null;
        $file_hash = null;

        if ( ! empty( $_FILES['ticketFile'] ) ) {
            $upload = TA_Security::handle_upload( $_FILES['ticketFile'], null, 5 );
            if ( is_wp_error( $upload ) ) return $upload;

            // Duplicate check via hash
            global $wpdb;
            $t = TA_Database::tickets_table();
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$t} WHERE file_hash = %s AND status NOT IN ('rejected', 'sold')",
                $upload['file_hash']
            ) );
            if ( $existing ) {
                @unlink( $upload['full_path'] );
                return new WP_Error( 'duplicate_ticket', 'This ticket image has already been submitted.', array( 'status' => 400 ) );
            }

            $file_url  = $upload['url'];
            $file_hash = $upload['file_hash'];
        }

        // Auto-create event if missing to ensure dedicated page exists
        if ( empty( $event_id ) ) {
            $existing_event = get_page_by_title( $event_name, OBJECT, 'events' );

            if ( $existing_event ) {
                $event_id = $existing_event->ID;
            } else {
                $event_id = wp_insert_post( array(
                    'post_title'   => $event_name,
                    'post_type'    => 'events',
                    'post_status'  => 'publish',
                    'post_content' => $description ?: 'Event automatically created from seller listing.',
                ) );

                if ( ! is_wp_error( $event_id ) ) {
                    update_post_meta( $event_id, 'event_date', $event_date );
                    update_post_meta( $event_id, 'event_time', $event_time );
                    update_post_meta( $event_id, 'event_location', $venue );
                    
                    if ( $section ) {
                        wp_set_object_terms( $event_id, $section, 'event_cat' );
                    }
                } else {
                    $event_id = null; // Fallback
                }
            }
        }

        global $wpdb;
        $table = TA_Database::tickets_table();
        $inserted = $wpdb->insert( $table, array(
            'event_id'    => $event_id ?: null,
            'event_name'  => $event_name,
            'type'        => in_array( $type, array( 'music', 'sports', 'comedy', 'theatre', 'other' ), true ) ? $type : 'other',
            'seller_id'   => $user_id,
            'price'       => $price,
            'quantity'    => $quantity,
            'section'     => $section,
            'row_label'   => $row_label,
            'seat_number' => $seat_number,
            'venue'       => $venue,
            'description' => $description,
            'event_date'  => $event_date,
            'event_time'  => $event_time,
            'file_url'    => $file_url,
            'file_hash'   => $file_hash,
            'status'      => 'pending',
        ), array( '%d', '%s', '%s', '%d', '%f', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );

        if ( ! $inserted ) {
            error_log( 'TickerAdda DB Error: ' . $wpdb->last_error );
            error_log( 'TickerAdda Failed Query: ' . $wpdb->last_query );
            return new WP_Error( 'db_error', 'Failed to save ticket. DB Error: ' . $wpdb->last_error, array( 'status' => 500 ) );
        }

        $ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $wpdb->insert_id ) );

        return rest_ensure_response( array(
            'success'   => true,
            'ticket_id' => (int) $wpdb->insert_id,
        ) );
    }

    // ── GET /tickets/approved ─────────────────────────────────────────────────
    public function get_approved_tickets( WP_REST_Request $request, $event_id = null ) {
        global $wpdb;
        $table = TA_Database::tickets_table();

        // If event_id is passed as param, use it
        if ( !$event_id ) {
            $event_id = TA_Security::clean_int( $request->get_param( 'event_id' ) );
        }

        $type = TA_Security::clean( $request->get_param( 'type' ) );
        $where = "WHERE status IN ('approved', 'available', 'sold') AND is_unlisted = 0";
        $args  = array();

        if ( $type ) {
            $where .= ' AND type = %s';
            $args[] = $type;
        }

        if ( $event_id ) {
            $where .= ' AND event_id = %d';
            $args[] = $event_id;
        }

        $query = "SELECT t.*, u.display_name as seller_name,
                  um.meta_value as seller_avg_rating,
                  um2.meta_value as seller_ratings_count
                  FROM {$table} t
                  LEFT JOIN {$wpdb->users} u ON t.seller_id = u.ID
                  LEFT JOIN {$wpdb->usermeta} um ON t.seller_id = um.user_id AND um.meta_key = 'ta_average_rating'
                  LEFT JOIN {$wpdb->usermeta} um2 ON t.seller_id = um2.user_id AND um2.meta_key = 'ta_ratings_count'
                  {$where} ORDER BY t.created_at DESC";

        $tickets = $args
            ? $wpdb->get_results( $wpdb->prepare( $query, ...$args ) )
            : $wpdb->get_results( $query );

        return rest_ensure_response( array_map( array( $this, 'format_ticket' ), $tickets ) );
    }

    public function get_recent_tickets( WP_REST_Request $request ) {
        global $wpdb;
        $table = TA_Database::tickets_table();
        $query = "SELECT t.*, u.display_name as seller_name,
                  um.meta_value as seller_avg_rating,
                  um2.meta_value as seller_ratings_count
                  FROM {$table} t
                  LEFT JOIN {$wpdb->users} u ON t.seller_id = u.ID
                  LEFT JOIN {$wpdb->usermeta} um ON t.seller_id = um.user_id AND um.meta_key = 'ta_average_rating'
                  LEFT JOIN {$wpdb->usermeta} um2 ON t.seller_id = um2.user_id AND um2.meta_key = 'ta_ratings_count'
                  WHERE t.status IN ('approved', 'available', 'sold') AND t.is_unlisted = 0
                  ORDER BY t.created_at DESC LIMIT 8";

        $tickets = $wpdb->get_results( $query );
        return rest_ensure_response( array_map( array( $this, 'format_ticket' ), $tickets ) );
    }

    public function get_event_tickets( WP_REST_Request $request ) {
        global $wpdb;
        $event_id = TA_Security::clean_int( $request->get_param( 'id' ) );
        $table = TA_Database::tickets_table();
        
        $query = "SELECT t.*, u.display_name as seller_name,
                  um.meta_value as seller_avg_rating,
                  um2.meta_value as seller_ratings_count
                  FROM {$table} t
                  LEFT JOIN {$wpdb->users} u ON t.seller_id = u.ID
                  LEFT JOIN {$wpdb->usermeta} um ON t.seller_id = um.user_id AND um.meta_key = 'ta_average_rating'
                  LEFT JOIN {$wpdb->usermeta} um2 ON t.seller_id = um2.user_id AND um2.meta_key = 'ta_ratings_count'
                  WHERE t.event_id = %d AND t.status IN ('approved', 'available', 'sold') AND t.is_unlisted = 0
                  ORDER BY t.price ASC";

        $tickets = $wpdb->get_results( $wpdb->prepare( $query, $event_id ) );
        return rest_ensure_response( array_map( array( $this, 'format_ticket' ), $tickets ) );
    }

    // ── GET /tickets/(?P<id>\d+) ──────────────────────────────────────────────
    public function get_ticket( WP_REST_Request $request ) {
        global $wpdb;
        $id    = TA_Security::clean_int( $request->get_param( 'id' ) );
        $table = TA_Database::tickets_table();

        $ticket = $wpdb->get_row( $wpdb->prepare(
            "SELECT t.*, u.display_name as seller_name,
             um.meta_value as seller_avg_rating,
             um2.meta_value as seller_ratings_count
             FROM {$table} t
             LEFT JOIN {$wpdb->users} u ON t.seller_id = u.ID
             LEFT JOIN {$wpdb->usermeta} um ON t.seller_id = um.user_id AND um.meta_key = 'ta_average_rating'
             LEFT JOIN {$wpdb->usermeta} um2 ON t.seller_id = um2.user_id AND um2.meta_key = 'ta_ratings_count'
             WHERE t.id = %d",
            $id
        ) );

        if ( ! $ticket ) {
            return new WP_Error( 'not_found', 'Ticket not found.', array( 'status' => 404 ) );
        }

        return rest_ensure_response( $this->format_ticket( $ticket ) );
    }

    // ── GET /tickets/my-tickets ───────────────────────────────────────────────
    public function get_my_tickets( WP_REST_Request $request ) {
        global $wpdb;
        $user_id = get_current_user_id();
        $table   = TA_Database::tickets_table();
        $orders  = TA_Database::orders_table();

        $tickets = $wpdb->get_results( $wpdb->prepare(
            "SELECT t.*,
             o.buyer_id, o.id as order_id, o.total_amount as order_total,
             u.display_name as buyer_name, u.user_email as buyer_email,
             um.meta_value as buyer_phone
             FROM {$table} t
             LEFT JOIN {$orders} o ON t.id = o.ticket_id AND o.status = 'completed'
             LEFT JOIN {$wpdb->users} u ON o.buyer_id = u.ID
             LEFT JOIN {$wpdb->usermeta} um ON o.buyer_id = um.user_id AND um.meta_key = 'ta_phone'
             WHERE t.seller_id = %d
             ORDER BY t.created_at DESC",
            $user_id
        ) );

        return rest_ensure_response( array_map( array( $this, 'format_ticket' ), $tickets ) );
    }

    // ── GET /tickets/pending ──────────────────────────────────────────────────
    public function get_pending_tickets( WP_REST_Request $request ) {
        global $wpdb;
        $table = TA_Database::tickets_table();

        $tickets = $wpdb->get_results(
            "SELECT t.*, u.display_name as seller_name, u.user_email as seller_email,
             um.meta_value as seller_phone
             FROM {$table} t
             LEFT JOIN {$wpdb->users} u ON t.seller_id = u.ID
             LEFT JOIN {$wpdb->usermeta} um ON t.seller_id = um.user_id AND um.meta_key = 'ta_phone'
             WHERE t.status = 'pending'
             ORDER BY t.created_at ASC"
        );

        return rest_ensure_response( array_map( array( $this, 'format_ticket' ), $tickets ) );
    }

    // ── GET /tickets/history ──────────────────────────────────────────────────
    public function get_ticket_history( WP_REST_Request $request ) {
        global $wpdb;
        $table = TA_Database::tickets_table();

        $tickets = $wpdb->get_results(
            "SELECT t.*, u.display_name as seller_name, u.user_email as seller_email
             FROM {$table} t
             LEFT JOIN {$wpdb->users} u ON t.seller_id = u.ID
             WHERE t.status IN ('approved', 'rejected', 'sold')
             ORDER BY t.updated_at DESC"
        );

        return rest_ensure_response( array_map( array( $this, 'format_ticket' ), $tickets ) );
    }

    // ── PUT /tickets/(?P<id>\d+)/status ──────────────────────────────────────
    public function update_ticket_status( WP_REST_Request $request ) {
        global $wpdb;
        $id     = TA_Security::clean_int( $request->get_param( 'id' ) );
        $status = TA_Security::clean( $request->get_param( 'status' ) );
        $table  = TA_Database::tickets_table();

        $allowed = array( 'approved', 'rejected', 'pending', 'available' );
        if ( ! in_array( $status, $allowed, true ) ) {
            return new WP_Error( 'invalid_status', 'Invalid status value.', array( 'status' => 400 ) );
        }

        $ticket = $wpdb->get_row( $wpdb->prepare(
            "SELECT t.*, u.display_name as seller_name, u.user_email as seller_email
             FROM {$table} t LEFT JOIN {$wpdb->users} u ON t.seller_id = u.ID
             WHERE t.id = %d", $id
        ) );

        if ( ! $ticket ) {
            return new WP_Error( 'not_found', 'Ticket not found.', array( 'status' => 404 ) );
        }

        $wpdb->update( $table, array( 'status' => $status ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );

        // Send notification email to seller
        if ( $ticket->seller_email && in_array( $status, array( 'approved', 'rejected' ), true ) ) {
            TA_Email::send_ticket_status(
                $ticket->seller_email,
                esc_html( $ticket->seller_name ),
                esc_html( $ticket->event_name ),
                $status
            );
        }

        return rest_ensure_response( array( 'msg' => "Ticket status updated to {$status}." ) );
    }

    // ── GET /tickets/secure-image/(?P<id>\d+) ────────────────────────────────
    public function serve_ticket_image( WP_REST_Request $request ) {
        global $wpdb;
        $id      = TA_Security::clean_int( $request->get_param( 'id' ) );
        $user_id = get_current_user_id();
        $table   = TA_Database::tickets_table();
        $orders  = TA_Database::orders_table();

        $ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
        if ( ! $ticket ) {
            return new WP_Error( 'not_found', 'Ticket not found.', array( 'status' => 404 ) );
        }

        $is_authorized = false;
        if ( current_user_can( 'manage_options' ) ) $is_authorized = true;
        if ( (int) $ticket->seller_id === $user_id ) $is_authorized = true;

        // Buyer: must have a completed order for this ticket
        if ( ! $is_authorized ) {
            $order = $wpdb->get_row( $wpdb->prepare(
                "SELECT id FROM {$orders} WHERE ticket_id = %d AND buyer_id = %d AND status = 'completed' AND is_ticket_sent = 1",
                $id, $user_id
            ) );
            if ( $order ) $is_authorized = true;
        }

        if ( ! $is_authorized ) {
            return new WP_Error( 'forbidden', 'Access denied.', array( 'status' => 403 ) );
        }

        if ( empty( $ticket->file_url ) ) {
            return new WP_Error( 'no_file', 'No ticket image available.', array( 'status' => 404 ) );
        }

        $full_path = TA_UPLOAD_DIR . $ticket->file_url;
        if ( ! file_exists( $full_path ) ) {
            return new WP_Error( 'file_not_found', 'Ticket file not found on server.', array( 'status' => 404 ) );
        }

        // Serve binary — bypass WP's REST JSON output
        $mime = mime_content_type( $full_path );
        header( 'Content-Type: ' . $mime );
        header( 'Content-Length: ' . filesize( $full_path ) );
        header( 'Cache-Control: no-store' );
        readfile( $full_path );
        exit;
    }

    // ── Format helper ─────────────────────────────────────────────────────────
    private function format_ticket( $t ) {
        if ( ! $t ) return null;
        return array(
            '_id'            => (int) $t->id,
            'id'             => (int) $t->id,
            'eventId'        => (int) ($t->event_id ?? 0),
            'event'          => esc_html( $t->event_name ),
            'type'           => $t->type,
            'sellerId'       => (int) $t->seller_id,
            'seller'         => array(
                '_id'            => (int) $t->seller_id,
                'name'           => esc_html( $t->seller_name ?? '' ),
                'email'          => esc_html( $t->seller_email ?? '' ),
                'phone'          => esc_html( $t->seller_phone ?? '' ),
                'averageRating'  => (float) ( $t->seller_avg_rating ?? 0 ),
                'ratingsCount'   => (int) ( $t->seller_ratings_count ?? 0 ),
            ),
            'sellerName'     => esc_html( $t->seller_name ?? '' ),
            'sellerEmail'    => esc_html( $t->seller_email ?? '' ),
            'sellerPhone'    => esc_html( $t->seller_phone ?? '' ),
            'avgRating'      => (float) ( $t->seller_avg_rating ?? 0 ),
            'ratingsCount'   => (int) ( $t->seller_ratings_count ?? 0 ),
            'price'          => (float) $t->price,
            'quantity'       => (int) $t->quantity,
            'section'        => esc_html( $t->section ?? '' ),
            'row'            => esc_html( $t->row_label ?? '' ),
            'seat'           => esc_html( $t->seat_number ?? '' ),
            'category'       => esc_html( $t->section ?? '' ),
            'venue'          => esc_html( $t->venue ?? '' ),
            'description'    => esc_html( $t->description ?? '' ),
            'date'           => $t->event_date,
            'eventDate'      => $t->event_date,
            'eventTime'      => $t->event_time,
            'hasImage'       => ! empty( $t->file_url ),
            'status'         => $t->status,
            'isUnlisted'     => (bool) $t->is_unlisted,
            'buyer'          => isset( $t->buyer_name ) ? array(
                'name'  => esc_html( $t->buyer_name ),
                'email' => esc_html( $t->buyer_email ),
                'phone' => esc_html( $t->buyer_phone ),
            ) : null,
            'buyerName'      => isset( $t->buyer_name ) ? esc_html( $t->buyer_name ) : null,
            'buyerEmail'     => isset( $t->buyer_email ) ? esc_html( $t->buyer_email ) : null,
            'buyerPhone'     => isset( $t->buyer_phone ) ? esc_html( $t->buyer_phone ) : null,
            'createdAt'      => $t->created_at,
            'order'          => isset($t->order_id) ? array(
                '_id' => $t->order_id,
                'totalAmount' => $t->order_total
            ) : null
        );
    }
}
