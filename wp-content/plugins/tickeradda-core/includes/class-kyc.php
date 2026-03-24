<?php
/**
 * TA_KYC — REST API for KYC submission and admin review
 *
 * Routes:
 *  POST  /kyc/submit               — Seller: submit KYC document
 *  GET   /kyc/status               — Seller: own KYC status
 *  GET   /admin/kyc/pending        — Admin: pending KYC queue
 *  PUT   /admin/kyc/(?P<id>\d+)/review — Admin: approve/reject
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class TA_KYC {

    public function register_routes() {
        $ns = TA_REST_NS;

        register_rest_route( $ns, '/kyc/submit', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'submit_kyc' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( $ns, '/kyc/status', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_kyc_status' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( $ns, '/admin/kyc/pending', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_pending_kyc' ),
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ) );

        register_rest_route( $ns, '/admin/kyc/(?P<id>\d+)/review', array(
            'methods'             => 'PUT',
            'callback'            => array( $this, 'review_kyc' ),
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ) );

        register_rest_route( $ns, '/admin/kyc/(?P<id>\d+)/file', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'serve_kyc_document' ),
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ) );
    }

    // ── POST /kyc/submit ──────────────────────────────────────────────────────
    public function submit_kyc( WP_REST_Request $request ) {
        global $wpdb;
        $user_id = get_current_user_id();
        $table   = TA_Database::kyc_table();

        // Only sellers (or admins) can submit KYC
        $user = wp_get_current_user();
        if ( ! TA_Roles::user_has_role( $user, 'seller' ) ) {
            return new WP_Error( 'forbidden', 'Only sellers can submit KYC.', array( 'status' => 403 ) );
        }

        $doc_type   = TA_Security::clean( $request->get_param( 'documentType' ) );
        $doc_number = TA_Security::clean( $request->get_param( 'documentNumber' ) );

        if ( empty( $doc_type ) || empty( $doc_number ) ) {
            return new WP_Error( 'missing_fields', 'Document type and number are required.', array( 'status' => 400 ) );
        }

        // Require file upload
        if ( empty( $_FILES['frontImage'] ) ) {
            return new WP_Error( 'no_file', 'Front Image file is required.', array( 'status' => 400 ) );
        }

        $upload_front = TA_Security::handle_upload( $_FILES['frontImage'], 'kyc' );
        if ( is_wp_error( $upload_front ) ) return $upload_front;

        $upload_back = '';
        if ( ! empty( $_FILES['backImage'] ) && $_FILES['backImage']['error'] === UPLOAD_ERR_OK ) {
            $up = TA_Security::handle_upload( $_FILES['backImage'], 'kyc' );
            if ( ! is_wp_error( $up ) ) {
                $upload_back = $up['url'];
            }
        }

        $upload_selfie = '';
        if ( ! empty( $_FILES['selfie'] ) && $_FILES['selfie']['error'] === UPLOAD_ERR_OK ) {
            $up = TA_Security::handle_upload( $_FILES['selfie'], 'kyc' );
            if ( ! is_wp_error( $up ) ) {
                $upload_selfie = $up['url'];
            }
        }

        // Check if existing KYC record
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND status = 'pending'", $user_id
        ) );

        if ( $existing ) {
            return new WP_Error( 'pending_kyc', 'You already have a pending KYC submission.', array( 'status' => 400 ) );
        }

        $wpdb->insert( $table, array(
            'user_id'         => $user_id,
            'document_type'   => $doc_type,
            'document_number' => $doc_number,
            'file_url'        => $upload_front['url'],
            'back_file_url'   => $upload_back,
            'selfie_file_url' => $upload_selfie,
            'status'          => 'pending',
        ), array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' ) );

        // Update user meta
        update_user_meta( $user_id, 'ta_kyc_status', 'pending' );

        return rest_ensure_response( array( 'msg' => 'KYC submitted successfully. Awaiting admin review.' ) );
    }

    // ── GET /kyc/status ───────────────────────────────────────────────────────
    public function get_kyc_status( WP_REST_Request $request ) {
        global $wpdb;
        $user_id = get_current_user_id();
        $table   = TA_Database::kyc_table();

        $record = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT 1",
            $user_id
        ) );

        $kyc_status = get_user_meta( $user_id, 'ta_kyc_status', true ) ?: 'not_submitted';

        return rest_ensure_response( array(
            'status'          => $kyc_status,
            'documentType'    => $record ? esc_html( $record->document_type ) : null,
            'rejectionReason' => $record ? esc_html( $record->rejection_reason ) : null,
            'submittedAt'     => $record ? $record->created_at : null,
        ) );
    }

    // ── GET /admin/kyc/pending ─────────────────────────────────────────────────
    public function get_pending_kyc( WP_REST_Request $request ) {
        global $wpdb;
        $table = TA_Database::kyc_table();

        $records = $wpdb->get_results(
            "SELECT k.*, u.display_name as user_name, u.user_email as user_email,
             um.meta_value as user_phone
             FROM {$table} k
             LEFT JOIN {$wpdb->users} u ON k.user_id = u.ID
             LEFT JOIN {$wpdb->usermeta} um ON k.user_id = um.user_id AND um.meta_key = 'ta_phone'
             WHERE k.status = 'pending'
             ORDER BY k.created_at ASC"
        );

        return rest_ensure_response( array_map( function( $r ) {
            return array(
                'id'              => (int) $r->id,
                '_id'             => (int) $r->id,
                'userId'          => (int) $r->user_id,
                'user'            => array(
                    '_id'   => (int) $r->user_id,
                    'name'  => esc_html( $r->user_name ),
                    'email' => esc_html( $r->user_email ),
                    'phone' => esc_html( $r->user_phone ),
                ),
                'userName'        => esc_html( $r->user_name ),
                'userEmail'       => esc_html( $r->user_email ),
                'userPhone'       => esc_html( $r->user_phone ),
                'documentType'    => esc_html( $r->document_type ),
                'documentNumber'  => esc_html( $r->document_number ),
                'fileUrl'         => rest_url( TA_REST_NS . '/admin/kyc/' . $r->id . '/file?type=front&_wpnonce=' . wp_create_nonce('wp_rest') ), // Legacy support
                'files'           => array(
                    'front'  => rest_url( TA_REST_NS . '/admin/kyc/' . $r->id . '/file?type=front' ),
                    'back'   => !empty($r->back_file_url) ? rest_url( TA_REST_NS . '/admin/kyc/' . $r->id . '/file?type=back' ) : null,
                    'selfie' => !empty($r->selfie_file_url) ? rest_url( TA_REST_NS . '/admin/kyc/' . $r->id . '/file?type=selfie' ) : null,
                ),
                'status'          => $r->status,
                'submittedAt'     => $r->created_at,
                'createdAt'      => $r->created_at,
            );
        }, $records ) );
    }

    // ── PUT /admin/kyc/(?P<id>\d+)/review ────────────────────────────────────
    public function review_kyc( WP_REST_Request $request ) {
        global $wpdb;
        $id                = TA_Security::clean_int( $request->get_param( 'id' ) );
        $status            = TA_Security::clean( $request->get_param( 'status' ) );
        $rejection_reason  = TA_Security::clean( $request->get_param( 'rejectionReason' ) );
        $table             = TA_Database::kyc_table();

        if ( ! in_array( $status, array( 'approved', 'rejected' ), true ) ) {
            return new WP_Error( 'invalid_status', 'Status must be approved or rejected.', array( 'status' => 400 ) );
        }

        $record = $wpdb->get_row( $wpdb->prepare(
            "SELECT k.*, u.display_name as user_name, u.user_email as user_email
             FROM {$table} k LEFT JOIN {$wpdb->users} u ON k.user_id = u.ID
             WHERE k.id = %d", $id
        ) );

        if ( ! $record ) {
            return new WP_Error( 'not_found', 'KYC record not found.', array( 'status' => 404 ) );
        }

        // Update record
        $upd = array(
            'status'      => $status,
            'reviewed_by' => get_current_user_id(),
        );
        if ( $status === 'rejected' && $rejection_reason ) {
            $upd['rejection_reason'] = $rejection_reason;
        }

        $wpdb->update( $table, $upd, array( 'id' => $id ), null, array( '%d' ) );

        // Update user meta KYC status
        update_user_meta( $record->user_id, 'ta_kyc_status', $status );

        // Send email notification
        TA_Email::send_kyc_status(
            $record->user_email,
            esc_html( $record->user_name ),
            $status,
            $rejection_reason
        );

        return rest_ensure_response( array(
            'msg'    => "KYC {$status} successfully.",
            'status' => $status,
        ) );
    }

    // ── GET /admin/kyc/(?P<id>\d+)/file ───────────────────────────────────────
    public function serve_kyc_document( WP_REST_Request $request ) {
        global $wpdb;
        $id    = TA_Security::clean_int( $request->get_param( 'id' ) );
        $type  = TA_Security::clean( $request->get_param( 'type' ) );
        $table = TA_Database::kyc_table();

        $record = $wpdb->get_row( $wpdb->prepare( "SELECT file_url, back_file_url, selfie_file_url FROM {$table} WHERE id = %d", $id ) );
        if ( ! $record ) {
            return new WP_Error( 'not_found', 'KYC record not found.', array( 'status' => 404 ) );
        }

        $url = $record->file_url;
        if ( $type === 'back' && ! empty( $record->back_file_url ) ) {
            $url = $record->back_file_url;
        } elseif ( $type === 'selfie' && ! empty( $record->selfie_file_url ) ) {
            $url = $record->selfie_file_url;
        }

        if ( empty( $url ) ) {
            return new WP_Error( 'no_file', 'No document available.', array( 'status' => 404 ) );
        }

        $full_path = TA_UPLOAD_DIR . $url;
        if ( ! file_exists( $full_path ) ) {
            return new WP_Error( 'file_not_found', 'Document file not found on server.', array( 'status' => 404 ) );
        }

        $mime = mime_content_type( $full_path );
        header( 'Content-Type: ' . $mime );
        header( 'Content-Length: ' . filesize( $full_path ) );
        header( 'Cache-Control: no-store' );
        readfile( $full_path );
        exit;
    }
}
