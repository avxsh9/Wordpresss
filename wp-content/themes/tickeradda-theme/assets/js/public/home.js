document.addEventListener('DOMContentLoaded', async () => {
    // ── 1. Containers ─────────────────────────────────────────────────
    const trendingGrid   = document.getElementById('trendingEventsGrid');
    const sportsGrid     = document.getElementById('homeSportsGrid');
    const moviesGrid     = document.getElementById('homeMoviesGrid');
    const theatreGrid    = document.getElementById('homeTheatreGrid');
    const moreGrid       = document.getElementById('homeMoreEventsGrid');
    const sellersGrid    = document.getElementById('featuredSellersGrid');

    const trendingPills = document.querySelectorAll('.hero-categories .category-pill');
    const sportsPills   = document.querySelectorAll('.sports-section .category-pill-small');

    const searchInput = document.getElementById('homeSearchInput');
    const searchBtn   = document.getElementById('homeSearchBtn');

    let allEventsCached = [];

    // ── 2. Unified Single Fetch ──────────────────────────────────────────
    try {
        console.log('TickerAdda Home: Starting fetches...');
        
        const fetchJSON = async (url) => {
            try {
                const res = await fetch(url);
                if (!res.ok) {
                    console.warn(`Fetch to ${url} returned status ${res.status}`);
                    return [];
                }
                const text = await res.text();
                if (!text || text.trim() === '') {
                    console.warn(`Fetch to ${url} returned empty body`);
                    return [];
                }
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error(`JSON parse error for ${url}:`, e, "Content:", text.substring(0, 100));
                    return [];
                }
            } catch (err) {
                console.error(`Fetch error for ${url}:`, err);
                return [];
            }
        };

        const [events, sellers] = await Promise.all([
            fetchJSON(`${TA.restUrl}/events-list?per_page=100`),
            fetchJSON(`${TA.restUrl}/users/featured`)
        ]);

        allEventsCached = Array.isArray(events) ? events : [];
        const featuredSellers = Array.isArray(sellers) ? sellers : [];

        console.log(`TickerAdda Home: Loaded ${allEventsCached.length} events, ${featuredSellers.length} sellers.`);

        // ── 3. Initial Distribution & Render ───────────────────────────
        distributeAndRender();
        renderSellersGrid(sellersGrid, featuredSellers);

        // ── 4. Reactive Trending section pills ─────────────────────────
        trendingPills.forEach(pill => {
            pill.addEventListener('click', () => {
                trendingPills.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                const cat = pill.getAttribute('data-category');
                
                const filtered = cat === 'all' 
                    ? allEventsCached 
                    : allEventsCached.filter(e => e.category_slug === cat || e.category === cat);
                
                renderEventGrid(trendingGrid, filtered.slice(0, 8));
            });
        });

        // ── 5. Reactive Sports sub-category pills ──────────────────────
        sportsPills.forEach(pill => {
            pill.addEventListener('click', () => {
                sportsPills.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                const sub = pill.getAttribute('data-sports-category');
                
                const sportsOnly = allEventsCached.filter(e => e.category_slug === 'sports');
                const filtered = sub === 'all' 
                    ? sportsOnly 
                    : sportsOnly.filter(e => {
                        const combined = (e.name + (e.teams?' '+e.teams:'')).toLowerCase();
                        return combined.includes(sub.toLowerCase());
                    });
                
                renderEventGrid(sportsGrid, filtered.slice(0, 4));
            });
        });

        // ── 6. Search ─────────────────────────────────────────────────
        if (searchBtn && searchInput) {
            const doSearch = () => {
                const q = searchInput.value.trim();
                if (q) window.location.href = `${TA.homeUrl}events/?s=${encodeURIComponent(q)}`;
            };
            searchBtn.addEventListener('click', doSearch);
            searchInput.addEventListener('keypress', e => { if (e.key === 'Enter') doSearch(); });
        }

    } catch (err) {
        console.error('Home load error:', err);
    }

    function distributeAndRender() {
        // Trending: Just show first 8 for 'all'
        renderEventGrid(trendingGrid, allEventsCached.slice(0, 8));

        // Sports Section
        const sports = allEventsCached.filter(e => e.category_slug === 'sports');
        renderEventGrid(sportsGrid, sports.slice(0, 4));

        // Movies Section
        const movies = allEventsCached.filter(e => e.category_slug === 'movies');
        renderEventGrid(moviesGrid, movies.slice(0, 4));

        // Theatre Section
        const theatre = allEventsCached.filter(e => e.category_slug === 'theatre');
        renderEventGrid(theatreGrid, theatre.slice(0, 4));

        // More Events: Everything else (Music, Comedy, etc.)
        const mainSlugs = ['sports', 'movies', 'theatre'];
        const more = allEventsCached.filter(e => !mainSlugs.includes(e.category_slug));
        renderEventGrid(moreGrid, more.slice(0, 4));
    }

    // ── Helper: Premium Card Rendering ──────────────────────────────
    function renderEventGrid(eventsContainer, data) {
        if (!eventsContainer) return;
        if (!data || data.length === 0) {
            eventsContainer.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#aaa;">No events found in this category.</div>';
            return;
        }

        eventsContainer.innerHTML = data.map(event => {
            const isMovie = event.post_type === 'movies' || (event.category && event.category.toLowerCase() === 'movies');
            const dateObj = event.date ? new Date(event.date) : null;
            const formattedDate = dateObj && !isNaN(dateObj)
                ? dateObj.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })
                : 'TBD';
            
            // Minimalist Movie Meta
            const rating = event.movieRating || '8.5';
            const cert   = event.movieCert || 'UA';

            // Enhanced Sell Link with params
            const sellUrl = `${TA.homeUrl}sell-ticket/?event_id=${event.id}&event_name=${encodeURIComponent(event.name)}&category=${encodeURIComponent(event.category || '')}&venue=${encodeURIComponent(event.location || '')}&date=${event.date || ''}&time=${encodeURIComponent(event.time || '')}`;

            // Movie specific content: Only Poster, Name, IMDb, Rating
            if (isMovie) {
                return `
                    <div class="event-card-premium movie-card" onclick="window.location.href='${event.url}'">
                        <div class="event-card-image">
                            <img src="${event.image}" alt="${event.name}" loading="lazy" 
                                 onerror="this.src='https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?auto=format&fit=crop&w=600&q=80'">
                            <div class="event-card-category">NOW SHOWING</div>
                            <div class="event-card-rating"><i class="fas fa-star"></i> ${rating}</div>
                        </div>
                        <div class="event-card-details">
                            <h3 class="event-card-title movie-title">${event.name}</h3>
                            <div class="event-card-meta">
                                <span class="movie-meta-badge"><i class="fas fa-star"></i> IMDb ${rating}</span>
                                <span class="movie-meta-badge"><i class="fas fa-user-shield"></i> Rated ${cert}</span>
                            </div>
                        </div>
                        <div class="event-card-actions">
                            <button class="card-btn-primary" onclick="event.stopPropagation(); window.location.href='${event.url}'">Book Tickets</button>
                            <button class="card-btn-secondary" onclick="event.stopPropagation(); window.location.href='${sellUrl}'">
                                <i class="fas fa-ticket-alt"></i> Sell Your Tickets
                            </button>
                        </div>
                    </div>
                `;
            }

            // General minimalist card
            return `
                <div class="event-card-premium" onclick="window.location.href='${event.url}'">
                    <div class="event-card-image">
                        <img src="${event.image}" alt="${event.name}" loading="lazy" 
                             onerror="this.src='https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?auto=format&fit=crop&w=600&q=80'">
                        <div class="event-card-category">${(event.category || 'EVENT').toUpperCase()}</div>
                    </div>
                    <div class="event-card-details">
                        <h3 class="event-card-title">${event.name}</h3>
                        <div class="event-card-meta">
                            <span><i class="far fa-calendar-alt"></i> ${formattedDate} ${event.time ? `• ${event.time}` : ''}</span>
                            <span><i class="fas fa-map-marker-alt"></i> ${event.location || 'Venue TBD'}</span>
                        </div>
                    </div>
                    <div class="event-card-actions">
                        <button class="card-btn-primary" onclick="event.stopPropagation(); window.location.href='${event.url}'">Book Tickets</button>
                        <button class="card-btn-secondary" onclick="event.stopPropagation(); window.location.href='${sellUrl}'">
                            <i class="fas fa-ticket-alt"></i> Sell Your Tickets
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderSellersGrid(container, sellers) {
        if (!container) return;
        if (!sellers || sellers.length === 0) {
            container.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#aaa;">No featured sellers at the moment.</div>';
            return;
        }
        container.innerHTML = Array.isArray(sellers) ? sellers.map(seller => `
            <div class="card" style="text-align:center;padding:25px; background: var(--card-bg); border: 1px solid var(--glass-border); border-radius: 20px;">
                <div style="width:60px;height:60px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:50%;margin:0 auto 15px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:bold;color:#fff;box-shadow:0 0 20px rgba(59,130,246,0.2);">
                    ${seller.name.charAt(0).toUpperCase()}
                </div>
                <h4 style="margin:0 0 5px;color:#fff;">${seller.name}</h4>
                <div style="color:#f59e0b;font-size:0.9rem;margin-bottom:15px;">
                    <i class="fas fa-star"></i> ${seller.avgRating || 0}
                    <span style="color:#aaa;font-size:0.8rem;">(${seller.ratingsCount || 0})</span>
                </div>
                <div style="font-size:0.8rem;color:#10B981;background:rgba(16,185,129,0.1);padding:4px 10px;border-radius:20px;display:inline-block;">
                    Verified Seller
                </div>
            </div>
        `).join('') : '';
    }
});
