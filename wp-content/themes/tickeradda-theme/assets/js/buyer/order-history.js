document.addEventListener('DOMContentLoaded', () => {
    if (!TA.loggedIn) {
        window.location.href = TA.homeUrl + "login/";
        return;
    }
    loadMyOrders();
});

async function loadMyOrders() {
    const container = document.getElementById('my-orders-container');
    if (!container) return;

    container.innerHTML = '<div style="text-align:center; color:#aaa; padding: 40px;"><i class="fas fa-spinner fa-spin"></i> Loading your tickets...</div>';
    
    try {
        const res = await fetch(TA.restUrl + '/orders/my-orders', {
            headers: { 'X-WP-Nonce': TA.nonce }
        });
        const orders = await res.json();
        
        if (!Array.isArray(orders) || orders.length === 0) {
            container.innerHTML = `
                <div style="text-align:center; padding: 40px; background: rgba(255,255,255,0.02); border-radius: 10px;">
                    <i class="fas fa-shopping-bag" style="font-size: 3rem; color: var(--text-gray); margin-bottom: 20px;"></i>
                    <p style="color: #aaa;">You haven't bought any tickets yet.</p>
                    <a href="${TA.homeUrl}events/" class="btn btn-primary" style="margin-top: 15px;">Browse Events</a>
                </div>`;
            return;
        }

        let html = '<div class="listings-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px;">';
        orders.forEach(order => {
            const ticket = order.ticket || {};
            const eventName = ticket.event || 'Unknown Event';
            html += `
                <div class="listing-card" style="background: var(--card-bg); padding: 24px; border-radius: 16px; border: 1px solid rgba(59, 130, 246, 0.2);">
                    <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <h3 style="margin:0; font-size:1.1rem; color:#fff;">${eventName}</h3>
                            <span class="badge badge-success"><i class="fas fa-check"></i> Purchased</span>
                        </div>
                    </div>
                    <div style="font-size: 0.9rem;">
                        <p style="color:#ccc; display:flex; justify-content:space-between; margin:8px 0;">
                            <span>Order ID:</span> <span style="font-family:monospace; color:#888;">#${order.id}</span>
                        </p>
                        <p style="color:#ccc; display:flex; justify-content:space-between; margin:8px 0;">
                            <span>Price Paid:</span> <span style="color:var(--primary); font-weight:bold;">₹${order.totalAmount}</span>
                        </p>
                        <p style="color:#ccc; display:flex; justify-content:space-between; margin:8px 0;">
                            <span>Purchased By:</span> <span style="color:#fff;">${order.buyerName || 'N/A'}</span>
                        </p>
                        <p style="color:#ccc; display:flex; justify-content:space-between; margin:8px 0;">
                            <span>Venue:</span> <span style="color:#fff;">${order.venue || 'N/A'}</span>
                        </p>
                        <p style="color:#ccc; display:flex; justify-content:space-between; margin:8px 0;">
                            <span>Section/Seat:</span> <span style="color:#fff;">${order.section || ''} ${order.seat || ''}</span>
                        </p>
                        <p style="color:#ccc; display:flex; justify-content:space-between; margin:8px 0;">
                            <span>Seller Phone:</span> <span style="color:#fff;">${order.sellerPhone || 'N/A'}</span>
                        </p>
                        <p style="color:#ccc; display:flex; justify-content:space-between; margin:8px 0;">
                            <span>Event Date:</span> <span>${order.eventDate || 'N/A'} ${order.eventTime || ''}</span>
                        </p>
                        <div style="display:flex; gap:10px; margin-top:20px; flex-wrap: wrap;">
                            <button class="btn btn-sm btn-outline" style="flex:1; min-width: 100px;" onclick="downloadInvoice(${order.id})">
                                <i class="fas fa-file-invoice"></i> Invoice
                            </button>
                            ${order.isTicketSent ? `
                                <button class="btn btn-sm btn-primary" style="flex:1; min-width: 100px; text-align:center;" onclick="downloadTicket(${ticket.id})">
                                    <i class="fas fa-download"></i> View Ticket
                                </button>
                            ` : `
                                <div style="flex:1; min-width: 100px; text-align:center; padding: 12px; background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-radius: 8px; font-size: 0.8rem; border: 1px solid rgba(245, 158, 11, 0.2);">
                                    <i class="fas fa-clock"></i> Verification Pending
                                </div>
                            `}
                        </div>
                    </div>
                </div>`;
        });
        html += '</div>';
        container.innerHTML = html;
    } catch (err) {
        console.error(err);
        container.innerHTML = '<p style="color:red; text-align:center;">Error loading orders.</p>';
    }
}

window.downloadInvoice = async function(orderId) {
    const btn = event.currentTarget;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    try {
        const res = await fetch(TA.restUrl + `/orders/${orderId}/invoice`, {
            headers: { 'X-WP-Nonce': TA.nonce }
        });
        if (!res.ok) throw new Error('Invoice not available');

        const blob = await res.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Invoice-${orderId}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
    } catch (err) {
        console.error(err);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Error',
                text: 'Could not download invoice. Please try again later.',
                icon: 'error',
                background: '#18181b', color: '#fff',
                confirmButtonColor: '#3b82f6'
            });
        }
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
};

window.downloadTicket = async function(ticketId) {
    const btn = event.currentTarget || event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    try {
        const res = await fetch(TA.restUrl + `/tickets/secure-image/${ticketId}`, {
            headers: { 'X-WP-Nonce': TA.nonce }
        });
        if (!res.ok) throw new Error('Ticket not available');

        const blob = await res.blob();
        const url = window.URL.createObjectURL(blob);
        window.open(url, '_blank');
        window.URL.revokeObjectURL(url);
    } catch (err) {
        console.error(err);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Error',
                text: 'Could not download ticket. Please try again later.',
                icon: 'error',
                background: '#18181b', color: '#fff',
                confirmButtonColor: '#3b82f6'
            });
        }
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
};
