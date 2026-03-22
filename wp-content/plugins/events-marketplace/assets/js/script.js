jQuery(document).ready(function($) {
    // Dynamic form switching
    $('#tae-form-cat').on('change', function() {
        const cat = $(this).val();
        if (!cat) {
            $('#tae-master-selector, #tae-dynamic-fields').hide();
            return;
        }

        $('#tae-master-selector').fadeIn();
        $('#tae-master-label').text(cat === 'movie' ? 'Select Movie' : 'Select Match');
        
        // Fetch master data
        $.ajax({
            url: tae_data.rest_url + '/master-data',
            data: { type: cat },
            beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', tae_data.nonce); },
            success: function(response) {
                let html = '<option value="">-- Choose --</option>';
                response.forEach(item => {
                    html += `<option value="${item.id}">${item.name}</option>`;
                });
                $('#tae-master-id').html(html);
            }
        });
    });

    $('#tae-master-id').on('change', function() {
        if ($(this).val()) {
            $('#tae-dynamic-fields').fadeIn();
            const cat = $('#tae-form-cat').val();
            $('.tae-cat-fields').hide();
            if (cat === 'movie') $('#tae-movie-fields').show();
        } else {
            $('#tae-dynamic-fields').hide();
        }
    });

    // Handle Form Submission
    $('#tae-add-event-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $msg = $('#tae-form-msg');
        $msg.html('<p>Processing...</p>');

        $.ajax({
            url: tae_data.rest_url + '/tickets',
            method: 'POST',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', tae_data.nonce); },
            data: $form.serialize(),
            success: function(res) {
                $msg.html('<p style="color:green; font-weight:700;">Success! Listing pending approval.</p>');
                $form[0].reset();
                $('#tae-dynamic-fields, #tae-master-selector').hide();
            },
            error: function(err) {
                $msg.html('<p style="color:red;">Error: ' + (err.responseJSON ? err.responseJSON.message : 'Submission failed') + '</p>');
            }
        });
    });

    // Load Listings Grid
    function loadListings(filters = {}) {
        const $grid = $('#tae-listing-grid');
        if (!$grid.length) return;

        $grid.html('<p>Loading latest events...</p>');
        const perPage = $grid.data('per-page') || 12;

        $.ajax({
            url: tae_data.rest_url + '/events',
            data: { per_page: perPage, ...filters },
            success: function(res) {
                if (!res.length) {
                    $grid.html('<p>No events found matching your criteria.</p>');
                    return;
                }
                let html = '';
                res.forEach(item => {
                    const priceStr = '₹' + item.price;
                    const imdbBadge = item.imdb ? `<div class="tae-imdb">IMDb: ${item.imdb}</div>` : '';
                    html += `
                        <a href="${item.permalink}" class="tae-card ${item.category}">
                            <div class="tae-card-media">
                                <img src="${item.poster || 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=500&q=80'}" alt="${item.title}">
                                <div class="tae-badge ${item.category}">${item.category}</div>
                                ${imdbBadge}
                                <div class="tae-card-overlay"></div>
                            </div>
                            <div class="tae-card-content">
                                <h3 class="tae-card-title">${item.title}</h3>
                                <div class="tae-card-meta">
                                    <div class="tae-meta-item"><i class="dashicons dashicons-location"></i> ${item.venue}</div>
                                    <div class="tae-meta-item"><i class="dashicons dashicons-calendar-alt"></i> ${item.date}</div>
                                </div>
                            </div>
                            <div class="tae-card-footer">
                                <div class="tae-price">${priceStr}</div>
                                <div class="tae-btn-card">Book Now</div>
                            </div>
                        </a>
                    `;
                });
                $grid.html(html);
            }
        });
    }

    loadListings();

    // Filters
    $('.tae-btn-filter').on('click', function() {
        const filters = {
            cat: $('#tae-filter-cat').val(),
            date: $('#tae-filter-date').val(),
            price: $('#tae-filter-price').val()
        };
        loadListings(filters);
    });
});
