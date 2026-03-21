document.addEventListener('DOMContentLoaded', () => {
    if (!TA.loggedIn) {
        window.location.href = TA.homeUrl + "login/";
        return;
    }
    loadMyOrders();
});

function switchTab(tab) {
    const sellingSection = document.getElementById('selling-section');
    const buyingSection = document.getElementById('buying-section');
    const tabs = document.querySelectorAll('.tab-btn');
    if (!sellingSection || !buyingSection) return;

    if (tab === 'selling') {
        sellingSection.style.display = 'block';
        buyingSection.style.display = 'none';
        if (tabs[0]) tabs[0].classList.add('active');
        if (tabs[1]) tabs[1].classList.remove('active');
        loadMyListings();
    } else {
        sellingSection.style.display = 'none';
        buyingSection.style.display = 'block';
        if (tabs[0]) tabs[0].classList.remove('active');
        if (tabs[1]) tabs[1].classList.add('active');
        loadMyOrders();
    }
}

async function loadMyListings() {
    const container = document.getElementById('my-listings-container');
    if (!container) return;

    container.innerHTML = '<div style="text-align: center; color: #aaa; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    try {
        const res = await fetch(TA.restUrl + '/tickets/my-tickets', {
            headers: { 'X-WP-Nonce': TA.nonce }
        });
        const tickets = await res.json();
        if (!Array.isArray(tickets) || tickets.length === 0) {
            container.innerHTML = `
                <div style="text-align:center; padding: 30px; background: rgba(255,255,255,0.02); border-radius: 10px;">
                    <i class="fas fa-ticket-alt" style="font-size: 2rem; color: var(--text-gray); margin-bottom: 15px;"></i>
                    <p style="color: #aaa; margin-bottom: 15px;">You haven't listed any tickets yet.</p>
                    <a href="${TA.homeUrl}sell-ticket/" class="btn btn-sm btn-primary">Sell Ticket</a>
                </div>`;
            return;
        }

        let html = '<div class="listings-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">';
        tickets.forEach(ticket => {
            let statusBadge = '';
            if (ticket.status === 'sold') {
                statusBadge = '<span class="badge badge-success" style="background-color: #10B981;"><i class="fas fa-check-circle"></i> SOLD</span>';
            } else if (ticket.status === 'pending') {
                statusBadge = '<span class="badge badge-warning"><i class="far fa-clock"></i> Pending</span>';
            } else if (ticket.status === 'approved') {
                statusBadge = '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Live</span>';
            } else {
                statusBadge = '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Rejected</span>';
            }
            html += `
                <div class="listing-card" style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
                    <div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <h3 style="margin:0; font-size:1.1rem; color:#fff;">${ticket.event}</h3>
                            ${statusBadge}
                        </div>
                    </div>
                    <div style="font-size: 0.9rem;">
                        <p style="color:#ccc; display:flex; justify-content:space-between; margin:5px 0;">
                            <span>Category:</span> <span style="color:#fff;">${ticket.category || 'N/A'}</span>
                        </p>
                        <p style="color:#ccc; display:flex; justify-content:space-between; margin:5px 0;">
                            <span>Quantity:</span> <span style="color:#fff;">${ticket.quantity}</span>
                        </p>
                        <p style="color:#ccc; display:flex; justify-content:space-between; margin:5px 0;">
                            <span>Price:</span> <span style="color:var(--primary); font-weight:bold;">₹${ticket.price}</span>
                        </p>
                   </div>
                </div>`;
        });
        html += '</div>';
        container.innerHTML = html;
    } catch (err) {
        console.error(err);
        container.innerHTML = '<p style="color:red; text-align:center;">Error loading listings.</p>';
    }
}

