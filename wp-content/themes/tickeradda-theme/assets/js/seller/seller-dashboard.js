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

        // Handle HTTP-level errors (4xx/5xx)
        if (!res.ok) {
            let errMsg = 'Server error (' + res.status + ')';
            try {
                const errData = await res.json();
                errMsg = errData.message || errMsg;
            } catch(e) {}
            throw new Error(errMsg);
        }

        const tickets = await res.json();

        // Handle WP_Error response (object instead of array)
        if (!Array.isArray(tickets)) {
            if (tickets && tickets.message) throw new Error(tickets.message);
            tableBody.innerHTML = '<tr><td colspan="6" style="padding: 20px; text-align: center; color: var(--text-gray);">No listings found.</td></tr>';
            return;
        }

        if (tickets.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" style="padding: 20px; text-align: center; color: var(--text-gray);">No listings found. <a href="' + TA.homeUrl + 'sell-ticket/" style="color:var(--primary);">List your first ticket →</a></td></tr>';
            return;
        }

        let activeHtml = '';
        let soldHtml = '';
        const tableBody = document.getElementById('listingsTable');
        const soldTableBody = document.getElementById('soldTable');
        
        let totalEarnings = 0;
        let activeCount = 0;
        let soldCount = 0;

        tickets.forEach(ticket => {
            let statusBadge = '';
            let actionHtml = '';

            if (ticket.status === 'sold') {
                soldCount += ticket.quantity;
                const saleValue = ticket.price * ticket.quantity;
                const netEarnings = saleValue;
                totalEarnings += netEarnings;
                statusBadge = '<span class="badge badge-success">SOLD</span>';
                
                soldHtml += `
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); color: #ddd;">
                        <td style="padding: 15px;" data-label="Event">
                            <div style="font-weight:600; color:white;">${ticket.event}</div>
                            <div style="font-size:0.8em; color:#888;">${ticket.category} | Sect: ${ticket.section || '-'}</div>
                        </td>
                        <td style="padding: 15px;" data-label="Date">${new Date(ticket.eventDate).toLocaleDateString()}</td>
                        <td style="padding: 15px; font-weight:bold; color:white;" data-label="Quantity">${ticket.quantity}</td>
                        <td style="padding: 15px;" data-label="Earnings">
                            <div style="color:#10B981; font-weight:bold;">₹${netEarnings}</div>
                        </td>
                        <td style="padding: 15px;" data-label="Status">${statusBadge}</td>
                        <td style="padding: 15px;" data-label="Buyer">
                            <div style="font-size: 0.9em; color: white;">${ticket.buyerName || 'Buyer'}</div>
                            <div style="font-size: 0.8em; color: #888;"><i class="fas fa-phone"></i> ${ticket.buyerPhone || 'N/A'}</div>
                        </td>
                    </tr>
                `;
            } else {
                if (ticket.status === 'approved' || ticket.status === 'available') {
                    activeCount++;
                    statusBadge = '<span class="badge badge-success">Active</span>';
                    actionHtml = `
                        <div style="display:flex; gap:8px;">
                            <button class="btn btn-sm btn-success" onclick="updateListingStatus(${ticket.id}, 'sold')" title="Mark as Sold"><i class="fas fa-check"></i> Sold</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="cancelListing(${ticket.id})">Cancel</button>
                        </div>`;
                } else if (ticket.status === 'rejected') {
                    statusBadge = '<span class="badge badge-danger">Rejected/Cancelled</span>';
                    actionHtml = `<button class="btn btn-sm btn-outline-primary" onclick="updateListingStatus(${ticket.id}, 'available')">List Again</button>`;
                } else {
                    activeCount++;
                    statusBadge = '<span class="badge badge-warning">Pending</span>';
                    actionHtml = '<button class="btn btn-sm btn-outline-danger" onclick="cancelListing(' + ticket.id + ')">Cancel</button>';
                }

                activeHtml += `
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
            }
        });

        if (activeHtml) tableBody.innerHTML = activeHtml;
        if (soldHtml) soldTableBody.innerHTML = soldHtml;
        
        if (totalEarningsEl) totalEarningsEl.innerText = `₹${totalEarnings}`;
        if (activeListingsEl) activeListingsEl.innerText = activeCount;
        if (ticketsSoldEl) ticketsSoldEl.innerText = soldCount;

    } catch (err) {
        console.error('Seller dashboard error:', err);
        if (tableBody) {
            tableBody.innerHTML = `<tr><td colspan="6" style="padding:20px; text-align:center;">
                <div style="color:#ef4444; margin-bottom:8px;"><i class="fas fa-exclamation-circle"></i> Error loading listings</div>
                <div style="color:#888; font-size:0.85rem;">${err.message || 'Please try refreshing the page.'}</div>
                <button onclick="location.reload()" style="margin-top:12px; padding:8px 16px; background:var(--primary); color:#fff; border:none; border-radius:8px; cursor:pointer;">Retry</button>
            </td></tr>`;
        }
    }
});

async function cancelListing(id) {
    const result = await Swal.fire({
        title: 'Cancel Listing?',
        text: "This will remove your ticket from the marketplace.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, cancel it!',
        confirmButtonColor: '#ef4444',
        background: '#18181b', color: '#fff'
    });

    if (result.isConfirmed) {
        updateListingStatus(id, 'rejected');
    }
}

async function updateListingStatus(id, newStatus) {
    try {
        const res = await fetch(TA.restUrl + `/tickets/${id}/seller-status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': TA.nonce
            },
            body: JSON.stringify({ status: newStatus })
        });
        
        const data = await res.json();
        if (res.ok) {
            Swal.fire({
                title: 'Success',
                text: data.message || `Status updated to ${newStatus}.`,
                icon: 'success',
                background: '#18181b', color: '#fff'
            }).then(() => location.reload());
        } else {
            Swal.fire('Error', data.message || 'Failed to update status.', 'error');
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Server error.', 'error');
    }
}
