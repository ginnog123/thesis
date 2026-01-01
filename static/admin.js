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


// --- DOCUMENT CHECKLIST MODAL LOGIC ---
const modal = document.getElementById('checklistModal');
const enrollBtn = document.getElementById('enrollBtn');
const checkboxes = document.querySelectorAll('.doc-check');

function openChecklist(id, name) {
    document.getElementById('modalStudentName').innerText = name;
    
    // This line caused the error before because the element didn't exist in PHP
    const appIdElem = document.getElementById('modalAppId');
    if(appIdElem) appIdElem.innerText = id;

    document.getElementById('formAppId').value = id;
    
    // Reset Checkboxes
    checkboxes.forEach(cb => cb.checked = false);
    updateModalBtn();
    
    modal.style.display = 'flex';
}

function closeChecklist() {
    modal.style.display = 'none';
}

function updateModalBtn() {
    const checkedCount = document.querySelectorAll('.doc-check:checked').length;
    const totalCount = checkboxes.length;

    if (checkedCount === totalCount) {
        enrollBtn.innerText = "Complete Enrollment";
        enrollBtn.style.backgroundColor = "#27ae60"; 
        enrollBtn.classList.remove('follow-up');
    } else {
        enrollBtn.innerText = "Enroll (To Be Followed)";
        enrollBtn.style.backgroundColor = "#e67e22";
        enrollBtn.classList.add('follow-up');
        
    }
}

// Close modal if clicked outside
window.onclick = function(event) {
    if (event.target == modal) {
        closeChecklist();
    }
}
