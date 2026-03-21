const token = TA.nonce;
if (!TA.loggedIn) window.location.href = TA.homeUrl + "login/";
let currentTicketsType = 'pending';
document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    setupNavigation();
});
function setupNavigation() {
    const navLinks = document.querySelectorAll('.sidebar-menu a[data-section]');
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
            const sectionId = link.getAttribute('data-section');
            showSection(sectionId);
        });
    });
}
function showSection(sectionId) {
    document.querySelectorAll('.admin-section').forEach(el => el.style.display = 'none');
    const target = document.getElementById(`section-${sectionId}`);
    if (target) {
        target.style.display = 'block';
        if (sectionId === 'approvals') loadTickets(currentTicketsType);
        if (sectionId === 'users') loadUsers();
        if (sectionId === 'kyc') loadKycRequests();
        if (sectionId === 'overview') loadStats();
    }
}
async function loadStats() {
    try {
        const res = await fetch(TA.restUrl + '/tickets/stats', { headers: { 'X-WP-Nonce': TA.nonce } });
        if (res.ok) {
            const stats = await res.json();
            updateStatElement('statPending', stats.pending || 0);
            updateStatElement('statApproved', stats.approvedToday || 0);
            updateStatElement('statTotalVal', '₹' + (stats.totalValue || 0).toLocaleString());
        }
    } catch (err) {
        console.error('Stats Error', err);
    }
}
function updateStatElement(id, value) {
    const el = document.getElementById(id);
    if (el) el.innerText = value;
}
async function switchTicketTab(type) {
    currentTicketsType = type;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    const activeBtnId = type === 'pending' ? 'btnPending' : 'btnHistory';
    const btn = document.getElementById(activeBtnId);
    if (btn) btn.classList.add('active'); 
    loadTickets(type);
}
async function loadTickets(type) {
    const container = document.getElementById('ticketsContainer');
    if (!container) return; 
    container.innerHTML = `
        <div class="ticket-card" style="height: 350px;"><div class="skeleton" style="width: 100%; height: 100%; opacity: 0.1;"></div></div>
        <div class="ticket-card" style="height: 350px;"><div class="skeleton" style="width: 100%; height: 100%; opacity: 0.1;"></div></div>
    `;
    try {
        const endpoint = type === 'pending' ? TA.restUrl + '/tickets/pending' : TA.restUrl + '/tickets/history';
        const res = await fetch(endpoint, { headers: { 'X-WP-Nonce': TA.nonce } });
        const tickets = await res.json();
        if (!Array.isArray(tickets) || tickets.length === 0) {
            container.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: #888;">
                    <i class="fas fa-clipboard-check" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.3;"></i>
                    <h3>No tickets found</h3>
                    <p>There are no tickets in this category right now.</p>
                </div>
            `;
            return;
        }
        container.innerHTML = tickets.map(ticket => {
            const startStatus = ticket.status || 'pending';
            const status = startStatus.toLowerCase().trim();
            const statusClass = status === 'pending' ? 'status-pending' : (status === 'approved' ? 'status-approved' : 'status-rejected');
            let imageSrc = 'https://placehold.co/600x400/222/888?text=No+Proof';
            let isPdf = false;
            let secureAttribute = '';
            if (ticket.fileUrl) {
                secureAttribute = `data-secure-src="/api/tickets/secure-image/${ticket._id}"`;
                imageSrc = 'https://placehold.co/600x400/222/888?text=Loading...';
            }
            let mediaHtml = isPdf ?
                `<div onclick="window.open('/uploads/${filename}', '_blank')" class="media-preview pdf-preview">
                    <i class="fas fa-file-pdf"></i><span>View PDF</span>
                  </div>` :
                `<img src="${imageSrc}" ${secureAttribute} onclick="openImageModal(this.getAttribute('data-secure-src') || this.src)" class="media-preview" onerror="this.onerror=null; this.src='https://placehold.co/600x400/333/ef4444?text=Image+Error';">`;
            let actionsHtml = status === 'pending' ? `
                 <button class="btn-action btn-approve" onclick="processTicket('${ticket._id}', 'approve')"><i class="fas fa-check"></i> Approve</button>
                 <button class="btn-action btn-reject" onclick="processTicket('${ticket._id}', 'reject')"><i class="fas fa-times"></i> Reject</button>
             ` : `
                 <div class="status-badge"><i class="fas ${status === 'approved' ? 'fa-check-circle' : 'fa-times-circle'}"></i> ${status.toUpperCase()}</div>
                 ${status === 'sold' ? `<button class="btn-action btn-reject" style="margin-top:10px; width:100%; border-color:#f59e0b; color:#f59e0b;" onclick="processTicket('${ticket._id}', 'unsell')"><i class="fas fa-undo"></i> Mark Unsold</button>` : ''}
                 <!-- Unlist / Relist Toggle -->
                 <button class="btn-action" 
                    style="margin-top:10px; width:100%; border-color:${ticket.isUnlisted ? '#10b981' : '#6b7280'}; color:${ticket.isUnlisted ? '#10b981' : '#ccc'};" 
                    onclick="processTicket('${ticket._id}', 'toggleListing', ${ticket.isUnlisted})">
                    <i class="fas ${ticket.isUnlisted ? 'fa-eye' : 'fa-eye-slash'}"></i> ${ticket.isUnlisted ? 'Relist to Market' : 'Unlist from Market'}
                 </button>
             `;
            return `
                <div class="ticket-card" style="opacity: ${ticket.isUnlisted ? '0.6' : '1'}; border: ${ticket.isUnlisted ? '1px dashed #555' : ''}">
                    <div style="height: 180px; position: relative; background: #000;">
                        ${mediaHtml}
                        <div class="ticket-status-bar ${statusClass}" style="position: absolute; bottom: 0; left: 0; right: 0;"></div>
                        ${ticket.isUnlisted ? '<div style="position:absolute; top:10px; right:10px; background:rgba(0,0,0,0.8); color:#ccc; padding:4px 8px; font-size:10px; border-radius:4px;"><i class="fas fa-eye-slash"></i> UNLISTED</div>' : ''}
                    </div>
                    <div class="card-body">
                        <div class="card-header">
                            <div>
                                <div class="event-name" title="${ticket.event}">${ticket.event}</div>
                                <div class="ticket-id">ID: ${ticket._id.substr(-6).toUpperCase()}</div>
                            </div>
                            <div class="card-price">₹${ticket.price}</div>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-calendar-alt"></i> ${new Date(ticket.eventDate).toLocaleDateString()} 
                            <i class="fas fa-clock" style="margin-left: 10px;"></i> ${ticket.eventTime}
                        </div>
                        <div class="info-row"><i class="fas fa-layer-group"></i> ${ticket.section || 'N/A'} <i class="fas fa-chair" style="margin-left:8px;"></i> ${ticket.seat || 'Gen'}</div>
                        <div class="seller-info">
                            <div class="seller-avatar">${(ticket.seller?.name || '?').charAt(0).toUpperCase()}</div>
                            <div class="seller-details">
                                <div class="seller-name">${ticket.seller?.name || 'Unknown'}</div>
                                <div class="seller-email">${ticket.seller?.email || ''}</div>
                            </div>
                        </div>
                        <div class="card-actions">${actionsHtml}</div>
                    </div>
                </div>
             `;
        }).join('');
        document.querySelectorAll('img[data-secure-src]').forEach(img => {
            const url = img.getAttribute('data-secure-src');
            fetch(url, { headers: { 'x-auth-token': token } })
                .then(r => {
                    if (r.ok) return r.blob();
                    throw new Error('Unauthorized');
                })
                .then(blob => {
                    img.src = URL.createObjectURL(blob);
                    img.setAttribute('data-blob-src', img.src);
                    img.onclick = () => openImageModal(img.src);
                })
                .catch(() => {
                    img.src = 'https://placehold.co/600x400/333/ef4444?text=Access+Denied';
                });
        });
    } catch (err) {
        console.error(err);
        container.innerHTML = '<p class="error-msg">Error loading tickets.</p>';
    }
}
async function processTicket(id, action, currentUnlistedState = false) {
    let title, text, icon, confirmColor, confirmText, endpoint, payload, method;
    method = 'PUT';
    if (action === 'unsell') {
        title = 'Mark as Unsold?';
        text = 'This will approve the ticket again and delete the order.';
        icon = 'warning';
        confirmColor = '#f59e0b';
        confirmText = 'Yes, Unsell';
        endpoint = `/api/admin/tickets/${id}/unsell`;
        payload = {};
    } else if (action === 'toggleListing') {
        const isRelisting = currentUnlistedState; 
        title = isRelisting ? 'Relist Ticket?' : 'Unlist Ticket?';
        text = isRelisting ? 'This ticket will reappear in search results.' : 'This ticket will be hidden from the marketplace.';
        icon = isRelisting ? 'question' : 'warning';
        confirmColor = isRelisting ? '#10b981' : '#6b7280';
        confirmText = isRelisting ? 'Yes, Relist' : 'Yes, Unlist';
        endpoint = `/api/admin/tickets/${id}/toggle-listing`;
        payload = {};
    } else {
        title = action === 'approve' ? 'Approve Ticket?' : 'Reject Ticket?';
        text = "This will update the ticket status.";
        icon = action === 'approve' ? 'question' : 'warning';
        confirmColor = action === 'approve' ? '#10b981' : '#ef4444';
        confirmText = `Yes, ${action}`;
        endpoint = `/api/tickets/${id}/status`;
        payload = { status: action === 'approve' ? 'approved' : 'rejected' };
    }
    const result = await Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        confirmButtonText: confirmText,
        background: '#18181b', color: '#fff'
    });
    if (result.isConfirmed) {
        try {
            const res = await fetch(endpoint, {
                method: method,
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': TA.nonce },
                body: JSON.stringify(payload)
            });
            if (res.ok) {
                Swal.fire({ title: 'Success', icon: 'success', timer: 1000, showConfirmButton: false, background: '#18181b', color: '#fff' });
                loadTickets(currentTicketsType);
            } else {
                Swal.fire('Error', 'Failed to update', 'error');
            }
        } catch (err) {
            console.error(err);
        }
    }
}
let currentKycType = 'pending';
async function loadKycRequests(type = 'pending') {
    currentKycType = type;
    const kycList = document.getElementById('kycList');
    if (!kycList) return;
    const headerId = 'kyc-section-header';
    if (!document.getElementById(headerId)) {
        const header = document.createElement('div');
        header.id = headerId;
        header.innerHTML = `
            <div class="tabs-container" style="margin-bottom: 20px;">
                <button id="btnKycPending" class="tab-btn active" onclick="switchKycTab('pending')">Pending Requests</button>
                <button id="btnKycHistory" class="tab-btn" onclick="switchKycTab('history')">History (Approved/Rejected)</button>
            </div>
        `;
        kycList.parentNode.insertBefore(header, kycList);
    }
    document.querySelectorAll('#kyc-section-header .tab-btn').forEach(btn => btn.classList.remove('active'));
    const activeBtn = type === 'pending' ? document.getElementById('btnKycPending') : document.getElementById('btnKycHistory');
    if (activeBtn) activeBtn.classList.add('active');
    kycList.innerHTML = `<div class="loading-state"><i class="fas fa-spinner fa-spin"></i><p>Loading ${type} requests...</p></div>`;
    try {
        const endpoint = type === 'pending' ? TA.restUrl + '/admin/kyc/pending' : TA.restUrl + '/admin/kyc/history';
        const res = await fetch(endpoint, { headers: { 'X-WP-Nonce': TA.nonce } });
        const requests = await res.json();
        if (requests.length === 0) {
            kycList.innerHTML = `
                <div style="text-align: center; padding: 60px; color: #888;">
                    <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.3;"></i>
                    <h3>All Caught Up!</h3>
                    <p>No ${type} KYC records found.</p>
                </div>`;
            return;
        }
        kycList.innerHTML = requests.map(req => {
            const renderDocBtn = (type, label) => {
                if (!type) return '';
                return `
                    <div class="doc-item">
                        <span class="doc-label">${label}</span>
                        <button class="btn-doc-view" onclick="viewKycImage('${req._id}', '${type}')">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </div>
                `;
            };
            const isHistory = type === 'history';
            let statusBadge = '';
            if (req.status === 'approved') statusBadge = '<span class="badge badge-success">APPROVED</span>';
            else if (req.status === 'rejected') statusBadge = '<span class="badge badge-danger">REJECTED</span>';
            else statusBadge = '<span class="badge badge-warning">PENDING</span>';
            return `
                <div class="kyc-card" style="border-left: 4px solid ${req.status === 'approved' ? '#10b981' : (req.status === 'rejected' ? '#ef4444' : '#f59e0b')}">
                    <div class="kyc-info">
                        ${statusBadge}
                        ${req.status === 'rejected' && req.rejectionReason ? `<div style="font-size:0.8rem; color:#ef4444; margin-top:5px;">Reason: ${req.rejectionReason}</div>` : ''}
                        <!-- AI Score Badge -->
                        <div style="margin: 10px 0; padding: 8px 12px; background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.3); border-radius: 6px; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-robot" style="color: #3b82f6;"></i>
                            <div>
                                <div style="font-size: 0.75rem; color: #888; text-transform: uppercase;">AI Face Match</div>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div id="score-${req._id}" style="font-size: 1.1rem; font-weight: 700; color: ${req.aiMatchScore >= 80 ? '#10b981' : (req.aiMatchScore >= 50 ? '#f59e0b' : '#ef4444')};">
                                        ${(req.aiMatchScore !== undefined && req.aiMatchScore !== null) ? req.aiMatchScore + '%' : 'N/A'}
                                    </div>
                                    <button onclick="analyzeFaceMatch('${req._id}')" style="background:none; border:none; color:#3b82f6; cursor:pointer;" title="Re-Analyze">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <h3>${req.user.name}</h3>
                        <p class="email">${req.user.email}</p>
                        <p class="meta">Doc Type: <strong>${req.documentType.toUpperCase()}</strong></p>
                        <p class="meta">Submitted: ${new Date(req.createdAt).toLocaleDateString()}</p>
                        ${req.verifiedAt ? `<p class="meta">Verified: ${new Date(req.verifiedAt).toLocaleDateString()}</p>` : ''}
                    </div>
                    <div class="kyc-docs">
                        <p class="section-label">Documents:</p>
                        <div class="docs-grid">
                            ${renderDocBtn(req.files.frontImage, 'Front ID')}
                            ${renderDocBtn(req.files.backImage, 'Back ID')}
                            ${renderDocBtn(req.files.selfie, 'Selfie')}
                        </div>
                    </div>
                    <div class="kyc-actions" style="flex-wrap: wrap; gap:10px;">
                        ${(!isHistory || req.status !== 'approved') ?
                    `<button class="btn-approve" onclick="verifyKyc('${req._id}', 'approved')"><i class="fas fa-check"></i> ${req.status === 'rejected' ? 'Re-Approve' : 'Approve'}</button>` : ''
                }
                        ${req.status !== 'rejected' ?
                    `<button class="btn-reject" onclick="verifyKyc('${req._id}', 'rejected', '${encodeURIComponent(req.aiMatchReason || '')}')"><i class="fas fa-times"></i> ${req.status === 'approved' ? 'Revoke & Reject' : 'Reject'}</button>` : ''
                }
                        <button class="btn-xs-admin" style="background:#ef4444; color:white; border:none; padding: 10px 15px; border-radius:6px; cursor:pointer; font-weight:600;" onclick="openBanModal('${req.user._id}', 'none')">
                            <i class="fas fa-ban"></i> Ban User
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    } catch (err) {
        console.error(err);
        kycList.innerHTML = '<p class="error-msg">Error loading requests.</p>';
    }
}
function switchKycTab(type) {
    loadKycRequests(type);
}
async function viewKycImage(kycId, type) {
    try {
        const res = await fetch(`${TA.restUrl}/admin/kyc/${kycId}/file?type=${type}`, { 
            headers: { 'X-WP-Nonce': TA.nonce } 
        });
        if (res.ok) {
            const blob = await res.blob();
            openImageModal(URL.createObjectURL(blob));
        } else {
            Swal.fire('Error', 'File not found', 'error');
        }
    } catch (err) {
        console.error(err);
    }
}
async function verifyKyc(kycId, status, aiReasonEncoded = '') {
    let reason = null;
    let defaultReason = '';
    if (aiReasonEncoded) {
        try {
            defaultReason = decodeURIComponent(aiReasonEncoded);
        } catch (e) {
            console.error('Error decoding reason', e);
        }
    }
    if (status === 'rejected') {
        const { value: text } = await Swal.fire({
            title: 'Reject Request',
            input: 'textarea',
            inputValue: defaultReason, 
            inputLabel: 'Reason',
            inputPlaceholder: 'Reason for rejection...',
            showCancelButton: true,
            background: '#18181b', color: '#fff',
            customClass: { input: 'swal-input-dark' } 
        });
        if (!text) return;
        reason = text;
    } else {
        const result = await Swal.fire({
            title: 'Approve User?',
            text: "Allow user to list tickets?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            background: '#18181b', color: '#fff'
        });
        if (!result.isConfirmed) return;
    }
    try {
        const res = await fetch('/api/admin/kyc/verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'x-auth-token': token },
            body: JSON.stringify({ kycId, status, rejectionReason: reason })
        });
        if (res.ok) {
            Swal.fire({ title: 'Success', icon: 'success', timer: 1000, showConfirmButton: false, background: '#18181b', color: '#fff' });
            loadKycRequests(currentKycType);
        } else {
            Swal.fire('Error', 'Action failed', 'error');
        }
    } catch (err) { console.error(err); }
}
async function loadUsers() {
    const tbody = document.querySelector('#usersTable tbody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Loading...</td></tr>';
    try {
        const res = await fetch('/api/admin/users', { headers: { 'x-auth-token': token } });
        const users = await res.json();
        if (users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No users found.</td></tr>';
            return;
        }
        tbody.innerHTML = users.map(u => {
            let roleBadge = '';
            if (u.role === 'admin') roleBadge = '<span class="badge badge-danger">ADMIN</span>';
            else if (u.role === 'both') roleBadge = '<span class="badge badge-primary">B&S</span>';
            else if (u.role === 'seller') roleBadge = '<span class="badge badge-warning">SELLER</span>';
            else roleBadge = '<span class="badge badge-success">BUYER</span>';
            let banBadge = '';
            if (u.banStatus === 'permanent') banBadge = '<span class="badge badge-danger" style="margin-left:5px;">BANNED</span>';
            if (u.banStatus === 'temporary') banBadge = '<span class="badge badge-warning" style="margin-left:5px;">BLOCKED</span>';
            return `
            <tr>
                <td>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div class="user-avatar-sm">${u.name.charAt(0).toUpperCase()}</div>
                        <div>
                            ${u.name}
                            ${banBadge}
                        </div>
                    </div>
                </td>
                <td style="color:#aaa;">${u.email}</td>
                <td>
                    ${roleBadge}
                    ${u.role !== 'admin' ? `<button onclick="makeAdmin('${u._id}')" class="btn-xs-admin" title="Promote">Promote</button>` : ''}
                </td>
                <td>${new Date(u.createdAt).toLocaleDateString()}</td>
                <td>
                <td>
                <td>
                    <div style="display: flex; gap: 8px;">
                        <button onclick="openBanModal('${u._id}', '${u.banStatus || 'none'}')" class="btn-xs-admin" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.3); padding: 5px 10px; font-size: 0.85rem;">
                            <i class="fas fa-ban"></i> Ban
                        </button>
                        <!-- Password Reset Button -->
                        <button onclick="openPasswordResetModal('${u._id}', '${u.name}')" class="btn-xs-admin" style="color: #f59e0b; border-color: rgba(245, 158, 11, 0.3); padding: 5px 10px; font-size: 0.85rem;" title="Reset Password">
                            <i class="fas fa-key"></i> Reset Pass
                        </button>
                    </div>
                </td>
                </td>
            </tr>
        `;
        }).join('');
    } catch (err) {
        console.error(err);
        tbody.innerHTML = '<tr><td colspan="5" style="color:red; text-align:center;">Error loading users.</td></tr>';
    }
}
async function makeAdmin(userId) {
    if (!confirm('Promote to Admin?')) return;
    try {
        const res = await fetch(`/api/users/${userId}/role`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'x-auth-token': token },
            body: JSON.stringify({ role: 'admin' })
        });
        if (res.ok) {
            Swal.fire('Success', 'User promoted', 'success');
            loadUsers();
        }
    } catch (err) { console.error(err); }
}
function openBanModal(userId, currentStatus) {
    document.getElementById('banUserId').value = userId;
    document.getElementById('banType').value = currentStatus;
    document.getElementById('banReason').value = '';
    document.getElementById('banModal').style.display = 'flex';
}
async function submitBanUser() {
    const userId = document.getElementById('banUserId').value;
    const banStatus = document.getElementById('banType').value;
    const banReason = document.getElementById('banReason').value;
    if (banStatus !== 'none' && !banReason.trim()) {
        Swal.fire('Error', 'Please provide a reason for suspension', 'error');
        return;
    }
    try {
        const res = await fetch(`/api/admin/users/${userId}/ban`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'x-auth-token': token },
            body: JSON.stringify({ banStatus, banReason })
        });
        if (res.ok) {
            document.getElementById('banModal').style.display = 'none';
            Swal.fire('Success', 'User status updated', 'success');
            loadUsers();
        } else {
            Swal.fire('Error', 'Failed to update status', 'error');
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Server Error', 'error');
    }
}
function openPasswordResetModal(userId, userName) {
    document.getElementById('resetUserId').value = userId;
    document.getElementById('resetUserName').innerText = `Resetting password for: ${userName}`;
    document.getElementById('adminNewPassword').value = '';
    document.getElementById('passwordResetModal').style.display = 'flex';
}
async function submitAdminPasswordReset() {
    const userId = document.getElementById('resetUserId').value;
    const newPassword = document.getElementById('adminNewPassword').value;
    if (!newPassword || newPassword.length < 6) {
        Swal.fire('Error', 'Password must be at least 6 characters', 'error');
        return;
    }
    try {
        const res = await fetch('/api/auth/admin/reset-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'x-auth-token': token },
            body: JSON.stringify({ userId, newPassword })
        });
        const data = await res.json();
        if (res.ok) {
            document.getElementById('passwordResetModal').style.display = 'none';
            Swal.fire('Success', data.msg, 'success');
        } else {
            Swal.fire('Error', data.msg || 'Failed to reset password', 'error');
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Server Error', 'error');
    }
}
function openImageModal(src) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    modal.style.display = 'flex';
    if (src.includes('/api/tickets/secure-image')) {
        fetch(src, {
            headers: { 'x-auth-token': localStorage.getItem('token') }
        })
            .then(res => {
                if (!res.ok) throw new Error('Unauthorized');
                return res.blob();
            })
            .then(blob => {
                const objectURL = URL.createObjectURL(blob);
                modalImg.src = objectURL;
            })
            .catch(err => {
                console.error(err);
                modalImg.src = 'https://placehold.co/600x400/333/ef4444?text=Access+Denied';
            });
    } else {
        modalImg.src = src;
    }
}
function closeModal() {
    document.getElementById('proofModal').style.display = 'none';
}
function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = TA.homeUrl + "login/";
}
window.onclick = function (e) {
    const modal = document.getElementById('proofModal');
    if (e.target === modal) closeModal();
}
async function analyzeFaceMatch(kycId) {
    const scoreEl = document.getElementById(`score-${kycId}`);
    if (scoreEl) scoreEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    try {
        const res = await fetch(`/api/admin/kyc/analyze/${kycId}`, {
            method: 'POST',
            headers: { 'x-auth-token': token }
        });
        const data = await res.json();
        if (res.ok) {
            Swal.fire({
                title: 'Analysis Complete',
                html: `
                    <div style="font-size:1.2rem; margin-bottom:10px;">Face Match Score: <strong style="color:${data.score >= 80 ? '#10b981' : '#ef4444'}">${data.score}%</strong></div>
                    <div style="text-align:left; background:#111; padding:15px; border-radius:8px; font-size:0.9rem; color:#ccc;">
                        <strong>🔎 AI Reasoning:</strong><br>
                        ${data.reason || "No reasoning provided."}
                    </div>
                `,
                icon: data.score >= 80 ? 'success' : 'warning',
                background: '#18181b', color: '#fff'
            });
            loadKycRequests(); 
        } else {
            Swal.fire('Error', data.msg, 'error');
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Analysis Failed', 'error');
    }
}
