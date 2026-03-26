<?php get_header(); ?>

<main id="main">
    <?php if (have_posts()) : while (have_posts()) : the_post(); 
        $event_id   = get_the_ID();
        $post_type  = get_post_type($event_id);
        $date       = get_post_meta($event_id, 'event_date', true);
        $time       = get_post_meta($event_id, 'event_time', true);
        $location   = get_post_meta($event_id, 'event_location', true) ?: get_post_meta($event_id, 'venue', true);
        $image      = get_post_meta($event_id, 'poster_url', true) ?: get_the_post_thumbnail_url($event_id, 'full') ?: 'https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?auto=format&fit=crop&w=1200&q=80';
        
        // Detect if this is a movie
        $cat_terms    = wp_get_post_terms($event_id, 'event_cat', array('fields' => 'slugs'));
        $is_movie     = ($post_type === 'movies') || in_array('movies', $cat_terms) || in_array('movie', $cat_terms);
        
        // Movie-specific meta
        $movie_cert   = get_post_meta($event_id, 'movieCert', true) ?: get_post_meta($event_id, 'certificate', true) ?: 'UA';
        $movie_rating = get_post_meta($event_id, 'movieRating', true) ?: get_post_meta($event_id, 'imdb_rating', true) ?: '';
        $movie_lang   = get_post_meta($event_id, 'movieLanguage', true) ?: get_post_meta($event_id, 'language', true) ?: '';
    ?>
    
    <!-- Event Hero -->
    <style>
    @media (max-width: 768px) {
        .hero-info-bar { gap: 12px !important; flex-wrap: wrap !important; }
        .hero-title-main { font-size: clamp(22px, 7vw, 38px) !important; }
        .col-md-6 { display: flex !important; flex-direction: column !important; gap: 10px !important; width: 100% !important; }
        .col-md-6 .btn { width: 100% !important; justify-content: center !important; font-size: 14px !important; padding: 12px 16px !important; }
        .event-hero-premium { padding: 90px 0 40px !important; min-height: auto !important; }
        .hero-inner { width: 100% !important; }
        .event-grid-layout { grid-template-columns: 1fr !important; }
        .details-column { display: none !important; }
    }
    </style>
    <section class="event-hero-premium" style="background: linear-gradient(to bottom, rgba(5,5,5,0.3) 0%, rgba(5,5,5,0.95) 100%), url('<?php echo esc_url($image); ?>')">
        <div class="container">
            <div class="hero-inner">
                <div class="hero-meta-top">
                    <span class="premium-badge">
                        <?php 
                        $terms = wp_get_post_terms($event_id, 'event_cat');
                        echo !empty($terms) ? esc_html($terms[0]->name) : ($is_movie ? 'Movie' : 'Event');
                        ?>
                    </span>
                </div>
                <h1 class="hero-title-main"><?php the_title(); ?></h1>
                
                <div class="hero-info-bar">
                    <?php if ($is_movie) : ?>
                        <!-- MOVIE: Show Certificate + IMDB Rating + Language -->
                        <div class="info-item">
                            <i class="fas fa-shield-alt"></i>
                            <div class="info-text">
                                <span class="info-label">Certificate</span>
                                <span class="info-value"><?php echo esc_html($movie_cert); ?></span>
                            </div>
                        </div>
                        <?php if ($movie_rating) : ?>
                        <div class="info-item">
                            <i class="fas fa-star" style="color:#f59e0b;"></i>
                            <div class="info-text">
                                <span class="info-label">IMDB</span>
                                <span class="info-value" style="color:#f59e0b;font-weight:800;"><?php echo esc_html($movie_rating); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($movie_lang) : ?>
                        <div class="info-item">
                            <i class="fas fa-language"></i>
                            <div class="info-text">
                                <span class="info-label">Language</span>
                                <span class="info-value"><?php echo esc_html($movie_lang); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($location) : ?>
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="info-text">
                                <span class="info-label">Venue</span>
                                <span class="info-value"><?php echo esc_html($location); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php else : ?>
                        <!-- NON-MOVIE EVENT: Show Date + Time + Venue -->
                        <div class="info-item">
                            <i class="far fa-calendar-alt"></i>
                            <div class="info-text">
                                <span class="info-label">Date</span>
                                <span class="info-value"><?php echo esc_html($date ? date('F j, Y', strtotime($date)) : 'Date TBD'); ?></span>
                            </div>
                        </div>
                        <?php if ($time) : ?>
                        <div class="info-item">
                            <i class="far fa-clock"></i>
                            <div class="info-text">
                                <span class="info-label">Time</span>
                                <span class="info-value"><?php echo esc_html(date('h:i A', strtotime($time))); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="info-text">
                                <span class="info-label">Venue</span>
                                <span class="info-value"><?php echo esc_html($location ?: 'Venue TBD'); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 12px; padding: 12px 20px; margin-bottom: 25px; display: inline-flex; align-items: center; gap: 12px;">
                    <i class="fas fa-info-circle" style="color: var(--primary);"></i>
                    <span style="font-size: 0.9rem; color: #fff;">TicketAdda currently charges <strong>Zero Commission</strong> for all users. No service fees!</span>
                </div>
                <div class="col-md-6 text-md-right text-center">
                    <a href="#tickets-section" class="btn btn-primary btn-xxl"><i class="fas fa-ticket-alt"></i> CONTACT SELLER / BUY TICKET</a>
                    <a href="<?php echo esc_url(home_url('/sell-ticket/?event_id=' . $event_id)); ?>" class="btn btn-glass btn-xxl"><i class="fas fa-plus"></i> SELL TICKETS</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Event Content & Tickets -->
    <section class="section-premium" id="tickets-section">
        <div class="container">
            <div class="event-grid-layout">
                
                <div class="listings-column">
                    <div class="section-header-compact">
                        <div class="header-left">
                            <h2 class="section-heading">Available Listings</h2>
                            <p class="section-subheading">Verified tickets from trusted fans</p>
                        </div>
                        <div class="header-right">
                            <span id="ticketCountBadge" class="count-badge">Checking availability...</span>
                        </div>
                    </div>
                    
                    <div id="eventTicketsGrid" class="tickets-container">
                        <!-- Tickets dynamically loaded -->
                        <div class="loading-state">
                            <div class="loader-ring"></div>
                            <p>Finding the best seats for you...</p>
                        </div>
                    </div>
                </div>

                <div class="details-column">
                    <div class="details-card-sticky">
                        <div class="card-inner">
                            <h3 class="card-title">About this Event</h3>
                            <div class="event-description">
                                <?php the_content(); ?>
                            </div>
                            
                            <div class="sell-cta-box">
                                <h4>Own tickets for this show?</h4>
                                <p>Join 10k+ sellers and get the best price for your extra tickets.</p>
                                <a href="<?php echo esc_url(home_url('/sell-ticket/?event_id=' . $event_id)); ?>" class="btn btn-primary-outline">List Your Tickets</a>
                            </div>

                            <div class="guarantee-box">
                                <i class="fas fa-shield-alt"></i>
                                <span>100% TickerAdda Buyer Guarantee</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <?php endwhile; endif; ?>
</main>

<script>
    // Pass event ID to JS
    window.currentEventId = <?php echo get_the_ID(); ?>;
</script>

<?php 
// Enqueue single event JS
wp_enqueue_script('ta-single-event', get_template_directory_uri() . '/assets/js/public/single-event.js', array('ta-common'), time(), true);
get_footer(); ?>
