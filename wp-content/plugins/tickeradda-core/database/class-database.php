<?php
/**
 * TA_Database — Centralized table name helpers
 * Uses WordPress $wpdb->prefix to stay multi-site compatible.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class TA_Database {
    public static function tickets_table()  { global $wpdb; return $wpdb->prefix . 'ta_tickets'; }
    public static function orders_table()   { global $wpdb; return $wpdb->prefix . 'ta_orders'; }
    public static function kyc_table()      { global $wpdb; return $wpdb->prefix . 'ta_kyc_records'; }
    public static function otp_table()      { global $wpdb; return $wpdb->prefix . 'ta_otp_verifications'; }
    public static function reviews_table()  { global $wpdb; return $wpdb->prefix . 'ta_reviews'; }
}
