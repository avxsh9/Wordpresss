<?php
/**
 * Template Name: Forgot password
 */
get_header();
?>

<main id="main">
<div class="auth-container">
        <div class="auth-left">
            <div class="auth-header">
                <h1>Reset Password</h1>
                <p>Don't worry, we'll get you back on track.</p>
            </div>
            <form id="step-email">
                <div class="form-group">
                    <label>Enter your email address</label>
                    <input type="email" id="email" class="form-input" placeholder="you@example.com">
                </div>
                <button id="sendOtpBtn" class="btn-auth">Send OTP</button>
                <div class="auth-footer">
                    Remembered? <a href="<?php echo esc_url(home_url('/login/')); ?>">Sign In</a>
                </div>
            </form>
            <div id="step-otp" style="display: none;">
                <input type="text" style="display:none" name="fake_username_trap" autocomplete="username">
                <input type="password" style="display:none" name="fake_password_trap" autocomplete="current-password">
                <div class="form-group">
                    <label>Enter 6-digit OTP sent to your email</label>
                    <input type="text" id="otp" name="otp_code_input" class="form-input" placeholder="123456"
                        maxlength="6" autocomplete="off" readonly onfocus="this.removeAttribute('readonly');">
                </div>
                <button id="verifyOtpBtn" class="btn-auth">Verify OTP</button>
                <div class="auth-footer">
                    <a href="#" onclick="window.location.reload()">Resend OTP / Start Over</a>
                </div>
            </div>
            <div id="step-reset" style="display: none;">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" id="newPassword" class="form-input" placeholder="Min 6 characters">
                </div>
                <button id="resetPasswordBtn" class="btn-auth">Reset Password</button>
            </div>
            <div id="step-success" style="display: none; text-align: center;">
                <div style="margin-bottom: 20px;">
                    <i class="fas fa-check-circle" style="font-size: 50px; color: #4ade80;"></i>
                </div>
                <h3 style="color: white; margin-bottom: 10px;">Password Reset Successful!</h3>
                <p style="color: #94a3b8; margin-bottom: 20px;">You can now login with your new password.</p>
                <a href="<?php echo esc_url(home_url('/login/')); ?>" class="btn-auth"
                    style="display: inline-block; text-decoration: none; text-align: center;">Login Now</a>
            </div>
        </div>
        <div class="auth-right">
            <div class="right-content">
                <h2 class="quote-large">"Find Your Ticket To Happiness"</h2>
                <p class="sub-text">Lost access? No problem. Use our secure OTP verification to recover your account
                    instantly.</p>
                <div class="features-list">
                    <div class="feature-item">
                        <i class="fas fa-lock feature-icon"></i>
                        <div>
                            <h4 style="margin:0; font-weight:600;">Secure Recovery</h4>
                            <p style="margin:2px 0 0; font-size:0.9rem; color:#94a3b8;">OTP based verification ensures
                                only you can access your account.</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <p class="testimonial-text">"I forgot my password right before a big ticket drop. The recovery
                        process was instant and I didn't miss out!"</p>
                    <div class="user-profile">
                        <div class="user-avatar">
                            <img src="https://ui-avatars.com/api/?name=Priya+Sharma&background=random" alt="Priya">
                        </div>
                        <div class="user-info">
                            <h4>Priya Sharma</h4>
                            <span>Verified Buyer</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php get_footer(); ?>
