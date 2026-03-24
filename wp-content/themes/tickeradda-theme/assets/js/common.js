// TickerAdda Global Support
window.getFilterValues = function(className) {
    const checked = document.querySelectorAll(`.${className}:checked`);
    return Array.from(checked).map(cb => cb.value);
};

function showAlert(title, text, icon = 'info') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            background: '#18181b', 
            color: '#ffffff',
            confirmButtonColor: '#3b82f6',
            confirmButtonText: 'OK'
        });
    } else {
        alert(`${title}: ${text}`);
    }
}

function initStickyNavbar() {
    const header = document.querySelector('.site-header');
    if (header) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    initStickyNavbar();
    setupMobileMenu(); 
    checkAuthStatus(); // Initial check from TA/LocalStorage
    syncAuth(); // Background sync to bypass HTML cache

    // Fill user initials if badge exists
    if (TA.loggedIn && TA.user) {
        const initials = document.querySelectorAll('.user-initials');
        initials.forEach(el => el.textContent = (TA.user.name || 'U').charAt(0).toUpperCase());

        // Mandatory Phone check (especially for Google users)
        if (TA.user.isPhoneRequired) {
            // Wait a second to not overwhelm immediately on load
            setTimeout(promptForPhone, 1500);
        }
    }
});

async function syncAuth() {
    try {
        const timestamp = Date.now();
        const res = await fetch(`${TA.restUrl}/auth/me?_t=${timestamp}`);
        if (!res.ok) return;
        
        const freshUser = await res.json();
        const isLoggedInNow = !!(freshUser && freshUser.id);
        
        // If status changed or user data differs, force UI update
        if (isLoggedInNow !== TA.loggedIn || JSON.stringify(freshUser) !== JSON.stringify(TA.user)) {
            console.log('[TA Sync] Auth status changed or stale. Hydrating UI...');
            TA.loggedIn = isLoggedInNow;
            TA.user = freshUser;
            
            // Re-run status checks
            checkAuthStatus();
            
            // If we just found out we are logged in, maybe show phone prompt
            if (isLoggedInNow && TA.user.isPhoneRequired) {
                setTimeout(promptForPhone, 1000);
            }
        }
    } catch (err) {
        console.warn('[TA Sync] Failed to sync auth status:', err);
    }
}

function setupMobileMenu() {
    const navLinks = document.querySelector('.nav-links');
    const menuToggle = document.querySelector('.menu-toggle');
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            const icon = menuToggle.querySelector('i');
            if (navLinks.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }
}

