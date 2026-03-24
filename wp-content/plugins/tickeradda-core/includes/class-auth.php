<?php
/**
 * TA_Auth — REST API Auth endpoints (namespace: tickeradda/v1)
 *
 * Routes (converted from Node.js Express):
 *  POST /auth/send-signup-otp
 *  POST /auth/register
 *  POST /auth/login
 *  GET  /auth/me
 *  POST /auth/forgot-password
 *  POST /auth/verify-otp
 *  POST /auth/reset-password
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class TA_Auth {

    public function register_routes() {
        $ns = TA_REST_NS;

        register_rest_route( $ns, '/auth/send-signup-otp', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'send_signup_otp' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $ns, '/auth/register', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'register' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $ns, '/auth/login', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'login' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $ns, '/auth/me', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_me' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( $ns, '/auth/forgot-password', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'forgot_password' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $ns, '/auth/verify-otp', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'verify_otp' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $ns, '/auth/reset-password', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'reset_password' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $ns, '/auth/logout', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'logout' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( $ns, '/auth/google-login', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'google_login' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $ns, '/auth/phone', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'update_phone' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( $ns, '/users/featured', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_featured_sellers' ),
            'permission_callback' => '__return_true',
        ) );
    }

    // ── POST /auth/send-signup-otp ─────────────────────────────────────────────
    public function send_signup_otp( WP_REST_Request $request ) {
        $email = TA_Security::clean_email( $request->get_param( 'email' ) );

        if ( empty( $email ) || ! is_email( $email ) ) {
            return new WP_Error( 'invalid_email', 'Please provide a valid email address.', array( 'status' => 400 ) );
        }

        // Rate limit: 3 OTPs per 5 min per email
        if ( TA_Security::is_rate_limited( 'otp_signup', $email, 3, 300 ) ) {
            return new WP_Error( 'rate_limited', 'Too many OTP requests. Please wait before trying again.', array( 'status' => 429 ) );
        }

        if ( email_exists( $email ) ) {
            return new WP_Error( 'user_exists', 'An account with this email already exists.', array( 'status' => 400 ) );
        }

        $otp        = self::generate_otp();
        $expires_at = date( 'Y-m-d H:i:s', current_time( 'timestamp', 1 ) + ( 30 * 60 ) ); 

        global $wpdb;
        $table = TA_Database::otp_table();

        // Upsert OTP
        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE email = %s", $email ) );
        if ( $existing ) {
            $wpdb->update( $table, array( 'otp' => $otp, 'expires_at' => $expires_at ), array( 'email' => $email ) );
        } else {
            $wpdb->insert( $table, array( 'email' => $email, 'otp' => $otp, 'expires_at' => $expires_at ) );
        }

        if ( ! TA_Email::send_otp( $email, $otp, 'signup' ) ) {
            return new WP_Error( 'email_failed', 'Failed to send OTP email. Please ensure your site has email services (SMTP) configured.', array( 'status' => 500 ) );
        }

        return rest_ensure_response( array( 'msg' => 'OTP sent to your email.' ) );
    }

    // ── POST /auth/register ────────────────────────────────────────────────────
    public function register( WP_REST_Request $request ) {
        $name  = TA_Security::clean( $request->get_param( 'name' ) );
        $email = TA_Security::clean_email( $request->get_param( 'email' ) );
        $phone = TA_Security::clean( $request->get_param( 'phone' ) );
        $pass  = $request->get_param( 'password' );
        $otp   = TA_Security::clean( $request->get_param( 'otp' ) );
        // Removed dynamic role, default to ta_both
        $role  = 'ta_both';

        // Validation
        if ( empty( $name ) || empty( $email ) || empty( $phone ) || empty( $pass ) || empty( $otp ) ) {
            return new WP_Error( 'missing_fields', 'All fields including OTP are required.', array( 'status' => 400 ) );
        }

        if ( ! is_email( $email ) ) {
            return new WP_Error( 'invalid_email', 'Invalid email address.', array( 'status' => 400 ) );
        }

        if ( ! TA_Security::is_strong_password( $pass ) ) {
            return new WP_Error( 'weak_password', 'Password must be at least 8 characters.', array( 'status' => 400 ) );
        }

        // Verify OTP
        global $wpdb;
        $table = TA_Database::otp_table();
        $now   = date( 'Y-m-d H:i:s', current_time( 'timestamp', 1 ) );
        $record = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE email = %s AND otp = %s AND expires_at > %s",
            $email, $otp, $now
        ) );

        if ( ! $record ) {
            return new WP_Error( 'invalid_otp', 'Invalid or expired OTP.', array( 'status' => 400 ) );
        }

        if ( email_exists( $email ) ) {
            return new WP_Error( 'user_exists', 'An account with this email already exists.', array( 'status' => 400 ) );
        }

        // Unify role to ta_both
        $role = 'ta_both';

        $user_id = wp_create_user( sanitize_user( $email ), $pass, $email );
        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }

        $user = new WP_User( $user_id );
        $user->set_role( $role );
        wp_update_user( array( 'ID' => $user_id, 'display_name' => $name, 'first_name' => $name ) );
        update_user_meta( $user_id, 'ta_phone', sanitize_text_field( $phone ) );
        update_user_meta( $user_id, 'ta_role_label', 'both' );
        update_user_meta( $user_id, 'ta_kyc_status', 'not_submitted' );
        update_user_meta( $user_id, 'ta_ban_status', 'none' );
        update_user_meta( $user_id, 'ta_average_rating', 0 );
        update_user_meta( $user_id, 'ta_ratings_count', 0 );

        // Clean OTP
        $wpdb->delete( $table, array( 'email' => $email ) );

        // Log user in (set auth cookie)
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, true );

        return rest_ensure_response( array(
            'msg'   => 'Registration successful.',
            'token' => wp_create_nonce( 'wp_rest' ),
            'user'  => self::user_response( $user_id ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
        ) );
    }

    // ── POST /auth/google-login ────────────────────────────────────────────────
    public function google_login( WP_REST_Request $request ) {
        $credential = $request->get_param( 'credential' );

        if ( empty( $credential ) ) {
            return new WP_Error( 'missing_credential', 'Google credential is required.', array( 'status' => 400 ) );
        }

        // Verify ID Token with Google API
        $response = wp_remote_get( "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode( $credential ) );
        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'google_api_error', 'Failed to connect to Google API.', array( 'status' => 500 ) );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( empty( $data ) || ! isset( $data['email'] ) || isset( $data['error'] ) ) {
            return new WP_Error( 'invalid_token', 'Invalid Google token.', array( 'status' => 401 ) );
        }

        // Validate Audience (Client ID)
        $client_id = "539426267370-e12lt552ilkencgo97qcaf01kl4mpt26.apps.googleusercontent.com";
        if ( $data['aud'] !== $client_id ) {
             return new WP_Error( 'invalid_aud', 'Invalid client ID.', array( 'status' => 401 ) );
        }

        $email = $data['email'];
        $name  = $data['name'] ?? '';
        $user  = get_user_by( 'email', $email );

        if ( ! $user ) {
            // Auto-register user
            $username = sanitize_user( $email );
            $password = wp_generate_password();
            $user_id  = wp_create_user( $username, $password, $email );

            if ( is_wp_error( $user_id ) ) {
                return $user_id;
            }

            $user = new WP_User( $user_id );
            $user->set_role( 'ta_both' );
            wp_update_user( array( 'ID' => $user_id, 'display_name' => $name, 'first_name' => $name ) );
            
            update_user_meta( $user_id, 'ta_role_label', 'both' );
            update_user_meta( $user_id, 'ta_kyc_status', 'not_submitted' );
            update_user_meta( $user_id, 'ta_ban_status', 'none' );
            update_user_meta( $user_id, 'ta_average_rating', 0 );
            update_user_meta( $user_id, 'ta_ratings_count', 0 );
            update_user_meta( $user_id, 'google_user_id', $data['sub'] ?? '' );
            
            $user = get_user_by( 'id', $user_id );
        } else {
             // Check ban status
            $ban = get_user_meta( $user->ID, 'ta_ban_status', true );
            if ( $ban && $ban !== 'none' ) {
                $reason = get_user_meta( $user->ID, 'ta_ban_reason', true ) ?: 'Policy Violation';
                $msg = $ban === 'permanent'
                    ? "Your account is permanently banned. Reason: {$reason}."
                    : "Your account is temporarily blocked. Reason: {$reason}.";
                return new WP_Error( 'account_banned', $msg, array( 'status' => 403 ) );
            }
            
            // Link Google ID if not already linked
            if ( ! get_user_meta( $user->ID, 'google_user_id', true ) ) {
                update_user_meta( $user->ID, 'google_user_id', $data['sub'] ?? '' );
            }
        }

        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, true );

        return rest_ensure_response( array(
            'msg'   => 'Login successful.',
            'token' => wp_create_nonce( 'wp_rest' ),
            'user'  => self::user_response( $user->ID ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
        ) );
    }

    // ── POST /auth/login ───────────────────────────────────────────────────────
    public function login( WP_REST_Request $request ) {
        $email = TA_Security::clean_email( $request->get_param( 'email' ) );
        $pass  = $request->get_param( 'password' );

        if ( empty( $email ) || empty( $pass ) ) {
            return new WP_Error( 'missing_fields', 'Email and password are required.', array( 'status' => 400 ) );
        }

        // Rate limit: 10 login attempts per 5 min per IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if ( TA_Security::is_rate_limited( 'login', $ip, 10, 300 ) ) {
            return new WP_Error( 'rate_limited', 'Too many login attempts. Please try again in 5 minutes.', array( 'status' => 429 ) );
        }

        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            return new WP_Error( 'invalid_credentials', 'Invalid email or password.', array( 'status' => 401 ) );
        }

        if ( ! wp_check_password( $pass, $user->user_pass, $user->ID ) ) {
            return new WP_Error( 'invalid_credentials', 'Invalid email or password.', array( 'status' => 401 ) );
        }

        // Check ban status
        $ban = get_user_meta( $user->ID, 'ta_ban_status', true );
        if ( $ban && $ban !== 'none' ) {
            $reason = get_user_meta( $user->ID, 'ta_ban_reason', true ) ?: 'Policy Violation';
            $msg = $ban === 'permanent'
                ? "Your account is permanently banned. Reason: {$reason}."
                : "Your account is temporarily blocked. Reason: {$reason}.";
            return new WP_Error( 'account_banned', $msg, array( 'status' => 403 ) );
        }

        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, true );

        return rest_ensure_response( array(
            'msg'   => 'Login successful.',
            'token' => wp_create_nonce( 'wp_rest' ),
            'user'  => self::user_response( $user->ID ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
        ) );
    }

    // ── GET /auth/me ───────────────────────────────────────────────────────────
    public function get_me( WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        return rest_ensure_response( self::user_response( $user_id ) );
    }

    // ── POST /auth/logout ──────────────────────────────────────────────────────
    public function logout( WP_REST_Request $request ) {
        wp_logout();
        return rest_ensure_response( array( 'msg' => 'Logged out successfully.' ) );
    }

    // ── POST /auth/phone ───────────────────────────────────────────────────────
    public function update_phone( WP_REST_Request $request ) {
        $phone = TA_Security::clean( $request->get_param( 'phone' ) );
        if ( empty( $phone ) || strlen( $phone ) < 10 ) {
            return new WP_Error( 'invalid_phone', 'Please provide a valid 10-digit phone number.', array( 'status' => 400 ) );
        }

        $user_id = get_current_user_id();
        update_user_meta( $user_id, 'ta_phone', $phone );

        return rest_ensure_response( array( 'message' => 'Phone number updated successfully.', 'phone' => $phone ) );
    }

    // ── POST /auth/forgot-password ─────────────────────────────────────────────
    public function forgot_password( WP_REST_Request $request ) {
        $email = TA_Security::clean_email( $request->get_param( 'email' ) );

        if ( ! is_email( $email ) ) {
            return new WP_Error( 'invalid_email', 'Invalid email.', array( 'status' => 400 ) );
        }

        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            // Security: don't reveal if email exists
            return rest_ensure_response( array( 'msg' => 'If that email is registered, an OTP has been sent.' ) );
        }

        if ( TA_Security::is_rate_limited( 'otp_reset', $email, 3, 300 ) ) {
            return new WP_Error( 'rate_limited', 'Too many OTP requests.', array( 'status' => 429 ) );
        }

        $otp        = self::generate_otp();
        $expires_at = current_time( 'timestamp', 1 ) + ( 30 * 60 );

        update_user_meta( $user->ID, 'ta_reset_otp', $otp );
        update_user_meta( $user->ID, 'ta_reset_otp_expires', $expires_at );

        TA_Email::send_otp( $email, $otp, 'reset' );

        return rest_ensure_response( array( 'msg' => 'If that email is registered, an OTP has been sent.' ) );
    }

    // ── POST /auth/verify-otp ──────────────────────────────────────────────────
    public function verify_otp( WP_REST_Request $request ) {
        $email = TA_Security::clean_email( $request->get_param( 'email' ) );
        $otp   = TA_Security::clean( $request->get_param( 'otp' ) );

        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            return new WP_Error( 'user_not_found', 'User not found.', array( 'status' => 404 ) );
        }

        $stored_otp     = get_user_meta( $user->ID, 'ta_reset_otp', true );
        $otp_expires    = (int) get_user_meta( $user->ID, 'ta_reset_otp_expires', true );
        $now            = current_time( 'timestamp', 1 );

        if ( $stored_otp !== $otp || $now > $otp_expires ) {
            return new WP_Error( 'invalid_otp', 'Invalid or expired OTP.', array( 'status' => 400 ) );
        }

        return rest_ensure_response( array( 'msg' => 'OTP verified.' ) );
    }

    // ── POST /auth/reset-password ──────────────────────────────────────────────
    public function reset_password( WP_REST_Request $request ) {
        $email       = TA_Security::clean_email( $request->get_param( 'email' ) );
        $otp         = TA_Security::clean( $request->get_param( 'otp' ) );
        $new_password = $request->get_param( 'newPassword' );

        if ( ! TA_Security::is_strong_password( $new_password ) ) {
            return new WP_Error( 'weak_password', 'Password must be at least 8 characters.', array( 'status' => 400 ) );
        }

        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            return new WP_Error( 'user_not_found', 'User not found.', array( 'status' => 404 ) );
        }

        $stored_otp  = get_user_meta( $user->ID, 'ta_reset_otp', true );
        $otp_expires = (int) get_user_meta( $user->ID, 'ta_reset_otp_expires', true );

        if ( $stored_otp !== $otp || time() > $otp_expires ) {
            return new WP_Error( 'invalid_otp', 'Invalid or expired OTP.', array( 'status' => 400 ) );
        }

        wp_set_password( $new_password, $user->ID );
        delete_user_meta( $user->ID, 'ta_reset_otp' );
        delete_user_meta( $user->ID, 'ta_reset_otp_expires' );

        return rest_ensure_response( array( 'msg' => 'Password reset successfully.' ) );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private static function generate_otp() {
        return (string) wp_rand( 100000, 999999 );
    }

    public static function user_response( $user_id ) {
        $user   = get_user_by( 'id', $user_id );
        $roles  = $user->roles;
        $role   = 'both';
        if ( in_array( 'administrator', $roles, true ) ) $role = 'admin';

        $phone = get_user_meta( $user_id, 'ta_phone', true );

        return array(
            'id'              => $user_id,
            'name'            => esc_html( $user->display_name ),
            'email'           => esc_html( $user->user_email ),
            'phone'           => esc_html( $phone ),
            'isPhoneRequired' => empty( $phone ),
            'role'            => $role,
            'kycStatus'       => esc_html( get_user_meta( $user_id, 'ta_kyc_status', true ) ?: 'not_submitted' ),
            'banStatus'       => esc_html( get_user_meta( $user_id, 'ta_ban_status', true ) ?: 'none' ),
            'avgRating'       => (float) get_user_meta( $user_id, 'ta_average_rating', true ),
            'ratingsCount'    => (int) get_user_meta( $user_id, 'ta_ratings_count', true ),
            'createdAt'       => $user->user_registered,
        );
    }

    public function get_featured_sellers( WP_REST_Request $request ) {
        global $wpdb;
        $users = $wpdb->get_results(
            "SELECT u.ID FROM {$wpdb->users} u
             JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'ta_average_rating'
             JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'ta_role_label' AND um2.meta_value IN ('seller', 'both')
             ORDER BY CAST(um.meta_value AS DECIMAL(10,2)) DESC LIMIT 6"
        );

        return rest_ensure_response( array_map( function( $u ) {
            return self::user_response( $u->ID );
        }, $users ) );
    }
}
