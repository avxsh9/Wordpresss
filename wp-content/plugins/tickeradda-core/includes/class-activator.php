<?php
/**
 * TA_Activator — Runs on plugin activation.
 * Creates all custom DB tables using dbDelta() and seeds a default admin.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class TA_Activator {

    public static function activate() {
        self::create_tables();
        TA_Roles::register();
        self::create_upload_dir();
        flush_rewrite_rules();
        
        // Auto-import movies on first activation
        self::auto_import_movies();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    // ── Create Custom Tables ──────────────────────────────────────────────────
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ── Tickets ──────────────────────────────────────────────────────────
        $tickets = $wpdb->prefix . 'ta_tickets';
        $sql_tickets = "CREATE TABLE {$tickets} (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id    BIGINT(20) UNSIGNED,
            event_name  VARCHAR(255) NOT NULL,
            type        VARCHAR(50)  NOT NULL DEFAULT 'other',
            seller_id   BIGINT(20) UNSIGNED NOT NULL,
            price       DECIMAL(10,2) NOT NULL,
            quantity    INT NOT NULL DEFAULT 1,
            section     VARCHAR(100),
            row_label   VARCHAR(50),
            seat_number VARCHAR(50),
            venue       VARCHAR(255),
            description TEXT,
            event_date  DATE NOT NULL,
            event_time  VARCHAR(10) NOT NULL,
            file_url    VARCHAR(512),
            file_hash   VARCHAR(64),
            payment_proof_url VARCHAR(512),
            agreement_accepted TINYINT(1) NOT NULL DEFAULT 0,
            status      ENUM('pending','approved','rejected','available','sold') NOT NULL DEFAULT 'pending',
            is_unlisted TINYINT(1) NOT NULL DEFAULT 0,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_idx (event_id),
            KEY seller_idx (seller_id),
            KEY status_idx (status),
            UNIQUE KEY file_hash_unique (file_hash)
        ) {$charset_collate};";

        // ── Orders ────────────────────────────────────────────────────────────
        $orders = $wpdb->prefix . 'ta_orders';
        $sql_orders = "CREATE TABLE {$orders} (
            id                   BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            buyer_id             BIGINT(20) UNSIGNED NOT NULL,
            ticket_id            BIGINT(20) UNSIGNED NOT NULL,
            quantity             INT NOT NULL DEFAULT 1,
            subtotal             DECIMAL(10,2) NOT NULL,
            platform_fee         DECIMAL(10,2) NOT NULL DEFAULT 0,
            total_amount         DECIMAL(10,2) NOT NULL,
            status               ENUM('pending','completed','cancelled','failed') NOT NULL DEFAULT 'pending',
            is_ticket_sent       TINYINT(1) NOT NULL DEFAULT 0,
            razorpay_order_id    VARCHAR(100),
            razorpay_payment_id  VARCHAR(100),
            razorpay_signature   VARCHAR(255),
            created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY buyer_idx (buyer_id),
            KEY ticket_idx (ticket_id),
            KEY rzp_order_idx (razorpay_order_id)
        ) {$charset_collate};";

        // ── KYC Records ───────────────────────────────────────────────────────
        $kyc = $wpdb->prefix . 'ta_kyc_records';
        $sql_kyc = "CREATE TABLE {$kyc} (
            id               BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id          BIGINT(20) UNSIGNED NOT NULL,
            document_type    VARCHAR(100) NOT NULL,
            document_number  VARCHAR(100) NOT NULL,
            file_url         VARCHAR(512),
            back_file_url    VARCHAR(512),
            selfie_file_url  VARCHAR(512),
            status           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            rejection_reason TEXT,
            reviewed_by      BIGINT(20) UNSIGNED,
            created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_idx (user_id)
        ) {$charset_collate};";

        // ── OTP Verifications ─────────────────────────────────────────────────
        $otp = $wpdb->prefix . 'ta_otp_verifications';
        $sql_otp = "CREATE TABLE {$otp} (
            id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email      VARCHAR(255) NOT NULL,
            otp        VARCHAR(10) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email_unique (email)
        ) {$charset_collate};";

        // ── Reviews ───────────────────────────────────────────────────────────
        $reviews = $wpdb->prefix . 'ta_reviews';
        $sql_reviews = "CREATE TABLE {$reviews} (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            reviewer_id BIGINT(20) UNSIGNED NOT NULL,
            seller_id   BIGINT(20) UNSIGNED NOT NULL,
            order_id    BIGINT(20) UNSIGNED NOT NULL,
            rating      TINYINT NOT NULL DEFAULT 5,
            comment     TEXT,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY seller_idx (seller_id),
            KEY reviewer_idx (reviewer_id),
            UNIQUE KEY order_review_unique (order_id)
        ) {$charset_collate};";

        dbDelta( $sql_tickets );
        dbDelta( $sql_orders );
        dbDelta( $sql_kyc );
        dbDelta( $sql_otp );
        dbDelta( $sql_reviews );

        update_option( 'tickeradda_db_version', TA_VERSION );
    }

    // ── Create Secure Upload Directory ────────────────────────────────────────
    private static function create_upload_dir() {
        if ( ! file_exists( TA_UPLOAD_DIR ) ) {
            wp_mkdir_p( TA_UPLOAD_DIR );
        }
        // Prevent direct HTTP access
        $htaccess = TA_UPLOAD_DIR . '.htaccess';
        if ( ! file_exists( $htaccess ) ) {
            file_put_contents( $htaccess, "Options -Indexes\nDeny from all\n" );
        }
        // index.php safety net
        $index = TA_UPLOAD_DIR . 'index.php';
        if ( ! file_exists( $index ) ) {
            file_put_contents( $index, '<?php // Silence is golden.' );
        }
    }

    // ── Auto Import Movies ────────────────────────────────────────────────────
    private static function auto_import_movies() {
        $already_imported = get_option( 'ta_movies_auto_imported' );
        if ( $already_imported ) {
            return; // Already imported, skip
        }
        
        // Check if TA_Movie_Importer class exists and has movies
        if ( class_exists( 'TA_Movie_Importer' ) ) {
            try {
                $importer = new TA_Movie_Importer();
                // Call import via a scheduled action to avoid blocking activation
                wp_schedule_single_event( time() + 2, 'ta_import_movies' );
                update_option( 'ta_movies_auto_imported', 'yes' );
            } catch ( Exception $e ) {
                error_log( '[TickerAdda] Movie auto-import error: ' . $e->getMessage() );
            }
        }
    }
}
