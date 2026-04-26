const chatBox = document.getElementById('chatBox');
const userInput = document.getElementById('userInput');
const sendBtn = document.querySelector('.send-btn');

// Expose function for the 'Chips' clicks
window.sendSuggestion = text => {
  userInput.value = text;
  sendMessage();
};

// Handle Enter key
window.handleEnter = event => {
  if (event.key === 'Enter') {
    sendMessage();
  }
};

// Handle Button Click
if (sendBtn) {
  sendBtn.addEventListener('click', sendMessage);
}

function sendMessage() {
  const text = userInput.value.trim();
  if (text === '') return;

  // 1. Add user message
  addMessage(text, 'user-message');
  userInput.value = '';

  // 2. Show typing indicator
  showTypingIndicator();

  // 3. Send to Flask backend
  fetch('http://127.0.0.1:5000/chat', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ message: text }),
  })
    .then(response => response.json())
    .then(data => {
      removeTypingIndicator();
      addMessage(data.response, 'bot-message');
    })
    .catch(error => {
      console.error(error);
      removeTypingIndicator();
      addMessage(
        '⚠️ The server is currently unavailable. Please try again later.',
        'bot-message',
      );
    });
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
