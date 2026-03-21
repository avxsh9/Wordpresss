document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('sellSportForm');
    const submitBtn = document.getElementById('submitSportBtn');

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Basic validation
            if (!TA.loggedIn) {
                Swal.fire({
                    icon: 'error',
                    title: 'Authentication Required',
                    text: 'You must be logged in to sell tickets.',
                    confirmButtonColor: '#3b82f6'
                }).then(() => {
                    window.location.href = TA.homeUrl + 'login/';
                });
                return;
            }

            const formData = new FormData(form);
            formData.append('action', 'ta_submit_sport_ticket');
            formData.append('nonce', TA.sellSportNonce);

            const origText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Listing Ticket...';
            submitBtn.disabled = true;

            try {
                const res = await fetch(TA.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await res.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Ticket Listed!',
                        text: data.data.message,
                        confirmButtonColor: '#10b981'
                    }).then(() => {
                        window.location.href = TA.homeUrl + 'seller-dashboard/';
                    });
                } else {
                    Swal.fire('Error', data.data.message || 'Something went wrong.', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Network error occurred. Please try again.', 'error');
            } finally {
                submitBtn.innerHTML = origText;
                submitBtn.disabled = false;
            }
        });
    }
});
