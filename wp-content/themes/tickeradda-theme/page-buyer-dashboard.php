<?php
/**
 * Template Name: Buyer Dashboard
 */
get_header();
?>

<style>
.tab-btn {
            background: none;
            border: none;
            color: #888;
            padding: 10px 20px;
            font-size: 1rem;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: 0.3s;
            font-family: 'Outfit', sans-serif;
        }
        .tab-btn.active {
            color: var(--primary-color, #3b82f6);
            border-bottom-color: var(--primary-color, #3b82f6);
            font-weight: 600;
        }
        .tab-btn:hover {
            color: #fff;
        }
        /* Reusing Card Styles from main.css */
        .listings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .listing-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
        }
</style>

<main id="main">
    <div class="container section">
        <h1 class="gradient-text mb-4">My Tickets</h1>
        <div id="buying-section">
            <div id="my-orders-grid" class="grid grid-3">
                <div style="text-align: center; color: #aaa; padding: 20px;">Loading...</div>
            </div>
        </div>
    </div>
    </style>

<main id="main">
    <div class="container section">
        <h1 class="gradient-text mb-4" style="font-size: 2.5rem; margin-bottom: 30px;">My Tickets</h1>
        <div id="buying-section">
            <div id="my-orders-grid" class="grid grid-3">
                <div style="text-align: center; color: #aaa; padding: 40px; background: rgba(255,255,255,0.02); border-radius: 20px; border: 1px solid rgba(255,255,255,0.05);">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 15px; display: block;"></i>
                    Loading your tickets...
                </div>
            </div>
        </div>
    </div>
<?php get_footer(); ?>
