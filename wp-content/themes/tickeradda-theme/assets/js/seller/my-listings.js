document.addEventListener('DOMContentLoaded', () => {
    if (!TA.loggedIn) {
        window.location.href = TA.homeUrl + "login/";
        return;
    }
    loadListings();
});

async function loadListings() {
    const container = document.getElementById('my-listings-container');
    if (!container) return;

    container.innerHTML = '<div style="text-align: center; color: #aaa; padding: 40px;"><i class="fas fa-spinner fa-spin"></i> Loading your listings...</div>';

    try {
        const res = await fetch(TA.restUrl + '/tickets/my-tickets', {
            headers: { 'X-WP-Nonce': TA.nonce }
        });
        const tickets = await res.json();

        if (!Array.isArray(tickets) || tickets.length === 0) {
            container.innerHTML = `
                <div style="text-align:center; padding: 40px; background: rgba(255,255,255,0.02); border-radius: 10px;">
                    <i class="fas fa-ticket-alt" style="font-size: 3rem; color: var(--text-gray); margin-bottom: 20px;"></i>
                    <p style="color: #aaa; margin-bottom: 20px;">You haven't listed any tickets yet.</p>
                    <a href="${TA.homeUrl}sell-ticket/" class="btn btn-primary">Sell Your First Ticket</a>
                </div>`;
            return;
        }

        let html = '<div class="listings-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">';
        tickets.forEach(ticket => {
            let statusBadge = '';
            let statusText = '';
            let claimUI = '';
            
            if (ticket.status === 'pending') {
                statusBadge = '<span class="badge badge-warning"><i class="far fa-clock"></i> Pending Approval</span>';
                statusText = 'Waiting for admin review';
            } else if (ticket.status === 'approved' || ticket.status === 'available' || ticket.status === 'active') {
                if (ticket.orderStatus === 'pending') {
                    statusBadge = '<span class="badge badge-warning" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);"><i class="fas fa-handshake"></i> Pending Claim</span>';
                    statusText = 'A buyer wants to purchase this';
                    claimUI = `
                        <div style="margin-top: 15px; padding: 15px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 8px;">
                            <h4 style="margin: 0 0 10px 0; color: #f59e0b; font-size: 0.95rem;">Buyer Interested</h4>
                            <p style="margin: 0 0 5px 0; font-size: 0.85rem; color: #ccc;"><strong>Name:</strong> ${ticket.buyerName}</p>
                            <p style="margin: 0 0 5px 0; font-size: 0.85rem; color: #ccc;"><strong>Email:</strong> <a href="mailto:${ticket.buyerEmail}" style="color: #4f46e5;">${ticket.buyerEmail}</a></p>
                            <p style="margin: 0 0 15px 0; font-size: 0.85rem; color: #ccc;"><strong>Phone:</strong> ${ticket.buyerPhone}</p>
                            <button class="btn btn-primary btn-sm btn-confirm-sale" data-order-id="${ticket.orderId}" style="width: 100%; background-color: #16a34a; border-color: #16a34a;"><i class="fas fa-check"></i> Confirm Sale</button>
                        </div>
                    `;
                } else {
                    statusBadge = '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Live</span>';
                    statusText = 'Visible on Marketplace';
                }
            } else if (ticket.status === 'rejected') {
                statusBadge = '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Rejected</span>';
                statusText = 'Listing declined';
            } else if (ticket.status === 'sold') {
                statusBadge = '<span class="badge badge-success" style="background: rgba(22, 163, 74, 0.2); color: #16a34a;">SOLD OUT</span>';
                statusText = 'Ticket sold successfully';
            } else {
                statusBadge = `<span class="badge badge-warning">${ticket.status}</span>`;
            }

            html += `
                <div class="listing-card" style="background: var(--card-bg); padding: 20px; border-radius: 15px; border: 1px solid var(--glass-border);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                        <h3 style="margin: 0; font-size: 1.2rem;">${ticket.event}</h3>
                        ${statusBadge}
                    </div>
                    <div style="color: #ccc; font-size: 0.9rem; margin-bottom: 15px;">
                        <p style="margin: 5px 0;"><strong>Venue:</strong> ${ticket.venue || 'N/A'}</p>
                        <p style="margin: 5px 0;"><strong>Category/Row:</strong> ${ticket.category} / ${ticket.row || 'N/A'}</p>
                        <p style="margin: 5px 0;"><strong>Seat:</strong> ${ticket.seat || 'N/A'}</p>
                        <p style="margin: 5px 0;"><strong>Price:</strong> ₹${ticket.price}</p>
                        <p style="margin: 5px 0;"><strong>Qty:</strong> ${ticket.quantity}</p>
                    </div>
                    <div style="font-size: 0.8rem; color: #888; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 10px;">
                        ${statusText} • ${new Date(ticket.createdAt).toLocaleDateString()}
                    </div>
                    ${claimUI}
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;
        
        // Add event listeners for Confirm Sale buttons
        document.querySelectorAll('.btn-confirm-sale').forEach(btn => {
            btn.addEventListener('click', async function() {
                const orderId = this.dataset.orderId;
                const result = await Swal.fire({
                    title: 'Confirm Sale?',
                    text: "You are confirming that you have arranged payment with the buyer. This will mark the ticket as SOLD OUT.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Confirm Sale',
                    background: '#18181b', color: '#fff',
                    confirmButtonColor: '#16a34a'
                });

                if (result.isConfirmed) {
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Confirming...';
                    
                    try {
                        const res = await fetch(TA.restUrl + `/orders/${orderId}/confirm-sale`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': TA.nonce }
                        });
                        const data = await res.json();
                        
                        if (res.ok) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Sale confirmed. An email has been sent to the buyer.',
                                icon: 'success',
                                background: '#18181b', color: '#fff'
                            }).then(() => loadListings());
                        } else {
                            throw new Error(data.message || 'Failed to confirm sale');
                        }
                    } catch (err) {
                        Swal.fire('Error', err.message, 'error');
                        this.disabled = false;
                        this.innerHTML = 'Confirm Sale';
                    }
                }
            });
        });

    } catch (err) {
        console.error(err);
        container.innerHTML = '<p style="color: #ef4444; text-align: center;">Error loading listings.</p>';
    }
}
