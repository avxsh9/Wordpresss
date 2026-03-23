<?php
/**
 * Template Name: Login
 */
get_header();
?>

<main id="main">
<div class="auth-container">
        <div class="auth-left">
            <div class="auth-header">
                <h1>Welcome Back</h1>
                <p>Enter your credentials to access the madness.</p>
            </div>
            <form id="loginForm">
                <div class="form-group">
                    <label>Email address</label>
                    <input type="email" name="email" class="form-input" placeholder="you@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-input" placeholder="Enter your password">
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                    <label style="display: flex; align-items: center; gap: 8px; color: #94a3b8; cursor: pointer;">
                        <input type="checkbox" style="accent-color: #3b82f6;"> Remember me
                    </label>
                    <a href="<?php echo esc_url(home_url('/forgot-password/')); ?>"
                        style="color: #3b82f6; text-decoration: none; font-size: 0.9rem;">Forgot password?</a>
                </div>
                <button type="submit" class="btn-auth">Sign In</button>

                <!-- Google Sign-In Button Container -->
                <div id="googleBtnContainer" style="margin-top: 20px;"></div>

                <div class="auth-footer">
                    Don't have an account? <a href="<?php echo esc_url(home_url('/register/')); ?>">Sign up</a>
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
                            text: 'Logged in successfully!',
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
                        Swal.fire('Error', data.message || 'Google login failed', 'error');
                        btnContainer.style.opacity = '1';
                        btnContainer.style.pointerEvents = 'all';
                    }
                })
                .catch(err => {
                    console.error('Google Login Error:', err);
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
                        text: "signin_with",
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
                <p class="sub-text">India's most trusted ticket marketplace. List your spare tickets in minutes and get
                    paid fast.</p>
                <div class="features-list">
                    <div class="feature-item">
                        <i class="fas fa-shield-alt feature-icon"></i>
                        <div>
                            <h4 style="margin:0; font-weight:600;">100% Secure</h4>
                            <p style="margin:2px 0 0; font-size:0.9rem; color:#94a3b8;">Transactions protected with
                                bank-level security.</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-bolt feature-icon"></i>
                        <div>
                            <h4 style="margin:0; font-weight:600;">Get Paid Fast</h4>
                            <p style="margin:2px 0 0; font-size:0.9rem; color:#94a3b8;">Receive payment within 24 hours
                                after your tickets sell.</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <p class="testimonial-text">"TickerAdda made buying Travis Scott tickets so easy and secure. The
                        best platform in India for resale!"</p>
                    <div class="user-profile">
                        <div class="user-avatar">
                            <img src="https://ui-avatars.com/api/?name=Avinash+Prasad&background=random" alt="Avinash">
                        </div>
                        <div class="user-info">
                            <h4>Avinash Prasad</h4>
                            <span>from Delhi NCR</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php get_footer(); ?>
