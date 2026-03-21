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
                <button type="button"
                    style="width: 100%; margin-top: 20px; padding: 12px; background: transparent; border: 1px solid #334155; border-radius: 8px; color: white; display: flex; align-items: center; justify-content: center; gap: 10px; cursor: pointer;">
                    <img src="https://www.svgrepo.com/show/475656/google-color.svg" style="width: 20px;">
                    Sign up with Google
                </button>
                <div class="auth-footer">
                    Already have an account? <a href="<?php echo esc_url(home_url('/login/')); ?>">Sign In</a>
                </div>
            </form>
        </div>
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
