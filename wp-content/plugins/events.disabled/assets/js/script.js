jQuery(document).ready(function($) {
    // 1. Fetch Master Events for Seller Form
    const $masterSelect = $('#uem-master-select');
    if ($masterSelect.length) {
        $.ajax({
            url: uem_data.rest_url + '/master-events',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', uem_data.nonce); },
            success: function(res) {
                let html = '<option value="">-- Choose Event --</option>';
                res.forEach(item => {
                    html += `<option value="${item.id}" data-venue="${item.venue}" data-date="${item.event_date}">${item.event_name}</option>`;
                });
                $masterSelect.html(html);
            }
        });

        $masterSelect.on('change', function() {
            const $opt = $(this).find(':selected');
            if ($(this).val()) {
                $('#uem-event-info').html(`
                    <p><strong>Venue:</strong> ${$opt.data('venue')}</p>
                    <p><strong>Date:</strong> ${$opt.data('date')}</p>
                `).fadeIn();
            } else {
                $('#uem-event-info').hide();
            }
        });
    }

    // 2. Handle Seller Form Submission
    $('#uem-add-listing-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $msg = $('#uem-form-msg');
        $msg.html('<p>Listing your ticket...</p>');

        $.ajax({
            url: uem_data.rest_url + '/tickets',
            method: 'POST',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', uem_data.nonce); },
            data: $form.serialize(),
            success: function(res) {
                $msg.html('<p style="color:green; font-weight:700;">Success! Listing pending approval.</p>');
                $form[0].reset();
                $('#uem-event-info').hide();
            },
            error: function(err) {
                $msg.html('<p style="color:red;">Error: ' + (err.responseJSON ? err.responseJSON.message : 'Submission failed') + '</p>');
            }
        });
    });

    // 3. Robust Listing Loader (Fixes the "Not Showing" Issue)
    function loadListings(filters = {}) {
        const $grid = $('#uem-listing-grid');
        if (!$grid.length) return;

        $grid.html('<p>Fetching latest events...</p>');
        const perPage = $grid.data('per-page') || 12;

        $.ajax({
            url: uem_data.rest_url + '/listings',
            data: { per_page: perPage, ...filters },
            success: function(res) {
                if (!res.length) {
                    $grid.html('<div style="grid-column:1/-1; padding:60px; text-align:center; background:#f8f9fa; border-radius:12px;"><h3>No Active Listings Found</h3><p>Try clearing filters or check back later.</p></div>');
                    return;
                }
                
                let html = '';
                res.forEach(item => {
                    const poster = item.poster || 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=500&q=80';
                    const imdb = item.imdb ? `<div class="uem-imdb-badge">IMDb: ${item.imdb}</div>` : '';
                    
                    html += `
                        <a href="${item.permalink}" class="uem-card shadow-sm">
                            <div class="uem-card-media">
                                <img src="${poster}" alt="${item.title}">
                                <div class="uem-badge">${item.category}</div>
                                ${imdb}
                            </div>
                            <div class="uem-card-content">
                                <h3 class="uem-card-title">${item.title}</h3>
                                <div class="uem-card-info">📍 ${item.venue}</div>
                                <div class="uem-card-info">📅 ${item.date}</div>
                            </div>
                            <div class="uem-card-footer">
                                <div class="uem-price">₹${item.price}</div>
                                <div class="uem-btn-card">Book Ticket</div>
                            </div>
                        </a>
                    `;
                });
                $grid.html(html);
            },
            error: function() {
                $grid.html('<p style="color:red;">Failed to load listings. Please refresh.</p>');
            }
        });
    }

    loadListings();

    // 4. Filters
    $('.uem-btn-filter').on('click', function() {
        const filters = {
            cat: $('#uem-filter-cat').val(),
            date: $('#uem-filter-date').val(),
            price: $('#uem-filter-price').val()
        };
        loadListings(filters);
    });
});
