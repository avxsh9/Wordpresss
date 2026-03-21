// TickerAdda Global Support
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
    checkAuthStatus();

    // Fill user initials if badge exists
    if (TA.loggedIn && TA.user) {
        const initials = document.querySelectorAll('.user-initials');
        initials.forEach(el => el.textContent = (TA.user.name || 'U').charAt(0).toUpperCase());
    }
});

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
    const loginBtn = document.getElementById('loginBtn') || document.querySelector('.nav-links a[href*="login"]');
    
    if (TA.loggedIn && user && loginBtn) {
        loginBtn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Logout';
        loginBtn.href = '#';
        loginBtn.classList.remove('btn-outline');
        loginBtn.classList.add('btn-primary');
        loginBtn.onclick = (e) => {
            e.preventDefault();
            logout();
        };

        const navLinks = document.getElementById('navLinks') || document.querySelector('.nav-links');
        if (navLinks && !document.getElementById('userBadge')) {
            const userBadge = document.createElement('div');
            userBadge.id = 'userBadge';
            userBadge.className = 'nav-link';
            userBadge.style.display = 'flex';
            userBadge.style.alignItems = 'center';
            userBadge.style.gap = '10px';
            userBadge.style.cursor = 'default';
            // Only add margin-right on desktop
            if (window.innerWidth > 768) {
                userBadge.style.marginRight = '15px';
            }
            
            const initials = (user.name || 'U').charAt(0).toUpperCase();
            userBadge.innerHTML = `
                <div style="width: 38px; height: 38px; background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white; border: 2px solid rgba(255,255,255,0.1); box-shadow: 0 0 15px rgba(59, 130, 246, 0.3); flex-shrink: 0;">${initials}</div>
                <div style="display: flex; flex-direction: column; line-height: 1.1; justify-content: center;">
                    <span style="font-weight: 600; font-size: 0.85rem; color: #fff; white-space: nowrap;">${(user.name || 'User').split(' ')[0]}</span>
                    <span style="font-size: 0.75rem; color: #f59e0b; display: flex; align-items: center; gap: 4px;">
                        <i class="fas fa-star" style="font-size: 0.7rem;"></i> ${user.averageRating || 0}
                    </span>
                </div>
            `;
            navLinks.insertBefore(userBadge, loginBtn);
            
            // Re-check width on resize
            window.addEventListener('resize', () => {
                userBadge.style.marginRight = window.innerWidth > 768 ? '15px' : '0';
            });

            // Sync legacy storage
            localStorage.setItem('user', JSON.stringify(user));
        }

        // Show relevant dashboard links (Unified role: everyone sees both sets of links)
        const dashboardLinks = document.querySelectorAll('.dashboard-link');
        dashboardLinks.forEach(link => {
            link.style.display = 'inline-block';
            if (user.role === 'admin') {
                link.href = TA.homeUrl + 'wp-admin/admin.php?page=tickeradda';
                link.innerHTML = '<i class="fas fa-tachometer-alt"></i> Admin';
            } else {
                // By default show seller dashboard link for the main dashboard button
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
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    sessionStorage.clear();
    
    // Redirect to WP Logout
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
            text: 'You need to login to buy tickets!',
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

