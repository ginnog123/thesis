// admin.js
// 1. Real-time applicant search
document.getElementById('adminSearch').addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#appTableBody tr');

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
});

// 2. Status change confirmation
function confirmStatusUpdate(selectElement) {
    const newStatus = selectElement.value;
    const appId = selectElement.closest('form').querySelector('input[name="app_id"]').value;
    
    if (confirm(`Are you sure you want to change Application ${appId} to ${newStatus}?`)) {
        selectElement.closest('form').submit();
    } else {
        selectElement.selectedIndex = 0; // Reset dropdown if cancelled
    }
}


// Password Strength Checker for registration
const passInput = document.getElementById('passInput');
if (passInput) {
    passInput.addEventListener('input', function() {
        const strength = document.getElementById('passStrength');
        const val = this.value;
        
        if (val.length === 0) {
            strength.innerHTML = '';
        } else if (val.length < 8) {
            strength.innerHTML = 'Too short';
            strength.style.color = '#cf222e';
        } else {
            strength.innerHTML = 'Strong password';
            strength.style.color = '#27ae60';
        }
    });
}

// Global active nav state highlight
const currentPath = window.location.pathname.split("/").pop();
document.querySelectorAll('.nav-link').forEach(link => {
    if (link.getAttribute('href') === currentPath) {
        link.classList.add('active');
    } else {
        link.classList.remove('active');
    }
});