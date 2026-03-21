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
            if (ticket.status === 'pending') {
                statusBadge = '<span class="badge badge-warning"><i class="far fa-clock"></i> Pending Approval</span>';
                statusText = 'Waiting for admin review';
            } else if (ticket.status === 'approved') {
                statusBadge = '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Live</span>';
                statusText = 'Visible on Marketplace';
            } else if (ticket.status === 'rejected') {
                statusBadge = '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Rejected</span>';
                statusText = 'Listing declined';
            } else if (ticket.status === 'sold') {
                statusBadge = '<span class="badge badge-success">SOLD</span>';
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
                    <div style="font-size: 0.8rem; color: #888; border-top: 1px solid rgba(255,255,255,0.05); pt: 10px;">
                        ${statusText} • ${new Date(ticket.createdAt).toLocaleDateString()}
                    </div>
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;
    } catch (err) {
        console.error(err);
        container.innerHTML = '<p style="color: #ef4444; text-align: center;">Error loading listings.</p>';
    }
}
