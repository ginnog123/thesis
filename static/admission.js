// admission.js

// Generate App ID on load
window.onload = function() {
    // Generates a unique ID using the current timestamp
    const randomId = "TUP-" + Date.now().toString().slice(-6);
    
    // Updated IDs to match admission.php
    const display = document.getElementById("gen-id"); 
    const input = document.getElementById("input-app-id");
    
    if(display) {
        display.innerText = randomId;
    }
    if(input) {
        input.value = randomId;
    }
};

// Your existing Step Navigation logic
function nextStep(step) {
    const currentActive = document.querySelector('.form-step.active');
    if (!currentActive) return; // Guard clause if steps aren't initialized

    const inputs = currentActive.querySelectorAll('[required]');
    let valid = true;

    if (step > parseInt(currentActive.id.split('-')[1])) {
        inputs.forEach(input => {
            if (!input.value) {
                valid = false;
                input.style.borderColor = "red";
            } else {
                input.style.borderColor = "#ccc";
            }
        });
    }

    if (valid) {
        document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
        const nextStepEl = document.getElementById(`step-${step}`);
        if (nextStepEl) nextStepEl.classList.add('active');

        const steps = document.querySelectorAll('.step');
        if (steps.length > 0) {
            steps.forEach((el, idx) => {
                if (idx < step) el.classList.add('active');
                else el.classList.remove('active');
            });
        }
    } else {
        alert("Please fill in all required fields before proceeding.");
    }
}