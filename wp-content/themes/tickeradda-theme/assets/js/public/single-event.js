document.addEventListener('DOMContentLoaded', function() {
    const ticketsGrid = document.getElementById('eventTicketsGrid');
    const badge = document.getElementById('ticketCountBadge');
    
    if (!ticketsGrid || !window.currentEventId) return;

    async function loadTickets() {
        try {
            const response = await fetch(`${TA.restUrl}/events-list/${window.currentEventId}/tickets`);
            const tickets = await response.json();
            
            renderTickets(tickets);
            updateBadge(tickets.length);
        } catch (error) {
            console.error('Error loading event tickets:', error);
            ticketsGrid.innerHTML = '<div style="padding: 40px; text-align: center; color: #ff5252;">Failed to load tickets. Please refresh.</div>';
        }
    }

    function renderTickets(tickets) {
        if (!tickets || tickets.length === 0) {
            ticketsGrid.innerHTML = `
                <div style="background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.1); border-radius: 15px; padding: 60px; text-align: center; color: #888;">
                    <i class="fas fa-ticket-alt" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.3;"></i>
                    <h3>No tickets listed yet</h3>
                    <p>Be the first to list tickets for this event!</p>
                    <a href="${TA.homeUrl}sell-ticket/?event_id=${window.currentEventId}" class="btn btn-primary" style="margin-top: 20px;">List My Tickets</a>
                </div>
            `;
            return;
        }

        ticketsGrid.innerHTML = tickets.map(ticket => `
            <div class="ticket-listing-card" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 20px; display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: center; transition: all 0.3s ease;">
                <div class="ticket-info">
                    <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 700; font-size: 1.1rem; color: #fff;">${ticket.section || 'General'}</span>
                        <span class="badge" style="background: rgba(255,255,255,0.1); color: #ccc; font-size: 0.7rem;">${ticket.type.toUpperCase()}</span>
                    </div>
                    <div style="display: flex; gap: 15px; font-size: 0.85rem; color: #aaa; margin-bottom: 12px;">
                        ${ticket.row ? `<span>Row: <strong>${ticket.row}</strong></span>` : ''}
                        ${ticket.seat ? `<span>Seat: <strong>${ticket.seat}</strong></span>` : ''}
                        <span>Qty: <strong>${ticket.quantity}</strong></span>
                    </div>
                    <div class="seller-mini-info" style="display: flex; align-items: center; gap: 10px; font-size: 0.8rem; color: #888;">
                        <span>Seller: <strong>${ticket.sellerName || 'Verified Seller'}</strong></span>
                    </div>
                </div>
                
                <div class="ticket-cta" style="text-align: right; border-left: 1px solid rgba(255,255,255,0.05); padding-left: 30px;">
                    <div style="font-size: 1.5rem; font-weight: 800; color: ${ticket.status === 'sold' ? '#ef4444' : 'var(--primary)'}; margin-bottom: 5px;">₹${ticket.price.toLocaleString()}</div>
                    <div style="font-size: 0.75rem; color: #16a34a; margin-bottom: 15px; font-weight: 600;"><i class="fas fa-check-circle"></i> No service fee</div>
                    ${ticket.status === 'sold' ? `
                        <button class="btn btn-danger btn-sm" disabled style="width: 100%; border-radius: 8px; cursor: not-allowed; opacity: 0.8;"><i class="fas fa-ban"></i> Sold Out</button>
                    ` : `
                        <button class="btn btn-primary btn-sm buy-btn" data-id="${ticket.id}" style="width: 100%; border-radius: 8px;"><i class="fas fa-phone-alt"></i> Request For Free / Get Seller Details</button>
                    `}
                </div>

            </div>
        `).join('');

        // Add event listeners to buttons
        document.querySelectorAll('.buy-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const ticketId = this.dataset.id;
                window.location.href = `${TA.homeUrl}buy-ticket/?ticket_id=${ticketId}`;
            });
        });
    }

    function updateBadge(count) {
        if (!badge) return;
        badge.textContent = count === 1 ? '1 ticket available' : `${count} tickets available`;
    }



    loadTickets();
});
