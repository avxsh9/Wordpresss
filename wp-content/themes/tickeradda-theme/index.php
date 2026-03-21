<?php get_header(); ?>

<main id="main">
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
        <section class="section">
            <div class="container">
                <h1><?php the_title(); ?></h1>
                <div><?php the_content(); ?></div>
            </div>
        </section>
    <?php endwhile; else : ?>
        <section class="section">
            <div class="container" style="text-align:center;">
                <h1>Page Not Found</h1>
                <p>Sorry, the page you are looking for does not exist.</p>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-primary" style="margin-top:24px;">Go Home</a>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php get_footer(); ?>
