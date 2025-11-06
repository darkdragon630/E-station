// Auto hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        // Auto hide setelah 5 detik
        setTimeout(() => {
            alert.classList.add('hiding');
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });

    // Fungsi untuk menutup alert manual
    document.querySelectorAll('.alert-close').forEach(button => {
        button.addEventListener('click', () => {
            const alert = button.parentElement;
            alert.classList.add('hiding');
            setTimeout(() => alert.remove(), 300);
        });
    });
});