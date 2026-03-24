/* Buyer Dashboard — loads buyer's ticket requests with seller details */
document.addEventListener('DOMContentLoaded', () => {
    if (!window.TA || !TA.loggedIn) {
        window.location.href = (TA?.homeUrl || '/') + 'login/';
        return;
    }
    loadMyOrders();
});

async function loadMyOrders() {
    const grid = document.getElementById('orders-grid');
    if (!grid) return;

    grid.innerHTML = `<div style="text-align:center;color:#aaa;padding:50px;">
        <i class="fas fa-spinner fa-spin" style="font-size:2rem;display:block;margin-bottom:12px;"></i>
        Loading your requests...
    </div>`;

    try {
        const res = await fetch(TA.restUrl + '/orders/my-orders', {
            headers: { 'X-WP-Nonce': TA.nonce }
        });
        const orders = await res.json();

        if (!Array.isArray(orders) || orders.length === 0) {
            grid.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-ticket-alt"></i>
                    <h3>No requests yet</h3>
                    <p>Browse events and claim tickets — they'll appear here!</p>
                    <a href="${TA.homeUrl}events/" class="btn btn-primary" style="margin-top:18px;display:inline-block;">Browse Events</a>
                </div>`;
            return;
        }

        grid.innerHTML = orders.map(renderOrderCard).join('');
    } catch (err) {
        console.error('Error loading orders:', err);
        grid.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Failed to load</h3><p>Please try refreshing the page.</p></div>`;
    }
}

function renderOrderCard(order) {
    let statusLabel = { pending: 'Pending', completed: 'Confirmed', cancelled: 'Cancelled' }[order.status] || order.status;
    let statusClass = { pending: 'status-pending', completed: 'status-completed', cancelled: 'status-cancelled' }[order.status] || '';

    if (order.ticketStatus === 'sold' && order.status === 'pending') {
        statusLabel = 'SOLD';
        statusClass = 'status-cancelled'; // Use red for sold tickets to buyer
    }

    const date = order.eventDate ? new Date(order.eventDate).toLocaleDateString('en-IN', { day:'numeric', month:'short', year:'numeric' }) : '—';
    const time = order.eventTime || '—';
    const venue = order.venue || '—';
    const section = order.section || '—';
    const seat = order.seat || '—';
    const qty = order.quantity || 1;
    const total = (order.totalAmount || 0).toLocaleString('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 });
    const createdDate = order.createdAt ? new Date(order.createdAt).toLocaleDateString('en-IN', { day:'numeric', month:'short', year:'numeric' }) : '';

    // KYC Badge logic - ensure it handles 'approved' correctly
    const kycApproved = order.sellerKycStatus === 'approved';
    const kycBadge = kycApproved
        ? `<span class="kyc-badge" style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); color:#10b981;"><i class="fas fa-shield-alt"></i> KYC Verified</span>`
        : `<span class="kyc-badge" style="background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.3); color:#f59e0b;"><i class="fas fa-clock"></i> KYC Pending</span>`;

    // Seller contact — ensure all details are prominent as per user request
    const sellerBlock = `
        <div class="seller-info-block" style="background:rgba(59,130,246,0.05); border:1px solid rgba(59,130,246,0.1); border-radius:12px; padding:15px; margin-top:15px;">
            <h4 style="margin:0 0 12px; font-size:0.85rem; color:#3b82f6; text-transform:uppercase; letter-spacing:1px; border-bottom:1px solid rgba(59,130,246,0.1); padding-bottom:5px;">
                <i class="fas fa-user-circle" style="margin-right:6px;"></i>Seller Details
            </h4>
            <div class="seller-name-row" style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                <span class="seller-display-name" style="font-weight:700; color:#fff; font-size:1.05rem;">${escHtml(order.sellerName || 'Verified Seller')}</span>
                ${kycBadge}
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                ${order.sellerPhone ? `
                <div class="seller-info-row" style="display:flex; align-items:center; gap:8px; font-size:0.9rem;">
                    <i class="fas fa-phone-alt" style="color:#3b82f6; width:16px;"></i>
                    <a href="tel:${escHtml(order.sellerPhone)}" style="color:#93c5fd; text-decoration:none;">${escHtml(order.sellerPhone)}</a>
                </div>
                <div class="seller-info-row" style="display:flex; align-items:center; gap:8px; font-size:0.9rem;">
                    <i class="fab fa-whatsapp" style="color:#25d366; width:16px;"></i>
                    <a href="https://wa.me/91${order.sellerPhone.replace(/\D/g,'')}" target="_blank" style="color:#25d366; text-decoration:none;">WhatsApp</a>
                </div>` : ''}
                ${order.sellerEmail ? `
                <div class="seller-info-row" style="display:flex; align-items:center; gap:8px; font-size:0.9rem; grid-column: span 2;">
                    <i class="fas fa-envelope" style="color:#3b82f6; width:16px;"></i>
                    <a href="mailto:${escHtml(order.sellerEmail)}" style="color:#93c5fd; text-decoration:none;">${escHtml(order.sellerEmail)}</a>
                </div>` : ''}
            </div>
            <div style="margin-top:12px; padding-top:10px; border-top:1px solid rgba(255,255,255,0.05); font-size:0.8rem; color:#888;">
                <i class="fas fa-info-circle" style="margin-right:4px;"></i>
                Contact the seller directly to complete the transfer.
            </div>
        </div>`;

    return `
    <div class="order-card">
        <div class="order-card-top">
            <h3 class="order-event-name">${escHtml(order.eventName || 'Ticket')}</h3>
            <span class="order-status ${statusClass}">${statusLabel}</span>
        </div>
        <div class="order-meta">
            <span><strong>Date:</strong> ${date}</span>
            <span><strong>Time:</strong> ${time}</span>
            <span><strong>Venue:</strong> ${escHtml(venue)}</span>
            <span><strong>Section:</strong> ${escHtml(section)}</span>
            <span><strong>Seat:</strong> ${escHtml(seat)}</span>
            <span><strong>Qty:</strong> ${qty}</span>
            <span><strong>Total:</strong> ${total}</span>
            <span><strong>Requested:</strong> ${createdDate}</span>
        </div>
        ${sellerBlock}
    </div>`;
}

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
