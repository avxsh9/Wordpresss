document.addEventListener('DOMContentLoaded', async () => {
    const grid            = document.getElementById('playGrid');
    const searchInput     = document.getElementById('playSearch');
    const clearBtn        = document.getElementById('clearPlayFilters');
    const dateFilter      = document.getElementById('playDateFilter');
    const sortSelect      = document.getElementById('playSort');
    const countEl         = document.getElementById('playResultCount');
    const activeFiltersEl = document.getElementById('playActiveFilters');
    const priceRange      = document.getElementById('priceRange');
    const priceDisplay    = document.getElementById('priceRangeDisplay');

    let allShows    = [];
    let maxPriceAll = 10000;

    // ── Fetch play/theatre/comedy shows ───────────────────────────────────────
    try {
        const timestamp = Date.now();
        const [r1, r2, r3] = await Promise.all([
            fetch(`${TA.restUrl}/events-list?category=theatre&per_page=500&_t=${timestamp}`),
            fetch(`${TA.restUrl}/events-list?category=other&per_page=500&_t=${timestamp}`),
            fetch(`${TA.restUrl}/events-list?category=comedy&per_page=500&_t=${timestamp}`),
        ]);
        const t1 = await r1.json();
        const t2 = await r2.json();
        const t3 = await r3.json();
        allShows = [
            ...(Array.isArray(t1) ? t1 : []),
            ...(Array.isArray(t2) ? t2 : []),
            ...(Array.isArray(t3) ? t3 : []),
        ];

        // Dynamic price max
        const prices = allShows.map(e => e.price || 0).filter(p => p > 0);
        if (prices.length > 0) {
            maxPriceAll = Math.max(...prices);
            if (priceRange) { priceRange.max = maxPriceAll; priceRange.value = maxPriceAll; }
            if (priceDisplay) priceDisplay.textContent = `₹${maxPriceAll.toLocaleString('en-IN')}`;
        }

        buildCategoryFilters();
        buildCityFilters();
        applyFilters();
    } catch (e) {
        console.error('Play load error:', e);
        if (grid) grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:60px;color:#aaa;">
            <i class="fas fa-exclamation-circle fa-2x" style="color:#ef4444;margin-bottom:15px;display:block;"></i>
            Could not load plays. Please try again.</div>`;
    }

    // ── Dynamic Category Filter ───────────────────────────────────────────────
    function buildCategoryFilters() {
        const container = document.getElementById('categoryFilters');
        if (!container) return;

        const PLAY_KEYWORDS = {
            play:    ['play', 'natak', 'drama', 'stage'],
            comedy:  ['comedy', 'stand-up', 'standup', 'open mic', 'improv'],
            musical: ['musical', 'broadway', 'opera', 'song', 'dance'],
            festival:['festival', 'fest', 'mela', 'celebration'],
            concert: ['concert', 'performance', 'live'],
        };

        const detected = new Set();
        allShows.forEach(e => {
            const combined = (e.name + ' ' + (e.category || '') + ' ' + (e.description || '')).toLowerCase();
            Object.entries(PLAY_KEYWORDS).forEach(([cat, kws]) => {
                if (kws.some(kw => combined.includes(kw))) detected.add(cat);
            });
        });

        if (detected.size === 0) ['play', 'comedy', 'musical'].forEach(c => detected.add(c));

        const LABELS = { play: 'Plays / Drama', comedy: 'Stand-up & Comedy', musical: 'Musical / Dance', festival: 'Festival / Mela', concert: 'Concert / Live' };
        container.innerHTML = [...detected].map(cat => `
            <label class="filter-label">
                <input type="checkbox" class="type-filter" value="${cat}">
                ${LABELS[cat] || cat.charAt(0).toUpperCase() + cat.slice(1)}
            </label>`).join('');

        container.querySelectorAll('.type-filter').forEach(cb => cb.addEventListener('change', applyFilters));
    }

    // ── Dynamic City Filter ───────────────────────────────────────────────────
    function buildCityFilters() {
        const container = document.getElementById('cityFilters');
        if (!container) return;

        const citySet = new Set();
        allShows.forEach(e => {
            if (e.location) {
                const parts = String(e.location).split(',');
                const city  = parts[parts.length - 1].trim();
                if (city.length > 1 && city.length < 30) citySet.add(city.charAt(0).toUpperCase() + city.slice(1).toLowerCase());
            }
        });

        if (citySet.size === 0) { container.innerHTML = '<p style="color:#666;font-size:0.85rem;">No city data yet.</p>'; return; }

        container.innerHTML = [...citySet].slice(0, 8).map(city => `
            <label class="filter-label">
                <input type="checkbox" class="city-filter" value="${city.toLowerCase()}">
                ${city}
            </label>`).join('');

        container.querySelectorAll('.city-filter').forEach(cb => cb.addEventListener('change', applyFilters));
    }

    // ── Filter Logic ─────────────────────────────────────────────────────────
    function applyFilters() {
        const types    = [...document.querySelectorAll('.type-filter:checked')].map(el => el.value);
        const cities   = [...document.querySelectorAll('.city-filter:checked')].map(el => el.value);
        const maxPrice = priceRange ? parseInt(priceRange.value) : maxPriceAll;
        const query    = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const dateVal  = dateFilter ? dateFilter.value : '';

        const PLAY_KEYWORDS = {
            play:    ['play', 'natak', 'drama', 'stage'],
            comedy:  ['comedy', 'stand-up', 'standup', 'open mic'],
            musical: ['musical', 'broadway', 'dance'],
            festival:['festival', 'fest', 'mela'],
            concert: ['concert', 'performance', 'live'],
        };

        let filtered = allShows.filter(event => {
            const combined = (event.name + ' ' + (event.location || '') + ' ' + (event.category || '') + ' ' + (event.description || '')).toLowerCase();

            let matchType = types.length === 0;
            if (!matchType) matchType = types.some(t => (PLAY_KEYWORDS[t] || [t]).some(kw => combined.includes(kw)));

            const loc = (event.location || '').toLowerCase();
            const matchCity = cities.length === 0 || cities.some(c => loc.includes(c));

            const price = event.price || 0;
            const matchPrice = price === 0 || price <= maxPrice;

            const matchQuery = query === '' || combined.includes(query);
            const matchDate  = !dateVal || !event.date || event.date >= dateVal;

            return matchType && matchCity && matchPrice && matchQuery && matchDate;
        });

        const sort = sortSelect?.value || 'date_asc';
        filtered.sort((a, b) => {
            if (sort === 'date_asc')   return (a.date || '') > (b.date || '') ?  1 : -1;
            if (sort === 'date_desc')  return (a.date || '') < (b.date || '') ?  1 : -1;
            if (sort === 'price_asc')  return (a.price || 0) - (b.price || 0);
            if (sort === 'price_desc') return (b.price || 0) - (a.price || 0);
            if (sort === 'name_asc')   return (a.name || '') > (b.name || '') ?  1 : -1;
            return 0;
        });

        renderActivePills(types, cities, maxPrice);
        if (countEl) countEl.textContent = `${filtered.length} play${filtered.length !== 1 ? 's' : ''} found`;
        renderGrid(filtered);
    }

    // ── Listeners ─────────────────────────────────────────────────────────────
    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (dateFilter)  dateFilter.addEventListener('change', applyFilters);
    if (sortSelect)  sortSelect.addEventListener('change', applyFilters);
    if (priceRange) {
        priceRange.addEventListener('input', () => {
            if (priceDisplay) priceDisplay.textContent = `₹${parseInt(priceRange.value).toLocaleString('en-IN')}`;
            applyFilters();
        });
    }
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            document.querySelectorAll('.type-filter, .city-filter').forEach(cb => cb.checked = false);
            if (priceRange)  { priceRange.value = maxPriceAll; if (priceDisplay) priceDisplay.textContent = `₹${maxPriceAll.toLocaleString('en-IN')}`; }
            if (searchInput) searchInput.value = '';
            if (dateFilter)  dateFilter.value  = '';
            if (sortSelect)  sortSelect.value  = 'date_asc';
            applyFilters();
        });
    }

    // ── Active Filter Pills ───────────────────────────────────────────────────
    function renderActivePills(types, cities, maxPrice) {
        if (!activeFiltersEl) return;
        const pills = [];
        types.forEach(t  => pills.push({ label: t.charAt(0).toUpperCase() + t.slice(1), val: t, cls: 'type-filter' }));
        cities.forEach(c => pills.push({ label: c.charAt(0).toUpperCase() + c.slice(1), val: c, cls: 'city-filter' }));
        if (maxPrice < maxPriceAll) pills.push({ label: `Max ₹${maxPrice.toLocaleString('en-IN')}`, val: 'price', cls: 'price' });

        if (!pills.length) { activeFiltersEl.innerHTML = ''; return; }
        activeFiltersEl.innerHTML = pills.map(p => `
            <span class="active-filter-pill" data-val="${p.val}" data-cls="${p.cls}">
                ${p.label} <i class="fas fa-times" style="font-size:0.7rem;"></i>
            </span>`).join('');

        activeFiltersEl.querySelectorAll('.active-filter-pill').forEach(pill => {
            pill.addEventListener('click', () => {
                const val = pill.dataset.val, cls = pill.dataset.cls;
                if (cls === 'price') {
                    if (priceRange) { priceRange.value = maxPriceAll; if (priceDisplay) priceDisplay.textContent = `₹${maxPriceAll.toLocaleString('en-IN')}`; }
                } else {
                    const cb = document.querySelector(`input.${cls}[value="${val}"]`);
                    if (cb) cb.checked = false;
                }
                applyFilters();
            });
        });
    }

    // ── Render Cards ──────────────────────────────────────────────────────────
    function renderGrid(events) {
        if (!grid) return;
        if (!events || events.length === 0) {
            grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:80px 20px;color:#aaa;">
                <i class="fas fa-masks-theater fa-3x" style="color:var(--primary);margin-bottom:20px;display:block;"></i>
                <h3 style="color:#fff;margin-bottom:10px;">No plays match your filters</h3>
                <p style="margin-bottom:25px;">Try clearing filters or broadening your search.</p>
                <a href="${TA.homeUrl}sell-ticket/" class="btn btn-primary"><i class="fas fa-plus-circle"></i> List a Play / Event</a>
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
                : ``;

            const tickets = event.ticketCount > 0
                ? `<span style="color:#10b981;"><i class="fas fa-ticket-alt"></i> ${event.ticketCount} available</span>` : '';

            const sellUrl = `${TA.homeUrl}sell-ticket/?event_id=${event.id}&event_name=${encodeURIComponent(event.name)}&category=${encodeURIComponent(event.category || 'play')}&venue=${encodeURIComponent(event.location || '')}&date=${event.date || ''}&time=${encodeURIComponent(event.time || '')}`;

            return `
                <div class="event-card-premium" onclick="window.location.href='${event.url}'">
                    <div class="event-card-image">
                        <img src="${event.image}" alt="${event.name}" loading="lazy"
                             onerror="this.src='https://images.unsplash.com/photo-1507676184212-d03ab07a01bf?auto=format&fit=crop&w=600&q=80'">
                        <div class="event-card-category">${(event.category || 'PLAY').toUpperCase()}</div>
                        ${priceTag}
                    </div>
                    <div class="event-card-details">
                        <h3 class="event-card-title">${event.name}</h3>
                        <div class="event-card-meta">
                            <span><i class="far fa-calendar-alt"></i> ${fmtDate}${event.time ? ` · ${event.time}` : ''}</span>
                            <span><i class="fas fa-map-marker-alt"></i> ${event.location || 'Venue TBD'}</span>
                            ${tickets}
                        </div>
                    </div>
                    <div class="event-card-actions">
                        <button class="card-btn-primary" onclick="event.stopPropagation(); window.location.href='${event.url}'"><i class="fas fa-ticket-alt"></i> Buy Ticket</button>
                        <button class="card-btn-secondary" onclick="event.stopPropagation(); window.location.href='${sellUrl}'"><i class="fas fa-plus-circle"></i> Sell Tickets</button>
                    </div>
                </div>
            `;
        }).join('');
    }
});
