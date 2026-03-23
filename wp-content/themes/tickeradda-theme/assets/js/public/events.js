document.addEventListener('DOMContentLoaded', async () => {
    const eventsContainer = document.getElementById('eventsGrid');
    const filterButtons   = document.querySelectorAll('.filter-btn');

    // Determine initial category from PHP-injected var or URL param
    const urlParams  = new URLSearchParams(window.location.search);
    const searchQuery = urlParams.get('s');
    const initialCat = (window.TA_INITIAL_CATEGORY && window.TA_INITIAL_CATEGORY !== 'all')
        ? window.TA_INITIAL_CATEGORY
        : (urlParams.get('category') || 'all');

    // Pre-activate the matching pill
    filterButtons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-category') === initialCat) btn.classList.add('active');
    });
    if (!document.querySelector('.filter-btn.active')) {
        const allBtn = document.querySelector('.filter-btn[data-category="all"]');
        if (allBtn) allBtn.classList.add('active');
    }

    try {
        let endpoint = `${TA.restUrl}/events-list`;
        const params = [];
        if (initialCat && initialCat !== 'all') params.push(`category=${encodeURIComponent(initialCat)}`);
        if (searchQuery) params.push(`s=${encodeURIComponent(searchQuery)}`);
        if (params.length) endpoint += '?' + params.join('&');

        if (searchQuery) {
            const title = document.querySelector('.gradient-text');
            if (title) title.textContent = `Search: "${searchQuery}"`;
        }

        const res    = await fetch(endpoint);
        const events = await res.json();
        renderEvents(events);

        // Filter pill clicks
        filterButtons.forEach(btn => {
            btn.addEventListener('click', async () => {
                filterButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const category = btn.getAttribute('data-category');
                eventsContainer.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px;"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary);"></i></div>';
                try {
                    const url = category === 'all'
                        ? `${TA.restUrl}/events-list`
                        : `${TA.restUrl}/events-list?category=${category}`;
                    const filterRes      = await fetch(url);
                    const filteredEvents = await filterRes.json();
                    renderEvents(filteredEvents);
                } catch (err) {
                    console.error('Filter error:', err);
                }
            });
        });

    } catch (err) {
        console.error(err);
        eventsContainer.innerHTML = '<p style="color:red;text-align:center;grid-column:1/-1;">Error loading events.</p>';
    }

    function renderEvents(data) {
        if (!data || data.length === 0) {
            eventsContainer.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#aaa;"><h3>No events found</h3><p>Try a different category.</p></div>';
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
                    <div class="event-card-premium" onclick="window.location.href='${event.url}'">
                        <div class="event-card-image">
                            <img src="${event.image}" alt="${event.name}" loading="lazy" 
                                 onerror="this.src='https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?auto=format&fit=crop&w=600&q=80'">
                            <div class="event-card-category">${(event.category || 'MOVIE').toUpperCase()}</div>
                            <div class="event-card-rating"><i class="fas fa-star"></i> ${rating}</div>
                        </div>
                        <div class="event-card-details">
                            <h3 class="event-card-title">${event.name}</h3>
                            <div class="event-card-meta">
                                <span><i class="fas fa-star"></i> IMDb ${rating} • ${cert}</span>
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
});
