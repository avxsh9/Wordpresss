document.addEventListener('DOMContentLoaded', async () => {
    const token = localStorage.getItem('token');
    const totalUsersEl = document.getElementById('totalUsers');
    const totalSalesEl = document.getElementById('totalSales');
    const activeEventsEl = document.getElementById('activeEvents');
    if (!token) {
        window.location.href = TA.homeUrl + "login/";
        return;
    }
    try {
        const res = await fetch('/api/admin/stats', {
            headers: {
                'x-auth-token': token
            }
        });
        if (!res.ok) {
            throw new Error('Failed to fetch stats');
        }
        const data = await res.json();
        totalUsersEl.textContent = data.totalUsers;
        totalSalesEl.textContent = '₹' + data.totalSales.toLocaleString('en-IN');
        activeEventsEl.textContent = data.activeEvents;
    } catch (err) {
        console.error(err);
        totalUsersEl.textContent = '0';
        totalSalesEl.textContent = '₹0';
        activeEventsEl.textContent = '0';
    }
});
