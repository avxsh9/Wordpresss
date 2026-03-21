<?php
/**
 * TA_Admin_Panel — WordPress admin menu pages for TickerAdda.
 * Provides: Dashboard, Tickets (approval queue), Orders, Users, KYC, Settings.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class TA_Admin_Panel {

    public function __construct() {
        add_action( 'admin_menu',    array( $this, 'register_menus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_post_ta_save_settings', array( $this, 'save_settings' ) );
        add_action( 'admin_post_ta_ban_user',       array( $this, 'ban_user_action' ) );
        add_action( 'admin_post_ta_unban_user',     array( $this, 'unban_user_action' ) );
    }

    // ── Register Admin Menu ────────────────────────────────────────────────────
    public function register_menus() {
        add_menu_page(
            'TickerAdda',
            'TickerAdda',
            'manage_options',
            'tickeradda',
            array( $this, 'page_dashboard' ),
            'dashicons-tickets-alt',
            25
        );

        add_submenu_page( 'tickeradda', 'Dashboard',    'Dashboard',    'manage_options', 'tickeradda',               array( $this, 'page_dashboard' ) );
        add_submenu_page( 'tickeradda', 'Tickets',      'Tickets',      'manage_options', 'tickeradda-tickets',       array( $this, 'page_tickets' ) );
        add_submenu_page( 'tickeradda', 'Orders',       'Orders',       'manage_options', 'tickeradda-orders',        array( $this, 'page_orders' ) );
        add_submenu_page( 'tickeradda', 'Send Ticket',  'Send Ticket',  'manage_options', 'tickeradda-send-ticket',   array( $this, 'page_send_ticket' ) );
        add_submenu_page( 'tickeradda', 'Users',        'Users',        'manage_options', 'tickeradda-users',         array( $this, 'page_users' ) );
        add_submenu_page( 'tickeradda', 'KYC',          'KYC',          'manage_options', 'tickeradda-kyc',           array( $this, 'page_kyc' ) );
        add_submenu_page( 'tickeradda', 'Settings',     'Settings',     'manage_options', 'tickeradda-settings',      array( $this, 'page_settings' ) );
    }

    // ── Enqueue admin assets ──────────────────────────────────────────────────
    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'tickeradda' ) === false ) return;
        wp_enqueue_style(
            'ta-admin',
            TA_PLUGIN_URL . 'assets/admin.css',
            array(),
            TA_VERSION
        );
    }

    // ── Dashboard Page ─────────────────────────────────────────────────────────
    public function page_dashboard() {
        global $wpdb;
        $t = TA_Database::tickets_table();
        $o = TA_Database::orders_table();

        $pending   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status = 'pending'" );
        $approved  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status = 'approved'" );
        $sold      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status = 'sold'" );
        $total_ord = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$o} WHERE status = 'completed'" );
        $pending_d = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$o} WHERE status = 'completed' AND (is_ticket_sent = 0 OR is_ticket_sent IS NULL)" );
        $gmv       = (float) $wpdb->get_var( "SELECT SUM(total_amount) FROM {$o} WHERE status = 'completed'" );
        $users     = count_users();
        $buyers    = $users['avail_roles']['ta_buyer']  ?? 0;
        $sellers   = $users['avail_roles']['ta_seller'] ?? 0;
        ?>
        <div class="wrap ta-admin">
            <h1>TickerAdda Dashboard</h1>
            <div class="ta-stats-grid">
                <?php $this->stat_card( 'Pending Tickets', $pending, '#f59e0b', 'dashicons-clock' ); ?>
                <?php $this->stat_card( 'Approved Tickets', $approved, '#10b981', 'dashicons-yes-alt' ); ?>
                <?php $this->stat_card( 'Tickets Sold', $sold, '#3b82f6', 'dashicons-tickets-alt' ); ?>
                <?php $this->stat_card( 'Total Orders', $total_ord, '#8b5cf6', 'dashicons-cart' ); ?>
                <?php $this->stat_card( 'Pending Delivery', $pending_d, '#f43f5e', 'dashicons-email-alt' ); ?>
                <?php $this->stat_card( 'GMV (₹)', number_format( $gmv, 2 ), '#ec4899', 'dashicons-money-alt' ); ?>
                <?php $this->stat_card( 'Total Buyers', $buyers, '#06b6d4', 'dashicons-groups' ); ?>
                <?php $this->stat_card( 'Total Sellers', $sellers, '#f97316', 'dashicons-store' ); ?>
            </div>
            <div class="ta-quick-links">
                <h2>Quick Actions</h2>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=tickeradda-tickets' ) ); ?>" class="button button-primary">
                    Review Pending Tickets (<?php echo esc_html( $pending ); ?>)
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=tickeradda-send-ticket' ) ); ?>" class="button button-primary" style="background:#f43f5e; border-color:#f43f5e;">
                    Pending Ticket Delivery (<?php echo esc_html( $pending_d ); ?>)
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=tickeradda-kyc' ) ); ?>" class="button">
                    Review Pending KYC
                </a>
            </div>
        </div>
        <?php
    }

    // ── Tickets Page ───────────────────────────────────────────────────────────
    public function page_tickets() {
        global $wpdb;
        $table = TA_Database::tickets_table();
        $filter = sanitize_text_field( $_GET['status'] ?? 'pending' );
        $allowed = array( 'pending', 'approved', 'rejected', 'sold' );
        if ( ! in_array( $filter, $allowed, true ) ) $filter = 'pending';

        $tickets = $wpdb->get_results( $wpdb->prepare(
            "SELECT t.*, u.display_name as seller_name, u.user_email as seller_email,
             um.meta_value as seller_phone
             FROM {$table} t
             LEFT JOIN {$wpdb->users} u ON t.seller_id = u.ID
             LEFT JOIN {$wpdb->usermeta} um ON t.seller_id = um.user_id AND um.meta_key = 'ta_phone'
             WHERE t.status = %s ORDER BY t.created_at DESC LIMIT 200",
            $filter
        ) );
        ?>
        <div class="wrap ta-admin">
            <h1>Tickets Management</h1>
            <div class="ta-filter-bar">
                <?php foreach ( $allowed as $s ) : ?>
                <a href="?page=tickeradda-tickets&status=<?php echo esc_attr($s); ?>"
                   class="button <?php echo $filter === $s ? 'button-primary' : ''; ?>">
                    <?php echo esc_html( ucfirst( $s ) ); ?>
                </a>
                <?php endforeach; ?>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th><th>Event</th><th>Type</th><th>Price</th><th>Qty</th>
                        <th>Event Date</th><th>Seller</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( $tickets ) : foreach ( $tickets as $t ) : ?>
                <tr>
                    <td><?php echo esc_html( $t->id ); ?></td>
                    <td><strong><?php echo esc_html( $t->event_name ); ?></strong></td>
                    <td><?php echo esc_html( $t->type ); ?></td>
                    <td>₹<?php echo esc_html( number_format( $t->price, 2 ) ); ?></td>
                    <td><?php echo esc_html( $t->quantity ); ?></td>
                    <td><?php echo esc_html( date( 'd M Y', strtotime( $t->event_date ) ) ); ?></td>
                    <td><?php echo esc_html( $t->seller_name . ' (' . $t->seller_email . ')' ); ?></td>
                    <td>
                        <span class="ta-badge ta-badge-<?php echo esc_attr( $t->status ); ?>">
                            <?php echo esc_html( ucfirst( $t->status ) ); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ( $t->status === 'pending' ) : ?>
                        <button class="button button-small ta-approve-btn" data-id="<?php echo esc_attr($t->id); ?>">✓ Approve</button>
                        <button class="button button-small ta-reject-btn"  data-id="<?php echo esc_attr($t->id); ?>">✕ Reject</button>
                        <?php endif; ?>
                        <?php if ( $t->file_url ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( '_wpnonce', wp_create_nonce( 'wp_rest' ), rest_url( TA_REST_NS . '/tickets/secure-image/' . $t->id ) ) ); ?>"
                           target="_blank" class="button button-small">View Proof</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else : ?>
                <tr><td colspan="9">No tickets found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rest = '<?php echo esc_js( rest_url( TA_REST_NS ) ); ?>';
            const nonce = '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>';

            function updateStatus(id, status) {
                fetch(rest + '/tickets/' + id + '/status', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
                    body: JSON.stringify({ status: status })
                }).then(r => r.json()).then(() => location.reload());
            }

            document.querySelectorAll('.ta-approve-btn').forEach(btn => {
                btn.addEventListener('click', () => updateStatus(btn.dataset.id, 'approved'));
            });
            document.querySelectorAll('.ta-reject-btn').forEach(btn => {
                btn.addEventListener('click', () => updateStatus(btn.dataset.id, 'rejected'));
            });
        });
        </script>
        <?php
    }

    // ── Orders Page ────────────────────────────────────────────────────────────
    public function page_orders() {
        global $wpdb;
        $o = TA_Database::orders_table();
        $t = TA_Database::tickets_table();

        $orders = $wpdb->get_results(
            "SELECT o.*, t.event_name, bu.display_name as buyer_name, bu.user_email as buyer_email
             FROM {$o} o
             LEFT JOIN {$t} t ON o.ticket_id = t.id
             LEFT JOIN {$wpdb->users} bu ON o.buyer_id = bu.ID
             ORDER BY o.created_at DESC LIMIT 500"
        );
        ?>
        <div class="wrap ta-admin">
            <h1>All Orders</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr><th>Order ID</th><th>Event</th><th>Buyer</th><th>Qty</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if ( $orders ) : foreach ( $orders as $o ) : ?>
                <tr>
                    <td>#<?php echo esc_html( str_pad( $o->id, 6, '0', STR_PAD_LEFT ) ); ?></td>
                    <td><?php echo esc_html( $o->event_name ); ?></td>
                    <td><?php echo esc_html( $o->buyer_name ) . '<br><small>' . esc_html( $o->buyer_email ) . '</small>'; ?></td>
                    <td><?php echo esc_html( $o->quantity ); ?></td>
                    <td>₹<?php echo esc_html( number_format( $o->total_amount, 2 ) ); ?></td>
                    <td><span class="ta-badge ta-badge-<?php echo esc_attr( $o->status ); ?>"><?php echo esc_html( ucfirst( $o->status ) ); ?></span></td>
                    <td><?php echo esc_html( date( 'd M Y', strtotime( $o->created_at ) ) ); ?></td>
                    <td>
                        <a href="<?php echo esc_url( add_query_arg( '_wpnonce', wp_create_nonce( 'wp_rest' ), rest_url( TA_REST_NS . '/orders/' . $o->id . '/invoice' ) ) ); ?>" 
                           target="_blank" class="button button-small">Invoice</a>
                    </td>
                </tr>
                <?php endforeach; else : ?>
                <tr><td colspan="7">No orders yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    // ── Send Ticket (Delivery Queue) Page ────────────────────────────────────
    public function page_send_ticket() {
        global $wpdb;
        $o = TA_Database::orders_table();
        $t = TA_Database::tickets_table();

        $orders = $wpdb->get_results(
            "SELECT o.*, t.event_name, t.event_date, t.section, t.seat_number, bu.display_name as buyer_name, bu.user_email as buyer_email
             FROM {$o} o
             LEFT JOIN {$t} t ON o.ticket_id = t.id
             LEFT JOIN {$wpdb->users} bu ON o.buyer_id = bu.ID
             WHERE o.status IN ('completed', 'pending') AND (o.is_ticket_sent = 0 OR o.is_ticket_sent IS NULL)
             ORDER BY o.status DESC, o.created_at ASC"
        );
        ?>
        <div class="wrap ta-admin">
            <h1>Ticket Delivery Queue</h1>
            <p>Verify if payment has been received in your bank/Razorpay account before sending the ticket.</p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Order ID</th><th>Event</th><th>Buyer</th><th>Amount</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( $orders ) : foreach ( $orders as $ord ) : ?>
                <tr>
                    <td>#<?php echo esc_html( str_pad( $ord->id, 6, '0', STR_PAD_LEFT ) ); ?></td>
                    <td>
                        <strong><?php echo esc_html( $ord->event_name ); ?></strong><br>
                        <small><?php echo esc_html( $ord->event_date ); ?> | <?php echo esc_html( $ord->section ); ?> <?php echo esc_html( $ord->seat_number ); ?></small>
                    </td>
                    <td><?php echo esc_html( $ord->buyer_name ); ?><br><small><?php echo esc_html( $ord->buyer_email ); ?></small></td>
                    <td>₹<?php echo esc_html( number_format( $ord->total_amount, 2 ) ); ?></td>
                    <td>
                        <span class="ta-badge ta-badge-<?php echo esc_attr( $ord->status ); ?>">
                            <?php echo esc_html( ucfirst( $ord->status ) === 'Completed' ? 'Paid' : 'Unpaid' ); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ( $ord->status === 'completed' ) : ?>
                        <button class="button button-primary ta-send-ticket-btn" data-id="<?php echo esc_attr($ord->id); ?>">
                            <span class="dashicons dashicons-email-alt" style="vertical-align: middle; margin-top: -2px;"></span> Send Ticket
                        </button>
                        <?php else : ?>
                        <button class="button ta-mark-paid-btn" data-id="<?php echo esc_attr($ord->id); ?>" style="background:#10b981; color:#fff; border-color:#10b981;">
                            <span class="dashicons dashicons-yes-alt" style="vertical-align: middle;"></span> Mark Paid
                        </button>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url( add_query_arg( '_wpnonce', wp_create_nonce( 'wp_rest' ), rest_url( TA_REST_NS . '/orders/' . $ord->id . '/invoice' ) ) ); ?>" 
                           target="_blank" class="button button-small">Invoice</a>
                    </td>
                </tr>
                <?php endforeach; else : ?>
                <tr><td colspan="6">No tickets pending delivery.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rest  = '<?php echo esc_js( rest_url( TA_REST_NS ) ); ?>';
            const nonce = '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>';

            document.querySelectorAll('.ta-send-ticket-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = btn.dataset.id;
                    if (!confirm('Have you confirmed the payment for Order #' + id.padStart(6, '0') + '?')) return;
                    
                    btn.disabled = true;
                    btn.innerHTML = '<span class="dashicons dashicons-update spin"></span> Sending...';

                    fetch(rest + '/admin/orders/' + id + '/send-ticket', {
                        method: 'POST',
                        headers: { 'X-WP-Nonce': nonce }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.msg) {
                            alert(data.msg);
                            location.reload();
                        } else {
                            alert('Error: ' + (data.message || 'Failed to send ticket.'));
                            btn.disabled = false;
                            btn.innerHTML = '<span class="dashicons dashicons-email-alt"></span> Send Ticket';
                        }
                    })
                    .catch(e => {
                        console.error(e);
                        alert('Server error occurred.');
                        btn.disabled = false;
                        btn.innerHTML = '<span class="dashicons dashicons-email-alt"></span> Send Ticket';
                    });
                });
            });

            document.querySelectorAll('.ta-mark-paid-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = btn.dataset.id;
                    if (!confirm('Mark Order #' + id.padStart(6, '0') + ' as PAID manually? This will mark the ticket as sold.' )) return;
                    
                    btn.disabled = true;
                    btn.innerHTML = '<span class="dashicons dashicons-update spin"></span>...';

                    fetch(rest + '/admin/orders/' + id + '/mark-paid', {
                        method: 'POST',
                        headers: { 'X-WP-Nonce': nonce }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (data.message || 'Failed to update order.'));
                            btn.disabled = false;
                            btn.innerHTML = 'Mark Paid';
                        }
                    });
                });
            });
        });
        </script>
        <style>
            .spin { animation: ta-spin 2s infinite linear; }
            @keyframes ta-spin { from { transform: rotate(0deg); } to { transform: rotate(359deg); } }
        </style>
        <?php
    }

    // ── Users Page ─────────────────────────────────────────────────────────────
    public function page_users() {
        $role_filter = sanitize_text_field( $_GET['role'] ?? '' );
        $args = array( 'number' => 200, 'orderby' => 'registered', 'order' => 'DESC' );
        if ( $role_filter ) $args['role'] = $role_filter;
        $users = get_users( $args );
        ?>
        <div class="wrap ta-admin">
            <h1>Users Management</h1>
            <div class="ta-filter-bar">
                <a href="?page=tickeradda-users" class="button <?php echo ! $role_filter ? 'button-primary' : ''; ?>">All</a>
                <a href="?page=tickeradda-users&role=ta_buyer"  class="button <?php echo $role_filter === 'ta_buyer'  ? 'button-primary' : ''; ?>">Buyers</a>
                <a href="?page=tickeradda-users&role=ta_seller" class="button <?php echo $role_filter === 'ta_seller' ? 'button-primary' : ''; ?>">Sellers</a>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>KYC</th><th>Ban</th><th>Since</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ( $users as $u ) :
                    $phone = get_user_meta( $u->ID, 'ta_phone', true );
                    $kyc   = get_user_meta( $u->ID, 'ta_kyc_status', true ) ?: 'not_submitted';
                    $ban   = get_user_meta( $u->ID, 'ta_ban_status', true ) ?: 'none';
                    $roles = implode( ', ', $u->roles );
                ?>
                <tr>
                    <td><?php echo esc_html( $u->display_name ); ?></td>
                    <td><?php echo esc_html( $u->user_email ); ?></td>
                    <td><?php echo esc_html( $phone ); ?></td>
                    <td><?php echo esc_html( $roles ); ?></td>
                    <td><span class="ta-badge ta-badge-<?php echo esc_attr( $kyc ); ?>"><?php echo esc_html( $kyc ); ?></span></td>
                    <td><span class="ta-badge <?php echo $ban !== 'none' ? 'ta-badge-rejected' : 'ta-badge-approved'; ?>"><?php echo esc_html( $ban ); ?></span></td>
                    <td><?php echo esc_html( date( 'd M Y', strtotime( $u->user_registered ) ) ); ?></td>
                    <td>
                        <?php if ( $ban === 'none' ) : ?>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                            <?php wp_nonce_field( 'ta_ban_user' ); ?>
                            <input type="hidden" name="action"  value="ta_ban_user">
                            <input type="hidden" name="user_id" value="<?php echo esc_attr( $u->ID ); ?>">
                            <input type="text"   name="reason"  placeholder="Reason" required style="width:100px">
                            <select name="ban_type">
                                <option value="temporary">Temp</option>
                                <option value="permanent">Perm</option>
                            </select>
                            <button class="button button-small">Ban</button>
                        </form>
                        <?php else : ?>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                            <?php wp_nonce_field( 'ta_unban_user' ); ?>
                            <input type="hidden" name="action"  value="ta_unban_user">
                            <input type="hidden" name="user_id" value="<?php echo esc_attr( $u->ID ); ?>">
                            <button class="button button-small">Unban</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    // ── KYC Page ───────────────────────────────────────────────────────────────
    public function page_kyc() {
        global $wpdb;
        $table = TA_Database::kyc_table();
        $status_filter = sanitize_text_field( $_GET['status'] ?? 'pending' );

        $records = $wpdb->get_results( $wpdb->prepare(
            "SELECT k.*, u.display_name as user_name, u.user_email as user_email,
             um.meta_value as user_phone
             FROM {$table} k
             LEFT JOIN {$wpdb->users} u ON k.user_id = u.ID
             LEFT JOIN {$wpdb->usermeta} um ON k.user_id = um.user_id AND um.meta_key = 'ta_phone'
             WHERE k.status = %s ORDER BY k.created_at ASC LIMIT 200",
            $status_filter
        ) );
        ?>
        <div class="wrap ta-admin">
            <h1>KYC Review</h1>
            <div class="ta-filter-bar">
                <?php foreach ( array( 'pending', 'approved', 'rejected' ) as $s ) : ?>
                <a href="?page=tickeradda-kyc&status=<?php echo esc_attr($s); ?>"
                   class="button <?php echo $status_filter === $s ? 'button-primary' : ''; ?>">
                   <?php echo esc_html( ucfirst($s) ); ?>
                </a>
                <?php endforeach; ?>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr><th>ID</th><th>User</th><th>Phone</th><th>Doc Type</th><th>Doc Number</th><th>Submitted</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if ( $records ) : foreach ( $records as $r ) : ?>
                    <tr>
                        <td><?php echo esc_html( $r->id ); ?></td>
                        <td><?php echo esc_html( $r->user_name ) . '<br><small>' . esc_html( $r->user_email ) . '</small>'; ?></td>
                        <td><?php echo esc_html( $r->user_phone ); ?></td>
                        <td><?php echo esc_html( $r->document_type ); ?></td>
                        <td><?php echo esc_html( $r->document_number ); ?></td>
                        <td><?php echo esc_html( date( 'd M Y', strtotime( $r->created_at ) ) ); ?></td>
                        <td>
                            <?php if ( $r->file_url ) : ?>
                            <a href="<?php echo esc_url( rest_url( TA_REST_NS . '/admin/kyc/' . $r->id . '/file?type=front&_wpnonce=' . wp_create_nonce( 'wp_rest' ) ) ); ?>"
                               target="_blank" class="button button-small" style="margin-bottom:4px;">Front</a>
                            <?php endif; ?>
                            <?php if ( ! empty( $r->back_file_url ) ) : ?>
                            <a href="<?php echo esc_url( rest_url( TA_REST_NS . '/admin/kyc/' . $r->id . '/file?type=back&_wpnonce=' . wp_create_nonce( 'wp_rest' ) ) ); ?>"
                               target="_blank" class="button button-small" style="margin-bottom:4px;">Back</a>
                            <?php endif; ?>
                            <?php if ( ! empty( $r->selfie_file_url ) ) : ?>
                            <a href="<?php echo esc_url( rest_url( TA_REST_NS . '/admin/kyc/' . $r->id . '/file?type=selfie&_wpnonce=' . wp_create_nonce( 'wp_rest' ) ) ); ?>"
                               target="_blank" class="button button-small" style="margin-bottom:4px;">Selfie</a>
                            <?php endif; ?>
                            <?php if ( $status_filter === 'pending' ) : ?>
                            <button class="button button-small ta-kyc-approve" data-id="<?php echo esc_attr($r->id); ?>">✓ Approve</button>
                            <button class="button button-small ta-kyc-reject"  data-id="<?php echo esc_attr($r->id); ?>">✕ Reject</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="7">No KYC submissions found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rest  = '<?php echo esc_js( rest_url( TA_REST_NS ) ); ?>';
            const nonce = '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>';

            function reviewKyc(id, status, reason = '') {
                fetch(rest + '/admin/kyc/' + id + '/review', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
                    body: JSON.stringify({ status, rejectionReason: reason })
                }).then(r => r.json()).then(() => location.reload());
            }

            document.querySelectorAll('.ta-kyc-approve').forEach(btn =>
                btn.addEventListener('click', () => reviewKyc(btn.dataset.id, 'approved'))
            );
            document.querySelectorAll('.ta-kyc-reject').forEach(btn =>
                btn.addEventListener('click', () => {
                    const reason = prompt('Enter rejection reason:');
                    if (reason !== null) reviewKyc(btn.dataset.id, 'rejected', reason);
                })
            );
        });
        </script>
        <?php
    }

    // ── Settings Page ──────────────────────────────────────────────────────────
    public function page_settings() {
        $saved = isset( $_GET['saved'] ) ? true : false;
        ?>
        <div class="wrap ta-admin">
            <h1>TickerAdda Settings</h1>
            <?php if ( $saved ) : ?><div class="notice notice-success"><p>Settings saved!</p></div><?php endif; ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'ta_save_settings' ); ?>
                <input type="hidden" name="action" value="ta_save_settings">
                <table class="form-table">
                    <tr>
                        <th>Razorpay Key ID</th>
                        <td><input type="text" name="ta_razorpay_key_id" value="<?php echo esc_attr( get_option('ta_razorpay_key_id','') ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>Razorpay Key Secret</th>
                        <td><input type="password" name="ta_razorpay_key_secret" value="<?php echo esc_attr( get_option('ta_razorpay_key_secret','') ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>Platform Fee (%)</th>
                        <td><input type="number" step="0.1" name="ta_platform_fee" value="<?php echo esc_attr( get_option('ta_platform_fee','5') ); ?>" class="small-text"> %</td>
                    </tr>
                    <tr>
                        <th>Support Email</th>
                        <td><input type="email" name="ta_support_email" value="<?php echo esc_attr( get_option('ta_support_email','support@tickeradda.in') ); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" value="Save Settings" class="button button-primary"></p>
            </form>
            <hr>
            <h2>SMTP Setup Instructions</h2>
            <div class="notice notice-info" style="margin:0;">
                <p>Install the free <strong><a href="https://wordpress.org/plugins/wp-mail-smtp/" target="_blank">WP Mail SMTP</a></strong> plugin to configure email sending.</p>
                <p>Then go to <em>Settings → WP Mail SMTP</em> and configure with your Gmail App Password or Brevo/SendGrid SMTP credentials.</p>
            </div>
        </div>
        <?php
    }

    // ── Save Settings Action ───────────────────────────────────────────────────
    public function save_settings() {
        if ( ! check_admin_referer( 'ta_save_settings' ) || ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }
        update_option( 'ta_razorpay_key_id',     sanitize_text_field( $_POST['ta_razorpay_key_id'] ?? '' ) );
        update_option( 'ta_razorpay_key_secret',  sanitize_text_field( $_POST['ta_razorpay_key_secret'] ?? '' ) );
        update_option( 'ta_platform_fee',         (float) ( $_POST['ta_platform_fee'] ?? 5 ) );
        update_option( 'ta_support_email',         sanitize_email( $_POST['ta_support_email'] ?? '' ) );
        wp_redirect( admin_url( 'admin.php?page=tickeradda-settings&saved=1' ) );
        exit;
    }

    // ── Ban/Unban Actions ──────────────────────────────────────────────────────
    public function ban_user_action() {
        if ( ! check_admin_referer( 'ta_ban_user' ) || ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        $user_id   = absint( $_POST['user_id'] );
        $reason    = sanitize_text_field( $_POST['reason'] ?? '' );
        $ban_type  = in_array( $_POST['ban_type'], array('temporary','permanent'), true ) ? $_POST['ban_type'] : 'temporary';
        update_user_meta( $user_id, 'ta_ban_status', $ban_type );
        update_user_meta( $user_id, 'ta_ban_reason', $reason );
        wp_redirect( admin_url( 'admin.php?page=tickeradda-users' ) );
        exit;
    }

    public function unban_user_action() {
        if ( ! check_admin_referer( 'ta_unban_user' ) || ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        $user_id = absint( $_POST['user_id'] );
        update_user_meta( $user_id, 'ta_ban_status', 'none' );
        delete_user_meta( $user_id, 'ta_ban_reason' );
        wp_redirect( admin_url( 'admin.php?page=tickeradda-users' ) );
        exit;
    }

    // ── Stat Card Helper ──────────────────────────────────────────────────────
    private function stat_card( $label, $value, $color, $icon ) {
        printf(
            '<div class="ta-stat-card" style="border-top:4px solid %s;">
                <span class="dashicons %s" style="color:%s;font-size:28px;"></span>
                <div class="ta-stat-value">%s</div>
                <div class="ta-stat-label">%s</div>
            </div>',
            esc_attr( $color ),
            esc_attr( $icon ),
            esc_attr( $color ),
            esc_html( $value ),
            esc_html( $label )
        );
    }
}
