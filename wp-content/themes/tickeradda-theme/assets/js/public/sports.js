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
        const res  = await fetch(`${TA.restUrl}/events-list?category=sports&per_page=500`);
        allSports  = await res.json();
        applyFilters();
    } catch (e) {
        console.error('Sports load error:', e);
        grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:60px;color:#aaa;">
            <i class="fas fa-exclamation-circle fa-2x" style="color:#ef4444;margin-bottom:15px;display:block;"></i>
            Could not load sports events. Please try again.
        </div>`;
    }

    // ... (rest of listeners stay basically the same but check properties) ...

    function applyFilters() {
        const sportTypes = [...document.querySelectorAll('.sport-filter:checked')].map(el => el.value);
        const cities     = [...document.querySelectorAll('.city-filter:checked')].map(el => el.value);
        const maxPrice   = priceRange  ? parseInt(priceRange.value) : 10000;
        const dateVal    = dateFilter  ? dateFilter.value : '';
        const query      = searchInput ? searchInput.value.toLowerCase().trim() : '';

        let filtered = allSports.filter(event => {
            const combined = (event.name + ' ' + (event.location||'') + ' ' + (event.description||'')).toLowerCase();
            const matchSport = sportTypes.length === 0 || sportTypes.some(s => combined.includes(s));
            const matchCity  = cities.length     === 0 || cities.some(c  => combined.includes(c));
            const matchQuery = query === ''              || combined.includes(query);
            let matchDate = true;
            if (dateVal && event.date) matchDate = event.date >= dateVal;
            const matchPrice = !event.price || event.price <= maxPrice;
            return matchSport && matchCity && matchQuery && matchDate && matchPrice;
        });

        const sort = sortSelect ? sortSelect.value : 'date_asc';
        filtered.sort((a, b) => {
            if (sort === 'date_asc')  return (a.date || '') > (b.date || '') ?  1 : -1;
            if (sort === 'date_desc') return (a.date || '') < (b.date || '') ?  1 : -1;
            if (sort === 'name_asc')  return (a.name || '') > (b.name || '') ?  1 : -1;
            return 0;
        });

        renderActivePills(sportTypes, cities);
        if (countEl) countEl.textContent = `${filtered.length} event${filtered.length !== 1 ? 's' : ''} found`;
        renderSports(filtered);
    }

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

    function renderSports(events) {
        if (!grid) return;
        if (!events || events.length === 0) {
            grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:80px 20px;color:#aaa;">
                <i class="fas fa-ticket-alt fa-3x" style="color:var(--primary);margin-bottom:20px;display:block;"></i>
                <h3 style="color:#fff;margin-bottom:10px;">No listings available</h3>
                <p style="margin-bottom: 25px;">Be the first to list a sports ticket on the platform.</p>
                <a href="${TA.homeUrl}sell-ticket/" class="btn btn-primary">Sell Ticket</a>
            </div>`;
            return;
        }

        grid.innerHTML = events.map(event => {
            const dateObj = event.date ? new Date(event.date) : null;
            const formattedDate = dateObj && !isNaN(dateObj)
                ? dateObj.toLocaleDateString('en-IN', { day:'numeric', month:'short' })
                : 'TBD';
            const year = dateObj && !isNaN(dateObj) ? dateObj.getFullYear() : '';
            return `
                <div class="event-card-premium" onclick="window.location.href='${event.url}'">
                    <div class="event-card-image">
                        <img src="${event.image}" alt="${event.name}" loading="lazy"
                             onerror="this.src='https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?auto=format&fit=crop&w=600&q=80'">
                        <div class="event-card-category">${(event.category || 'SPORTS').toUpperCase()}</div>
                        ${event.ticketCount > 0 ? `<div class="event-card-count"><i class="fas fa-ticket-alt"></i> ${event.ticketCount} Listings</div>` : ''}
                    </div>
                    <div class="event-card-details">
                        <div class="event-card-date">
                            <span class="day">${formattedDate}</span>
                            <span class="year">${year}</span>
                        </div>
                        <div class="event-card-info">
                            <h3 class="event-card-title">${event.name}</h3>
                            <div class="event-card-meta">
                                <span><i class="fas fa-map-marker-alt"></i> ${event.location || 'TBD'}</span>
                                ${event.time ? `<span><i class="far fa-clock"></i> ${event.time}</span>` : ''}
                            </div>
                        </div>
                        <div class="event-card-price" style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.05);">
                            <div class="price-value" style="font-size: 1.4rem; font-weight: 800; color: #fff;">
                                ${event.price > 0 ? `${event.price.toLocaleString()}` : ''}
                            </div>
                            <div class="card-actions">
                                ${event.ticketCount > 0 
                                    ? `<button class="btn-buy" onclick="event.stopPropagation(); window.location.href='${event.url}'" style="background: #2563eb; color: #fff; border: none; padding: 10px 24px; border-radius: 12px; font-weight: 700; cursor: pointer;">Book Tickets</button>`
                                    : `<button class="btn-sell" onclick="event.stopPropagation(); window.location.href='${TA.homeUrl}sell-ticket/?event_id=${event.id}'" style="background: rgba(255,255,255,0.05); color: #888; border: 1px solid rgba(255,255,255,0.1); padding: 10px 24px; border-radius: 12px; font-weight: 600; cursor: pointer;">Sell Tickets</button>`
                                }
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }
});
