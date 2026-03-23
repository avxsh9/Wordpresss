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
        const res = await fetch(`${TA.restUrl}/events-list?category=movies&per_page=500`);
        if (!res.ok) throw new Error('Network error: ' + res.status);
        allMovies = await res.json();
        if (!Array.isArray(allMovies)) allMovies = [];
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

    // ... (rest of listeners stay basically the same but check properties) ...

    function applyFilters() {
        const langs    = getFilterValues('lang-filter');
        const ratings  = getFilterValues('rating-filter');
        const certs    = getFilterValues('cert-filter');
        const query    = (searchInput?.value || '').toLowerCase().trim();

        let filtered = allMovies.filter(event => {
            const combined = (event.name + ' ' + (event.location||'') + ' ' + (event.description||'')).toLowerCase();
            const matchLang   = langs.length === 0 || langs.some(l => (event.movieLanguage||'').toLowerCase().includes(l.toLowerCase()));
            const matchRating = ratings.length === 0 || ratings.some(r => parseFloat(event.movieRating || '8.0') >= parseFloat(r));
            const matchCert   = certs.length === 0 || certs.some(c => (event.movieCert || 'UA').toUpperCase().includes(c.toUpperCase()));
            const matchQuery  = query === '' || combined.includes(query);

            return matchLang && matchRating && matchCert && matchQuery;
        });

        const sort = sortSelect ? sortSelect.value : 'date_desc';
        filtered.sort((a, b) => {
            if (sort === 'date_asc')  return (a.date || '') > (b.date || '') ?  1 : -1;
            if (sort === 'date_desc') return (a.date || '') < (b.date || '') ?  1 : -1;
            if (sort === 'name_asc')  return (a.name || '') > (b.name || '') ?  1 : -1;
            return 0;
        });

        renderActivePills(langs, ratings.map(r => r + '+ Rating'), certs);
        if (countEl) countEl.textContent = `${filtered.length} movie${filtered.length !== 1 ? 's' : ''} found`;
        renderMovies(filtered);
    }

    function renderMovies(movies) {
        if (!grid) return;
        if (!movies || movies.length === 0) {
            grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:80px 20px;color:#aaa;">
                <i class="fas fa-ticket-alt fa-3x" style="color:var(--primary);margin-bottom:20px;display:block;"></i>
                <h3 style="color:#fff;margin-bottom:10px;">No listings available</h3>
                <p style="margin-bottom: 25px;">Be the first to list a movie ticket on the platform.</p>
                <a href="${TA.homeUrl}sell-ticket/" class="btn btn-primary">Sell Ticket</a>
            </div>`;
            return;
        }

        grid.innerHTML = movies.map(event => {
            const isMovie = true;
            const dateObj = event.date ? new Date(event.date) : null;
            const formattedDate = dateObj && !isNaN(dateObj)
                ? dateObj.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })
                : 'TBD';
            
            // Minimalist Movie Meta
            const rating = event.movieRating || '8.5';
            const cert   = event.movieCert || 'UA';
            const lang   = event.movieLanguage || 'Hindi';

            return `
                <div class="event-card-premium" onclick="window.location.href='${event.url}'">
                    <div class="event-card-image">
                        <img src="${event.image}" alt="${event.name}" loading="lazy"
                             onerror="this.src='https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=500&h=750&q=80'">
                        <div class="event-card-category">${(event.category || 'MOVIE').toUpperCase()}</div>
                        <div class="event-card-rating"><i class="fas fa-star"></i> ${rating}</div>
                    </div>
                    <div class="event-card-details">
                        <h3 class="event-card-title">${event.name}</h3>
                        <div class="event-card-meta">
                            <span><i class="far fa-calendar-alt"></i> ${formattedDate} • ${cert} • ${lang}</span>
                            <span><i class="fas fa-star"></i> IMDb ${rating}</span>
                        </div>
                    </div>
                    <div class="event-card-actions">
                        <button class="card-btn-primary" onclick="event.stopPropagation(); window.location.href='${event.url}'">Book Tickets</button>
                        <button class="card-btn-secondary" onclick="event.stopPropagation(); window.location.href='${TA.homeUrl}sell-ticket/?event_id=${event.id}'">
                            <i class="fas fa-ticket-alt"></i> Sell Your Tickets
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }
});
