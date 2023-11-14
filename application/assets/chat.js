/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/chat.css';

const chatInput = document.querySelector('#chat-input');
const sendButton = document.querySelector('#send-btn');
const chatContainer = document.querySelector('.chat-container');
const themeButton = document.querySelector('#theme-btn');
const deleteButton = document.querySelector('#delete-btn');

let userText = null;
const initialHeight = chatInput.scrollHeight;
let sessionId = null;
let previousResponse = '';

const loadDataFromLocalStorage = () => {
    const themeColor = localStorage.getItem('theme-color');
    document.body.classList.toggle('light-mode', themeColor === 'light_mode');
    themeButton.innerText = document.body.classList.contains('light-mode') ? 'dark_mode' : 'light_mode';

    const defaultText = `<div class="default-text">
                                    <h1>symplr ChatGPT Clone</h1>
                                    <p>Start a conversation and explore the power of AI.<br>Your chat history will  be displayed here</p>
                                </div>`;
    chatContainer.innerHTML = localStorage.getItem('all-chats') || defaultText;
    chatContainer.scrollTo(0, chatContainer.scrollHeight);
    sessionId = localStorage.getItem('session-id');
    previousResponse = localStorage.getItem('previous-response');
}

loadDataFromLocalStorage();

const createElement = (html, className) => {
    const chatDiv = document.createElement('div');
    chatDiv.classList.add('chat', className);
    chatDiv.innerHTML = html;
    return chatDiv;
}

const getChatResponse = (incomingChatDiv) => {
    const pElement = document.createElement('p');
    const symplrChatUrl = document.getElementById('symplr-chat-answer-url').value;
    fetch(symplrChatUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            prompt: userText,
            sessionId: sessionId,
            previousResponse: previousResponse,
        })
    })
    .then(response => response.json())
    .then(data => {
        pElement.textContent = data['answer'];
        incomingChatDiv.querySelector('.typing-animation').remove();
        incomingChatDiv.querySelector('.chat-details').appendChild(pElement);
        chatContainer.scrollTo(0, chatContainer.scrollHeight);
        localStorage.setItem('all-chats', chatContainer.innerHTML);
        previousResponse = '[{"role": "user", "content": "' + userText + '"},{"role": "assistant", "content": ' + JSON.stringify(data['answer']) + '}]';
        localStorage.setItem('previous-response', previousResponse);
        localStorage.setItem('session-id', data['id']);
    })
    .catch(error => {
        pElement.classList.add('error');
        pElement.textContent = 'Ooops! Something went wrong while retrieving the response. Please try again.';
    });
}

window.copyResponse = (copyBtn) => {
    const responseTextElement = copyBtn.parentElement.querySelector('p');
    navigator.clipboard.writeText(responseTextElement.textContent);
    copyBtn.textContent = 'done';
    setTimeout(() => copyBtn.textContent = 'content_copy', 1000);
}

const showTypingAnimation = () => {
    const html = `<div class="chat-content">
            <div class="chat-details">
                <img src="/build/images/chatbot.jpg" alt="chatbot-img">
                <div class="typing-animation">
                    <div class="typing-dot" style="--delay: 0.2s"></div>
                    <div class="typing-dot" style="--delay: 0.3s"></div>
                    <div class="typing-dot" style="--delay: 0.4s"></div>
                </div>
            </div>
            <span onclick="copyResponse(this)" class="material-symbols-rounded">content_copy</span>
        </div>`;
    const incomingChatDiv = createElement(html, 'incoming');
    chatContainer.appendChild(incomingChatDiv);
    chatContainer.scrollTo(0, chatContainer.scrollHeight);
    getChatResponse(incomingChatDiv);
}

const handleOutgoingChat = () => {
    userText = chatInput.value.trim();
    if (!userText) {
        return;
    }
    chatInput.value = "";
    chatInput.style.height = `${initialHeight}px`;
    const html = `<div class="chat-content">
            <div class="chat-details">
                <img src="/build/images/chatbot.jpg" alt="chatbot-img">
                <p>${userText}</p>
            </div>
        </div>`;
    const outgoingChatDiv = createElement(html, 'outgoing');
    outgoingChatDiv.querySelector('p').textContent = userText;
    document.querySelector('.default-text')?.remove();
    chatContainer.appendChild(outgoingChatDiv);
    chatContainer.scrollTo(0, chatContainer.scrollHeight);
    setTimeout(showTypingAnimation, 500);
}

themeButton.addEventListener('click', () => {
    document.body.classList.toggle('light-mode');
    localStorage.setItem('theme-color', themeButton.innerText);
    themeButton.innerText = document.body.classList.contains('light-mode') ? 'dark_mode' : 'light_mode';
});

deleteButton.addEventListener('click', () => {
    if (confirm('Are you sure you want to delete all the chats?')) {
        localStorage.removeItem('all-chats');
        localStorage.removeItem('session-id');
        localStorage.removeItem('previous-response');
        loadDataFromLocalStorage();
    }
});

chatInput.addEventListener('input', () => {
    chatInput.style.height = `${initialHeight}px`;
    chatInput.style.height = `${chatInput.scrollHeight}px`;
});

chatInput.addEventListener('keydown', (e) => {
    if (e.key === "Enter" && !e.shiftKey && window.innerHeight > 800) {
        e.preventDefault();
        handleOutgoingChat();
    }
});

sendButton.addEventListener('click', handleOutgoingChat);

// function toggleDivsWithTripleBackticks(input) {
//     // Trennt den String an jedem Vorkommen von ```
//     const segments = input.split("```");
//
//     // Erstellt ein Fragment, um DOM-Operationen zu optimieren
//     const fragment = document.createDocumentFragment();
//
//     segments.forEach((segment, index) => {
//         if (index % 2 === 1) {
//             // Für Segmente zwischen ``` wird ein div erstellt
//             const div = document.createElement('div');
//             div.classList.add('symplr-chat-answer-code');
//             // Ersetzt Zeilenumbrüche durch <br> und fügt sie als HTML ein
//             div.innerHTML = segment.replace(/\n/g, "<br>");
//             fragment.appendChild(div);
//         } else {
//             // Für Segmente außerhalb von ``` fügt man den Text mit Zeilenumbrüchen hinzu
//             const textWithBreaks = segment.replace(/\n/g, "<br>");
//             const span = document.createElement('span'); // Verwenden eines Span für diesen Teil
//             span.innerHTML = textWithBreaks;
//             fragment.appendChild(span);
//         }
//     });
//     return fragment;
// }

