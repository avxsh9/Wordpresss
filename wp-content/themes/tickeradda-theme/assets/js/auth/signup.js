document.addEventListener('DOMContentLoaded', () => {
    const signupForm = document.getElementById('signupForm');
    if (!signupForm) return;

    signupForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const submitBtn = document.getElementById('signupBtn');
        const otpSection = document.getElementById('signup-otp-section');
        const otpInput = this.querySelector('input[name="otp"]');
        const name = this.querySelector('input[name="name"]').value;
        const email = this.querySelector('input[name="email"]').value;
        const phoneInput = this.querySelector('input[name="phone"]').value;
        const password = this.querySelector('input[name="password"]').value;
        if (!name || !email || !phoneInput || !password) {
            Swal.fire('Missing Info', 'Please fill in all fields', 'warning');
            return;
        }
        if (phoneInput.length !== 10) {
            Swal.fire('Invalid Phone', 'Please enter a valid 10-digit phone number', 'warning');
            return;
        }
        const phone = '+91' + phoneInput;
        if (otpSection.style.display === 'none') {
            try {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending OTP...';
                submitBtn.disabled = true;
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 30000);
                const res = await fetch(TA.restUrl + '/auth/send-signup-otp', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': TA.nonce
                    },
                    body: JSON.stringify({ email }),
                    signal: controller.signal
                });
                clearTimeout(timeoutId);
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || data.msg || 'Failed to send OTP');
                Swal.fire({
                    icon: 'success',
                    title: 'OTP Sent!',
                    text: `Please check ${email} for your verification code.`,
                    timer: 2000,
                    showConfirmButton: false
                });
                otpSection.style.display = 'block';
                submitBtn.innerHTML = 'Verify & Sign Up';
                submitBtn.disabled = false;
                this.querySelector('input[name="email"]').readOnly = true;
            } catch (err) {
                console.error(err);
                let msg = err.message;
                if (err.name === 'AbortError') {
                    msg = 'Request timed out. Please check your internet or try again.';
                }
                Swal.fire('Error', msg, 'error');
                submitBtn.innerHTML = 'Sign Up';
                submitBtn.disabled = false;
            }
        } else {
            const otp = otpInput.value.trim();
            if (!otp || otp.length !== 6) {
                Swal.fire('Invalid OTP', 'Please enter the 6-digit OTP', 'warning');
                return;
            }
            try {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
                submitBtn.disabled = true;
                const res = await fetch(TA.restUrl + '/auth/register', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': TA.nonce
                    },
                    body: JSON.stringify({
                        name,
                        email,
                        phone,
                        password,
                        otp 
                    })
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || data.msg || 'Registration failed');
                localStorage.setItem('token', data.token);
                localStorage.setItem('user', JSON.stringify(data.user));
                await Swal.fire({
                    icon: 'success',
                    title: 'Welcome to TickerAdda!',
                    text: 'Email verified & account created successfully.',
                    timer: 1500,
                    showConfirmButton: false
                });
                window.location.href = TA.homeUrl + "kyc-verification/";
                
                // Backup redirect
                setTimeout(() => {
                    window.location.href = TA.homeUrl;
                }, 2000);
            } catch (err) {
                console.error(err);
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Registration Failed', err.message, 'error');
                }
                submitBtn.innerHTML = 'Verify & Sign Up';
                submitBtn.disabled = false;
            }
        }
    });
});
