<?php
/**
 * Template Name: Dashboard
 */
get_header();
?>

<style>
/* Extra styles for SPA consistency specific to this file if not in admin.css yet */
        .admin-section {
            animation: fadeIn 0.3s ease;
        }
        /* Checkbox/View Button Styling for KYC */
        .kyc-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            display: grid;
            grid-template-columns: 1.5fr 2fr 150px;
            /* Info | Docs | Actions */
            gap: 24px;
            align-items: center;
        }
        .docs-grid {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .doc-item {
            background: rgba(0, 0, 0, 0.3);
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .doc-label {
            font-size: 0.8rem;
            color: #aaa;
            text-transform: uppercase;
        }
        .btn-doc-view {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            border: none;
            padding: 4px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: 0.2s;
        }
        .btn-doc-view:hover {
            background: #3b82f6;
            color: white;
        }
        .kyc-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn-approve,
        .btn-reject {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-approve {
            background: #10b981;
            color: white;
        }
        .btn-reject {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        .btn-reject:hover {
            background: #ef4444;
            color: white;
        }
        @media (max-width: 1024px) {
            .kyc-card {
                grid-template-columns: 1fr;
            }
            .kyc-actions {
                flex-direction: row;
            }
        }
        /* User Table Styles Fixes */
        .user-avatar-sm {
            width: 28px;
            height: 28px;
            background: #3b82f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            color: white;
        }
        .btn-xs-admin {
            margin-left: 8px;
            padding: 2px 8px;
            font-size: 10px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: #aaa;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-xs-admin:hover {
            color: white;
            background: rgba(255, 255, 255, 0.2);
        }
        /* PDF Preview */
        .media-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
        }
        .pdf-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #1a1a1a;
            color: #ef4444;
        }
        /* Unified Card Status Badge */
        .status-badge {
            grid-column: 1/-1;
            text-align: center;
            padding: 12px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            color: #888;
            font-size: 0.9rem;
        }
        /* Layout Fixes */
        .admin-content {
            padding: 30px 40px;
        }
        .header-title h1 {
            margin-bottom: 5px;
        }
        .table-container {
            background: var(--admin-card-bg);
            border-radius: 12px;
            border: 1px solid var(--admin-border);
            overflow: hidden;
        }
        .table th {
            background: rgba(0, 0, 0, 0.3);
            color: #888;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            padding: 16px 20px;
        }
        .table td {
            border-bottom: 1px solid var(--admin-border);
            padding: 16px 20px;
            font-size: 0.95rem;
        }
</style>

<main id="main">
<div class="admin-container">
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand" style="display: flex; align-items: center;">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/public/images/logo.png'); ?>" alt="TickerAdda" style="height: 35px; margin-right: 10px;">
                    <span
                        style="font-size: 10px; background: #333; padding: 2px 6px; border-radius: 4px; color: #fff; letter-spacing: 1px;">ADMIN</span>
                </div>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="#" data-section="overview" class="active">
                        <i class="fas fa-home"></i> <span>Overview</span>
                    </a>
                </li>
                <li>
                    <a href="#" data-section="approvals">
                        <i class="fas fa-check-circle"></i> <span>Ticket Approvals</span>
                    </a>
                </li>
                <li>
                    <a href="#" data-section="kyc">
                        <i class="fas fa-id-card"></i> <span>KYC Requests</span>
                    </a>
                </li>
                <li>
                    <a href="#" data-section="users">
                        <i class="fas fa-users"></i> <span>User Management</span>
                    </a>
                </li>
                <li style="margin-top: auto;">
                    <a href="#" onclick="logout()">
                        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                    </a>
                </li>
            </ul>
            <div
                style="padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 20px; display: flex; align-items: center; gap: 10px;">
                <div
                    style="width: 32px; height: 32px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                    A</div>
                <div>
                    <div style="font-size: 14px; font-weight: 600; color: white;">Avinash</div>
                    <div style="font-size: 11px; color: #888;">Super Admin</div>
                </div>
            </div>
        </nav>
        <main class="admin-content">
            <div id="section-overview" class="admin-section">
                <div class="page-header">
                    <div class="header-title">
                        <h1>Admin Overview</h1>
                        <p>Platform status and quick actions.</p>
                    </div>
                </div>
                <div class="grid grid-3">
                    <div class="stat-card card">
                        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i
                                class="fas fa-clock"></i></div>
                        <div class="stat-info">
                            <h3 id="statPending">0</h3>
                            <p>Pending Review</p>
                        </div>
                    </div>
                    <div class="stat-card card">
                        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i
                                class="fas fa-check-circle"></i></div>
                        <div class="stat-info">
                            <h3 id="statApproved">0</h3>
                            <p>Approved Today</p>
                        </div>
                    </div>
                    <div class="stat-card card">
                        <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i
                                class="fas fa-coins"></i></div>
                        <div class="stat-info">
                            <h3 id="statTotalVal">₹0</h3>
                            <p>Total Value</p>
                        </div>
                    </div>
                </div>
                <h3 style="margin: 40px 0 20px;">Quick Actions</h3>
                <div class="grid grid-3">
                    <div onclick="showSection('approvals')" class="card"
                        style="padding: 30px; display: flex; align-items: center; gap: 20px; cursor: pointer;">
                        <i class="fas fa-ticket-alt" style="font-size: 2rem; color: #10b981;"></i>
                        <div>
                            <h3 style="margin:0;">Approve Tickets</h3>
                            <p style="margin:5px 0 0; color:#888;">Verify new listings</p>
                        </div>
                    </div>
                    <div onclick="showSection('kyc')" class="card"
                        style="padding: 30px; display: flex; align-items: center; gap: 20px; cursor: pointer;">
                        <i class="fas fa-id-card" style="font-size: 2rem; color: #f59e0b;"></i>
                        <div>
                            <h3 style="margin:0;">KYC Pending</h3>
                            <p style="margin:5px 0 0; color:#888;">Verify seller docs</p>
                        </div>
                    </div>
                    <div onclick="showSection('users')" class="card"
                        style="padding: 30px; display: flex; align-items: center; gap: 20px; cursor: pointer;">
                        <i class="fas fa-users" style="font-size: 2rem; color: #3b82f6;"></i>
                        <div>
                            <h3 style="margin:0;">Manage Users</h3>
                            <p style="margin:5px 0 0; color:#888;">View user database</p>
                        </div>
                    </div>
                </div>
            </div>
            <div id="section-approvals" class="admin-section" style="display: none;">
                <div class="page-header">
                    <div class="header-title">
                        <h1>Ticket Approvals</h1>
                        <p>Review and verify ticket listings.</p>
                    </div>
                    <button onclick="loadTickets(currentTicketsType)" class="btn btn-outline btn-sm"><i
                            class="fas fa-sync-alt"></i> Refresh</button>
                </div>
                <div class="custom-tabs">
                    <button id="btnPending" class="tab-btn active" onclick="switchTicketTab('pending')">Pending</button>
                    <button id="btnHistory" class="tab-btn" onclick="switchTicketTab('history')">History</button>
                </div>
                <div id="ticketsContainer" class="tickets-grid">
                </div>
            </div>
            <div id="section-kyc" class="admin-section" style="display: none;">
                <div class="page-header">
                    <div class="header-title">
                        <h1>KYC Verification</h1>
                        <p>Verify seller identities to enable selling.</p>
                    </div>
                    <button onclick="loadKycRequests()" class="btn btn-outline btn-sm"><i class="fas fa-sync-alt"></i>
                        Refresh</button>
                </div>
                <div id="kycList">
                </div>
            </div>
            <div id="section-users" class="admin-section" style="display: none;">
                <div class="page-header">
                    <div class="header-title">
                        <h1>User Management</h1>
                        <p>System users and roles.</p>
                    </div>
                    <button onclick="loadUsers()" class="btn btn-outline btn-sm"><i class="fas fa-sync-alt"></i>
                        Refresh</button>
                </div>
                <div class="table-container">
                    <table class="table" id="usersTable">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <div id="imageModal"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div style="position: relative; max-width: 90%; max-height: 90%;">
            <button onclick="document.getElementById('imageModal').style.display='none'"
                style="position: absolute; top: -40px; right: 0; background: none; border: none; color: white; font-size: 24px; cursor: pointer;">&times;
                Close</button>
            <img id="modalImage" src="" alt="Proof"
                style="max-width: 100%; max-height: 80vh; border-radius: 8px; box-shadow: 0 0 20px rgba(0,0,0,0.5);">
        </div>
    </div>
    <div id="banModal"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div
            style="background: var(--admin-card-bg); width: 100%; max-width: 400px; padding: 25px; border-radius: 12px; border: 1px solid var(--admin-border);">
            <h3 style="margin-bottom: 20px;">Manage User Suspension</h3>
            <input type="hidden" id="banUserId">
            <div style="margin-bottom: 15px;">
                <label style="color: #aaa; margin-bottom: 5px; display: block;">Action Type</label>
                <select id="banType"
                    style="width: 100%; padding: 10px; background: rgba(0,0,0,0.3); border: 1px solid var(--admin-border); color: white; border-radius: 6px;">
                    <option value="temporary">Temporary Block</option>
                    <option value="permanent">Permanent Ban</option>
                    <option value="none">Remove Ban</option>
                </select>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="color: #aaa; margin-bottom: 5px; display: block;">Reason (Required for Ban)</label>
                <textarea id="banReason" placeholder="e.g. Violation of Policy..."
                    style="width: 100%; height: 80px; padding: 10px; background: rgba(0,0,0,0.3); border: 1px solid var(--admin-border); color: white; border-radius: 6px; resize: none;"></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="document.getElementById('banModal').style.display='none'"
                    class="btn btn-outline btn-sm">Cancel</button>
                <button onclick="submitBanUser()" class="btn btn-primary btn-sm">Submit</button>
            </div>
        </div>
    </div>
    <div id="passwordResetModal"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div
            style="background: var(--admin-card-bg); width: 100%; max-width: 400px; padding: 25px; border-radius: 12px; border: 1px solid var(--admin-border);">
            <h3 style="margin-bottom: 20px;">Reset User Password</h3>
            <input type="hidden" id="resetUserId">
            <p id="resetUserName" style="color: #aaa; margin-bottom: 15px; font-size: 0.9rem;"></p>
            <div style="margin-bottom: 20px;">
                <label style="color: #aaa; margin-bottom: 5px; display: block;">New Password</label>
                <input type="text" id="adminNewPassword" placeholder="Enter new password"
                    style="width: 100%; padding: 10px; background: rgba(0,0,0,0.3); border: 1px solid var(--admin-border); color: white; border-radius: 6px;">
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="document.getElementById('passwordResetModal').style.display='none'"
                    class="btn btn-outline btn-sm">Cancel</button>
                <button onclick="submitAdminPasswordReset()" class="btn btn-primary btn-sm">Reset Password</button>
            </div>
        </div>
    </div>
</main>
<?php get_footer(); ?>
