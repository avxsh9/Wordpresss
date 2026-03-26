document.addEventListener('DOMContentLoaded', async () => {
    const grid            = document.getElementById('moviesGrid');
    const searchInput     = document.getElementById('movieSearch');
    const clearBtn        = document.getElementById('clearFiltersBtn');
    const countEl         = document.getElementById('moviesResultCount');
    const sortSelect      = document.getElementById('moviesSort');
    const activeFiltersEl = document.getElementById('activeFiltersContainer');
    const priceRange      = document.getElementById('priceRange');
    const priceDisplay    = document.getElementById('priceRangeDisplay');
    const dateFilter      = document.getElementById('dateFilter');

    let allMovies   = [];
    let maxPriceAll = 10000;

    // ── Fetch all movies ──────────────────────────────────────────────────────
    try {
        const timestamp = Date.now();
        const res = await fetch(`${TA.restUrl}/events-list?category=movies&per_page=500&_t=${timestamp}`);
        const data = await res.json();
        allMovies = Array.isArray(data) ? data : [];

        // Dynamic price max
        const prices = allMovies.map(e => e.price || 0).filter(p => p > 0);
        if (prices.length > 0) {
            maxPriceAll = Math.max(...prices);
            if (priceRange) { priceRange.max = maxPriceAll; priceRange.value = maxPriceAll; }
            if (priceDisplay) priceDisplay.textContent = `₹${maxPriceAll.toLocaleString('en-IN')}`;
        }

        // Build dynamic language filters from real data
        buildLanguageFilters();
        buildCityFilters();

        applyFilters();
    } catch (err) {
        console.error('Movies load error:', err);
        if (grid) grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:60px;color:#aaa;">
            <i class="fas fa-exclamation-circle fa-2x" style="color:#ef4444;margin-bottom:15px;display:block;"></i>
            <p>Could not load movies. Please try again.</p>
        </div>`;
    }

    // ── Dynamic Language Filter Builder ───────────────────────────────────────
    function buildLanguageFilters() {
        const container = document.getElementById('langFilters');
        if (!container) return;

        const langSet = new Set();
        allMovies.forEach(e => {
            if (e.movieLanguage) {
                // Handle comma separated languages like "Hindi, English"
                String(e.movieLanguage).split(',').forEach(l => {
                    const lang = l.trim();
                    if (lang.length > 1) langSet.add(lang.charAt(0).toUpperCase() + lang.slice(1).toLowerCase());
                });
            }
        });

        // Fallback defaults
        if (langSet.size === 0) {
            ['Hindi', 'English', 'Tamil', 'Telugu', 'Kannada', 'Marathi'].forEach(l => langSet.add(l));
        }

        container.innerHTML = [...langSet].map(lang => `
            <label class="filter-label">
                <input type="checkbox" class="lang-filter" value="${lang.toLowerCase()}">
                ${lang}
            </label>
        `).join('');

        container.querySelectorAll('.lang-filter').forEach(cb => cb.addEventListener('change', applyFilters));
    }

    // ── Dynamic City Filter Builder ───────────────────────────────────────────
    function buildCityFilters() {
        const container = document.getElementById('cityFilters');
        if (!container) return;

        const citySet = new Set();
        allMovies.forEach(e => {
            if (e.location) {
                const parts = String(e.location).split(',');
                const city  = parts[parts.length - 1].trim();
                if (city.length > 1 && city.length < 30) citySet.add(city.charAt(0).toUpperCase() + city.slice(1).toLowerCase());
            }
        });

        if (citySet.size === 0) {
            container.innerHTML = '<p style="color:#666;font-size:0.85rem;">No city data yet.</p>';
            return;
        }

        container.innerHTML = [...citySet].slice(0, 8).map(city => `
            <label class="filter-label">
                <input type="checkbox" class="city-filter" value="${city.toLowerCase()}">
                ${city}
            </label>
        `).join('');

        container.querySelectorAll('.city-filter').forEach(cb => cb.addEventListener('change', applyFilters));
    }

    // ── Filter Logic ─────────────────────────────────────────────────────────
    function applyFilters() {
        const langs    = [...document.querySelectorAll('.lang-filter:checked')].map(el => el.value);
        const certs    = [...document.querySelectorAll('.cert-filter:checked')].map(el => el.value);
        const cities   = [...document.querySelectorAll('.city-filter:checked')].map(el => el.value);
        const maxPrice = priceRange ? parseInt(priceRange.value) : maxPriceAll;
        const dateVal  = dateFilter ? dateFilter.value : '';
        const query    = (searchInput?.value || '').toLowerCase().trim();

        let filtered = allMovies.filter(event => {
            const combined = (event.name + ' ' + (event.location || '') + ' ' + (event.description || '')).toLowerCase();

            // Language filter
            const movieLang = (event.movieLanguage || '').toLowerCase();
            const matchLang = langs.length === 0 || langs.some(l => movieLang.includes(l));

            // Certificate filter
            const cert = (event.movieCert || 'UA').toUpperCase();
            const matchCert = certs.length === 0 || certs.some(c => cert === c.toUpperCase());

            // City filter
            const loc = (event.location || '').toLowerCase();
            const matchCity = cities.length === 0 || cities.some(c => loc.includes(c));

            // Price filter
            const price = event.price || 0;
            const matchPrice = price === 0 || price <= maxPrice;

            // Date filter
            let matchDate = true;
            if (dateVal && event.date) matchDate = event.date >= dateVal;

            // Text search
            const matchQuery = query === '' || combined.includes(query);

            return matchLang && matchCert && matchCity && matchPrice && matchDate && matchQuery;
        });

        // Sort
        const sort = sortSelect?.value || 'date_desc';
        filtered.sort((a, b) => {
            if (sort === 'date_asc')   return (a.date || '') > (b.date || '') ?  1 : -1;
            if (sort === 'date_desc')  return (a.date || '') < (b.date || '') ?  1 : -1;
            if (sort === 'price_asc')  return (a.price || 0) - (b.price || 0);
            if (sort === 'price_desc') return (b.price || 0) - (a.price || 0);
            if (sort === 'name_asc')   return (a.name || '') > (b.name || '') ?  1 : -1;
            return 0;
        });

        renderActivePills(langs, certs, cities, maxPrice);
        if (countEl) countEl.textContent = `${filtered.length} movie${filtered.length !== 1 ? 's' : ''} found`;
        renderMovies(filtered);
    }

    // ── Listeners ────────────────────────────────────────────────────────────
    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (sortSelect)  sortSelect.addEventListener('change', applyFilters);
    if (dateFilter)  dateFilter.addEventListener('change', applyFilters);
    if (priceRange) {
        priceRange.addEventListener('input', () => {
            if (priceDisplay) priceDisplay.textContent = `₹${parseInt(priceRange.value).toLocaleString('en-IN')}`;
            applyFilters();
        });
    }
    document.querySelectorAll('.cert-filter').forEach(cb => cb.addEventListener('change', applyFilters));
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            document.querySelectorAll('.lang-filter, .cert-filter, .city-filter').forEach(cb => cb.checked = false);
            if (priceRange)  { priceRange.value = maxPriceAll; if (priceDisplay) priceDisplay.textContent = `₹${maxPriceAll.toLocaleString('en-IN')}`; }
            if (dateFilter)  dateFilter.value  = '';
            if (searchInput) searchInput.value = '';
            applyFilters();
        });
    }

    // ── Active Filter Pills ──────────────────────────────────────────────────
    function renderActivePills(langs, certs, cities, maxPrice) {
        if (!activeFiltersEl) return;
        const pills = [];
        langs.forEach(l  => pills.push({ label: l.charAt(0).toUpperCase() + l.slice(1), val: l, cls: 'lang-filter' }));
        certs.forEach(c  => pills.push({ label: `Cert: ${c}`, val: c, cls: 'cert-filter' }));
        cities.forEach(c => pills.push({ label: c.charAt(0).toUpperCase() + c.slice(1), val: c, cls: 'city-filter' }));
        if (maxPrice < maxPriceAll) pills.push({ label: `Max ₹${maxPrice.toLocaleString('en-IN')}`, val: 'price', cls: 'price' });

        if (pills.length === 0) { activeFiltersEl.innerHTML = ''; return; }

        activeFiltersEl.innerHTML = pills.map(p => `
            <span class="active-filter-pill" data-val="${p.val}" data-cls="${p.cls}">
                ${p.label} <i class="fas fa-times" style="font-size:0.7rem;"></i>
            </span>`
        ).join('');

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

    // ── Render Movie Cards ───────────────────────────────────────────────────
    function renderMovies(movies) {
        if (!grid) return;
        if (!movies || movies.length === 0) {
            grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:80px 20px;color:#aaa;">
                <i class="fas fa-film fa-3x" style="color:var(--primary);margin-bottom:20px;display:block;"></i>
                <h3 style="color:#fff;margin-bottom:10px;">No movies match your filters</h3>
                <p style="margin-bottom:25px;">Try clearing filters or broadening your search.</p>
                <a href="${TA.homeUrl}sell-ticket/" class="btn btn-primary"><i class="fas fa-plus-circle"></i> List a Movie Ticket</a>
            </div>`;
            return;
        }

        grid.innerHTML = movies.map(event => {
            const cert     = event.movieCert     || 'UA';
            const lang     = event.movieLanguage || '';
            const rating   = event.movieRating   || '';
            const sellUrl  = `${TA.homeUrl}sell-ticket/?event_id=${event.id}&event_name=${encodeURIComponent(event.name)}&category=movies&venue=${encodeURIComponent(event.location||'')}&date=${event.date||''}`;

            const priceTag = event.price > 0
                ? `<span class="price-tag">From ₹${event.price.toLocaleString('en-IN')}</span>`
                : ``;
            const tickets = event.ticketCount > 0
                ? `<span style="color:#10b981;font-size:0.82rem;"><i class="fas fa-ticket-alt"></i> ${event.ticketCount} available</span>` : '';

            // Ratings row — cert badge + IMDB star if available
            const imdbBadge = rating
                ? `<span style="display:inline-flex;align-items:center;gap:3px;background:rgba(245,158,11,0.15);border:1px solid rgba(245,158,11,0.4);border-radius:6px;padding:2px 6px;font-size:10px;font-weight:700;color:#f59e0b;"><i class="fas fa-star"></i> ${rating}</span>`
                : '';
            const certBadge = `<span style="display:inline-flex;align-items:center;gap:3px;background:rgba(59,130,246,0.15);border:1px solid rgba(59,130,246,0.3);border-radius:6px;padding:2px 6px;font-size:10px;font-weight:700;color:#93c5fd;">${cert}</span>`;
            const langBadge = lang
                ? `<span style="display:inline-flex;align-items:center;gap:3px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.15);border-radius:6px;padding:2px 6px;font-size:10px;color:#e2e8f0;">${lang}</span>`
                : '';

            return `
                <div class="event-card-premium movie-card" onclick="window.location.href='${event.url}'">
                    <div class="event-card-image">
                        <img src="${event.image}" alt="${event.name}" loading="lazy"
                             onerror="this.src='https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=600&q=80'">
                        <div class="event-card-category">NOW SHOWING</div>
                        ${priceTag}
                    </div>
                    <div class="event-card-details">
                        <h3 class="event-card-title movie-title">${event.name}</h3>
                        <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:4px;">
                            ${certBadge}${imdbBadge}${langBadge}
                        </div>
                        <div class="event-card-meta">
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
