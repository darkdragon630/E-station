<!-- BOTTOM NAVIGATION (MOBILE ONLY) - 6 Items Support -->
<nav class="bottom-nav">
    <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <span>Beranda</span>
    </a>
    <a href="search_location.php" class="<?= basename($_SERVER['PHP_SELF']) == 'search_location.php' ? 'active' : '' ?>">
        <i class="fas fa-map-marked-alt"></i>
        <span>Lokasi</span>
    </a>
    <a href="station_list.php" class="<?= basename($_SERVER['PHP_SELF']) == 'station_list.php' ? 'active' : '' ?>">
        <i class="fas fa-charging-station"></i>
        <span>Stasiun</span>
    </a>
    <a href="battery_stock.php" class="<?= basename($_SERVER['PHP_SELF']) == 'battery_stock.php' ? 'active' : '' ?>">
        <i class="fas fa-battery-full"></i>
        <span>Baterai</span>
    </a>
    <a href="transaction_history.php" class="<?= basename($_SERVER['PHP_SELF']) == 'transaction_history.php' ? 'active' : '' ?>">
        <i class="fas fa-history"></i>
        <span>Riwayat</span>
    </a>
    <a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
        <i class="fas fa-user"></i>
        <span>Profil</span>
    </a>
</nav>

<script>
// Active bottom nav highlight - Enhanced for 6 items
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.bottom-nav a');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        
        // Remove active class first
        link.classList.remove('active');
        
        // Check if current page matches
        if (href === currentPage || (currentPage === '' && href === 'dashboard.php')) {
            link.classList.add('active');
        }
        
        // Special handling for pages with query parameters
        if (currentPage.includes('?')) {
            const baseCurrentPage = currentPage.split('?')[0];
            const baseHref = href.split('?')[0];
            if (baseCurrentPage === baseHref) {
                link.classList.add('active');
            }
        }
    });
    
    // Optional: Auto scroll to active item if overflow
    const activeLink = document.querySelector('.bottom-nav a.active');
    if (activeLink) {
        activeLink.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }
});
</script>