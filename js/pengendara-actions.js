/**
 * Pengendara Actions JavaScript
 * Handles status toggling for pengendara (riders)
 */

/**
 * Toggle pengendara account status
 * @param {number} id - Pengendara ID
 * @param {string} status - New status ('aktif' or 'nonaktif')
 */
function toggleStatus(id, status) {
    const action = status === 'aktif' ? 'mengaktifkan' : 'menonaktifkan';
    const confirmMessage = `Apakah Anda yakin ingin ${action} pengendara ini?`;
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    submitStatusChange(id, status);
}

/**
 * Submit status change form
 * @param {number} id - Pengendara ID
 * @param {string} status - New status
 */
function submitStatusChange(id, status) {
    const form = createStatusForm(id, status);
    document.body.appendChild(form);
    form.submit();
}

/**
 * Create hidden form for status change
 * @param {number} id - Pengendara ID
 * @param {string} status - New status
 * @returns {HTMLFormElement} Form element
 */
function createStatusForm(id, status) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'process_pengendara.php';
    
    const fields = [
        { name: 'id_pengendara', value: id },
        { name: 'status', value: status },
        { name: 'action', value: 'toggle_status' }
    ];
    
    fields.forEach(field => {
        const input = createHiddenInput(field.name, field.value);
        form.appendChild(input);
    });
    
    return form;
}

/**
 * Create hidden input element
 * @param {string} name - Input name
 * @param {string|number} value - Input value
 * @returns {HTMLInputElement} Input element
 */
function createHiddenInput(name, value) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    return input;
}

// Optional: Add event listener for table row highlighting
document.addEventListener('DOMContentLoaded', function() {
    const tableRows = document.querySelectorAll('.table tbody tr');
    
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8f9fa';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
});