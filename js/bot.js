
const API_KEY = 'AIzaSyAQIwxoB74A0ER12Umi1JeltxOL3ddcVc8';
const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
const chatMessages = document.getElementById('chat-messages');
const userInput = document.getElementById('user-input');
const sendButton = document.getElementById('send-button');
const chatContainer = document.getElementById('chatContainer');
const chatButton = document.getElementById('chatButton');
const chatClose = document.getElementById('chatClose');
const chatOverlay = document.getElementById('chatOverlay');
const contentBlur = document.querySelector('.content-blur');
const typingIndicator = document.getElementById('typingIndicator');

// Mobile viewport adjustments
function adjustChatHeight() {
    if (window.innerWidth <= 768) {
        const visualViewport = window.visualViewport;
        chatContainer.style.height = `${visualViewport.height - 50}px`;
    }
}

function resetChatHeight() {
    if (window.innerWidth <= 768) {
        chatContainer.style.height = '65vh';
        chatContainer.style.bottom = '80px';
    }
}

window.visualViewport.addEventListener('resize', adjustChatHeight);
userInput.addEventListener('focus', adjustChatHeight);
userInput.addEventListener('blur', resetChatHeight);

// Chat toggle functionality
function toggleChat() {
    chatContainer.classList.toggle('active');
    chatOverlay.style.display = chatContainer.classList.contains('active') ? 'block' : 'none';
    contentBlur.classList.toggle('active', chatContainer.classList.contains('active'));
    if (chatContainer.classList.contains('active')) userInput.focus();
}

chatButton.addEventListener('click', toggleChat);
chatClose.addEventListener('click', toggleChat);
chatOverlay.addEventListener('click', toggleChat);

// Gemini API functions
async function generateResponse(prompt) {
    const response = await fetch(`${API_URL}?key=${API_KEY}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ contents: [{ parts: [{ text: prompt }] }] })
    });

    if (!response.ok) throw new Error('Failed to generate response');
    const data = await response.json();
    return data.candidates[0].content.parts[0].text;
}

function cleanMarkdown(text) {
    return text
        .replace(/#{1,6}\s?/g, '')
        .replace(/\*\*/g, '')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
}

function addMessage(message, isUser) {
    const messageElement = document.createElement('div');
    messageElement.classList.add('message', isUser ? 'user-message' : 'bot-message');
    
    const profileImage = document.createElement('div');
    profileImage.classList.add('profile-image');
    profileImage.dataset.role = isUser ? 'user' : 'bot';

    const messageContent = document.createElement('div');
    messageContent.classList.add('message-content');
    messageContent.textContent = message;

    messageElement.append(profileImage, messageContent);
    chatMessages.insertBefore(messageElement, typingIndicator);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

async function handleUserInput() {
    const userMessage = userInput.value.trim();
    if (!userMessage) return;

    addMessage(userMessage, true);
    userInput.value = '';
    sendButton.disabled = userInput.disabled = true;
    
    typingIndicator.style.display = 'flex';
    chatMessages.scrollTop = chatMessages.scrollHeight;

    try {
        const botResponse = await generateResponse(userMessage);
        addMessage(cleanMarkdown(botResponse), false);
    } catch (error) {
        console.error('Error:', error);
        addMessage('Sorry, I encountered an error. Please try again.', false);
    } finally {
        typingIndicator.style.display = 'none';
        sendButton.disabled = userInput.disabled = false;
        userInput.focus();
    }
}

sendButton.addEventListener('click', handleUserInput);
userInput.addEventListener('keypress', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleUserInput();
    }
});
// Modified toggle function
function toggleChat() {
    const isActive = !chatContainer.classList.contains('active');
    chatContainer.classList.toggle('active');
    chatOverlay.style.display = isActive ? 'block' : 'none';
    contentBlur.classList.toggle('active', isActive);
    document.body.classList.toggle('no-scroll', isActive);
    if (isActive) userInput.focus();
}