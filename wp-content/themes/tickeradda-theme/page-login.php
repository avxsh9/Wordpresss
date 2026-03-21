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
                <button type="button"
                    style="width: 100%; margin-top: 20px; padding: 12px; background: transparent; border: 1px solid #334155; border-radius: 8px; color: white; display: flex; align-items: center; justify-content: center; gap: 10px; cursor: pointer;">
                    <img src="https://www.svgrepo.com/show/475656/google-color.svg" style="width: 20px;">
                    Sign in with Google
                </button>
                <div class="auth-footer">
                    Don't have an account? <a href="<?php echo esc_url(home_url('/register/')); ?>">Sign up</a>
                </div>
            </form>
        </div>
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
