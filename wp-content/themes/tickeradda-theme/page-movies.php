<?php
/**
 * Template Name: Movies
 * Description: Movies archive Template
 */
get_header();
?>
<style>
.movies-page-container { display:flex; gap:30px; align-items:flex-start; }

/* Sidebar */
.movies-sidebar {
    width:260px; flex-shrink:0;
    background:var(--card-bg);
    border:1px solid var(--glass-border);
    border-radius:20px;
    padding:25px;
    position:sticky;
    top:100px;
}
.filter-group { margin-bottom:22px; }
.filter-group:last-child { margin-bottom:0; }
.filter-group h4 {
    font-size:1rem; margin-bottom:10px; color:#fff;
    border-bottom:1px solid rgba(255,255,255,0.1);
    padding-bottom:8px;
}
.filter-label {
    display:flex; align-items:center; gap:10px;
    margin-bottom:9px; color:var(--text-gray);
    cursor:pointer; font-size:0.9rem; transition:color 0.2s;
}
.filter-label:hover { color:#fff; }
.filter-label input { 
    accent-color:var(--primary);
    width:16px; height:16px; cursor:pointer;
}

/* Header */
.movies-header {
    display:flex; justify-content:space-between;
    align-items:center; margin-bottom:25px; flex-wrap:wrap; gap:15px;
}
.movies-header h1 { font-size:2rem; margin:0; }
.movies-sub-desc { color:var(--text-gray); font-size:1rem; margin-top:4px; }

/* Active filter pills */
.active-filters { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:15px; min-height:28px; }
.active-filter-pill {
    background:rgba(59,130,246,0.15); border:1px solid rgba(59,130,246,0.4);
    color:var(--primary); padding:3px 12px; border-radius:20px;
    font-size:0.8rem; cursor:pointer; display:flex; align-items:center; gap:6px;
}
.active-filter-pill:hover { background:rgba(59,130,246,0.25); }

/* Result count */
.result-count { color:var(--text-gray); font-size:0.9rem; margin-bottom:20px; }

/* Sort */
.sort-bar { display:flex; align-items:center; gap:12px; margin-bottom:20px; }
.sort-bar select {
    background:var(--card-bg); border:1px solid var(--glass-border);
    color:#fff; padding:8px 14px; border-radius:10px; cursor:pointer;
}

.movies-main { flex:1; min-width:0; }

@media (max-width:900px) {
    .movies-page-container { flex-direction:column; }
    .movies-sidebar { width:100%; position:static; }
}

.search-input-wrap {
    position:relative; flex:1; max-width:320px;
}
.search-input-wrap input {
    width:100%; padding:11px 18px;
    border-radius:30px; border:1px solid var(--glass-border);
    background:var(--card-bg); color:#fff; outline:none; font-size:0.95rem;
}
.search-input-wrap i {
    position:absolute; right:14px; top:50%;
    transform:translateY(-50%); color:var(--text-gray); pointer-events:none;
}
</style>

<main id="main">
    <section class="section" style="padding-bottom:60px;">
        <div class="container">

            <div class="movies-page-container">
                <!-- ── Sidebar ──────────────────────────────── -->
                <aside class="movies-sidebar">
                    <h3 style="margin:0 0 20px; font-size:1.1rem; color:#fff;">Filter Movies</h3>

                    <!-- Languages (built dynamically by movies.js) -->
                    <div class="filter-group">
                        <h4>Language</h4>
                        <div id="langFilters">
                            <!-- Defaults shown while JS loads -->
                            <label class="filter-label"><input type="checkbox" class="lang-filter" value="hindi"> Hindi</label>
                            <label class="filter-label"><input type="checkbox" class="lang-filter" value="english"> English</label>
                            <label class="filter-label"><input type="checkbox" class="lang-filter" value="tamil"> Tamil</label>
                            <label class="filter-label"><input type="checkbox" class="lang-filter" value="telugu"> Telugu</label>
                        </div>
                    </div>

                    <!-- City (built dynamically) -->
                    <div class="filter-group">
                        <h4>City</h4>
                        <div id="cityFilters">
                            <p style="color:#666;font-size:0.85rem;">Loading cities...</p>
                        </div>
                    </div>

                    <!-- Certificate -->
                    <div class="filter-group">
                        <h4>Certificate</h4>
                        <label class="filter-label"><input type="checkbox" class="cert-filter" value="U"> U</label>
                        <label class="filter-label"><input type="checkbox" class="cert-filter" value="UA"> UA</label>
                        <label class="filter-label"><input type="checkbox" class="cert-filter" value="A"> A</label>
                    </div>

                    <!-- Price Range -->
                    <div class="filter-group">
                        <h4>Max Price (₹)</h4>
                        <input type="range" id="priceRange" min="0" max="10000" step="500" value="10000">
                        <div class="range-display">
                            <span>₹0</span>
                            <span id="priceRangeDisplay">₹10,000</span>
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="filter-group">
                        <h4>From Date</h4>
                        <input type="date" id="dateFilter" style="width:100%;padding:8px 12px;background:var(--card-bg);border:1px solid var(--glass-border);border-radius:10px;color:#fff;outline:none;">
                    </div>

                    <button id="clearFiltersBtn" class="btn btn-outline" style="width:100%;margin-top:8px;">
                        <i class="fas fa-times"></i> Clear All
                    </button>
                </aside>

                <!-- ── Main Content ─────────────────────────── -->
                <div class="movies-main">
                    <div class="movies-header">
                        <div>
                            <h1 class="gradient-text">Movies</h1>
                            <p class="movies-sub-desc">Book verified movie tickets at the best prices</p>
                        </div>
                        <div class="search-input-wrap">
                            <input type="text" id="movieSearch" placeholder="Search movies...">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>

                    <div class="active-filters" id="activeFiltersContainer"></div>

                    <div class="sort-bar">
                        <span style="color:var(--text-gray);font-size:0.9rem;" id="moviesResultCount">Loading...</span>
                        <div style="margin-left:auto;display:flex;align-items:center;gap:10px;">
                            <span style="color:var(--text-gray);font-size:0.9rem;">Sort by:</span>
                            <select id="moviesSort">
                                <option value="date_desc">Latest (Default)</option>
                                <option value="date_asc">Date (Earliest)</option>
                                <option value="price_asc">Price (Low to High)</option>
                                <option value="price_desc">Price (High to Low)</option>
                                <option value="name_asc">Name (A-Z)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-3" id="moviesGrid">
                        <div style="grid-column:1/-1;text-align:center;padding:80px;color:var(--text-gray);">
                            <i class="fas fa-spinner fa-spin fa-3x" style="color:var(--primary);"></i>
                            <p style="margin-top:20px;">Loading movies...</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</main>

<?php get_footer(); ?>

