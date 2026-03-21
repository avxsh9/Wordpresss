document.addEventListener('DOMContentLoaded', () => {
    const emailForm = document.getElementById('step-email');
    const otpForm = document.getElementById('step-otp');
    const resetForm = document.getElementById('step-reset');
    const successMsg = document.getElementById('step-success');
    const emailInput = document.getElementById('email');
    const otpInput = document.getElementById('otp');
    const passwordInput = document.getElementById('newPassword');
    let userEmail = '';
    document.getElementById('sendOtpBtn').addEventListener('click', async (e) => {
        e.preventDefault();
        const email = emailInput.value.trim();
        if (!email) {
            Swal.fire('Error', 'Please enter your email', 'error');
            return;
        }
        try {
            const btn = e.target;
            const originalText = btn.innerText;
            btn.innerText = 'Sending...';
            btn.disabled = true;
            const res = await fetch('/api/auth/forgot-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email })
            });
            const data = await res.json();
            if (res.ok) {
                userEmail = email;
                Swal.fire('Success', 'OTP sent to your email!', 'success');
                emailForm.style.display = 'none';
                otpForm.style.display = 'block';
            } else {
                Swal.fire('Error', data.message || data.msg || 'User not found', 'error');
            }
            btn.innerText = originalText;
            btn.disabled = false;
        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Something went wrong', 'error');
        }
    });
    document.getElementById('verifyOtpBtn').addEventListener('click', async (e) => {
        e.preventDefault();
        const otp = otpInput.value.trim();
        if (!otp) {
            Swal.fire('Error', 'Please enter OTP', 'error');
            return;
        }
        try {
            const res = await fetch('/api/auth/verify-otp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: userEmail, otp })
            });
            const data = await res.json();
            if (res.ok) {
                otpForm.style.display = 'none';
                resetForm.style.display = 'block';
            } else {
                Swal.fire('Error', data.message || data.msg || 'Invalid OTP', 'error');
            }
        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Something went wrong', 'error');
        }
    });
    document.getElementById('resetPasswordBtn').addEventListener('click', async (e) => {
        e.preventDefault();
        const password = passwordInput.value.trim();
        if (!password || password.length < 6) {
            Swal.fire('Error', 'Password must be at least 6 characters', 'error');
            return;
        }
        try {
            const res = await fetch('/api/auth/reset-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    email: userEmail,
                    otp: otpInput.value.trim(),
                    newPassword: password
                })
            });
            const data = await res.json();
            if (res.ok) {
                resetForm.style.display = 'none';
                successMsg.style.display = 'block';
            } else {
                Swal.fire('Error', data.message || data.msg || 'Failed to reset password', 'error');
            }
        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Something went wrong', 'error');
        }
    });
});
