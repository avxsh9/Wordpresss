<?php get_header(); ?>

<main id="main">
    <?php if (have_posts()) : while (have_posts()) : the_post(); 
        $event_id = get_the_ID();
        $date = get_post_meta($event_id, 'event_date', true);
        $time = get_post_meta($event_id, 'event_time', true);
        $location = get_post_meta($event_id, 'event_location', true);
        $image = get_the_post_thumbnail_url($event_id, 'full') ?: 'https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?auto=format&fit=crop&w=1200&q=80';
    ?>
    
    <!-- Event Hero -->
    <section class="event-hero-premium" style="background: linear-gradient(to bottom, rgba(5,5,5,0.3) 0%, rgba(5,5,5,0.95) 100%), url('<?php echo esc_url($image); ?>');">
        <div class="container">
            <div class="hero-inner">
                <div class="hero-meta-top">
                    <span class="premium-badge">
                        <?php 
                        $terms = wp_get_post_terms($event_id, 'event_cat');
                        echo !empty($terms) ? esc_html($terms[0]->name) : 'Event';
                        ?>
                    </span>
                </div>
                <h1 class="hero-title-main"><?php the_title(); ?></h1>
                
                <div class="hero-info-bar">
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
                </div>

                <div class="hero-cta-group">
                    <a href="#tickets-section" class="btn btn-primary btn-xxl"><i class="fas fa-bolt"></i> GET TICKETS NOW</a>
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