function checkAuthStatus() {
    // Priority: WordPress Session (TA) -> LocalStorage (Legacy/Sync)
    const user = TA.user || JSON.parse(localStorage.getItem('user'));
    
    // Update dashboard links if we have them
    const dashboardLink = document.getElementById('dashboardLink');
    if (dashboardLink) dashboardLink.style.display = TA.loggedIn ? 'inline-block' : 'none';
    const myTicketsLink = document.getElementById('myTicketsLink');
    if (myTicketsLink) myTicketsLink.style.display = TA.loggedIn ? 'inline-block' : 'none';

    const navLinks = document.getElementById('navLinks') || document.querySelector('.nav-links');
    const loginBtn = document.getElementById('loginBtn') || document.querySelector('.nav-links a[href*="login"]');
    
    if (TA.loggedIn && user) {
        // If loginBtn exists (meaning HTML was cached as logged-out), transform it to logout
        if (loginBtn && !loginBtn.classList.contains('btn-logout')) {
            loginBtn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Logout';
            loginBtn.href = '#';
            loginBtn.classList.remove('btn-outline');
            loginBtn.classList.add('btn-primary', 'btn-logout');
            loginBtn.onclick = (e) => {
                e.preventDefault();
                logout();
            };
        }

        // Handle userBadge (Profile)
        if (navLinks && !document.getElementById('userBadge')) {
            const userBadge = document.createElement('div');
            userBadge.id = 'userBadge';
            userBadge.className = 'nav-link';
            userBadge.style.display = 'flex';
            userBadge.style.alignItems = 'center';
            userBadge.style.gap = '10px';
            userBadge.style.cursor = 'default';
            if (window.innerWidth > 768) userBadge.style.marginRight = '15px';
            
            const initials = (user.name || 'U').charAt(0).toUpperCase();
            userBadge.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px; cursor: pointer;" onclick="window.location.href='${TA.homeUrl}seller-dashboard/'">
                    <span class="user-name-header" style="color: white; font-weight: 600; font-size: 0.95rem;">${user.name}</span>
                    <div style="width: 38px; height: 38px; background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white; border: 2px solid rgba(255,255,255,0.1); box-shadow: 0 0 15px rgba(59, 130, 246, 0.3); flex-shrink: 0;">${initials}</div>
                </div>
            `;
            // Insert before login/logout button
            if (loginBtn) {
                navLinks.insertBefore(userBadge, loginBtn);
            } else {
                navLinks.appendChild(userBadge);
            }
            
            window.addEventListener('resize', () => {
                userBadge.style.marginRight = window.innerWidth > 768 ? '15px' : '0';
            });

            localStorage.setItem('user', JSON.stringify(user));
        }

        // Show relevant dashboard links
        const dashboardLinks = document.querySelectorAll('.dashboard-link');
        dashboardLinks.forEach(link => {
            link.style.display = 'inline-block';
            if (user.role === 'admin') {
                link.href = TA.homeUrl + 'wp-admin/admin.php?page=tickeradda';
                link.innerHTML = '<i class="fas fa-tachometer-alt"></i> Admin';
            } else {
                link.href = TA.homeUrl + 'seller-dashboard/';
            }
        });
    }
}

function requireAuth() {
    if (!TA.loggedIn) {
        window.location.href = TA.homeUrl + "login/";
    }
}

async function logout() {
    // Clear all storage
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    sessionStorage.clear();
    
    // Nonce for logout is special
    window.location.href = TA.homeUrl + 'wp-login.php?action=logout&_wpnonce=' + TA.nonce + '&redirect_to=' + encodeURIComponent(TA.homeUrl);
}

function getEventImageUrl(eventName) {
    if (!eventName) return 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&q=80&w=1000'; 
    const name = eventName.toLowerCase();
    if (name.includes('ipl') || name.includes('cricket') || name.includes('match')) {
        return 'https://images.unsplash.com/photo-1531415074968-bc924375b263?auto=format&fit=crop&q=80&w=1000';
    }
    if (name.includes('concert') || name.includes('music') || name.includes('live')) {
        return 'https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?auto=format&fit=crop&q=80&w=1000'; 
    }
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(eventName)}&background=random&size=512`;
}

async function buyTicket(ticketId, price) {
    if (!TA.loggedIn) {
        Swal.fire({
            title: 'Login Required',
            text: 'You need to login to request tickets!',

            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Login Now'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = TA.homeUrl + "login/";
        });
        return;
    }
    
    const result = await Swal.fire({
        title: 'Confirm Purchase?',
        text: `You are about to buy this ticket for ₹${price}.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Buy it!',
        confirmButtonColor: '#3b82f6'
    });
    if (!result.isConfirmed) return;

    try {
        Swal.showLoading();
        const res = await fetch(TA.restUrl + '/orders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': TA.nonce
            },
            body: JSON.stringify({ ticketId }),
            credentials: 'same-origin'
        });
        const data = await res.json();
        if (res.ok) {
            Swal.fire({
                title: 'Order Created! 🎟️',
                text: 'Redirecting to checkout...',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = TA.homeUrl + 'buy-ticket/?id=' + data.orderId;
            });
        } else {
            Swal.fire('Error', data.message || data.msg || 'Failed to create order', 'error');
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Server Error', 'error');
    }
}

function getIcon(type) {
    const icons = { 
        music: 'music', 
        sports: 'football-ball', 
        comedy: 'theater-masks', 
        theatre: 'masks-theater' 
    };
    return icons[type] || 'calendar-star';
}

let isPhonePromptActive = false;

async function promptForPhone() {
    if (isPhonePromptActive) return;
    isPhonePromptActive = true;

    while (true) {
        const { value: phone, isDismissed } = await Swal.fire({
            title: 'Mobile Number Required',
            text: 'Please provide your 10-digit mobile number to continue. This is mandatory for selling tickets.',
            input: 'tel',
            inputLabel: 'Mobile Number',
            inputPlaceholder: 'e.g. 9876543210',
            background: '#18181b', color: '#fff',
            confirmButtonText: 'Save Number',
            allowOutsideClick: false,
            allowEscapeKey: false,
            inputValidator: (value) => {
                if (!value || value.length < 10) {
                    return 'Please enter a valid 10-digit number!';
                }
            }
        });

        if (phone) {
            try {
                Swal.showLoading();
                const res = await fetch(TA.restUrl + '/auth/phone', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': TA.nonce
                    },
                    body: JSON.stringify({ phone })
                });
                const data = await res.json();
                
                if (res.ok) {
                    await Swal.fire({
                        title: 'Saved!',
                        text: 'Your number has been updated.',
                        icon: 'success',
                        background: '#18181b', color: '#fff'
                    });
                    
                    TA.user.phone = phone;
                    TA.user.isPhoneRequired = false;
                    isPhonePromptActive = false;
                    
                    if (window.location.href.includes('sell-ticket')) {
                        location.reload();
                    }
                    return; // Exit loop
                } else {
                    await Swal.fire('Error', data.message || 'Failed to update phone number.', 'error');
                }
            } catch (err) {
                console.error(err);
                await Swal.fire('Error', 'Server error.', 'error');
            }
        } else {
            await Swal.fire({
                title: 'Required',
                text: 'Mobile number is mandatory.',
                icon: 'error',
                background: '#18181b', color: '#fff'
            });
        }
    }
}
