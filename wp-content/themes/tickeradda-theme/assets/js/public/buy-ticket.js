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
            const fee = Math.ceil(subtotal * 0.05);
            document.getElementById('ticketPrice').innerHTML = `₹${ticket.price} <span style="font-size:0.5em; color:var(--text-gray); vertical-align:middle;">/ ticket</span>`;
            document.getElementById('summaryPrice').textContent = `₹${subtotal} (${qtyToBuy} tickets)`;
            document.getElementById('platformFee').textContent = `₹${fee}`;
            document.getElementById('summaryTotal').textContent = `₹${subtotal + fee}`;
            document.getElementById('ticketSection').textContent = ticket.section || 'General';
            document.getElementById('ticketRow').textContent = ticket.row || 'N/A';
            document.getElementById('ticketSeat').textContent = ticket.seat || 'GA';
            document.getElementById('ticketQty').textContent = `${qtyToBuy} Tickets`;
            if (ticket.seller) {
                document.getElementById('sellerName').textContent = ticket.seller.name || 'Verified Seller';
                const initials = (ticket.seller.name || 'V').split(' ').map(n => n[0]).join('').toUpperCase();
                document.getElementById('sellerInitials').textContent = initials.substring(0, 2);
                const rating = ticket.seller.averageRating ? ticket.seller.averageRating.toFixed(1) : 'New';
                const count = ticket.seller.ratingsCount || 0;
                document.getElementById('sellerRating').innerHTML = `
                    <i class="fas fa-star"></i> ${rating} 
                    <span style="color: var(--text-gray);">(${count} reviews)</span>
                `;
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
                    text: 'You need to login to complete the purchase.',
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
            payBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Initializing Payment...';
            try {
                const realTicketId = ticket.id;
                const orderRes = await fetch(TA.restUrl + '/payment/create-order', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': TA.nonce },
                    body: JSON.stringify({ ticketId: realTicketId, quantity: ticket.quantity })
                });
                const orderData = await orderRes.json();
                if (!orderRes.ok) throw new Error(orderData.message || orderData.msg || 'Order creation failed');

                const options = {
                    "key": TA.rzpKeyId, 
                    "amount": orderData.amount,
                    "currency": orderData.currency,
                    "name": "TickerAdda",
                    "description": `Ticket for ${ticket.event}`,
                    "image": TA.themeUrl + "/assets/images/logo.png",
                    "order_id": orderData.id,
                    "handler": async function (response) {
                        payBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
                        try {
                            const verifyRes = await fetch(TA.restUrl + '/payment/verify', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': TA.nonce },
                                body: JSON.stringify({
                                    razorpay_order_id: response.razorpay_order_id,
                                    razorpay_payment_id: response.razorpay_payment_id,
                                    razorpay_signature: response.razorpay_signature,
                                    internal_order_id: orderData.orderId 
                                })
                            });
                            const verifyData = await verifyRes.json();
                            if (verifyRes.ok && verifyData.status === 'success') {
                                window.location.href = TA.homeUrl + `order-success/?orderId=${verifyData.orderId}`;
                            } else {
                                Swal.fire('Verification Failed', verifyData.message || verifyData.msg || 'Payment failed', 'error');
                                payBtn.disabled = false;
                                payBtn.innerHTML = 'Proceed to Pay';
                            }
                        } catch (err) {
                            console.error(err);
                            Swal.fire('Error', 'Server Connection Error during verification', 'error');
                            payBtn.disabled = false;
                            payBtn.innerHTML = 'Proceed to Pay';
                        }
                    },
                    "prefill": {
                        "name": TA.user ? TA.user.name : "", 
                        "email": TA.user ? TA.user.email : "",
                        "contact": TA.user ? TA.user.phone : ""
                    },
                    "theme": { "color": "#3b82f6" },
                    "modal": {
                        "ondismiss": function () {
                            payBtn.disabled = false;
                            payBtn.innerHTML = 'Proceed to Pay';
                        }
                    }
                };
                const rzp1 = new Razorpay(options);
                rzp1.open();
            } catch (err) {
                console.error(err);
                Swal.fire('Error', err.message || 'Could not initiate payment', 'error');
                payBtn.disabled = false;
                payBtn.innerHTML = 'Proceed to Pay';
            }
        });
    }
});
