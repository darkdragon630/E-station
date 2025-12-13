<!-- BOTTOM NAVIGATION (MOBILE ONLY) - 6 Items Support -->
<nav class="bottom-nav">
    <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <span>Beranda</span>
    </a>
    <a href="tambah_stasiun.php" class="<?= basename($_SERVER['PHP_SELF']) == 'tambah_stasiun.php' || basename($_SERVER['PHP_SELF']) == 'tambah_stasiun.php' ? 'active' : '' ?>">
        <i class="fas fa-charging-station"></i>
        <span>Stasiun</span>
    </a>
    <a href="stok_baterai.php" class="<?= basename($_SERVER['PHP_SELF']) == 'stok_baterai.php' ? 'active' : '' ?>">
        <i class="fas fa-battery-three-quarters"></i>
        <span>Baterai</span>
    </a>
    <a href="usage_history.php" class="<?= basename($_SERVER['PHP_SELF']) == 'usage_history.php' ? 'active' : '' ?>">
        <i class="fas fa-history"></i>
        <span>Riwayat</span>
    </a>
    <a href="reports.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
        <i class="fas fa-file-invoice-dollar"></i>
        <span>Laporan</span>
    </a>
    <a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) == 'edit_profile.php' ? 'active' : '' ?>">
        <i class="fas fa-user"></i>
        <span>Profil</span>
    </a>
</nav>

<style>
/* BOTTOM NAVIGATION - Mobile Only */
.bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(15, 39, 70, 0.95);
    backdrop-filter: blur(20px);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    padding: 8px 0 calc(8px + env(safe-area-inset-bottom));
    z-index: 1000;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3);
}

body.light .bottom-nav {
    background: rgba(255, 255, 255, 0.95);
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
}

.bottom-nav a {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 8px 4px;
    color: rgba(255, 255, 255, 0.6);
    text-decoration: none;
    transition: all 0.3s;
    position: relative;
    border-radius: 12px;
    margin: 0 2px;
}

body.light .bottom-nav a {
    color: rgba(0, 0, 0, 0.6);
}

.bottom-nav a i {
    font-size: 1.3rem;
    margin-bottom: 4px;
    transition: all 0.3s;
}

.bottom-nav a span {
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.3px;
}

/* Active state */
.bottom-nav a.active {
    color: #7b61ff;
    background: rgba(123, 97, 255, 0.15);
}

body.light .bottom-nav a.active {
    color: #6d28d9;
    background: rgba(109, 40, 217, 0.15);
}

.bottom-nav a.active i {
    transform: scale(1.15);
    filter: drop-shadow(0 2px 8px rgba(123, 97, 255, 0.5));
}

/* Hover effect */
.bottom-nav a:active {
    transform: scale(0.95);
}

/* Badge notification (untuk future use) */
.bottom-nav a .badge-notif {
    position: absolute;
    top: 6px;
    right: 8px;
    background: #ef4444;
    color: white;
    font-size: 0.65rem;
    padding: 2px 5px;
    border-radius: 10px;
    font-weight: 700;
    min-width: 16px;
    text-align: center;
}

/* Hide on desktop */
@media (min-width: 768px) {
    .bottom-nav {
        display: none;
    }
}

/* Responsive adjustments */
@media (max-width: 380px) {
    .bottom-nav a i {
        font-size: 1.2rem;
    }
    
    .bottom-nav a span {
        font-size: 0.65rem;
    }
}

/* Prevent content from being hidden behind bottom nav */
@media (max-width: 767px) {
    body {
        padding-bottom: 80px;
    }
}
</style>

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

// Prevent zoom on double tap (iOS)
let lastTouchEnd = 0;
document.addEventListener('touchend', function(event) {
    const now = (new Date()).getTime();
    if (now - lastTouchEnd <= 300) {
        event.preventDefault();
    }
    lastTouchEnd = now;
}, false);
</script>