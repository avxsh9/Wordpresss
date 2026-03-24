document.addEventListener('DOMContentLoaded', async () => {
    const urlParams = new URLSearchParams(window.location.search);
    const ticketId = urlParams.get('ticket_id');
    const payBtn = document.getElementById('payBtn');
    
    if (!ticketId) {
        Swal.fire('Error', 'Ticket Not Found', 'error');
        return;
    }
    
    try {
        const res = await fetch(TA.restUrl + `/tickets/${ticketId}`);
        const ticket = await res.json();
        
        if (res.ok) {
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('ticketDetails').style.display = 'block';
            document.getElementById('eventName').textContent = ticket.event;
            document.getElementById('eventDate').innerHTML = `<i class="far fa-calendar-alt me-2"></i>${new Date(ticket.date).toLocaleDateString()}`;
            document.getElementById('eventLocation').innerHTML = `<i class="fas fa-map-marker-alt me-2"></i>${ticket.venue || 'TBA'}`;
            
            const qtyToBuy = ticket.quantity; 
            const subtotal = ticket.price * qtyToBuy;
            
            document.getElementById('ticketPrice').innerHTML = `₹${ticket.price} <span style="font-size:0.5em; color:var(--text-gray); vertical-align:middle;">/ ticket</span>`;
            document.getElementById('summaryPrice').textContent = `₹${subtotal} (${qtyToBuy} tickets)`;
            
            const summaryTotalEl = document.getElementById('summaryTotal');
            if (summaryTotalEl) summaryTotalEl.textContent = `₹${subtotal}`;
            
            document.getElementById('ticketSection').textContent = ticket.section || 'General';
            document.getElementById('ticketRow').textContent = ticket.row || 'N/A';
            document.getElementById('ticketSeat').textContent = ticket.seat || 'GA';
            document.getElementById('ticketQty').textContent = `${qtyToBuy} Tickets`;
            
            if (ticket.seller) {
                const sNameEl = document.getElementById('sellerName');
                if (sNameEl) sNameEl.textContent = ticket.seller.name || 'Verified Seller';
                
                const sInitialsEl = document.getElementById('sellerInitials');
                const initials = (ticket.seller.name || 'V').split(' ').map(n => n[0]).join('').toUpperCase();
                if (sInitialsEl) sInitialsEl.textContent = initials.substring(0, 2);
                

            }
            
            setupPayment(ticket);
        } else {
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('errorState').style.display = 'block';
            Swal.fire('Error', ticket.message || 'Could not load ticket details', 'error');
        }
    } catch (err) {
        console.error(err);
        document.getElementById('loadingState').style.display = 'none';
        document.getElementById('errorState').style.display = 'block';
    }

    function setupPayment(ticket) {
        payBtn.addEventListener('click', async () => {
            if (!TA.loggedIn) {
                Swal.fire({
                    title: 'Login Required',
                    text: 'You need to login to claim this ticket.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Login Now',
                    background: '#18181b', color: '#fff',
                    confirmButtonColor: '#3b82f6'
                }).then((result) => {
                    if (result.isConfirmed) {
                        sessionStorage.setItem('returnUrl', window.location.href);
                        window.location.href = TA.homeUrl + 'login/';
                    }
                });
                return;
            }
            
            payBtn.disabled = true;
            payBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting Request...';
            
            try {
                const res = await fetch(TA.restUrl + '/orders/claim', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': TA.nonce },
                    body: JSON.stringify({ ticketId: ticket.id, quantity: ticket.quantity })
                });
                
                const data = await res.json();
                
                if (!res.ok) throw new Error(data.message || 'Failed to submit request');

                const seller = data.seller || {};
                const sellerHtml = `
                    <div style="margin-top:20px; padding:15px; background:rgba(255,255,255,0.05); border-radius:10px; text-align:left; border:1px solid rgba(255,255,255,0.1);">
                        <h4 style="margin:0 0 10px; color:#3b82f6; font-size:1rem; border-bottom:1px solid rgba(59,130,246,0.2); padding-bottom:5px;">Seller Contact Details</h4>
                        <p style="margin:5px 0;"><strong>Name:</strong> ${seller.name || 'N/A'}</p>
                        <p style="margin:5px 0;"><strong>Email:</strong> <a href="mailto:${seller.email}" style="color:#93c5fd;">${seller.email || 'N/A'}</a></p>
                        <p style="margin:5px 0;"><strong>Phone:</strong> <a href="tel:${seller.phone}" style="color:#93c5fd;">${seller.phone || 'N/A'}</a></p>
                        <p style="margin:10px 0 0; font-size:0.8rem; color:#888; font-style:italic;">You can also find these details in your Buyer Dashboard.</p>
                    </div>
                `;

                Swal.fire({
                    title: 'Request Sent!',
                    html: `
                        <p>The seller has been notified of your interest.</p>
                        ${sellerHtml}
                        <p style="margin-top:15px;">You have also received an email with these details.</p>
                    `,
                    icon: 'success',
                    background: '#18181b', color: '#fff',
                    confirmButtonColor: '#16a34a',
                    confirmButtonText: 'Go to Dashboard'
                }).then(() => {
                    window.location.href = TA.homeUrl + 'buyer-dashboard-2/';
                });
                
            } catch (err) {
                console.error(err);
                Swal.fire('Error', err.message || 'Could not process request', 'error');
                payBtn.disabled = false;
                payBtn.innerHTML = '<i class="fas fa-handshake"></i> I have bought this ticket';
            }
        });
    }
});
