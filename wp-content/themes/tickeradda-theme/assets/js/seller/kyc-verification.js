document.addEventListener('DOMContentLoaded', async () => {
    if (!TA.loggedIn) {
        window.location.href = TA.homeUrl + "login/";
        return;
    }

    const form = document.getElementById('kycForm');
    const formContainer = document.getElementById('kycFormContainer');
    const statusMessage = document.getElementById('statusMessage');

    try {
        const res = await fetch(TA.restUrl + '/kyc/status', {
            headers: { 'X-WP-Nonce': TA.nonce }
        });
        const data = await res.json();
        if (data.status === 'pending') {
            showStatus('pending', 'Verification Pending', 'Your documents are under review. This usually takes 24 hours.');
        } else if (data.status === 'approved') {
            showStatus('approved', 'Verified', 'You are verified! Returning to home page...');
            setTimeout(() => window.location.href = TA.homeUrl, 1500);
        } else if (data.status === 'rejected') {
            showStatus('rejected', 'Verification Failed', `Reason: ${data.rejectionReason || 'Documents invalid'}. Please resubmit.`);
            formContainer.style.display = 'block';
        }
    } catch (err) {
        console.error('Error fetching KYC status:', err);
    }

    function showStatus(type, title, msg) {
        statusMessage.style.display = 'block';
        let color = '#fff';
        let icon = 'fa-info-circle';
        if (type === 'pending') { color = '#f59e0b'; icon = 'fa-clock'; formContainer.style.display = 'none'; }
        if (type === 'approved') { color = '#10b981'; icon = 'fa-check-circle'; formContainer.style.display = 'none'; }
        if (type === 'rejected') { color = '#ef4444'; icon = 'fa-times-circle'; }
        statusMessage.innerHTML = `
            <i class="fas ${icon}" style="font-size: 3rem; color: ${color}; margin-bottom: 15px;"></i>
            <h3 style="color: ${color}; margin-bottom: 10px;">${title}</h3>
            <p style="color: #ccc;">${msg}</p>
        `;
    }

    setupFileUpload('frontImage', 'dropFront', 'frontFileName');
    setupFileUpload('backImage', 'dropBack', 'backFileName');
    setupFileUpload('selfie', 'dropSelfie', 'selfieFileName');

    function setupFileUpload(inputId, dropId, nameId) {
        const input = document.getElementById(inputId);
        const drop = document.getElementById(dropId);
        const name = document.getElementById(nameId);
        if (!input || !drop || !name) return;

        drop.addEventListener('click', () => input.click());
        input.addEventListener('change', () => {
            if (input.files.length > 0) {
                name.textContent = input.files[0].name;
                drop.style.borderColor = '#10b981';
            }
        });
    }

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = form.querySelector('button');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            const formData = new FormData();
            formData.append('documentType', document.getElementById('documentType').value);
            formData.append('documentNumber', document.getElementById('documentNumber').value);
            if (document.getElementById('frontImage').files[0])
                formData.append('frontImage', document.getElementById('frontImage').files[0]);
            if (document.getElementById('backImage').files[0])
                formData.append('backImage', document.getElementById('backImage').files[0]);
            if (document.getElementById('selfie').files[0])
                formData.append('selfie', document.getElementById('selfie').files[0]);

            try {
                const res = await fetch(TA.restUrl + '/kyc/submit', {
                    method: 'POST',
                    headers: { 'X-WP-Nonce': TA.nonce },
                    body: formData
                });
                const data = await res.json();
                if (res.ok) {
                    Swal.fire({
                        title: 'Submission Successful',
                        text: 'Your documents have been submitted for review.',
                        icon: 'success',
                        background: '#18181b', color: '#fff',
                        confirmButtonColor: '#10b981'
                    }).then(() => {
                        window.location.href = TA.homeUrl;
                    });
                } else {
                    showAlert('Submission Failed', data.message || data.msg || 'Please try again', 'error');
                    btn.disabled = false;
                    btn.innerHTML = 'Submit for Verification';
                }
            } catch (err) {
                console.error(err);
                showAlert('Server Error', 'Something went wrong. Please check your connection.', 'error');
                btn.disabled = false;
                btn.innerHTML = 'Submit for Verification';
            }
        });
    }
});
