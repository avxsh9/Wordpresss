<?php
/**
 * Template Name: Theatre
 */
get_header();
?>

<style>
.theatre-page-container { display:flex; gap:30px; align-items:flex-start; }
.theatre-sidebar {
    width:260px; flex-shrink:0;
    background:var(--card-bg); border:1px solid var(--glass-border);
    border-radius:20px; padding:25px;
    position:sticky; top:100px;
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
.filter-label input { accent-color:var(--primary); width:16px; height:16px; cursor:pointer; }

input[type="range"] { width:100%; accent-color:var(--primary); margin:8px 0; }
.range-display { display:flex; justify-content:space-between; font-size:0.8rem; color:var(--text-gray); }

.theatre-main { flex:1; min-width:0; }
.theatre-header {
    display:flex; justify-content:space-between;
    align-items:center; margin-bottom:25px; flex-wrap:wrap; gap:15px;
}
.theatre-header h1 { font-size:2rem; margin:0; }
.active-filters { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:15px; min-height:28px; }
.active-filter-pill {
    background:rgba(59,130,246,0.15); border:1px solid rgba(59,130,246,0.4);
    color:var(--primary); padding:3px 12px; border-radius:20px;
    font-size:0.8rem; cursor:pointer; display:flex; align-items:center; gap:6px;
}
.active-filter-pill:hover { background:rgba(59,130,246,0.25); }
.sort-bar { display:flex; align-items:center; gap:12px; margin-bottom:20px; }
.sort-bar select {
    background:var(--card-bg); border:1px solid var(--glass-border);
    color:#fff; padding:8px 14px; border-radius:10px; cursor:pointer;
}
.search-input-wrap { position:relative; flex:1; max-width:320px; }
.search-input-wrap input {
    width:100%; padding:11px 18px; border-radius:30px;
    border:1px solid var(--glass-border); background:var(--card-bg);
    color:#fff; outline:none; font-size:0.95rem;
}
.search-input-wrap i {
    position:absolute; right:14px; top:50%;
    transform:translateY(-50%); color:var(--text-gray); pointer-events:none;
}
@media (max-width:900px) {
    .theatre-page-container { flex-direction:column; }
    .theatre-sidebar { width:100%; position:static; }
}
</style>

<main id="main">
    <section class="section" style="padding-top:120px; padding-bottom:60px;">
        <div class="container">
            <div class="theatre-page-container">

                <!-- Sidebar -->
                <aside class="theatre-sidebar">
                    <h3 style="margin:0 0 20px;font-size:1.1rem;color:#fff;">Filter Shows</h3>

                    <div class="filter-group">
                        <h4>Event Type</h4>
                        <label class="filter-label"><input type="checkbox" class="type-filter" value="drama"> Drama / Play</label>
                        <label class="filter-label"><input type="checkbox" class="type-filter" value="comedy"> Stand-up Comedy</label>
                        <label class="filter-label"><input type="checkbox" class="type-filter" value="concert"> Concert</label>
                        <label class="filter-label"><input type="checkbox" class="type-filter" value="festival"> Festival</label>
                        <label class="filter-label"><input type="checkbox" class="type-filter" value="musical"> Musical</label>
                    </div>

                    <div class="filter-group">
                        <h4>City</h4>
                        <label class="filter-label"><input type="checkbox" class="city-filter" value="mumbai"> Mumbai</label>
                        <label class="filter-label"><input type="checkbox" class="city-filter" value="delhi"> Delhi</label>
                        <label class="filter-label"><input type="checkbox" class="city-filter" value="bangalore"> Bengaluru</label>
                        <label class="filter-label"><input type="checkbox" class="city-filter" value="kolkata"> Kolkata</label>
                        <label class="filter-label"><input type="checkbox" class="city-filter" value="pune"> Pune</label>
                    </div>

                    <div class="filter-group">
                        <h4>Language</h4>
                        <label class="filter-label"><input type="checkbox" class="lang-filter" value="hindi"> Hindi</label>
                        <label class="filter-label"><input type="checkbox" class="lang-filter" value="english"> English</label>
                        <label class="filter-label"><input type="checkbox" class="lang-filter" value="marathi"> Marathi</label>
                        <label class="filter-label"><input type="checkbox" class="lang-filter" value="bengali"> Bengali</label>
                    </div>

                    <div class="filter-group">
                        <h4>Date From</h4>
                        <input type="date" id="theatreDateFilter" style="width:100%;padding:8px 12px;background:var(--card-bg);border:1px solid var(--glass-border);border-radius:10px;color:#fff;outline:none;">
                    </div>

                    <button id="clearTheatreFilters" class="btn btn-outline" style="width:100%;margin-top:8px;">
                        <i class="fas fa-times"></i> Clear All
                    </button>
                </aside>

                <!-- Main -->
                <div class="theatre-main">
                    <div class="theatre-header">
                        <div>
                            <h1 class="gradient-text">Theatre & Events</h1>
                            <p style="color:var(--text-gray);margin-top:4px;">Plays, concerts & live performances</p>
                        </div>
                        <div class="search-input-wrap">
                            <input type="text" id="theatreSearch" placeholder="Search shows...">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>

                    <div class="active-filters" id="theatreActiveFilters"></div>

                    <div class="sort-bar">
                        <span id="theatreResultCount" style="color:var(--text-gray);font-size:0.9rem;">Loading...</span>
                        <div style="margin-left:auto;display:flex;align-items:center;gap:10px;">
                            <span style="color:var(--text-gray);font-size:0.9rem;">Sort:</span>
                            <select id="theatreSort">
                                <option value="date_asc">Date (Earliest)</option>
                                <option value="date_desc">Date (Latest)</option>
                                <option value="name_asc">Name (A-Z)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-3" id="theatreGrid">
                        <div style="grid-column:1/-1;text-align:center;padding:80px;color:var(--text-gray);">
                            <i class="fas fa-spinner fa-spin fa-3x" style="color:var(--primary);"></i>
                            <p style="margin-top:20px;">Loading shows...</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>
