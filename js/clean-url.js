/**
 * Clean URL - Menghapus query parameters dari URL
 * File ini akan otomatis membersihkan parameter URL tanpa reload halaman
 */

// Fungsi untuk membersihkan URL
function cleanURL() {
    if (window.location.search) {
        // Menggunakan replaceState untuk mengganti URL tanpa reload
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

// Fungsi untuk membersihkan URL dengan delay
function cleanURLWithDelay(delayMs = 4000) {
    if (window.location.search) {
        setTimeout(function() {
            window.history.replaceState({}, document.title, window.location.pathname);
        }, delayMs);
    }
}

// Fungsi untuk membersihkan parameter tertentu saja
function cleanSpecificParam(paramName) {
    if (window.location.search) {
        const url = new URL(window.location.href);
        url.searchParams.delete(paramName);
        window.history.replaceState({}, document.title, url.pathname + url.search);
    }
}

// Fungsi untuk membersihkan beberapa parameter sekaligus
function cleanMultipleParams(paramArray) {
    if (window.location.search) {
        const url = new URL(window.location.href);
        paramArray.forEach(param => {
            url.searchParams.delete(param);
        });
        window.history.replaceState({}, document.title, url.pathname + url.search);
    }
}

// Membersihkan URL setelah 4 detik
cleanURLWithDelay(4000);

