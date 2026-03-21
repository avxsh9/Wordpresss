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
        let endpoint = `${TA.restUrl}/events`;
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
                        ? `${TA.restUrl}/events`
                        : `${TA.restUrl}/events?category=${category}`;
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
            let formattedDate = 'TBD', year = '';
            if (event.date) {
                const d = new Date(event.date);
                if (!isNaN(d)) {
                    formattedDate = d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
                    year = d.getFullYear();
                }
            }
            return `
                <div class="event-card-premium" onclick="window.location.href='${event.url}'">
                    <div class="event-card-image">
                        <img src="${event.image}" alt="${event.name}" loading="lazy">
                        <div class="event-card-category">${event.category.toUpperCase()}</div>
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
                                <span><i class="fas fa-map-marker-alt"></i> ${event.location}</span>
                                ${event.time ? `<span><i class="far fa-clock"></i> ${event.time}</span>` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="event-card-footer">
                        <button class="btn-view">Book / Sell Tickets <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
            `;
        }).join('');
    }
});
