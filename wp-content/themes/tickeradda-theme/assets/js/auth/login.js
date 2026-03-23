document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    if (!loginForm) return;

    loginForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const email = this.querySelector('input[name="email"]').value;
        const password = this.querySelector('input[name="password"]').value;
        const submitBtn = this.querySelector('button[type="submit"]');
        if (!email || !password) {
            showAlert('Missing Info', 'Please fill in all fields', 'warning');
            return;
        }
        try {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
            submitBtn.disabled = true;
            const res = await fetch(TA.restUrl + '/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': TA.nonce
                },
                body: JSON.stringify({ email, password })
            });
            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || data.msg || 'Login failed');
            }
            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
            await Swal.fire({
                icon: 'success',
                title: 'Welcome Back!',
                text: 'Login successful.',
                timer: 1500,
                showConfirmButton: false,
                background: '#18181b',
                color: '#fff'
            });
            const urlParams = new URLSearchParams(window.location.search);
            let redirectUrl = urlParams.get('redirect_to') || sessionStorage.getItem('returnUrl');

            if (redirectUrl) {
                sessionStorage.removeItem('returnUrl');
                window.location.href = decodeURIComponent(redirectUrl);
            } else if (data.user && data.user.role === 'admin') {
                window.location.href = TA.homeUrl + 'wp-admin/admin.php?page=tickeradda';
            } else if (data.user && (data.user.role === 'both' || data.user.role === 'seller')) {
                window.location.href = TA.homeUrl + 'seller-dashboard/';
            } else {
                window.location.href = TA.homeUrl + 'buyer-dashboard-2/';
            }
            
            // Backup redirect after 2 seconds if above fails
            setTimeout(() => {
                window.location.href = TA.homeUrl;
            }, 2000);
        } catch (err) {
            console.error(err);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Login Failed',
                    text: err.message,
                    icon: 'error',
                    background: '#18181b', color: '#fff'
                });
            }
            submitBtn.innerHTML = 'Sign In';
            submitBtn.disabled = false;
        }
    });
});
