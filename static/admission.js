window.addEventListener('load', function() {
    
    // 1. Guest View: Generate Random App ID
    const randomId = "TUP-" + Date.now().toString().slice(-6);
    const display = document.getElementById("gen-id"); 
    const input = document.getElementById("input-app-id");
    
    if(display) display.innerText = randomId;
    if(input) input.value = randomId;

    // 2. Student View: Generate RICH QR Code (Info + ID)
    const name = document.getElementById('student-name');
    const appId = document.getElementById('student-app-id');
    const course = document.getElementById('student-course');
    const examDate = document.getElementById('exam-date');
    const examTime = document.getElementById('exam-time');
    const examVenue = document.getElementById('exam-venue');

    if (name && appId) {
        // Construct the info string
        const qrContent = `TUP EXAM PERMIT\n----------------\nName: ${name.value}\nID: ${appId.value}\nCourse: ${course.value}\nDate: ${examDate.value}\nTime: ${examTime.value}\nVenue: ${examVenue.value}`;

        // Web Display (Ticket in Tab 3)
        const webDisplay = document.getElementById("qr-display-web");
        if(webDisplay) {
            webDisplay.innerHTML = "";
            new QRCode(webDisplay, {
                text: qrContent,
                width: 150,
                height: 150,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.M
            });
        }

        // Print Display (Hidden until printed)
        const printDisplay = document.getElementById("qr-display-print");
        if(printDisplay) {
            printDisplay.innerHTML = "";
            new QRCode(printDisplay, {
                text: qrContent,
                width: 150,
                height: 150,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.M
            });
        }
    }
});

function switchStep(stepNum) {
    const steps = document.querySelectorAll('.modern-tab');
    const clickedStep = steps[stepNum - 1];

    if (!clickedStep.classList.contains('locked')) {
        steps.forEach(el => el.classList.remove('active'));
        clickedStep.classList.add('active');

        document.querySelectorAll('.step-pane').forEach(el => el.classList.remove('active'));
        document.getElementById('step-' + stepNum).classList.add('active');
    }
}