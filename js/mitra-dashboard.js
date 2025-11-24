// ========================================
// MITRA DASHBOARD JAVASCRIPT - E-STATION
// ========================================

(function() {
    'use strict';

    // THEME TOGGLE - DESKTOP
    const toggleButton = document.getElementById("toggleTheme");
    if (toggleButton) {
        const savedTheme = localStorage.getItem("theme");
        if (savedTheme === "light") {
            document.body.classList.add("light");
            toggleButton.textContent = "☀️";
        } else {
            toggleButton.textContent = "🌙";
        }

        toggleButton.addEventListener("click", () => {
            document.body.classList.toggle("light");
            const isLight = document.body.classList.contains("light");
            toggleButton.textContent = isLight ? "☀️" : "🌙";
            localStorage.setItem("theme", isLight ? "light" : "dark");
            
            // Sync mobile button
            const mobileBtn = document.getElementById("mobileThemeToggle");
            if (mobileBtn) {
                mobileBtn.textContent = isLight ? "☀️" : "🌙";
            }
        });
    }

    // THEME TOGGLE - MOBILE
    const mobileToggleButton = document.getElementById("mobileThemeToggle");
    if (mobileToggleButton) {
        const savedTheme = localStorage.getItem("theme");
        if (savedTheme === "light") {
            document.body.classList.add("light");
            mobileToggleButton.textContent = "☀️";
        } else {
            mobileToggleButton.textContent = "🌙";
        }

        mobileToggleButton.addEventListener("click", () => {
            document.body.classList.toggle("light");
            const isLight = document.body.classList.contains("light");
            mobileToggleButton.textContent = isLight ? "☀️" : "🌙";
            localStorage.setItem("theme", isLight ? "light" : "dark");
            
            // Sync desktop button
            const desktopBtn = document.getElementById("toggleTheme");
            if (desktopBtn) {
                desktopBtn.textContent = isLight ? "☀️" : "🌙";
            }
        });
    }

    // FORM VALIDATION - STASIUN
    const stationForm = document.getElementById('stationForm');
    if (stationForm) {
        stationForm.addEventListener('submit', function(e) {
            const latitude = document.getElementById('latitude').value;
            const longitude = document.getElementById('longitude').value;
            
            // Validasi format koordinat
            if (isNaN(latitude) || isNaN(longitude)) {
                e.preventDefault();
                alert('⚠️ Koordinat harus berupa angka yang valid!');
                return false;
            }
            
            // Validasi range latitude dan longitude
            if (latitude < -90 || latitude > 90) {
                e.preventDefault();
                alert('⚠️ Latitude harus antara -90 dan 90!');
                return false;
            }
            
            if (longitude < -180 || longitude > 180) {
                e.preventDefault();
                alert('⚠️ Longitude harus antara -180 dan 180!');
                return false;
            }
            
            // Validasi file dokumen
            const dokumen = document.getElementById('dokumen_izin');
            if (dokumen && dokumen.files[0]) {
                const file = dokumen.files[0];
                const fileSize = file.size / 1024 / 1024; // dalam MB
                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                
                if (fileSize > 5) {
                    e.preventDefault();
                    alert('⚠️ Ukuran file dokumen maksimal 5MB!');
                    return false;
                }
                
                if (!allowedTypes.includes(file.type)) {
                    e.preventDefault();
                    alert('⚠️ Format file harus PDF, JPG, atau PNG!');
                    return false;
                }
            }
            
            // Validasi foto stasiun (optional)
            const foto = document.getElementById('foto_stasiun');
            if (foto && foto.files[0]) {
                const file = foto.files[0];
                const fileSize = file.size / 1024 / 1024;
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                
                if (fileSize > 5) {
                    e.preventDefault();
                    alert('⚠️ Ukuran foto maksimal 5MB!');
                    return false;
                }
                
                if (!allowedTypes.includes(file.type)) {
                    e.preventDefault();
                    alert('⚠️ Format foto harus JPG atau PNG!');
                    return false;
                }
            }
            
            // Konfirmasi submit
            if (!confirm('✅ Apakah Anda yakin ingin mengajukan pendaftaran stasiun ini?')) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    }

    // FORM VALIDATION - PROFIL MITRA
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            const nomorTelepon = document.getElementById('no_telepon');
            const passwordBaru = document.getElementById('password_baru');
            const konfirmasiPassword = document.getElementById('konfirmasi_password');
            
            // Validasi format nomor telepon Indonesia (jika diisi)
            if (nomorTelepon && nomorTelepon.value) {
                const phoneRegex = /^(08|62|0)[0-9]{9,12}$/;
                if (!phoneRegex.test(nomorTelepon.value)) {
                    e.preventDefault();
                    alert('⚠️ Format nomor telepon tidak valid! Gunakan format: 08xxxxxxxxxx');
                    return false;
                }
            }
            
            // Validasi password baru (jika diisi)
            if (passwordBaru && passwordBaru.value) {
                if (passwordBaru.value.length < 6) {
                    e.preventDefault();
                    alert('⚠️ Password minimal 6 karakter!');
                    return false;
                }
                
                if (konfirmasiPassword && passwordBaru.value !== konfirmasiPassword.value) {
                    e.preventDefault();
                    alert('⚠️ Konfirmasi password tidak sama dengan password baru!');
                    return false;
                }
            }
            
            // Konfirmasi submit
            if (!confirm('✅ Apakah Anda yakin ingin menyimpan perubahan profil?')) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    }

    // AUTO-FORMAT NOMOR TELEPON (hanya angka)
    const nomorTeleponInput = document.getElementById('no_telepon');
    if (nomorTeleponInput) {
        nomorTeleponInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            e.target.value = value;
        });
    }

    // PREVIEW IMAGE SEBELUM UPLOAD
    const fotoStasiunInput = document.getElementById('foto_stasiun');
    if (fotoStasiunInput) {
        fotoStasiunInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    console.log('✅ Foto stasiun siap diupload:', file.name);
                    // Bisa ditambahkan preview image di sini jika diperlukan
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // SMOOTH SCROLL UNTUK NAVIGASI
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const offsetTop = target.offsetTop - 80; // offset untuk header
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });

    // PREVENT ZOOM ON DOUBLE TAP (iOS)
    let lastTouchEnd = 0;
    document.addEventListener('touchend', function(event) {
        const now = (new Date()).getTime();
        if (now - lastTouchEnd <= 300) {
            event.preventDefault();
        }
        lastTouchEnd = now;
    }, false);

    // VALIDASI KOORDINAT REAL-TIME
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');

    if (latitudeInput) {
        latitudeInput.addEventListener('input', function(e) {
            const value = parseFloat(e.target.value);
            if (!isNaN(value)) {
                if (value < -90 || value > 90) {
                    e.target.style.borderColor = '#ef4444';
                } else {
                    e.target.style.borderColor = '#10b981';
                }
            }
        });
    }

    if (longitudeInput) {
        longitudeInput.addEventListener('input', function(e) {
            const value = parseFloat(e.target.value);
            if (!isNaN(value)) {
                if (value < -180 || value > 180) {
                    e.target.style.borderColor = '#ef4444';
                } else {
                    e.target.style.borderColor = '#10b981';
                }
            }
        });
    }

    // FILE INPUT CUSTOM LABEL
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                const label = document.querySelector(`label[for="${e.target.id}"]`);
                if (label) {
                    const originalText = label.textContent;
                    label.innerHTML = `${originalText} <span style="color: #10b981; font-weight: 600;">✓ ${fileName}</span>`;
                }
            }
        });
    });

    // TOOLTIP UNTUK KOORDINAT
    const koordinatTooltip = function() {
        const latInput = document.getElementById('latitude');
        const lonInput = document.getElementById('longitude');
        
        if (latInput && lonInput) {
            latInput.setAttribute('title', 'Contoh: -6.200000 (Jakarta)');
            lonInput.setAttribute('title', 'Contoh: 106.816666 (Jakarta)');
        }
    };

    // INIT TOOLTIP
    koordinatTooltip();

    // LOADING ANIMATION ON FORM SUBMIT
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton && !e.defaultPrevented) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
                
                // Re-enable after 3 seconds (fallback)
                setTimeout(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = submitButton.dataset.originalText || 'Submit';
                }, 3000);
            }
        });
        
        // Save original button text
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.dataset.originalText = submitButton.innerHTML;
        }
    });

    // DEBUG MODE (development)
    const DEBUG = false;
    if (DEBUG) {
        console.log('🚀 Mitra Dashboard initialized');
        console.log('📱 Mobile mode:', window.innerWidth <= 768);
        console.log('🎨 Theme:', document.body.classList.contains('light') ? 'Light' : 'Dark');
    }

    // DYNAMIC YEAR IN FOOTER (jika ada)
    const yearElement = document.getElementById('currentYear');
    if (yearElement) {
        yearElement.textContent = new Date().getFullYear();
    }

    // NOTIFICATION BADGE UPDATE (untuk future implementation)
    const updateNotificationBadge = function(count) {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'block' : 'none';
        }
    };

    // EXPORT FUNCTIONS (untuk digunakan dari PHP/HTML)
    window.mitraDashboard = {
        updateNotificationBadge: updateNotificationBadge,
        koordinatTooltip: koordinatTooltip
    };

    console.log('✅ Mitra Dashboard JS loaded successfully!');

})();