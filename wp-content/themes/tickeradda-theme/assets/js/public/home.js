document.addEventListener('DOMContentLoaded', async () => {
    // ── 1. Containers ─────────────────────────────────────────────────
    const trendingGrid   = document.getElementById('trendingEventsGrid');
    const sportsGrid     = document.getElementById('homeSportsGrid');
    const moviesGrid     = document.getElementById('homeMoviesGrid');
    const theatreGrid    = document.getElementById('homeTheatreGrid');
    const moreGrid       = document.getElementById('homeMoreEventsGrid');
    const sellersGrid    = document.getElementById('featuredSellersGrid');

    // Pills
    const trendingPills = document.querySelectorAll('.hero-categories .category-pill');
    const sportsPills   = document.querySelectorAll('.sports-section .category-pill-small');

    const searchInput = document.getElementById('homeSearchInput');
    const searchBtn   = document.getElementById('homeSearchBtn');

    // ── 2. Parallel fetches ───────────────────────────────────────────
    try {
        const customUrl = TA.restUrl.replace('tickeradda/v2', 'custom/v1');
        const [allRes, sportsRes, moviesRes, theatreRes, sellersRes] = await Promise.all([
            fetch(`${TA.restUrl}/events`),
            fetch(`${customUrl}/sports`),
            fetch(`${customUrl}/movies`),
            fetch(`${TA.restUrl}/events?category=theatre`),
            fetch(`${TA.restUrl}/users/featured`)
        ]);

        const allEvents    = await allRes.json();
        const sportsEventsRaw = await sportsRes.json();
        const moviesEventsRaw = await moviesRes.json();
        const theatreEvents= await theatreRes.json();
        
        const sportsEvents = Array.isArray(sportsEventsRaw) ? sportsEventsRaw.map(m => ({
            name: m.match_name || m.title,
            image: m.match_poster || 'https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?auto=format&fit=crop&w=800&q=80',
            location: m.venue || 'Multiple Venues',
            date: m.date,
            time: m.time,
            url: m.url,
            category: 'SPORTS',
            ticketCount: m.quantity || 0
        })) : [];

        const moviesEvents = Array.isArray(moviesEventsRaw) ? moviesEventsRaw.map(m => ({
            name: m.movie_name || m.title,
            image: m.poster_url || 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=500&h=750&q=80',
            location: m.venue || 'Multiple Cinemas',
            date: m.date,
            time: m.time,
            url: m.url,
            category: 'MOVIE',
            ticketCount: m.quantity || 0
        })) : [];
        const featuredSellers = await sellersRes.json();

        // ── 3. Initial render (4 items max per home section) ─────────
        renderEventGrid(trendingGrid, allEvents.slice(0, 8));
        renderEventGrid(sportsGrid,   sportsEvents.slice(0, 4));
        renderEventGrid(moviesGrid,   moviesEvents.slice(0, 4));
        renderEventGrid(theatreGrid,  theatreEvents.slice(0, 4));

        // "More Events" = everything not in sports / movies / theatre
        const moreEvents = allEvents.filter(
            e => !['sports', 'movies', 'theatre'].includes((e.category || '').toLowerCase())
        );
        renderEventGrid(moreGrid, moreEvents.slice(0, 4));

        renderSellersGrid(sellersGrid, featuredSellers);

        // ── 4. Trending section pills ─────────────────────────────────
        trendingPills.forEach(pill => {
            pill.addEventListener('click', async () => {
                trendingPills.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                const cat = pill.getAttribute('data-category');
                trendingGrid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:var(--text-gray);">Filtering...</p>';
                try {
                    const url = cat === 'all'
                        ? `${TA.restUrl}/events`
                        : `${TA.restUrl}/events?category=${cat}`;
                    const res = await fetch(url);
                    const events = await res.json();
                    renderEventGrid(trendingGrid, events.slice(0, 8));
                } catch (e) { console.error('Trending filter error:', e); }
            });
        });

        // ── 5. Sports sub-category pills ──────────────────────────────
        sportsPills.forEach(pill => {
            pill.addEventListener('click', async () => {
                sportsPills.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                const sub = pill.getAttribute('data-sports-category');
                sportsGrid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:var(--text-gray);">Filtering sports...</p>';
                try {
                    const customUrl = TA.restUrl.replace('tickeradda/v2', 'custom/v1');
                    let url = `${customUrl}/sports`;
                    if (sub !== 'all') url += `?s=${encodeURIComponent(sub)}`;
                    const res = await fetch(url);
                    const raw = await res.json();
                    const filtered = Array.isArray(raw) ? raw.map(m => ({
                        name: m.match_name || m.title,
                        image: m.match_poster || 'https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?auto=format&fit=crop&w=800&q=80',
                        location: m.venue || 'Multiple Venues',
                        date: m.date,
                        time: m.time,
                        url: m.url,
                        category: 'SPORTS',
                        ticketCount: m.quantity || 0
                    })) : [];
                    renderEventGrid(sportsGrid, filtered.slice(0, 4));
                } catch (e) { console.error('Sports filter error:', e); }
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

    // ── Helpers ────────────────────────────────────────────────────────

    function renderEventGrid(container, events) {
        if (!container) return;
        if (!events || events.length === 0) {
            container.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#aaa;">No events found in this category.</div>';
            return;
        }
        container.innerHTML = events.map(event => {
            const dateObj = event.date ? new Date(event.date) : null;
            const formattedDate = dateObj && !isNaN(dateObj)
                ? dateObj.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })
                : 'TBD';
            const year = dateObj && !isNaN(dateObj) ? dateObj.getFullYear() : '';

            return `
                <div class="event-card-premium" onclick="window.location.href='${event.url}'">
                    <div class="event-card-image">
                        <img src="${event.image}" alt="${event.name}" loading="lazy">
                        <div class="event-card-category">${(event.category || 'EVENT').toUpperCase()}</div>
                        ${event.ticketCount > 0
                            ? `<div class="event-card-count"><i class="fas fa-ticket-alt"></i> ${event.ticketCount} Listings</div>`
                            : ''}
                    </div>
                    <div class="event-card-details">
                        <div class="event-card-date">
                            <span class="day">${formattedDate}</span>
                            <span class="year">${year}</span>
                        </div>
                        <div class="event-card-info">
                            <h3 class="event-card-title">${event.name}</h3>
                            <div class="event-card-meta">
                                <span><i class="fas fa-map-marker-alt"></i> ${event.location}</span>
                                ${event.time ? `<span><i class="far fa-clock"></i> ${event.time}</span>` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="event-card-footer" style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:var(--color-success); font-weight:800; font-size:1.1rem;">₹${event.price ? event.price : '--'}</span>
                        <div style="display:flex; gap:10px;">
                            ${event.ticketCount > 0
                                ? `<button class="btn btn-primary btn-sm">Buy Ticket</button>`
                                : `<a href="${TA.homeUrl}sell-ticket/?event_id=${event.id}" class="btn btn-outline btn-sm" onclick="event.stopPropagation();">Sell Ticket</a>`
                            }
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderSellersGrid(container, sellers) {
        if (!container) return;
        if (!sellers || sellers.length === 0) {
            container.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#aaa;">None yet.</div>';
            return;
        }
        container.innerHTML = sellers.map(seller => `
            <div class="card" style="text-align:center;padding:25px;">
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
        `).join('');
    }
});
