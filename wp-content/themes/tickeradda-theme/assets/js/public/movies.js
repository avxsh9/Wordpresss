document.addEventListener('DOMContentLoaded', async () => {
    const grid = document.getElementById('moviesGrid');
    const searchInput = document.getElementById('movieSearch');
    const clearBtn = document.getElementById('clearFiltersBtn');
    const countEl = document.getElementById('moviesResultCount');
    const sortSelect = document.getElementById('moviesSort');
    const activeFiltersEl = document.getElementById('activeFiltersContainer');

    let allMovies = [];

    // ── Fetch ALL movies from REST API ──────────────────────────────────────
    try {
        const res = await fetch(`${TA.restUrl.replace('tickeradda/v2', 'custom/v1')}/movies?per_page=500`);
        if (!res.ok) throw new Error('Network error: ' + res.status);
        allMovies = await res.json();
        if (!Array.isArray(allMovies)) allMovies = [];
        console.log('✓ Movies loaded: ' + allMovies.length + ' total');
        applyFilters();
    } catch (err) {
        console.error('Movies load error:', err);
        if (grid) {
            grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:60px;color:#aaa;">
                <i class="fas fa-exclamation-circle fa-2x" style="color:#ef4444;margin-bottom:15px;display:block;"></i>
                <p>Could not load movies. Please try again.</p>
            </div>`;
        }
    }

    // ── Get filter selections ───────────────────────────────────────────
    function getFilterValues(className) {
        return [...document.querySelectorAll(`.${className}:checked`)].map(el => el.value);
    }

    // ── Apply filters client-side for instant response ──────────────────
    function applyFilters() {
        const langs    = getFilterValues('lang-filter');
        const ratings  = getFilterValues('rating-filter');
        const certs    = getFilterValues('cert-filter');
        const query    = (searchInput?.value || '').toLowerCase().trim();

        let filtered = allMovies.filter(m => {
            const name      = (m.title || '').toLowerCase();
            const lang      = (m.language || '').toLowerCase();
            const desc      = (m.description || '').toLowerCase();
            const rating    = parseFloat((m.movie_rating || '8.0/10').split('/')[0]);
            const cert      = (m.movie_cert || 'UA').toUpperCase();
            const combined  = name + ' ' + lang + ' ' + desc;

            const matchLang   = langs.length === 0 || langs.some(l => lang.includes(l.toLowerCase()));
            const matchRating = ratings.length === 0 || ratings.some(r => rating >= parseFloat(r));
            const matchCert   = certs.length === 0 || certs.some(c => cert.includes(c));
            const matchQuery  = query === '' || combined.includes(query);

            return matchLang && matchRating && matchCert && matchQuery;
        });

        // Sort
        const sort = sortSelect ? sortSelect.value : 'date_desc';
        filtered.sort((a, b) => {
            if (sort === 'date_asc')  return (a.date || '') > (b.date || '') ?  1 : -1;
            if (sort === 'date_desc') return (a.date || '') < (b.date || '') ?  1 : -1;
            if (sort === 'name_asc')  return (a.title || '') > (b.title || '') ?  1 : -1;
            return 0;
        });

        renderActivePills(langs, ratings.map(r => r + '+ Rating'), certs);

        if (countEl) countEl.textContent = `${filtered.length} movie${filtered.length !== 1 ? 's' : ''} found`;

        renderMovies(filtered);
    }

    // ── Render active filter chips ────────────────────────────────────
    function renderActivePills(langs, ratings, certs) {
        if (!activeFiltersEl) return;
        const all = [...langs, ...ratings, ...certs];
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
                // Could be from lang, rating, or cert. So try all
                let cb = document.querySelector(`input[value="${val}"]`) 
                      || document.querySelector(`input[value="${val.split('+')[0]}"]`)
                      || document.querySelector(`input[value="${val.toLowerCase()}"]`);
                if (cb) { cb.checked = false; applyFilters(); }
            });
        });
    }

    // ── Attach listeners to filter checkboxes ─────────────────────────
    document.querySelectorAll('.lang-filter, .rating-filter, .cert-filter').forEach(cb => {
        cb.addEventListener('change', applyFilters);
    });

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (sortSelect)  sortSelect.addEventListener('change', applyFilters);

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            document.querySelectorAll('.lang-filter, .rating-filter, .cert-filter').forEach(cb => {
                cb.checked = false;
            });
            if (searchInput) searchInput.value = '';
            if (sortSelect) sortSelect.value = 'date_desc';
            applyFilters();
        });
    }

    // ── Render ─────────────────────────────────────────────────────────
    function renderMovies(movies) {
        if (!grid) return;
        if (!movies || movies.length === 0) {
            grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:80px 20px;color:#aaa;">
                <i class="fas fa-ticket-alt fa-3x" style="color:var(--primary);margin-bottom:20px;display:block;"></i>
                <h3 style="color:#fff;margin-bottom:10px;">No listings available</h3>
                <p style="margin-bottom: 25px;">Be the first to list a movie ticket on the platform.</p>
                <a href="${TA.homeUrl}sell-movie/" class="btn btn-primary">Sell Ticket</a>
            </div>`;
            return;
        }

        grid.innerHTML = movies.map(movie => {
            const movieUrl = movie.url || '#';
            const movie_rating = movie.movie_rating || '8.5/10';
            const movie_cert = movie.movie_cert || 'UA';
            const posterImg = movie.image || 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=500&h=750&q=80';

            return `
                <div class="event-card-premium" onclick="window.location.href='${movieUrl}'">
                    <div class="event-card-image">
                        <img src="${posterImg}" alt="${movie.title}" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=500&h=750&q=80'">
                        <div class="event-card-category">MOVIE</div>
                        ${movie.ticketCount > 0 
                            ? `<div class="event-card-count" style="background:var(--color-success);"><i class="fas fa-ticket-alt"></i> ${movie.ticketCount} Listings</div>` 
                            : ''}
                    </div>
                    <div class="event-card-details">
                        <div class="event-card-date">
                            <span class="day">${movie_rating.split('/')[0]}</span>
                            <span class="year"><i class="fas fa-star" style="color:var(--color-warning);"></i></span>
                        </div>
                        <div class="event-card-info">
                            <h3 class="event-card-title">${movie.title}</h3>
                            <div class="event-card-meta">
                                <span><i class="fas fa-map-marker-alt"></i> ${movie.venue || 'Multiple Cinemas'}</span>
                                <span><i class="fas fa-language"></i> ${movie.language || 'Hindi'} • ${movie_cert}</span>
                            </div>
                        </div>
                    </div>
                    <div class="event-card-footer" style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:var(--color-success); font-weight:800; font-size:1.1rem;">₹${movie.price ? movie.price : '--'}</span>
                        <div style="display:flex; gap:10px;">
                            ${(movie.quantity && movie.quantity > 0) || (movie.ticketCount && movie.ticketCount > 0)
                                ? `<a href="${TA.homeUrl}sell-ticket/?event_id=${movie.id}" class="btn btn-outline btn-sm" onclick="event.stopPropagation();">Sell</a>
                                   <button class="btn btn-primary btn-sm">Buy Ticket</button>`
                                : `<a href="${TA.homeUrl}sell-ticket/?event_id=${movie.id}" class="btn btn-outline btn-sm" onclick="event.stopPropagation();">Be the first to sell</a>`
                            }
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }
});
