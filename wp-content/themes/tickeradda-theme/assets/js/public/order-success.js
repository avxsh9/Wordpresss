document.addEventListener('DOMContentLoaded', async () => {
    confetti({
        particleCount: 100,
        spread: 70,
        origin: { y: 0.6 }
    });
    const urlParams = new URLSearchParams(window.location.search);
    const orderId = urlParams.get('orderId');
    const token = localStorage.getItem('token');
    if (!orderId) return;
    try {
        const ordersRes = await fetch(TA.restUrl + '/orders/my-orders', { 
            headers: { 'X-WP-Nonce': TA.nonce } 
        });
        const orders = await ordersRes.json();
        const order = orders.find(o => String(o.id) === String(orderId));
        if (!order) {
            console.error('Order not found in user list');
            return;
        }
        const ticket = order.ticket; 
        document.getElementById('eventName').textContent = ticket.event || "Event Ticket";
        document.getElementById('orderId').textContent = `Order ID: #${order.id}`;

        const detailsHtml = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px; font-size: 0.9rem; color: #ccc;">
                <div><strong>Venue:</strong> ${order.venue || 'N/A'}</div>
                <div><strong>Section/Seat:</strong> ${order.section || ''} ${order.seat || ''}</div>
                <div><strong>Event Date:</strong> ${order.eventDate || 'N/A'}</div>
                <div><strong>Event Time:</strong> ${order.eventTime || 'N/A'}</div>
            </div>
        `;
        document.getElementById('eventName').insertAdjacentHTML('afterend', detailsHtml);
        
        const noImageMsg = document.getElementById('noImageMsg');
        if (ticket.id) { 
            const secureUrl = TA.restUrl + `/tickets/secure-image/${ticket.id}`;
            const imgId = `ticket-img-${ticket.id}`;
            let html = `
                 <div class="ticket-image-container" style="margin-top: 20px; text-align: center;">
                     <p style="color: #ccc; margin-bottom: 10px;">Your Ticket</p>
                     <img id="${imgId}" src="https://placehold.co/600x400/111/444?text=Loading+Secure+Ticket..." alt="Ticket" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--glass-border);">
                     <div style="margin-top: 10px;">
                         <a id="download-${imgId}" href="#" class="btn btn-primary btn-sm" style="display:none">
                             <i class="fas fa-download"></i> Download Ticket
                         </a>
                     </div>
                 </div>
             `;
            document.getElementById('order-details').insertAdjacentHTML('beforeend', html);
            fetch(secureUrl, {
                headers: { 'X-WP-Nonce': TA.nonce }
            })
                .then(res => {
                    if (!res.ok) throw new Error('Secure Load Failed');
                    return res.blob();
                })
                .then(blob => {
                    const objectUrl = URL.createObjectURL(blob);
                    const imgEl = document.getElementById(imgId);
                    const downloadBtn = document.getElementById(`download-${imgId}`);
                    if (imgEl) {
                        imgEl.src = objectUrl;
                        if (downloadBtn) {
                            downloadBtn.href = objectUrl;
                            downloadBtn.download = `ticket-${ticket.id}.jpg`;
                            downloadBtn.style.display = 'inline-flex';
                        }
                    }
                })
                .catch(err => {
                    console.error('Ticket Image Load Error:', err);
                    const imgEl = document.getElementById(imgId);
                    if (imgEl) imgEl.src = 'https://placehold.co/600x400/333/ef4444?text=Image+Load+Failed';
                });
        } else {
            noImageMsg.style.display = 'block';
        }
    } catch (err) {
        console.error('Error loading details:', err);
    }
});
