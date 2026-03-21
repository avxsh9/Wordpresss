document.addEventListener('DOMContentLoaded', async () => {
    const grid         = document.getElementById('sportsGrid');
    const searchInput  = document.getElementById('sportsSearch');
    const clearBtn     = document.getElementById('clearSportsFilters');
    const priceRange   = document.getElementById('priceRange');
    const priceDisplay = document.getElementById('priceRangeDisplay');
    const dateFilter   = document.getElementById('dateFilter');
    const sortSelect   = document.getElementById('sportsSort');
    const countEl      = document.getElementById('sportsResultCount');
    const activeFiltersEl = document.getElementById('activeFiltersContainer');

    let allSports = [];

    // ── Fetch all sports events ──────────────────────────────────────
    try {
        const res  = await fetch(`${TA.restUrl.replace('tickeradda/v2', 'custom/v1')}/sports?per_page=500`);
        allSports  = await res.json();
        applyFilters();
    } catch (e) {
        console.error('Sports load error:', e);
        grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:60px;color:#aaa;">
            <i class="fas fa-exclamation-circle fa-2x" style="color:#ef4444;margin-bottom:15px;display:block;"></i>
            Could not load sports events. Please try again.
        </div>`;
    }

    // ── Price range display ─────────────────────────────────────────
    if (priceRange) {
        priceRange.addEventListener('input', () => {
            priceDisplay.textContent = `₹${parseInt(priceRange.value).toLocaleString('en-IN')}`;
            applyFilters();
        });
    }

    // ── All other filter listeners ──────────────────────────────────
    document.querySelectorAll('.sport-filter, .city-filter').forEach(cb => {
        cb.addEventListener('change', applyFilters);
    });

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (dateFilter)  dateFilter.addEventListener('change', applyFilters);
    if (sortSelect)  sortSelect.addEventListener('change', applyFilters);

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            document.querySelectorAll('.sport-filter, .city-filter').forEach(cb => cb.checked = false);
            if (searchInput)  searchInput.value  = '';
            if (dateFilter)   dateFilter.value   = '';
            if (priceRange)   priceRange.value   = 10000;
            if (priceDisplay) priceDisplay.textContent = '₹10,000';
            if (sortSelect)   sortSelect.value   = 'date_asc';
            applyFilters();
        });
    }

    // ── Main filter + render function ────────────────────────────────
    function applyFilters() {
        const sportTypes = [...document.querySelectorAll('.sport-filter:checked')].map(el => el.value);
        const cities     = [...document.querySelectorAll('.city-filter:checked')].map(el => el.value);
        const maxPrice   = priceRange  ? parseInt(priceRange.value) : 10000;
        const dateVal    = dateFilter  ? dateFilter.value : '';
        const query      = searchInput ? searchInput.value.toLowerCase().trim() : '';

        let filtered = allSports.filter(event => {
            const combined = (event.title + ' ' + (event.teams||'') + ' ' + (event.venue||'') + ' ' + (event.description||'')).toLowerCase();

            const matchSport = sportTypes.length === 0 || sportTypes.some(s => combined.includes(s));
            const matchCity  = cities.length     === 0 || cities.some(c  => combined.includes(c));
            const matchQuery = query === ''              || combined.includes(query);

            // Date filter: event date >= selected date
            let matchDate = true;
            if (dateVal && event.date) {
                matchDate = event.date >= dateVal;
            }

            // Price: we use ticketCount as a proxy (no price in current API), 
            // but structure allows future price meta from event.price
            // For now pass all — hook in when price field exists
            return matchSport && matchCity && matchQuery && matchDate;
        });

        // Sort
        const sort = sortSelect ? sortSelect.value : 'date_asc';
        filtered.sort((a, b) => {
            if (sort === 'date_asc')  return (a.date || '') > (b.date || '') ?  1 : -1;
            if (sort === 'date_desc') return (a.date || '') < (b.date || '') ?  1 : -1;
            if (sort === 'name_asc')  return (a.name || '') > (b.name || '') ?  1 : -1;
            return 0;
        });

        // Active filter pills
        renderActivePills(sportTypes, cities);

        // Result count
        if (countEl) countEl.textContent = `${filtered.length} event${filtered.length !== 1 ? 's' : ''} found`;

        renderSports(filtered);
    }

    // ── Render active filter chips ────────────────────────────────────
    function renderActivePills(sportTypes, cities) {
        if (!activeFiltersEl) return;
        const all = [...sportTypes, ...cities];
        if (all.length === 0) { activeFiltersEl.innerHTML = ''; return; }
        activeFiltersEl.innerHTML = all.map(f => `
            <span class="active-filter-pill" data-val="${f}" title="Remove filter">
                ${f.charAt(0).toUpperCase() + f.slice(1)}
                <i class="fas fa-times" style="font-size:0.7rem;"></i>
            </span>
        `).join('');

        activeFiltersEl.querySelectorAll('.active-filter-pill').forEach(pill => {
            pill.addEventListener('click', () => {
                const val = pill.getAttribute('data-val');
                const cb  = document.querySelector(`input[value="${val}"]`);
                if (cb) { cb.checked = false; applyFilters(); }
            });
        });
    }

    // ── Render events grid ───────────────────────────────────────────
    function renderSports(events) {
        if (!grid) return;
        if (!events || events.length === 0) {
            grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:80px 20px;color:#aaa;">
                <i class="fas fa-ticket-alt fa-3x" style="color:var(--primary);margin-bottom:20px;display:block;"></i>
                <h3 style="color:#fff;margin-bottom:10px;">No listings available</h3>
                <p style="margin-bottom: 25px;">Be the first to list a sports ticket on the platform.</p>
                <a href="${TA.homeUrl}sell-sport/" class="btn btn-primary">Sell Ticket</a>
            </div>`;
            return;
        }

        grid.innerHTML = events.map(event => {
            const dateObj   = event.date ? new Date(event.date) : null;
            const dateStr   = dateObj && !isNaN(dateObj)
                ? dateObj.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
                : 'Date TBD';

            return `
                <div class="event-card-premium" onclick="window.location.href='${event.url}'">
                    <div class="event-card-image">
                        <img src="${event.match_poster || event.image}" alt="${event.title}" loading="lazy"
                             onerror="this.src='https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?auto=format&fit=crop&w=600&q=80'">
                        <div class="event-card-category">SPORTS</div>
                        ${(event.quantity && event.quantity > 0) || (event.ticketCount && event.ticketCount > 0)
                            ? `<div class="event-card-count"><i class="fas fa-ticket-alt"></i> ${event.quantity || event.ticketCount} Listings</div>`
                            : ''}
                    </div>
                    <div class="event-card-details">
                        <div class="event-card-date">
                            <span class="day">${dateObj && !isNaN(dateObj)
                                ? dateObj.toLocaleDateString('en-IN', { day:'numeric', month:'short' })
                                : 'TBD'}</span>
                            <span class="year">${dateObj && !isNaN(dateObj) ? dateObj.getFullYear() : ''}</span>
                        </div>
                        <div class="event-card-info">
                            <h3 class="event-card-title">${event.title}</h3>
                            ${event.teams ? `<div style="color:var(--primary); font-size:0.85rem; font-weight:600; margin-bottom:10px;">${event.teams}</div>` : ''}
                            <div class="event-card-meta">
                                <span><i class="fas fa-map-marker-alt"></i> ${event.venue || event.location || 'TBD'}</span>
                                ${event.time ? `<span><i class="far fa-clock"></i> ${event.time}</span>` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="event-card-footer" style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:var(--color-success); font-weight:800; font-size:1.1rem;">₹${event.price ? event.price : '--'}</span>
                        <div style="display:flex; gap:10px;">
                            ${(event.quantity && event.quantity > 0) || (event.ticketCount && event.ticketCount > 0)
                                ? `<a href="${TA.homeUrl}sell-ticket/?event_id=${event.id}" class="btn btn-outline btn-sm" onclick="event.stopPropagation();">Sell</a>
                                   <button class="btn btn-primary btn-sm">Buy Ticket</button>`
                                : `<a href="${TA.homeUrl}sell-ticket/?event_id=${event.id}" class="btn btn-outline btn-sm" onclick="event.stopPropagation();">Be the first to sell</a>`
                            }
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }
});
