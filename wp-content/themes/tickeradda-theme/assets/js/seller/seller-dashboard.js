document.addEventListener('DOMContentLoaded', async () => {
    if (!TA.loggedIn) {
        window.location.href = TA.homeUrl + "login/";
        return;
    }

    const tableBody = document.getElementById('listingsTable');
    const totalEarningsEl = document.querySelectorAll('.container > div > div:nth-child(1) > div:nth-child(2)')[0];
    const activeListingsEl = document.querySelectorAll('.container > div > div:nth-child(2) > div:nth-child(2)')[0];
    const ticketsSoldEl = document.querySelectorAll('.container > div > div:nth-child(3) > div:nth-child(2)')[0];

    try {
        const res = await fetch(TA.restUrl + '/tickets/my-tickets', {
            headers: { 'X-WP-Nonce': TA.nonce }
        });
        const tickets = await res.json();

        if (!Array.isArray(tickets) || tickets.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" style="padding: 20px; text-align: center; color: var(--text-gray);">No listings found.</td></tr>';
            return;
        }

        let html = '';
        let totalEarnings = 0;
        let activeCount = 0;
        let soldCount = 0;

        tickets.forEach(ticket => {
            let statusBadge = '';
            let actionHtml = '';

            if (ticket.status === 'sold') {
                soldCount += ticket.quantity;
                const saleValue = ticket.price * ticket.quantity;
                const platformFee = Math.ceil(saleValue * 0.05);
                const netEarnings = saleValue - platformFee;
                totalEarnings += netEarnings;
                statusBadge = '<span class="badge badge-success">SOLD</span>';
                actionHtml = `
                    <div style="font-size: 0.8em; color: #10B981;">
                        <div>Sold to: <strong>${ticket.buyerName || 'Buyer'}</strong></div>
                        <div><i class="fas fa-phone"></i> ${ticket.buyerPhone || 'N/A'}</div>
                    </div>`;
            } else if (ticket.status === 'approved') {
                activeCount++;
                statusBadge = '<span class="badge badge-success">Active</span>';
                actionHtml = '<button class="btn btn-sm btn-outline-danger" onclick="cancelListing(' + ticket.id + ')">Cancel</button>';
            } else if (ticket.status === 'rejected') {
                statusBadge = '<span class="badge badge-danger">Rejected</span>';
                actionHtml = '-';
            } else {
                activeCount++;
                statusBadge = '<span class="badge badge-warning">Pending</span>';
                actionHtml = '<button class="btn btn-sm btn-outline-danger" onclick="cancelListing(' + ticket.id + ')">Cancel</button>';
            }

            html += `
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); color: #ddd;">
                    <td style="padding: 15px;" data-label="Event">
                        <div style="font-weight:600; color:white;">${ticket.event}</div>
                        <div style="font-size:0.8em; color:#aaa;">${new Date(ticket.eventDate).toLocaleDateString()} ${ticket.eventTime}</div>
                        <div style="font-size:0.8em; color:#888;">${ticket.category} | Sect: ${ticket.section || '-'}</div>
                    </td>
                    <td style="padding: 15px;" data-label="Date">${new Date(ticket.createdAt).toLocaleDateString()}</td>
                    <td style="padding: 15px; font-weight:bold; color:white;" data-label="Quantity">${ticket.quantity}</td>
                    <td style="padding: 15px;" data-label="Price">
                        <div style="color:white;">₹${ticket.price}<span style="font-size:0.8em; color:#888;">/tkt</span></div>
                        ${ticket.quantity > 1 ? `<div style="font-size:0.8rem; color:#aaa;">Total: ₹${ticket.price * ticket.quantity}</div>` : ''}
                    </td>
                    <td style="padding: 15px;" data-label="Status">${statusBadge}</td>
                    <td style="padding: 15px;" data-label="Action">${actionHtml}</td>
                </tr>
            `;
        });

        tableBody.innerHTML = html;
        if (totalEarningsEl) totalEarningsEl.innerText = `₹${totalEarnings}`;
        if (activeListingsEl) activeListingsEl.innerText = activeCount;
        if (ticketsSoldEl) ticketsSoldEl.innerText = soldCount;

    } catch (err) {
        console.error(err);
        if (tableBody) tableBody.innerHTML = '<tr><td colspan="6" style="color:red; text-align:center;">Error loading data.</td></tr>';
    }
});

async function cancelListing(id) {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: "This will remove your ticket listing from the marketplace.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, cancel it!',
        confirmButtonColor: '#ef4444',
        background: '#18181b', color: '#fff'
    });

    if (result.isConfirmed) {
        try {
            const res = await fetch(TA.restUrl + `/tickets/${id}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': TA.nonce
                },
                body: JSON.stringify({ status: 'rejected' }) // Or a deleted status if implemented
            });
            if (res.ok) {
                showAlert('Cancelled', 'Your listing has been removed.', 'success');
                location.reload();
            } else {
                showAlert('Error', 'Failed to cancel listing.', 'error');
            }
        } catch (err) {
            console.error(err);
            showAlert('Error', 'Server error.', 'error');
        }
    }
}
