document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('ticketFile');
    const fileNameDisplay = document.getElementById('fileName');
    const submitBtn = document.getElementById('submitTicketBtn');

    if (!TA.loggedIn) {
        window.location.href = TA.homeUrl + "login/";
        return;
    }

    checkKycStatus();
    handleEventPreselection();
    setupEventSelection();
    setupManualListing();

    async function checkKycStatus() {
        try {
            const res = await fetch(TA.restUrl + '/kyc/status', {
                headers: { 'X-WP-Nonce': TA.nonce }
            });
            const data = await res.json();
            if (data.status !== 'approved') {
                Swal.fire({
                    title: 'KYC Required',
                    text: 'You must complete KYC verification before listing tickets.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Verify Now',
                    confirmButtonColor: '#3b82f6',
                    background: '#18181b', color: '#fff'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = TA.homeUrl + 'kyc-verification/';
                    } else {
                        window.location.href = TA.homeUrl + "seller-dashboard/";
                    }
                });
            }
        } catch (err) {
            console.error('KYC Check Error:', err);
        }
    }

    async function handleEventPreselection() {
        const urlParams = new URLSearchParams(window.location.search);
        const eventId = urlParams.get('event_id');
        const urlEventName = urlParams.get('event_name');
        const urlCategory = urlParams.get('category');
        const urlVenue = urlParams.get('venue');
        const urlDate = urlParams.get('date');
        const urlTime = urlParams.get('time');
        
        const eventEl = document.getElementById('ticketEvent');
        const categoryEl = document.getElementById('ticketCategory');
        const venueEl = document.getElementById('ticketVenue');
        const dateEl = document.getElementById('ticketEventDate');
        const timeEl = document.getElementById('ticketEventTime');
        const eventIdEl = document.getElementById('eventId');

        // Immediate fill from URL params for "instant" feel
        if (urlEventName && eventEl) eventEl.value = decodeURIComponent(urlEventName);
        if (urlCategory && categoryEl) categoryEl.value = urlCategory.toLowerCase();
        if (urlVenue && venueEl) venueEl.value = decodeURIComponent(urlVenue);
        if (urlDate && dateEl) dateEl.value = urlDate;
        if (urlTime && timeEl) timeEl.value = decodeURIComponent(urlTime);
        if (eventId && eventIdEl) eventIdEl.value = eventId;

        if (!eventId) return;

        // Mark as pre-selected
        if (eventIdEl) eventIdEl.setAttribute('data-preselected', 'true');

        try {
            // Still fetch from API to ensure we have the most accurate data (like correct ID/Slug)
            const res = await fetch(`${TA.restUrl}/events/${eventId}`);
            if (!res.ok) return;
            
            const event = await res.json();
            const isMovie = (event.category_slug && event.category_slug.toLowerCase() === 'movies') || 
                            (event.category && event.category.toLowerCase() === 'movies');
            
            // Fill fields only if they haven't been touched or were already filled from URL
            if (eventEl && (!eventEl.value || eventEl.value === event.name)) {
                eventEl.value = event.name;
                eventEl.readOnly = true;
                eventEl.style.opacity = '0.7';
            }
            if (categoryEl && event.category) {
                categoryEl.value = isMovie ? 'movies' : event.category.toLowerCase();
                categoryEl.disabled = true;
                categoryEl.style.opacity = '0.7';
            }
            
            if (isMovie) {
                if (venueEl) {
                    if (venueEl.value === 'Venue TBD' || venueEl.value === event.location) venueEl.value = '';
                    venueEl.placeholder = 'e.g. PVR Director\'s Cut, Vasant Kunj';
                    venueEl.readOnly = false;
                    venueEl.style.opacity = '1';
                }
                if (dateEl) {
                    if (dateEl.value === 'TBD' || dateEl.value === event.date) dateEl.value = '';
                    dateEl.readOnly = false;
                    dateEl.style.opacity = '1';
                }
                if (timeEl) {
                    if (timeEl.value === 'TBD' || timeEl.value === event.time) timeEl.value = '';
                    timeEl.readOnly = false;
                    timeEl.style.opacity = '1';
                }
            } else {
                if (venueEl && event.location && event.location.toLowerCase() !== 'venue tbd') {
                    venueEl.value = event.location;
                    venueEl.readOnly = true;
                    venueEl.style.opacity = '0.7';
                }
                if (dateEl && event.date && event.date !== 'TBD') {
                    dateEl.value = event.date;
                    dateEl.readOnly = true;
                    dateEl.style.opacity = '0.7';
                }
                if (timeEl && event.time && event.time !== 'TBD') {
                    timeEl.value = event.time;
                    timeEl.readOnly = true;
                    timeEl.style.opacity = '0.7';
                }
            }
        } catch (err) {
            console.error('Event fetch error:', err);
        }
    }

    function setupEventSelection() {
        const eventInput = document.getElementById('ticketEvent');
        if (!eventInput || !window.taEvents) return;

        eventInput.addEventListener('input', () => {
            const selected = window.taEvents.find(e => e.name === eventInput.value);
            const eventIdEl = document.getElementById('eventId');
            const categoryEl = document.getElementById('ticketCategory');
            const venueEl = document.getElementById('ticketVenue');
            const dateEl = document.getElementById('ticketEventDate');

            if (selected) {
                if (eventIdEl) eventIdEl.value = selected.id;
                
                const isMovie = (selected.category && selected.category.toLowerCase() === 'movies');
                if (categoryEl) categoryEl.value = isMovie ? 'movies' : selected.category.toLowerCase();

                const timeInput = document.getElementById('ticketEventTime');

                if (isMovie) {
                    if (venueEl) {
                        venueEl.value = '';
                        venueEl.placeholder = 'e.g. PVR Director\'s Cut';
                        venueEl.readOnly = false;
                        venueEl.style.opacity = '1';
                    }
                    if (dateEl) {
                        dateEl.value = '';
                        dateEl.readOnly = false;
                        dateEl.style.opacity = '1';
                    }
                    if (timeInput) {
                        timeInput.value = '';
                        timeInput.readOnly = false;
                        timeInput.style.opacity = '1';
                    }
                } else {
                    if (venueEl) {
                        venueEl.value = selected.location && selected.location.toLowerCase() !== 'venue tbd' ? selected.location : '';
                        venueEl.readOnly = !!venueEl.value;
                        venueEl.style.opacity = venueEl.value ? '0.7' : '1';
                    }
                    if (dateEl) {
                        dateEl.value = selected.date && selected.date !== 'TBD' ? selected.date : '';
                        dateEl.readOnly = !!dateEl.value;
                        dateEl.style.opacity = dateEl.value ? '0.7' : '1';
                    }
                    if (timeInput) {
                        timeInput.value = selected.time && selected.time !== 'TBD' ? selected.time : '';
                        timeInput.readOnly = !!timeInput.value;
                        timeInput.style.opacity = timeInput.value ? '0.7' : '1';
                    }
                }
                
                eventInput.style.border = '1px solid var(--primary)';
            } else {
                // Only clear if not pre-selected from URL
                if (eventIdEl && !eventIdEl.hasAttribute('data-preselected')) {
                    eventIdEl.value = '';
                    eventInput.style.border = '1px solid var(--glass-border)';
                }
            }
        });
    }

    if (dropZone) {
        dropZone.addEventListener('click', () => {
            if (fileInput) fileInput.click();
        });

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0 && fileInput) {
                fileInput.files = files; 
                handleFile(files[0]);
            }
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFile(e.target.files[0]);
            }
        });
    }

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function highlight() {
        if (dropZone) {
            dropZone.style.borderColor = 'var(--primary)';
            dropZone.style.backgroundColor = 'rgba(59, 130, 246, 0.1)';
        }
    }

    function unhighlight() {
        if (dropZone) {
            dropZone.style.borderColor = 'var(--glass-border)';
            dropZone.style.backgroundColor = 'transparent';
        }
    }

    function handleFile(file) {
        const validTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        if (!validTypes.includes(file.type)) {
            if (typeof showAlert === 'function') showAlert('Invalid file', 'Please upload a PDF or Image.', 'error');
            if (fileInput) fileInput.value = ''; 
            if (fileNameDisplay) fileNameDisplay.textContent = '';
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            if (typeof showAlert === 'function') showAlert('File too large', 'Max size is 5MB.', 'error');
            if (fileInput) fileInput.value = '';
            if (fileNameDisplay) fileNameDisplay.textContent = '';
            return;
        }
        if (fileNameDisplay) {
            fileNameDisplay.innerHTML = `<i class="fas fa-check-circle" style="margin-right: 5px;"></i> ${file.name}`;
        }
    }

    if (submitBtn) {
        submitBtn.addEventListener('click', async () => {
            const eventEl = document.getElementById('ticketEvent');
            const categoryEl = document.getElementById('ticketCategory');
            const seatEl = document.getElementById('ticketSeat');
            const rowEl = document.getElementById('ticketRow');
            const quantityEl = document.getElementById('ticketQuantity');
            const priceEl = document.getElementById('ticketPrice');
            const eventDateEl = document.getElementById('ticketEventDate');
            const eventTimeEl = document.getElementById('ticketEventTime');
            const venueEl = document.getElementById('ticketVenue');
            const sectionEl = document.getElementById('ticketSection');

            if (!eventEl || !categoryEl || !priceEl || !eventDateEl || !eventTimeEl || !venueEl || !fileInput) {
                console.error('One or more required form elements are missing.');
                return;
            }

            const event = eventEl.value;
            const category = categoryEl.value;
            const seat = seatEl ? seatEl.value : '';
            const row = rowEl ? rowEl.value : '';
            const quantity = quantityEl ? quantityEl.value : '1';
            const price = priceEl.value;
            const eventDate = eventDateEl.value;
            const eventTime = eventTimeEl.value;
            const venue = venueEl.value;
            const section = sectionEl ? sectionEl.value : '';

            if (!event || !category || !price || !eventDate || !eventTime || !venue) {
                if (typeof showAlert === 'function') showAlert("Missing Fields", "Please fill in all required fields.", "warning");
                return;
            }

            if (fileInput.files.length === 0) {
                if (typeof showAlert === 'function') showAlert("Missing File", "Please upload a ticket file (PDF or Image).", "warning");
                return;
            }

            try {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Listing...';
                submitBtn.disabled = true;

                const formData = new FormData();
                
                // Get event_id directly from URL or hidden input
                const urlParams = new URLSearchParams(window.location.search);
                const urlEventId = urlParams.get('event_id');
                const eventIdEl = document.getElementById('eventId');
                
                if (urlEventId) {
                    formData.append('event_id', urlEventId);
                } else if (eventIdEl && eventIdEl.value) {
                    formData.append('event_id', eventIdEl.value);
                }
                
                formData.append('event', event);
                formData.append('section', section);
                formData.append('category', category); 
                formData.append('row', row);
                formData.append('seat_number', seat);
                formData.append('eventDate', eventDate);
                formData.append('eventTime', eventTime);
                formData.append('venue', venue);
                formData.append('quantity', quantity);
                formData.append('price', price);
                formData.append('ticketFile', fileInput.files[0]);

                const res = await fetch(TA.restUrl + '/tickets', {
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': TA.nonce
                    },
                    body: formData
                });

                const data = await res.json();

                if (data.success || res.ok) {
                    Swal.fire({
                        title: 'Success! 🎉',
                        text: 'Ticket listed successfully! Our team will verify it shortly.',
                        icon: 'success',
                        background: '#18181b', color: '#fff',
                        confirmButtonColor: '#3b82f6'
                    }).then(() => {
                        window.location.href = TA.homeUrl + "seller-dashboard/";
                    });
                } else {
                    if (typeof showAlert === 'function') {
                        showAlert('Error', data.message || data.msg || 'Error listing ticket', 'error');
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({ title: 'Error', text: data.message || data.msg || 'Error listing ticket', icon: 'error' });
                    }
                    submitBtn.innerHTML = 'List Ticket for Sale';
                    submitBtn.disabled = false;
                }
            } catch (err) {
                console.error(err);
                if (typeof showAlert === 'function') showAlert('Server Error', 'Could not connect to the server.', 'error');
                submitBtn.innerHTML = 'List Ticket for Sale';
                submitBtn.disabled = false;
            }
        });
    }

    function setupManualListing() {
        const btnManual = document.getElementById('btnListManually');
        if (!btnManual) return;

        btnManual.addEventListener('click', () => {
            const eventEl = document.getElementById('ticketEvent');
            const eventIdEl = document.getElementById('eventId');
            const categoryEl = document.getElementById('ticketCategory');
            const venueEl = document.getElementById('ticketVenue');
            const dateEl = document.getElementById('ticketEventDate');
            const timeEl = document.getElementById('ticketEventTime');

            // Reset ID and unlock fields
            if (eventIdEl) eventIdEl.value = '';
            
            [eventEl, venueEl, dateEl, timeEl].forEach(el => {
                if (el) {
                    el.readOnly = false;
                    el.disabled = false;
                    el.style.opacity = '1';
                    el.value = '';
                }
            });

            if (categoryEl) {
                categoryEl.disabled = false;
                categoryEl.style.opacity = '1';
                categoryEl.value = 'other';
            }

            if (eventEl) {
                eventEl.focus();
                eventEl.placeholder = "Enter event name fully...";
            }

            btnManual.parentElement.innerHTML = '<span style="color: #10b981; font-size: 0.85rem;"><i class="fas fa-check"></i> Manual mode active</span>';
            
            // Mark form as manual
            document.getElementById('sellTicketForm').setAttribute('data-manual', 'true');
        });
    }
});
