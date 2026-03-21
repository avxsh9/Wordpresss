<?php
/**
 * TA_Security — Centralized security helpers.
 * All input sanitization, nonce checks, file validation, and SQL prep live here.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class TA_Security {

    public function __construct() {
        // Automatically compress images on upload
        add_filter( 'wp_handle_upload', array( $this, 'auto_compress_upload' ) );
    }

    // ── Input Sanitization ────────────────────────────────────────────────────

    /**
     * Deeply sanitize a scalar value (text).
     */
    public static function clean( $value ) {
        return sanitize_text_field( wp_unslash( $value ) );
    }

    /**
     * Sanitize email address.
     */
    public static function clean_email( $email ) {
        return sanitize_email( wp_unslash( $email ) );
    }

    /**
     * Sanitize an integer value.
     */
    public static function clean_int( $value ) {
        return absint( $value );
    }

    /**
     * Sanitize a price/decimal value.
     */
    public static function clean_float( $value ) {
        return (float) $value;
    }

    /**
     * Sanitize a URL.
     */
    public static function clean_url( $url ) {
        return esc_url_raw( $url );
    }

    /**
     * Sanitize HTML content (rich text — removes dangerous tags).
     */
    public static function clean_html( $html ) {
        return wp_kses_post( $html );
    }

    // ── Output Escaping ───────────────────────────────────────────────────────

    public static function esc_text( $text )  { return esc_html( $text ); }
    public static function esc_attr( $attr )  { return esc_attr( $attr ); }
    public static function esc_url( $url )    { return esc_url( $url ); }

    // ── Nonce Verification ────────────────────────────────────────────────────

    /**
     * Verify a WP nonce from REST request X-WP-Nonce header or POST field.
     */
    public static function verify_nonce( $nonce, $action = 'wp_rest' ) {
        $result = wp_verify_nonce( $nonce, $action );
        if ( false === $result ) {
            return new WP_Error( 'invalid_nonce', 'Security verification failed.', array( 'status' => 403 ) );
        }
        return true;
    }

    // ── File Upload Validation ────────────────────────────────────────────────

    /** Allowed MIME types for ticket proof / KYC docs */
    const ALLOWED_MIMES = array(
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png'          => 'image/png',
        'webp'         => 'image/webp',
        'pdf'          => 'application/pdf',
    );

    /**
     * Validates a $_FILES entry. Returns WP_Error on failure, true on success.
     *
     * @param array  $file          $_FILES['fieldname']
     * @param array  $allowed_mimes Key => MIME pairs (defaults to ALLOWED_MIMES)
     * @param int    $max_size_mb   Max file size in megabytes (default 5)
     */
    public static function validate_file( $file, $allowed_mimes = null, $max_size_mb = 5 ) {
        if ( empty( $file ) || $file['error'] !== UPLOAD_ERR_OK ) {
            return new WP_Error( 'upload_error', 'File upload error: ' . $file['error'], array( 'status' => 400 ) );
        }

        // Size check
        $max_bytes = $max_size_mb * 1024 * 1024;
        if ( $file['size'] > $max_bytes ) {
            return new WP_Error( 'file_too_large', "File exceeds maximum size of {$max_size_mb}MB.", array( 'status' => 400 ) );
        }

        // MIME type validation via WordPress (checks against real file content)
        $mimes = $allowed_mimes ?: self::ALLOWED_MIMES;
        $check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $mimes );

        if ( empty( $check['type'] ) ) {
            return new WP_Error(
                'invalid_file_type',
                'Invalid file type. Allowed: JPG, PNG, WebP, PDF.',
                array( 'status' => 400 )
            );
        }

        return true;
    }

    /**
     * Compute SHA-256 hash of a file for duplicate detection.
     */
    public static function file_hash( $file_path ) {
        return hash_file( 'sha256', $file_path );
    }

    /**
     * Move an uploaded file to the TA secure upload directory.
     * Returns the relative URL and filename on success, WP_Error on failure.
     */
    public static function handle_upload( $file, $sub_dir = '' ) {
        $valid = self::validate_file( $file );
        if ( is_wp_error( $valid ) ) {
            return $valid;
        }

        $dest_dir = TA_UPLOAD_DIR . ( $sub_dir ? trailingslashit( $sub_dir ) : '' );
        if ( ! file_exists( $dest_dir ) ) {
            wp_mkdir_p( $dest_dir );
        }

        $filename   = wp_unique_filename( $dest_dir, sanitize_file_name( $file['name'] ) );
        $dest_path  = $dest_dir . $filename;
        $file_hash  = self::file_hash( $file['tmp_name'] );

        if ( ! move_uploaded_file( $file['tmp_name'], $dest_path ) ) {
            return new WP_Error( 'upload_failed', 'Could not save file.', array( 'status' => 500 ) );
        }

        // Compress if it's an image
        if ( strpos( $file['type'], 'image/' ) !== false ) {
            self::compress_image( $dest_path );
        }

        $relative_url = ( $sub_dir ? trailingslashit( $sub_dir ) : '' ) . $filename;

        return array(
            'filename'  => $filename,
            'file_hash' => $file_hash,
            'url'       => $relative_url,       // relative to TA_UPLOAD_URL
            'full_path' => $dest_path,
        );
    }

    /**
     * Helper to compress an image file.
     */
    public static function compress_image( $file_path, $quality = 82 ) {
        if ( ! file_exists( $file_path ) ) return false;
        
        $editor = wp_get_image_editor( $file_path );
        if ( ! is_wp_error( $editor ) ) {
            $editor->set_quality( $quality );
            $editor->save( $file_path );
            return true;
        }
        return false;
    }

    /**
     * Hook for standard WP uploads.
     */
    public function auto_compress_upload( $upload ) {
        if ( isset( $upload['file'] ) && strpos( $upload['type'], 'image/' ) !== false ) {
            self::compress_image( $upload['file'] );
        }
        return $upload;
    }

    // ── Password Strength Check ───────────────────────────────────────────────
    public static function is_strong_password( $password ) {
        return strlen( $password ) >= 8;
    }

    // ── Rate Limit (Transient-based simple limiter) ───────────────────────────
    /**
     * Returns true if the request should be blocked (rate-limited).
     * @param string $action    e.g. 'otp_send'
     * @param string $key       e.g. IP or email
     * @param int    $max       Max allowed calls
     * @param int    $window    Time window in seconds
     */
    public static function is_rate_limited( $action, $key, $max = 5, $window = 60 ) {
        $transient_key = 'ta_rl_' . md5( $action . $key );
        $hits          = (int) get_transient( $transient_key );

        if ( $hits >= $max ) {
            return true;
        }

        if ( $hits === 0 ) {
            set_transient( $transient_key, 1, $window );
        } else {
            set_transient( $transient_key, $hits + 1, $window );
        }

        return false;
    }
}
