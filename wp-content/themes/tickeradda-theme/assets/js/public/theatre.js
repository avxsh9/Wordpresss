document.addEventListener('DOMContentLoaded', async () => {
    const grid         = document.getElementById('theatreGrid');
    const searchInput  = document.getElementById('theatreSearch');
    const clearBtn     = document.getElementById('clearTheatreFilters');
    const dateFilter   = document.getElementById('theatreDateFilter');
    const sortSelect   = document.getElementById('theatreSort');
    const countEl      = document.getElementById('theatreResultCount');
    const activeFiltersEl = document.getElementById('theatreActiveFilters');

    let allShows = [];

    // Fetch both 'theatre' and 'other' categories for this page
    try {
        const [theatreRes, otherRes] = await Promise.all([
            fetch(`${TA.restUrl}/events-list?category=theatre`),
            fetch(`${TA.restUrl}/events-list?category=other`)
        ]);
        const theatreShows = await theatreRes.json();
        const otherShows   = await otherRes.json();
        allShows = [...theatreShows, ...otherShows];
        applyFilters();
    } catch (e) {
        console.error('Theatre load error:', e);
        grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:60px;color:#aaa;">
            <i class="fas fa-exclamation-circle fa-2x" style="color:#ef4444;margin-bottom:15px;display:block;"></i>
            Could not load shows. Please try again.
        </div>`;
    }

    // Attach filter listeners
    document.querySelectorAll('.type-filter, .city-filter, .lang-filter').forEach(cb => {
        cb.addEventListener('change', applyFilters);
    });
    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (dateFilter)  dateFilter.addEventListener('change', applyFilters);
    if (sortSelect)  sortSelect.addEventListener('change', applyFilters);

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            document.querySelectorAll('.type-filter, .city-filter, .lang-filter').forEach(cb => cb.checked = false);
            if (searchInput) searchInput.value = '';
            if (dateFilter)  dateFilter.value  = '';
            if (sortSelect)  sortSelect.value  = 'date_asc';
            applyFilters();
        });
    }

    function applyFilters() {
        const types  = [...document.querySelectorAll('.type-filter:checked')].map(el => el.value);
        const cities = [...document.querySelectorAll('.city-filter:checked')].map(el => el.value);
        const langs  = [...document.querySelectorAll('.lang-filter:checked')].map(el => el.value);
        const query  = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const dateVal = dateFilter ? dateFilter.value : '';

        let filtered = allShows.filter(event => {
            const combined = (event.name + ' ' + event.location + ' ' + event.description).toLowerCase();
            const matchType  = types.length  === 0 || types.some(t  => combined.includes(t));
            const matchCity  = cities.length === 0 || cities.some(c  => combined.includes(c));
            const matchLang  = langs.length  === 0 || langs.some(l   => combined.includes(l));
            const matchQuery = query === ''         || combined.includes(query);
            const matchDate  = !dateVal || !event.date || event.date >= dateVal;
            return matchType && matchCity && matchLang && matchQuery && matchDate;
        });

        // Sort
        const sort = sortSelect ? sortSelect.value : 'date_asc';
        filtered.sort((a, b) => {
            if (sort === 'date_asc')  return (a.date || '') > (b.date || '') ?  1 : -1;
            if (sort === 'date_desc') return (a.date || '') < (b.date || '') ?  1 : -1;
            if (sort === 'name_asc')  return (a.name || '') > (b.name || '') ?  1 : -1;
            return 0;
        });

        // Active pills
        renderActivePills([...types, ...cities, ...langs]);
        if (countEl) countEl.textContent = `${filtered.length} show${filtered.length !== 1 ? 's' : ''} found`;
        renderGrid(filtered);
    }

    function renderActivePills(active) {
        if (!activeFiltersEl) return;
        if (active.length === 0) { activeFiltersEl.innerHTML = ''; return; }
        activeFiltersEl.innerHTML = active.map(f => `
            <span class="active-filter-pill" data-val="${f}">
                ${f.charAt(0).toUpperCase() + f.slice(1)}
                <i class="fas fa-times" style="font-size:0.7rem;"></i>
            </span>
        `).join('');
        activeFiltersEl.querySelectorAll('.active-filter-pill').forEach(pill => {
            pill.addEventListener('click', () => {
                const cb = document.querySelector(`input[value="${pill.getAttribute('data-val')}"]`);
                if (cb) { cb.checked = false; applyFilters(); }
            });
        });
    }

    function renderGrid(events) {
        if (!grid) return;
        if (!events || events.length === 0) {
            grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:80px 20px;color:#aaa;">
                <i class="fas fa-masks-theater fa-3x" style="color:var(--primary);margin-bottom:20px;display:block;"></i>
                <h3 style="color:#fff;margin-bottom:10px;">No shows found</h3>
                <p>Try adjusting your filters or search term.</p>
            </div>`;
            return;
        }
        grid.innerHTML = events.map(event => {
            const dateObj = event.date ? new Date(event.date) : null;
            const dateStr = dateObj && !isNaN(dateObj)
                ? dateObj.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
                : 'Date TBD';
            return `
                <div class="event-card-premium" onclick="window.location.href='${event.url}'">
                    <div class="event-card-image">
                        <img src="${event.image}" alt="${event.name}" loading="lazy"
                             onerror="this.src='https://images.unsplash.com/photo-1503095396549-807759245b35?auto=format&fit=crop&w=600&q=80'">
                        <div class="event-card-category">${(event.category || 'EVENT').toUpperCase()}</div>
                        ${event.ticketCount > 0 ? `<div class="event-card-count"><i class="fas fa-ticket-alt"></i> ${event.ticketCount} Listings</div>` : ''}
                    </div>
                    <div class="event-card-details">
                        <div class="event-card-date">
                            <span class="day">${dateObj && !isNaN(dateObj) ? dateObj.toLocaleDateString('en-IN', { day:'numeric', month:'short' }) : 'TBD'}</span>
                            <span class="year">${dateObj && !isNaN(dateObj) ? dateObj.getFullYear() : ''}</span>
                        </div>
                        <div class="event-card-info">
                            <h3 class="event-card-title">${event.name}</h3>
                            <div class="event-card-meta">
                                <span><i class="fas fa-map-marker-alt"></i> ${event.location}</span>
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
