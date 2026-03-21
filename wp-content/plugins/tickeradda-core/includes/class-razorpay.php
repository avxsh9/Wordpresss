<?php
/**
 * TA_Razorpay — Handles Razorpay payment API via cURL (no composer required).
 * API docs: https://razorpay.com/docs/api/
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class TA_Razorpay {

    private static function get_key_id() {
        return get_option( 'ta_razorpay_key_id', 'rzp_test_SQ0ySa9NBL4rWx' );
    }

    private static function get_key_secret() {
        return get_option( 'ta_razorpay_key_secret', 'J08Dn7EnUu2lwYPQE7W1oOR9' );
    }

    /**
     * Create a Razorpay Order.
     * @param  float  $amount_inr  Total amount in INR
     * @param  string $receipt     Unique order receipt string
     * @return array|WP_Error      Razorpay order object or error
     */
    public static function create_order( $amount_inr, $receipt = '' ) {
        $key_id     = trim( self::get_key_id() );
        $key_secret = trim( self::get_key_secret() );

        if ( empty( $key_id ) || empty( $key_secret ) ) {
            error_log( "[TickerAdda] Razorpay keys are empty!" );
            return new WP_Error( 'razorpay_config', 'Razorpay keys not configured. Go to WP Admin → TickerAdda → Settings.', array( 'status' => 500 ) );
        }

        $payload = array(
            'amount'   => (int) ( $amount_inr * 100 ),   // Convert to paise
            'currency' => 'INR',
            'receipt'  => $receipt ?: 'rcpt_' . time(),
        );

        $response = wp_remote_post( 'https://api.razorpay.com/v1/orders', array(
            'headers'     => array(
                'Authorization' => 'Basic ' . base64_encode( "{$key_id}:{$key_secret}" ),
                'Content-Type'  => 'application/json',
            ),
            'body'        => wp_json_encode( $payload ),
            'timeout'     => 15,
            'data_format' => 'body',
        ) );

        if ( is_wp_error( $response ) ) {
            error_log( "[TickerAdda] Razorpay Remote Post Error: " . $response->get_error_message() );
            return new WP_Error( 'razorpay_request_failed', $response->get_error_message(), array( 'status' => 502 ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['id'] ) ) {
            $err_desc = $body['error']['description'] ?? 'Unknown error from Razorpay';
            error_log( "[TickerAdda] Razorpay API Response Detail: " . wp_remote_retrieve_body( $response ) );
            return new WP_Error( 'razorpay_create_order', $err_desc, array( 'status' => 502 ) );
        }

        return $body; // Contains id, amount, currency, receipt, etc.
    }

    /**
     * Verify Razorpay payment signature (HMAC-SHA256).
     *
     * @param  string $razorpay_order_id
     * @param  string $razorpay_payment_id
     * @param  string $razorpay_signature   Signature from client
     * @return bool   true if valid, false if tampered
     */
    public static function verify_signature( $razorpay_order_id, $razorpay_payment_id, $razorpay_signature ) {
        $key_secret = self::get_key_secret();
        $payload    = $razorpay_order_id . '|' . $razorpay_payment_id;

        $expected = hash_hmac( 'sha256', $payload, $key_secret );

        return hash_equals( $expected, $razorpay_signature );
    }
}
