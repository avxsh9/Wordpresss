<?php
/**
 * Template Name: Register
 */
get_header();
?>

<main id="main">
<div class="auth-container">
        <div class="auth-left">
            <div class="auth-header">
                <h1>Create Account</h1>
                <p>Join the revolution. Buy & Sell tickets securely.</p>
            </div>
            <form id="signupForm">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-input" placeholder="Enter your name">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-input" placeholder="Enter your email">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" value="+91" disabled
                            style="width: 60px; padding: 14px; background: #334155; border: 1px solid #475569; border-radius: 8px; color: #cbd5e1; text-align: center;">
                        <input type="tel" name="phone" class="form-input" placeholder="9876543210" pattern="[0-9]{10}"
                            maxlength="10">
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-input" placeholder="Create a password">
                </div>
                <div id="signup-otp-section"
                    style="display: none; margin-top: 15px; margin-bottom: 20px; padding: 15px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 8px;">
                    <label style="color: #3b82f6; font-weight: 600; display: block; margin-bottom: 8px;">Verify Email
                        OTP</label>
                    <input type="text" style="display:none" name="fake_username_trap_signup" autocomplete="username">
                    <input type="password" style="display:none" name="fake_password_trap_signup"
                        autocomplete="current-password">
                    <input type="text" name="otp" class="form-input" placeholder="Enter 6-digit OTP" maxlength="6"
                        autocomplete="off" readonly onfocus="this.removeAttribute('readonly');"
                        style="border-color: #3b82f6;">
                    <small style="color: #94a3b8; display: block; margin-top: 5px;">OTP sent to your email. It expires
                        in 10 mins.</small>
                </div>
                <button type="submit" class="btn-auth" id="signupBtn">Sign Up</button>
                
                <!-- Google Sign-In Button Container -->
                <div id="googleBtnContainer" style="margin-top: 20px;"></div>

                <div class="auth-footer">
                    Already have an account? <a href="<?php echo esc_url(home_url('/login/')); ?>">Sign In</a>
                </div>
            </form>
        </div>

        <!-- Google Identity Services Library -->
        <script src="https://accounts.google.com/gsi/client" async></script>
        <script>
            function handleGoogleResponse(response) {
                if (!response.credential) return;

                // Show loading state
                const btnContainer = document.getElementById('googleBtnContainer');
                btnContainer.style.opacity = '0.5';
                btnContainer.style.pointerEvents = 'none';

                fetch(TA.restUrl + '/auth/google-login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': TA.nonce
                    },
                    body: JSON.stringify({ credential: response.credential })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.user) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Account created/logged in successfully!',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            const urlParams = new URLSearchParams(window.location.search);
                            let redirectUrl = urlParams.get('redirect_to') || sessionStorage.getItem('returnUrl');
                            
                            if (redirectUrl) {
                                sessionStorage.removeItem('returnUrl');
                                window.location.href = decodeURIComponent(redirectUrl);
                            } else {
                                window.location.href = TA.homeUrl + (data.user.role === 'admin' ? 'wp-admin/admin.php?page=tickeradda' : 'buyer-dashboard-2/');
                            }
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Google signup failed', 'error');
                        btnContainer.style.opacity = '1';
                        btnContainer.style.pointerEvents = 'all';
                    }
                })
                .catch(err => {
                    console.error('Google Signup Error:', err);
                    Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
                    btnContainer.style.opacity = '1';
                    btnContainer.style.pointerEvents = 'all';
                });
            }

            window.onload = function () {
                google.accounts.id.initialize({
                    client_id: "539426267370-e12lt552ilkencgo97qcaf01kl4mpt26.apps.googleusercontent.com",
                    callback: handleGoogleResponse
                });
                google.accounts.id.renderButton(
                    document.getElementById("googleBtnContainer"),
                    { 
                        theme: "outline", 
                        size: "large", 
                        width: document.querySelector('.btn-auth').offsetWidth,
                        text: "signup_with",
                        shape: "rectangular",
                        logo_alignment: "left"
                    }
                );
                // Also show One Tap dialogue
                google.accounts.id.prompt(); 
            };
        </script>
        <div class="auth-right">
            <div class="right-content">
                <h2 class="quote-large">"I ADMIT IT, ANOTHER CLASSIC"</h2>
                <p class="sub-text">Join India's most trusted ticket marketplace. Safe, fast, and reliable.</p>
                <div class="features-list">
                    <div class="feature-item">
                        <i class="fas fa-check-circle feature-icon"></i>
                        <div>
                            <h4 style="margin:0; font-weight:600;">Verified Sellers</h4>
                            <p style="margin:2px 0 0; font-size:0.9rem; color:#94a3b8;">All sellers are KYC verified via
                                Aadhaar.</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-money-bill-wave feature-icon"></i>
                        <div>
                            <h4 style="margin:0; font-weight:600;">Best Prices</h4>
                            <p style="margin:2px 0 0; font-size:0.9rem; color:#94a3b8;">Find tickets at fair market
                                value.</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <p class="testimonial-text">"I sold my extra IPL tickets in 10 minutes. The payout was instant.
                        Highly recommended!"</p>
                    <div class="user-profile">
                        <div class="user-avatar">
                            <img src="https://ui-avatars.com/api/?name=Rahul+Verma&background=random" alt="Rahul">
                        </div>
                        <div class="user-info">
                            <h4>Rahul Verma</h4>
                            <span>Verified Seller</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php get_footer(); ?>
