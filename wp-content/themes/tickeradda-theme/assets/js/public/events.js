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
            const dateObj = event.date ? new Date(event.date) : null;
            const formattedDate = dateObj && !isNaN(dateObj)
                ? dateObj.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })
                : 'TBD';
            const year = dateObj && !isNaN(dateObj) ? dateObj.getFullYear() : '';
            const category = (event.category || 'EVENT').toUpperCase();

            return `
                <div class="event-card-premium" onclick="window.location.href='${event.url}'">
                    <div class="event-card-image">
                        <img src="${event.image}" alt="${event.name}" loading="lazy" 
                             onerror="this.src='https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?auto=format&fit=crop&w=600&q=80'">
                        <div class="event-card-category">${category}</div>
                        ${event.movieRating ? `<div class="event-card-rating"><i class="fas fa-star" style="color:#ffc107;"></i> ${event.movieRating}</div>` : ''}
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
                                <span><i class="fas fa-map-marker-alt"></i> ${event.location || 'Venue TBD'}</span>
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
