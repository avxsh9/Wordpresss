document.addEventListener('DOMContentLoaded', async () => {
    const grid            = document.getElementById('sportsGrid');
    const searchInput     = document.getElementById('sportsSearch');
    const clearBtn        = document.getElementById('clearSportsFilters');
    const priceRange      = document.getElementById('priceRange');
    const priceDisplay    = document.getElementById('priceRangeDisplay');
    const dateFilter      = document.getElementById('dateFilter');
    const sortSelect      = document.getElementById('sportsSort');
    const countEl         = document.getElementById('sportsResultCount');
    const activeFiltersEl = document.getElementById('activeFiltersContainer');

    let allSports   = [];
    let maxPriceAll = 10000;

    // ── Fetch all sports events ──────────────────────────────────────────────
    try {
        const timestamp = Date.now();
        const res = await fetch(`${TA.restUrl}/events-list?category=sports&per_page=500&_t=${timestamp}`);
        const data = await res.json();
        allSports = Array.isArray(data) ? data : [];

        // Dynamically set max price based on actual listings
        const prices = allSports.map(e => e.price || 0).filter(p => p > 0);
        if (prices.length > 0) {
            maxPriceAll = Math.max(...prices);
            if (priceRange) {
                priceRange.max   = maxPriceAll;
                priceRange.value = maxPriceAll;
            }
            if (priceDisplay) priceDisplay.textContent = `₹${maxPriceAll.toLocaleString('en-IN')}`;
        }

        // Build dynamic sport-type checkboxes from actual team/name data
        buildDynamicSportFilters();
        buildDynamicCityFilters();

        applyFilters();
    } catch (e) {
        console.error('Sports load error:', e);
        if (grid) grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:60px;color:#aaa;">
            <i class="fas fa-exclamation-circle fa-2x" style="color:#ef4444;margin-bottom:15px;display:block;"></i>
            Could not load sports events. Please try again.</div>`;
    }

    // ── Dynamic Sport-Type Filter Builder ────────────────────────────────────
    function buildDynamicSportFilters() {
        const container = document.getElementById('sportTypeFilters');
        if (!container) return;

        // Detect sport type from teams or title
        const SPORT_KEYWORDS = {
            cricket:  ['ipl','cricket','test','odi','rcb','csk','mi','kkr','srh','gt','pk','dc','rr','lsg'],
            football: ['isl','football','fc','united','city','fc goa','blasters','bengaluru fc'],
            kabaddi:  ['kabaddi','pro kabaddi','pkl'],
            basketball:['nba','basketball'],
            tennis:   ['tennis','atp','wta'],
            hockey:   ['hockey','nhl'],
        };

        const detected = new Set();
        allSports.forEach(e => {
            const combined = (e.name + ' ' + (e.teams || '')).toLowerCase();
            Object.entries(SPORT_KEYWORDS).forEach(([sport, kws]) => {
                if (kws.some(kw => combined.includes(kw))) detected.add(sport);
            });
        });

        if (detected.size === 0) {
            // Fallback to defaults
            ['cricket', 'football', 'kabaddi'].forEach(s => detected.add(s));
        }

        container.innerHTML = [...detected].map(sport => `
            <label class="filter-label">
                <input type="checkbox" class="sport-filter" value="${sport}">
                ${sport.charAt(0).toUpperCase() + sport.slice(1)}
            </label>
        `).join('');

        // Attach change listeners
        container.querySelectorAll('.sport-filter').forEach(cb => {
            cb.addEventListener('change', applyFilters);
        });
    }

    // ── Dynamic City Filter Builder ───────────────────────────────────────────
    function buildDynamicCityFilters() {
        const container = document.getElementById('cityFilters');
        if (!container) return;

        const citySet = new Set();
        allSports.forEach(e => {
            if (e.location) {
                // Extract city from location (usually "Stadium, City" format)
                const parts = e.location.split(',');
                const city  = parts[parts.length - 1].trim().toLowerCase();
                if (city.length > 2 && city.length < 30) citySet.add(city);
            }
        });

        if (citySet.size === 0) {
            container.innerHTML = '<p style="color:#666;font-size:0.85rem;">No city data yet.</p>';
            return;
        }

        container.innerHTML = [...citySet].slice(0, 8).map(city => `
            <label class="filter-label">
                <input type="checkbox" class="city-filter" value="${city}">
                ${city.charAt(0).toUpperCase() + city.slice(1)}
            </label>
        `).join('');

        container.querySelectorAll('.city-filter').forEach(cb => {
            cb.addEventListener('change', applyFilters);
        });
    }

    // ── Filter Logic ─────────────────────────────────────────────────────────
    function applyFilters() {
        const sportTypes = [...document.querySelectorAll('.sport-filter:checked')].map(el => el.value);
        const cities     = [...document.querySelectorAll('.city-filter:checked')].map(el => el.value);
        const maxPrice   = priceRange ? parseInt(priceRange.value) : maxPriceAll;
        const dateVal    = dateFilter ? dateFilter.value : '';
        const query      = searchInput ? searchInput.value.toLowerCase().trim() : '';

        let filtered = allSports.filter(event => {
            const combined = (event.name + ' ' + (event.location || '') + ' ' + (event.teams || '') + ' ' + (event.description || '')).toLowerCase();

            // Sport type filter — match against combined text
            const SPORT_KEYWORDS = {
                cricket:  ['ipl','cricket','rcb','csk','mi','kkr','srh','gt','pk','dc','rr'],
                football: ['isl','football','fc goa','blasters','bengaluru fc'],
                kabaddi:  ['kabaddi','pkl'],
                basketball:['basketball'],
                tennis:   ['tennis'],
                hockey:   ['hockey'],
            };
            let matchSport = sportTypes.length === 0;
            if (!matchSport) {
                matchSport = sportTypes.some(s => (SPORT_KEYWORDS[s] || [s]).some(kw => combined.includes(kw)));
            }

            // City filter
            const loc = (event.location || '').toLowerCase();
            const matchCity = cities.length === 0 || cities.some(c => loc.includes(c));

            // Full text search
            const matchQuery = query === '' || combined.includes(query);

            // Date filter
            let matchDate = true;
            if (dateVal && event.date) matchDate = event.date >= dateVal;

            // Price filter (free if no tickets OR price within range)
            const price    = event.price || 0;
            const matchPrice = price === 0 || price <= maxPrice;

            return matchSport && matchCity && matchQuery && matchDate && matchPrice;
        });

        // Sorting
        const sort = sortSelect ? sortSelect.value : 'date_asc';
        filtered.sort((a, b) => {
            if (sort === 'date_asc')   return (a.date || '') > (b.date || '') ?  1 : -1;
            if (sort === 'date_desc')  return (a.date || '') < (b.date || '') ?  1 : -1;
            if (sort === 'price_asc')  return (a.price || 0) - (b.price || 0);
            if (sort === 'price_desc') return (b.price || 0) - (a.price || 0);
            if (sort === 'name_asc')   return (a.name || '') > (b.name || '') ?  1 : -1;
            return 0;
        });

        renderActivePills(sportTypes, cities, maxPrice);
        if (countEl) countEl.textContent = `${filtered.length} event${filtered.length !== 1 ? 's' : ''} found`;
        renderSports(filtered);
    }

    // ── Price slider live update ─────────────────────────────────────────────
    if (priceRange) {
        priceRange.addEventListener('input', () => {
            if (priceDisplay) priceDisplay.textContent = `₹${parseInt(priceRange.value).toLocaleString('en-IN')}`;
            applyFilters();
        });
    }

    // ── Other listeners ──────────────────────────────────────────────────────
    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (dateFilter)  dateFilter.addEventListener('change', applyFilters);
    if (sortSelect)  sortSelect.addEventListener('change', applyFilters);
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            document.querySelectorAll('.sport-filter, .city-filter').forEach(cb => cb.checked = false);
            if (priceRange)   { priceRange.value = maxPriceAll; if (priceDisplay) priceDisplay.textContent = `₹${maxPriceAll.toLocaleString('en-IN')}`; }
            if (dateFilter)   dateFilter.value   = '';
            if (searchInput)  searchInput.value  = '';
            if (sortSelect)   sortSelect.value   = 'date_asc';
            applyFilters();
        });
    }

    // ── Active Filter Pills ──────────────────────────────────────────────────
    function renderActivePills(sportTypes, cities, maxPrice) {
        if (!activeFiltersEl) return;
        const pills = [];
        sportTypes.forEach(s => pills.push({ label: s.charAt(0).toUpperCase() + s.slice(1), val: s, type: 'sport' }));
        cities.forEach(c => pills.push({ label: c.charAt(0).toUpperCase() + c.slice(1), val: c, type: 'city' }));
        if (maxPrice < maxPriceAll) pills.push({ label: `Max ₹${maxPrice.toLocaleString('en-IN')}`, val: 'price', type: 'price' });

        if (pills.length === 0) { activeFiltersEl.innerHTML = ''; return; }

        activeFiltersEl.innerHTML = pills.map(p => `
            <span class="active-filter-pill" data-val="${p.val}" data-type="${p.type}" title="Remove filter">
                ${p.label} <i class="fas fa-times" style="font-size:0.7rem;"></i>
            </span>
        `).join('');

        activeFiltersEl.querySelectorAll('.active-filter-pill').forEach(pill => {
            pill.addEventListener('click', () => {
                const val  = pill.dataset.val;
                const type = pill.dataset.type;
                if (type === 'price') {
                    if (priceRange) { priceRange.value = maxPriceAll; if (priceDisplay) priceDisplay.textContent = `₹${maxPriceAll.toLocaleString('en-IN')}`; }
                } else {
                    const cb = document.querySelector(`input[value="${val}"]`);
                    if (cb) cb.checked = false;
                }
                applyFilters();
            });
        });
    }

    // ── Render Event Cards ───────────────────────────────────────────────────
    function renderSports(events) {
        if (!grid) return;
        if (!events || events.length === 0) {
            grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:80px 20px;color:#aaa;">
                <i class="fas fa-ticket-alt fa-3x" style="color:var(--primary);margin-bottom:20px;display:block;"></i>
                <h3 style="color:#fff;margin-bottom:10px;">No listings match your filters</h3>
                <p style="margin-bottom: 25px;">Try clearing filters or broadening your search.</p>
                <a href="${TA.homeUrl}sell-ticket/" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Be the First to Sell</a>
            </div>`;
            return;
        }

        grid.innerHTML = events.map(event => {
            const dateObj = event.date ? new Date(event.date) : null;
            const fmtDate = dateObj && !isNaN(dateObj)
                ? dateObj.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
                : 'Date TBD';

            const priceTag = event.price > 0
                ? `<span class="price-tag">From ₹${event.price.toLocaleString('en-IN')}</span>`
                : `<span class="price-tag free-tag"><i class="fas fa-gift"></i> Free</span>`;

            const sellUrl = `${TA.homeUrl}sell-ticket/?event_id=${event.id}&event_name=${encodeURIComponent(event.name)}&category=sports&venue=${encodeURIComponent(event.location||'')}&date=${event.date||''}&time=${encodeURIComponent(event.time||'')}`;

            const teams = event.teams ? `<span><i class="fas fa-users" style="color:var(--primary);"></i> ${event.teams}</span>` : '';
            const tickets = event.ticketCount > 0 ? `<span style="color:#10b981;"><i class="fas fa-ticket-alt"></i> ${event.ticketCount} ticket${event.ticketCount !== 1 ? 's' : ''} available</span>` : '';

            return `
                <div class="event-card-premium" onclick="window.location.href='${event.url}'">
                    <div class="event-card-image">
                        <img src="${event.image}" alt="${event.name}" loading="lazy"
                             onerror="this.src='https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?auto=format&fit=crop&w=600&q=80'">
                        <div class="event-card-category">SPORTS</div>
                        ${priceTag}
                    </div>
                    <div class="event-card-details">
                        <h3 class="event-card-title">${event.name}</h3>
                        <div class="event-card-meta">
                            <span><i class="far fa-calendar-alt"></i> ${fmtDate}${event.time ? ` · ${event.time}` : ''}</span>
                            <span><i class="fas fa-map-marker-alt"></i> ${event.location || 'Venue TBD'}</span>
                            ${teams}
                            ${tickets}
                        </div>
                    </div>
                    <div class="event-card-actions">
                        <button class="card-btn-primary" onclick="event.stopPropagation(); window.location.href='${event.url}'"><i class="fas fa-ticket-alt"></i> Get For Free</button>
                        <button class="card-btn-secondary" onclick="event.stopPropagation(); window.location.href='${sellUrl}'"><i class="fas fa-plus-circle"></i> Sell Tickets</button>
                    </div>
                </div>
            `;
        }).join('');
    }
});