async function loadMyOrders() {
    const gridContainer = document.getElementById('my-orders-grid') || document.getElementById('my-orders-container');
    if (!gridContainer) return;

    gridContainer.innerHTML = '<div style="text-align:center; color:#aaa; padding: 40px;"><i class="fas fa-spinner fa-spin"></i> Loading your tickets...</div>';
    try {
        const res = await fetch(TA.restUrl + '/orders/my-orders', {
            headers: { 'X-WP-Nonce': TA.nonce }
        });
        const orders = await res.json();
        if (!Array.isArray(orders) || orders.length === 0) {
            gridContainer.innerHTML = `
                <div style="text-align:center; padding: 40px; background: rgba(255,255,255,0.02); border-radius: 10px;">
                    <i class="fas fa-shopping-bag" style="font-size: 3rem; color: var(--text-gray); margin-bottom: 20px;"></i>
                    <p style="color: #aaa;">You haven't bought any tickets yet.</p>
                </div>`;
            return;
        }

        let html = '<div class="listings-grid grid grid-3">';
        orders.forEach(order => {
            const ticket = order.ticket || {};
            const eventName = ticket.event || 'Unknown Event';
            html += `
                <div class="listing-card" style="background: rgba(255,255,255,0.03); padding: 24px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.08); display: flex; flex-direction: column; height: 100%;">
                    <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.08);">
                        <div style="display:flex; justify-content:space-between; align-items:start; gap: 10px;">
                            <h3 style="margin:0; font-size:1.15rem; color:#fff; font-weight: 600; line-height: 1.4;">${eventName}</h3>
                            <span class="badge badge-success"><i class="fas fa-check"></i> PURCHASED</span>
                        </div>
                    </div>
                    
                    <div class="meta-grid" style="flex: 1;">
                        <div class="meta-item">
                            <span class="meta-label">Order ID:</span>
                            <span class="meta-value" style="font-family: monospace; color: #888;">#${order.id}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Qty:</span>
                            <span class="meta-value">${ticket.quantity || 0}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Total Paid:</span>
                            <span class="meta-value highlight">₹${order.totalAmount}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Purchased By:</span>
                            <span class="meta-value">${order.buyerName || 'N/A'}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Venue:</span>
                            <span class="meta-value">${order.venue || 'N/A'}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Section/Seat:</span>
                            <span class="meta-value">${order.section || ''} ${order.seat || ''}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Seller Phone:</span>
                            <span class="meta-value">${order.sellerPhone || 'N/A'}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Event Date:</span>
                            <span class="meta-value">${order.eventDate || 'N/A'} ${order.eventTime || ''}</span>
                        </div>
                    </div>

                    <div style="display:flex; gap:12px; margin-top:25px;">
                        <button class="btn btn-sm btn-outline" style="flex:1; justify-content: center;" onclick="downloadInvoice(${order.id})">
                            <i class="fas fa-file-invoice"></i> Invoice
                        </button>
                        ${order.isTicketSent ? `
                            <button class="btn btn-sm btn-primary" style="flex:1; justify-content: center;" onclick="downloadTicket(${ticket.id})">
                                <i class="fas fa-download"></i> View Ticket
                            </button>
                        ` : `
                            <button class="btn btn-sm" style="flex:1; justify-content: center; background: rgba(255,255,255,0.05); color: #666; cursor: not-allowed; border: 1px solid rgba(255,255,255,0.05);" disabled title="Admin verification in progress">
                                <i class="fas fa-clock"></i> Verifying...
                            </button>
                        `}
                    </div>
                </div>`;
        });
        html += '</div>';
        gridContainer.innerHTML = html;
    } catch (err) {
        console.error(err);
        gridContainer.innerHTML = '<p style="color:red; text-align:center;">Error loading orders.</p>';
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
        a.download = `Invoice-${orderId}.html`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
    } catch (err) {
        console.error(err);
        showAlert('Error', 'Could not download invoice. Please try again later.', 'error');
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
        if (typeof showAlert !== 'undefined') {
            showAlert('Error', 'Could not load ticket. Please try again later.', 'error');
        } else {
            alert('Could not load ticket.');
        }
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
};
