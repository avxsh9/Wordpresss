<?php
/**
 * Template Name: Home
 */

// ── Prevent LiteSpeed / any proxy from caching this page ──────────────────────
// This page is dynamic (JS fetches live event data), caching breaks it.
do_action( 'litespeed_control_set_nocache', 'Homepage is fully dynamic (JS-driven)' );
header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
header( 'Pragma: no-cache' );
header( 'Expires: 0' );

get_header();
?>

<main id="main">
<section class="hero">
        <div class="hero-content">
            <span class="hero-badge"><i class="fa-solid fa-rocket"></i> Faster, safer, bug-free</span>
            <h1 class="hero-title">Your ticket to the best<br>live experiences</h1>
            <p class="hero-subtitle">Buy and sell tickets for thousands of amazing events across India</p>
            
            <div class="search-container" style="max-width: 600px; margin: 30px auto; position: relative;">
                <input type="text" id="homeSearchInput" placeholder="Search by event, artist or city..." style="width: 100%; padding: 18px 25px; border-radius: 50px; border: none; background: rgba(255,255,255,0.08); backdrop-filter: blur(10px); color: #fff; font-size: 1.1rem; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                <button id="homeSearchBtn" style="position: absolute; right: 8px; top: 8px; padding: 12px 25px; border-radius: 40px; border: none; background: var(--primary); color: #fff; font-weight: bold; cursor: pointer; transition: 0.3s;">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>

            <div class="hero-categories">
                <button class="category-pill active" data-category="all">All</button>
                <button class="category-pill" data-category="music">Music</button>
                <button class="category-pill" data-category="sports">Sports</button>
                <button class="category-pill" data-category="comedy">Comedy</button>
                <button class="category-pill" data-category="theatre">Theatre</button>
                <button class="category-pill" data-category="movies">Movies</button>
            </div>
        </div>
    </section>

    <!-- Trending Now -->
    <section class="section trending-events">
        <div class="container">
            <div class="section-header">
                <div class="section-title">
                    <h2>Trending now</h2>
                    <p>Top selling events this week</p>
                </div>
                <a href="<?php echo esc_url(home_url('/events/')); ?>" class="btn btn-outline">View all events</a>
            </div>
            <div class="grid grid-4" id="trendingEventsGrid">
                <p style="grid-column: 1/-1; text-align: center; color: var(--text-gray);">Loading trending events...</p>
            </div>
        </div>
    </section>

    <!-- Sports Events -->
    <section class="section sports-section">
        <div class="container">
            <div class="section-header" style="flex-wrap:wrap; gap:12px;">
                <div class="section-title">
                    <h2>Sports events</h2>
                    <p>Catch the live action</p>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap:wrap;">
                    <button class="category-pill-small active" data-sports-category="all">All Sports</button>
                    <button class="category-pill-small" data-sports-category="cricket">
                        <i class="fas fa-baseball-bat-ball" style="font-size:0.75rem;"></i> Cricket
                    </button>
                    <button class="category-pill-small" data-sports-category="football">
                        <i class="fa-regular fa-futbol" style="font-size:0.75rem;"></i> Football
                    </button>
                    <button class="category-pill-small" data-sports-category="kabaddi">
                        <i class="fa-solid fa-person-running" style="font-size:0.75rem;"></i> Kabaddi
                    </button>
                </div>
                <a href="<?php echo esc_url(home_url('/sports/')); ?>" class="btn btn-outline">View all sports</a>
            </div>
            <div class="grid grid-4" id="homeSportsGrid">
                <p style="grid-column: 1/-1; text-align: center; color: var(--text-gray);">Loading sports events...</p>
            </div>
        </div>
    </section>

    <!-- Movies -->
    <section class="section movies-section">
        <div class="container">
            <div class="section-header">
                <div class="section-title">
                    <h2>Movies</h2>
                    <p>Latest blockbusters in theatres near you</p>
                </div>
                <a href="<?php echo esc_url(home_url('/movies/')); ?>" class="btn btn-outline">View all movies</a>
            </div>
            <div class="grid grid-4" id="homeMoviesGrid">
                <p style="grid-column: 1/-1; text-align: center; color: var(--text-gray);">Loading movies...</p>
            </div>
        </div>
    </section>

    <!-- Theatre & Plays -->
    <section class="section theatre-section">
        <div class="container">
            <div class="section-header">
                <div class="section-title">
                    <h2>Theatre &amp; Plays</h2>
                    <p>Experience the magic of stage</p>
                </div>
                <a href="<?php echo esc_url(home_url('/theatre/')); ?>" class="btn btn-outline">View all theatre</a>
            </div>
            <div class="grid grid-4" id="homeTheatreGrid">
                <p style="grid-column: 1/-1; text-align: center; color: var(--text-gray);">Loading theatre events...</p>
            </div>
        </div>
    </section>

    <!-- More Events -->
    <section class="section more-events-section" id="homeMoreSection">
        <div class="container">
            <div class="section-header">
                <div class="section-title">
                    <h2>More Events</h2>
                    <p>Comedy, Music, and everything else</p>
                </div>
                <a href="<?php echo esc_url(home_url('/events/')); ?>" class="btn btn-outline">Browse all events</a>
            </div>
            <div class="grid grid-4" id="homeMoreEventsGrid">
                <p style="grid-column: 1/-1; text-align: center; color: var(--text-gray);">Loading events...</p>
            </div>
        </div>
    </section>

    <!-- Sell CTA -->
    <section class="section sell-cta" style="background: linear-gradient(rgba(59, 130, 246, 0.1), transparent);">
        <div class="container">
            <div class="hero-content" style="max-width: 100%;"> 
                <h2 style="font-size: 36px; margin-bottom: 20px;">Have tickets to sell?</h2>
                <p style="margin-bottom: 30px; font-size: 18px; color: var(--text-gray);">Turn your extra tickets into cash. List for free and get paid fast in India.</p>
                <div style="display: flex; justify-content: center; gap: 40px; margin-bottom: 40px; flex-wrap: wrap;">
                    <div style="display: flex; gap: 10px; align-items: center;"><i class="fas fa-check" style="color: var(--primary);"></i> List in 60s</div>
                    <div style="display: flex; gap: 10px; align-items: center;"><i class="fas fa-check" style="color: var(--primary);"></i> Set your price</div>
                    <div style="display: flex; gap: 10px; align-items: center;"><i class="fas fa-check" style="color: var(--primary);"></i> Fast Payouts</div>
                </div>
                <a href="<?php echo esc_url(home_url('/sell-ticket/')); ?>" class="btn btn-primary">Start selling</a>
            </div>
        </div>
    </section>

    <!-- About -->
    <section class="section about-section" style="background-color: var(--card-bg);">
        <div class="container">
            <div class="section-header" style="justify-content: center; text-align: center;">
                <div class="section-title">
                    <h2>About TickerAdda</h2>
                    <p>India's safest and most trusted ticket marketplace</p>
                </div>
            </div>
            <div style="max-width: 800px; margin: 0 auto; text-align: center; color: var(--text-gray);">
                <p style="font-size: 18px; line-height: 1.6;">
                    TickerAdda is the premier destination for buying and selling tickets to live events in India.
                    Whether you're looking for sold-out concert tickets or want to sell extras for a game you can't attend,
                    we provide a secure platform with 100% money-back guarantee. Join thousands of fans who trust us for
                    their live entertainment needs.
                </p>
                <a href="<?php echo esc_url(home_url('/about/')); ?>" class="btn btn-outline" style="margin-top: 30px;">Read More</a>
            </div>
        </div>
    </section>

    <!-- Trust -->
    <section class="section trust-section">
        <div class="container">
            <div class="section-header" style="justify-content: center; text-align: center;">
                <div class="section-title">
                    <h2>Your safety is our priority</h2>
                </div>
            </div>
            <div class="grid grid-3">
                <div class="feature-box card">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <h3 class="feature-title">TickerAdda Guarantee</h3>
                    <p class="feature-desc">Every order is backed by our guarantee. Get your money back if your event is cancelled.</p>
                </div>
                <div class="feature-box card">
                    <div class="feature-icon"><i class="fas fa-user-check"></i></div>
                    <h3 class="feature-title">Verified Sellers</h3>
                    <p class="feature-desc">We require sellers to verify their identity (Aadhaar/PAN) and list only authentic tickets.</p>
                </div>
                <div class="feature-box card">
                    <div class="feature-icon"><i class="fas fa-lock"></i></div>
                    <h3 class="feature-title">Secure Payments</h3>
                    <p class="feature-desc">Your payment is encrypted. We support UPI, Cards, and Net Banking.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Top Sellers -->
    <section class="section featured-sellers" style="background: rgba(255,255,255,0.02);">
        <div class="container">
            <div class="section-header" style="justify-content: center; text-align: center;">
                <div class="section-title">
                    <h2>Top Rated Sellers</h2>
                    <p>Trusted by thousands of fans</p>
                </div>
            </div>
            <div class="grid grid-4" id="featuredSellersGrid">
                <p style="grid-column: 1/-1; text-align: center; color: var(--text-gray);">Loading top sellers...</p>
            </div>
        </div>
    </section>
</main>
<?php get_footer(); ?>