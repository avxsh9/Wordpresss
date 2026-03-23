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
    const statusLabel = { pending: 'Pending', completed: 'Confirmed', cancelled: 'Cancelled' }[order.status] || order.status;
    const statusClass = { pending: 'status-pending', completed: 'status-completed', cancelled: 'status-cancelled' }[order.status] || '';

    const date = order.eventDate ? new Date(order.eventDate).toLocaleDateString('en-IN', { day:'numeric', month:'short', year:'numeric' }) : '—';
    const time = order.eventTime || '—';
    const venue = order.venue || '—';
    const section = order.section || '—';
    const seat = order.seat || '—';
    const qty = order.quantity || 1;
    const total = (order.totalAmount || 0).toLocaleString('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 });
    const createdDate = order.createdAt ? new Date(order.createdAt).toLocaleDateString('en-IN', { day:'numeric', month:'short', year:'numeric' }) : '';

    // KYC Badge
    const kycApproved = order.sellerKycStatus === 'approved';
    const kycBadge = kycApproved
        ? `<span class="kyc-badge"><i class="fas fa-shield-alt"></i> KYC Verified</span>`
        : `<span class="kyc-badge" style="background:rgba(245,158,11,0.1); border-color:rgba(245,158,11,0.3); color:#f59e0b;"><i class="fas fa-clock"></i> KYC Pending</span>`;

    // Seller contact — always show (buyer requested so seller is notified)
    const sellerBlock = `
        <div class="seller-info-block">
            <h4><i class="fas fa-user-circle" style="margin-right:6px;"></i>Seller Details</h4>
            <div class="seller-name-row">
                <span class="seller-display-name">${escHtml(order.sellerName || 'Unknown Seller')}</span>
                ${kycBadge}
            </div>
            ${order.sellerPhone ? `
            <div class="seller-info-row">
                <i class="fas fa-phone-alt"></i>
                <a href="tel:${escHtml(order.sellerPhone)}">${escHtml(order.sellerPhone)}</a>
                <a href="https://wa.me/91${order.sellerPhone.replace(/\D/g,'')}" target="_blank" style="color:#25d366; margin-left:8px;">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
            </div>` : ''}
            ${order.sellerEmail ? `
            <div class="seller-info-row">
                <i class="fas fa-envelope"></i>
                <a href="mailto:${escHtml(order.sellerEmail)}">${escHtml(order.sellerEmail)}</a>
            </div>` : ''}
            ${order.status === 'pending' ? `
            <p style="margin:10px 0 0;font-size:0.8rem;color:#888;">
                <i class="fas fa-info-circle" style="margin-right:4px;"></i>
                The seller has been notified. They will contact you directly to complete the transfer.
            </p>` : ''}
            ${order.status === 'completed' ? `
            <p style="margin:10px 0 0;font-size:0.82rem;color:#10b981;">
                <i class="fas fa-check-circle" style="margin-right:4px;"></i>
                Sale confirmed! Contact the seller to arrange the handover.
            </p>` : ''}
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
