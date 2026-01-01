document.addEventListener("DOMContentLoaded", () => {
    
    // --- 1. PROMPT DATA / KNOWLEDGE BASE ---
    // You can add more questions here easily.
    
    const botKnowledge = [
        // --- ADMISSION STATUS & ERRORS ---
        {
            keywords: ["fail", "rejected", "didn't pass", "not passed", "failed"],
            response: "<strong>Regarding failed status:</strong><br>" +
                      "1. <strong>First Step (Pending):</strong> If you didn't get an exam schedule, your grades (GWA) likely didn't meet the cutoff.<br>" +
                      "2. <strong>Exam Result:</strong> If you took the exam but got 'Rejected', your score was below the passing rate."
        },
        {
            keywords: ["status", "result", "check", "update"],
            response: "To check your status:<br>1. Log in to your account.<br>2. Look at the <strong>Status Bar</strong> on your dashboard.<br>" +
                      "It updates from <em>Pending</em> &rarr; <em>Exam Status</em> &rarr; <em>Exam Schedule</em> &rarr; <em>Results</em>."
        },

        // --- COURSES OFFERED ---
        {
            keywords: ["course", "program", "offer", "major", "engineering", "technology", "science"],
            response: "<div style='text-align:left; font-size:13px; max-height:200px; overflow-y:auto;'>" +
                      "<strong>College of Science:</strong><br>- BAS Lab Tech<br>- BS Computer Science<br>- BS Environmental Science<br>- BS Info System<br>- BS Info Technology<br><br>" +
                      "<strong>College of Engineering:</strong><br>- BS Civil Engineering<br>- BS Electrical Engineering<br>- BS Electronics Engineering<br>- BS Mechanical Engineering<br><br>" +
                      "<strong>Engineering Technology (BET):</strong><br>- Chemical, Electrical, Electronics, Automotive, Electromechanical, Civil, Instrumentation, Mechatronics, Non-Destructive Testing<br><br>" +
                      "<strong>Teacher Education (BTVTEd):</strong><br>- Electrical, Electronics, Computer Programming, Hardware Servicing" +
                      "</div>"
        },

        // --- TUITION & FEES ---
        {
            keywords: ["tuition", "fee", "pay", "cost", "price", "money", "scholarship", "free"],
            response: "<strong>Good news!</strong><br>TUP is a state university covered by the <em>Universal Access to Quality Tertiary Education Act</em>.<br><br>" +
                      "This means <strong>Tuition is FREE</strong> for eligible Filipino undergraduates."
        },

        // --- EXAM DETAILS ---
        {
            keywords: ["exam", "schedule", "when", "date", "permit", "coverage", "subject"],
            response: "<strong>Entrance Exam Info:</strong><br>" +
                      "If you are qualified, the schedule will appear on your Dashboard.<br><br>" +
                      "<strong>Exam Coverage usually includes:</strong><br>- Mental Ability<br>- Mathematics<br>- Science<br>- English Proficiency"
        },

        // --- REQUIREMENTS ---
        {
            keywords: ["requirement", "document", "bring", "submit", "upload", "file"],
            response: "<strong>Freshmen Requirements:</strong><br>" +
                      "- Form 138 (Report Card)<br>" +
                      "- Certificate of Good Moral Character<br>" +
                      "- PSA Birth Certificate (Original & Photocopy)<br>" +
                      "- 2x2 ID Pictures<br><br>" +
                      "<em>Note: Please wait for your status to become 'Document Checking' before submitting hard copies.</em>"
        },

        // --- ACCOUNT & TECH SUPPORT ---
        {
            keywords: ["password", "forgot", "reset", "access", "login"],
            response: "<strong>Forgot Password?</strong><br>" +
                      "For security reasons, please visit the <strong>Admission Office</strong> or the <strong>Registrar</strong> to request a password reset for your account."
        },
        {
            keywords: ["create", "account", "register", "sign up", "apply"],
            response: "To create an account:<br>" +
                      "1. Go to the <a href='admission.php' style='color:#8b0000;'>Admissions Page</a>.<br>" +
                      "2. Fill out the application form.<br>" +
                      "3. The system will generate your <strong>Application ID</strong>. This is your Username."
        },
        
        // --- GENERAL INFO ---
        {
            keywords: ["location", "where", "address", "contact"],
            response: "TUP is located at <strong>Ayala Blvd., Ermita, Manila</strong>.<br>Office hours are typically 8:00 AM to 5:00 PM, Monday to Friday."
        },
        {
            keywords: ["hello", "hi", "hey", "good morning"],
            response: "Hello! I am the TUP Admissions Bot. Ask me about courses, tuition, or your application status!"
        }
    ];

    const defaultResponse = "I'm not sure about that. Try asking about 'courses offered', 'tuition fee', or 'exam schedule'.";


    // --- 2. CHAT LOGIC (Standard) ---
    
    const chatBox = document.getElementById('chatBox');
    const userInput = document.getElementById('userInput');
    const sendBtn = document.querySelector('.send-btn');

    // Expose function for the 'Chips' clicks
    window.sendSuggestion = (text) => {
        userInput.value = text;
        sendMessage();
    };

    // Handle Enter key
    window.handleEnter = (event) => {
        if (event.key === 'Enter') {
            sendMessage();
        }
    };
    
    // Handle Button Click
    if(sendBtn) {
        sendBtn.addEventListener('click', sendMessage);
    }

    function sendMessage() {
        const text = userInput.value.trim();
        if (text === "") return;

        // 1. Add User Message
        addMessage(text, 'user-message');
        userInput.value = '';

        // 2. Simulate Bot Thinking
        showTypingIndicator();
        setTimeout(() => {
            removeTypingIndicator();
            const response = getBotResponse(text);
            addMessage(response, 'bot-message');
        }, 800);
    }

    function addMessage(text, className) {
        const div = document.createElement('div');
        div.className = `message ${className}`;
        div.innerHTML = text;
        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function showTypingIndicator() {
        const div = document.createElement('div');
        div.id = 'typing-indicator';
        div.className = 'message bot-message';
        div.style.fontStyle = 'italic';
        div.style.color = '#777';
        div.innerText = 'Typing...';
        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function removeTypingIndicator() {
        const indicator = document.getElementById('typing-indicator');
        if (indicator) indicator.remove();
    }

    // --- 3. SEARCH ENGINE LOGIC ---
    function getBotResponse(input) {
        input = input.toLowerCase();
        
        // Loop through knowledge base
        for (const item of botKnowledge) {
            // Check if ANY keyword matches
            const match = item.keywords.some(keyword => input.includes(keyword));
            if (match) {
                return item.response;
            }
        }
        
        return defaultResponse;
    }
});