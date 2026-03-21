document.addEventListener('DOMContentLoaded', () => {
    // TMDB Search Elements
    const searchInput = document.getElementById('movieSearchInput');
    const searchSpinner = document.getElementById('movieSearchSpinner');
    const searchResults = document.getElementById('movieSearchResults');
    
    // Form Elements
    const form = document.getElementById('sellMovieForm');
    const movieNameField = document.getElementById('movie_name');
    const posterUrlField = document.getElementById('poster_url');
    const posterPreview = document.getElementById('moviePosterPreview');
    const posterImg = document.getElementById('moviePosterImg');
    const submitBtn = document.getElementById('submitMovieBtn');

    let searchTimeout;

    // ── 1. TMDB Autocomplete ────────────────────────────────────────────────
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            searchSpinner.style.display = 'block';

            searchTimeout = setTimeout(async () => {
                try {
                    const formData = new FormData();
                    formData.append('action', 'ta_tmdb_search');
                    formData.append('nonce', TA.tmdbNonce);
                    formData.append('query', query);

                    const res = await fetch(TA.ajaxUrl, {
                        method: 'POST',
                        body: formData
                    });

                    const data = await res.json();
                    
                    if (data.success && data.data.length > 0) {
                        renderSearchResults(data.data);
                    } else {
                        searchResults.innerHTML = '<div style="padding:15px; color:#94a3b8;">No movies found. You can still list it manually.</div>';
                        searchResults.style.display = 'block';
                    }
                } catch (err) {
                    console.error('TMDB Search Error:', err);
                } finally {
                    searchSpinner.style.display = 'none';
                }
            }, 500); // 500ms debounce
        });

        // Hide results when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }

    function renderSearchResults(movies) {
        searchResults.innerHTML = movies.map(movie => `
            <div class="tmdb-result-item" data-title="${encodeURIComponent(movie.title)}" data-poster="${movie.poster_url}">
                ${movie.poster_url 
                    ? `<img src="${movie.poster_url}" alt="poster">` 
                    : `<div style="width:30px;height:45px;background:#334155;border-radius:4px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-film" style="color:#64748b;font-size:12px;"></i></div>`
                }
                <div>
                    <div style="color:#fff; font-weight:600; font-size:0.95rem;">${escapeHtml(movie.title)}</div>
                    ${movie.year ? `<div style="color:#94a3b8; font-size:0.8rem;">${movie.year}</div>` : ''}
                </div>
            </div>
        `).join('');
        
        searchResults.style.display = 'block';

        // Add click listeners to items
        searchResults.querySelectorAll('.tmdb-result-item').forEach(item => {
            item.addEventListener('click', () => {
                const title = decodeURIComponent(item.dataset.title);
                const poster = item.dataset.poster;

                // Set visible input
                searchInput.value = title;
                searchResults.style.display = 'none';

                // Set hidden form fields
                movieNameField.value = title;
                posterUrlField.value = poster;

                // Show preview
                if (poster) {
                    posterImg.src = poster;
                    posterPreview.style.display = 'block';
                } else {
                    posterPreview.style.display = 'none';
                }
            });
        });
    }

    // Allow manual entry if they don't click a suggestion
    if (searchInput) {
        searchInput.addEventListener('blur', () => {
            if (searchInput.value.trim() !== '' && movieNameField.value === '') {
                movieNameField.value = searchInput.value.trim();
            }
        });
    }

    // ── 2. Form Submission ──────────────────────────────────────────────────
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Basic validation
            if (!TA.loggedIn) {
                Swal.fire({
                    icon: 'error',
                    title: 'Authentication Required',
                    text: 'You must be logged in to sell tickets.',
                    confirmButtonColor: '#3b82f6'
                }).then(() => {
                    window.location.href = TA.homeUrl + 'login/';
                });
                return;
            }

            // If they typed a name but didn't select from dropdown
            if (!movieNameField.value && searchInput.value.trim()) {
                movieNameField.value = searchInput.value.trim();
            }

            if (!movieNameField.value) {
                Swal.fire('Error', 'Please enter a movie name.', 'error');
                return;
            }

            const formData = new FormData(form);
            formData.append('action', 'ta_submit_movie_ticket');
            formData.append('nonce', TA.sellMovieNonce);

            const origText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Listing Ticket...';
            submitBtn.disabled = true;

            try {
                const res = await fetch(TA.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await res.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Ticket Listed!',
                        text: data.data.message,
                        confirmButtonColor: '#10b981'
                    }).then(() => {
                        window.location.href = TA.homeUrl + 'seller-dashboard/';
                    });
                } else {
                    Swal.fire('Error', data.data.message || 'Something went wrong.', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Network error occurred. Please try again.', 'error');
            } finally {
                submitBtn.innerHTML = origText;
                submitBtn.disabled = false;
            }
        });
    }

    // Utility
    function escapeHtml(unsafe) {
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }
});
