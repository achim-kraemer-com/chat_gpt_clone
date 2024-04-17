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
const settingsButton = document.querySelector('#settings-btn');
const historiesButton = document.querySelector('#history-btn');
const faqButton = document.querySelector('#faq-btn');
const chatTypeSelection = document.querySelector('#chat-types');
const settingsModal = document.getElementById("settingsModal");
const settingsSaveButton = document.getElementById("settings-save-btn");
const settingsCloseButton = document.getElementById("settings-close-btn");

settingsModal.style.display = "none";
const defaultChatType = 'gpt-4-1106-preview';
let userText = null;
const initialHeight = chatInput.scrollHeight;
let sessionId = null;
let previousResponse = [];
let previousResponseJson = '';
let chatType = defaultChatType;

const loadDataFromLocalStorage = () => {
    let chatType = localStorage.getItem('chat-type') || defaultChatType;
    const themeColor = localStorage.getItem('theme-color');
    document.body.classList.toggle('light-mode', themeColor === 'light_mode');
    const imageUrlLight = document.getElementById('mso-image-url-light').value;
    const imageUrlDark = document.getElementById('mso-image-url-dark').value;
    const conversationStart = document.getElementById('app_chat_conversation_start').value;
    const historyDisplay = document.getElementById('app_chat_history_displaying').value;
    themeButton.innerText = document.body.classList.contains('light-mode') ? 'dark_mode' : 'light_mode';
    let imageUrl = imageUrlLight;
    if (themeButton.innerText === 'light_mode') {
        imageUrl = imageUrlDark;
    }
    const defaultText = `<div class="default-text">
                                    <img src="${imageUrl}" class="logo" id="prompt-privacy-portal-image">
                                    <p>${conversationStart}<br>${historyDisplay}</p>
                                </div>`;
    chatContainer.innerHTML = localStorage.getItem('all-chats') || defaultText;
    chatContainer.scrollTo(0, chatContainer.scrollHeight);
    sessionId = localStorage.getItem('session-id');
    previousResponseJson = localStorage.getItem('previous-response');
    previousResponse = JSON.parse(previousResponseJson) || '';
    const chatCountValue = document.getElementById('chat-count').value;
    if (null !== previousResponse) {
        while (previousResponse.length > 2 * chatCountValue) {
            previousResponse.shift();
        }
    }
    document.querySelector('#chat-types').value = chatType;
}

loadDataFromLocalStorage();

const createElement = (html, className) => {
    const chatDiv = document.createElement('div');
    chatDiv.classList.add('chat', className);
    chatDiv.innerHTML = html;
    return chatDiv;
}

const getChatResponse = (incomingChatDiv) => {
    const symplrChatUrl = document.getElementById('symplr-chat-answer-url').value;
    chatType = localStorage.getItem('chat-type') || defaultChatType;
    const chatCountValue = document.getElementById('chat-count').value;
    fetch(symplrChatUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            prompt: userText,
            previousResponse: previousResponseJson,
            sessionId: sessionId,
            chatType: chatType,
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(errorMessage => {
                throw new Error(errorMessage);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data['error_message'] && data['error_code']) {
            // Hier können Sie die Fehlermeldung und den Fehlercode anzeigen
            const fragment = document.createElement('div');
            const pElement = document.createElement('p');
            pElement.classList.add('error');
            pElement.textContent = 'Ooops! Something went wrong while retrieving the response. Please try again. - code: '+data['error_code']+' - message: '+data['error_message'];
            fragment.appendChild(pElement);
            incomingChatDiv.querySelector('.typing-animation').remove();
            incomingChatDiv.querySelector('.chat-details').appendChild(fragment);
        } else {
            let answer = toggleDivsWithTripleBackticks(data['answer'], chatType);
            incomingChatDiv.querySelector('.typing-animation').remove();
            incomingChatDiv.querySelector('.chat-details').appendChild(answer);
            chatContainer.scrollTo(0, chatContainer.scrollHeight);
            localStorage.setItem('all-chats', chatContainer.innerHTML);
            if (null === previousResponse) {
                previousResponse = [];
            }
            previousResponse.push({"role": "user", "content": userText},{"role": "assistant", "content":JSON.stringify(data['answer'])});
            while (previousResponse.length > 2 * chatCountValue) {
                previousResponse.shift();
            }
            previousResponseJson = JSON.stringify(previousResponse);
            localStorage.setItem('previous-response', previousResponseJson);
            localStorage.setItem('session-id', data['id']);
        }
    })
    .catch(error => {
        console.error(error);
        const fragment = document.createElement('div');
        const pElement = document.createElement('p');
        pElement.classList.add('error');
        pElement.textContent = 'Ooops! Something went wrong while retrieving the response. Please try again. - '+error;
        fragment.appendChild(pElement);
        incomingChatDiv.querySelector('.typing-animation').remove();
        incomingChatDiv.querySelector('.chat-details').appendChild(fragment);
    });
}

window.copyResponse = (copyBtn) => {
    const responseTextElement = copyBtn.parentElement.nextElementSibling.querySelector('code');
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
    const imageUrlDark = document.getElementById('mso-image-url-dark').value;
    const imageUrlLight = document.getElementById('mso-image-url-light').value;
    const imgChange = document.getElementById("prompt-privacy-portal-image");
    if(themeButton.innerText === "light_mode"){
        imgChange.src = imageUrlLight;
    } else {
        imgChange.src = imageUrlDark;
    }
    
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

settingsButton.addEventListener('click', () => {
    settingsModal.style.display = "block";
});

if(historiesButton){
    historiesButton.addEventListener('click', ()=> {
        let currentURL = document.getElementById("symplr-chat-history-url").value;
        window.open(currentURL, '_blank');
    });
}

faqButton.addEventListener('click', function(){
    window.location.href = document.getElementById('symplr-faq-url').value;
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

function isPasswordSecure(password) {
    // Mindestens ein Großbuchstabe, ein Kleinbuchstabe, eine Zahl und ein Sonderzeichen
    return /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).+$/.test(password);
}

function isValidEmail(email) {
    const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
    return emailRegex.test(email);
}

settingsSaveButton.addEventListener('click', () => {
    const symplrChatUrl = document.getElementById('symplr-save-settings-url').value;
    const chatGptApiToken = document.getElementById('chat-gpt-api-token').value;
    const newPasswordOne = document.getElementById('new-password-one').value;
    const newPasswordTwo = document.getElementById('new-password-two').value;
    const newUserEmail = document.getElementById('new-user-email').value;
    const chatCount = document.getElementById('new-chat-count').value;
    const isAdmin = document.getElementById('is-admin');
    const passwordErrorMessage = document.getElementById('password-error-message');
    if (chatGptApiToken === '') {
        const chatGptApiTokenErrorMessage = document.getElementById('chatgpt-api-token-error-message');
        chatGptApiTokenErrorMessage.innerText = 'Der ChatGPT API Token ist ungültig';
        chatGptApiTokenErrorMessage.style.display = 'block';
        openTab('tab1');
        return;
    }
    if (newPasswordOne !== '') {
        if (newPasswordOne.length < 12) {
            passwordErrorMessage.innerText = 'Mindestlänge von 12 Zeiche';
            passwordErrorMessage.style.display = 'block';
            openTab('tab2');
            return;
        } else if (!isPasswordSecure(newPasswordOne)) {
            passwordErrorMessage.innerText = 'Mindestens ein Großbuchstabe, ein Kleinbuchstabe, eine Zahl und ein Sonderzeichen';
            passwordErrorMessage.style.display = 'block';
            openTab('tab2');
            return;
        } else if (newPasswordOne !== newPasswordTwo) {
            passwordErrorMessage.innerText = 'Die Passwörter sind unterschiedlich';
            passwordErrorMessage.style.display = 'block';
            openTab('tab2');
            return;
        }
    }
    if (newUserEmail !== '' && !isValidEmail(newUserEmail)) {
        const emailErrorMessage = document.getElementById('email-error-message');
        emailErrorMessage.innerText = 'Die E-Mail-Adresse ist ungültig';
        emailErrorMessage.style.display = 'block';
        openTab('tab3');
        return;
    }
    fetch(symplrChatUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            chatGptApiToken: chatGptApiToken,
            newPasswordOne: newPasswordOne,
            newPasswordTwo: newPasswordTwo,
            newUserEmail: newUserEmail,
            chatCount: chatCount,
            isAdmin: isAdmin.checked,
        })
    })
    .then(response => response.json())
    .then(data => {
        settingsModal.style.display = 'none';
    })
    .catch(error => {
        pElement.classList.add('error');
        pElement.textContent = 'Ooops! Something went wrong while retrieving the response. Please try again.';
    });
    settingsModal.style.display = 'none';
});

settingsCloseButton.addEventListener('click', () => {
    settingsModal.style.display = 'none';
});

const changeChatType = () => {
    let changeType = chatTypeSelection.value;
    localStorage.setItem('chat-type', changeType);
}

chatTypeSelection.addEventListener('change', changeChatType)

function toggleDivsWithTripleBackticks(input, chatType) {
    // Erstellt ein Fragment, um DOM-Operationen zu optimieren
    const fragment = document.createElement('div');

    if ('dall-e-3' === chatType && input.startsWith('images/')) {
        const imgElement = document.createElement("img");
        imgElement.src = input;
        imgElement.alt = "Beschreibung des Bildes";
        imgElement.classList.add('dall-e-3-image');
        imgElement.style.width = '300px'
        imgElement.style.height = '300px'
        imgElement.style.marginLeft = '20px'

        fragment.appendChild(imgElement);
    } else {
        // Trennt den String an jedem Vorkommen von ```
        const segments = input.split("```");
        segments.forEach((segment, index) => {
            if (index % 2 === 1) {
                const sections = segment.split("\n");

                // Die erste Zeile in ein <div>-Element packen
                const firstLine = sections.shift(); // Entfernt die erste Zeile aus dem Array
                const divElement = document.createElement("div");
                divElement.classList.add('symplr-chat-answer-code-title');
                divElement.innerHTML = firstLine + `<span onclick="copyResponse(this)" class="material-symbols-rounded">content_copy</span>`;
                fragment.appendChild(divElement);

                // Den Rest in ein <p>-Element packen
                const restText = sections.join("\n"); // Die verbleibenden Zeilen wieder zu einem Text verbinden
                // Für Segmente zwischen ``` wird ein div erstellt
                const preElement = document.createElement('pre');
                preElement.classList.add('symplr-chat-code-text');
                const codeElement = document.createElement('code');
                codeElement.classList.add('language-' + firstLine);
                // Ersetzt Zeilenumbrüche durch <br> und fügt sie als HTML ein
                codeElement.textContent = restText;
                preElement.appendChild(codeElement);
                fragment.appendChild(preElement);
            } else {
                const pElement = document.createElement('p');
                // Für Segmente außerhalb von ``` fügt man den Text mit Zeilenumbrüchen hinzu
                pElement.textContent = segment;
                fragment.appendChild(pElement);
            }
        });
    }
    return fragment;
}

function openTab(tabName) {
    const tabs = settingsModal.querySelectorAll(".tabcontent");
    tabs.forEach(function (tab) {
        tab.style.display = "none";
    });

    const selectedTab = document.getElementById(tabName);
    if (selectedTab) {
        selectedTab.style.display = "block";
    }
}

openTab("tab2");

const tabButtons = settingsModal.querySelectorAll('.tablinks');
tabButtons.forEach(function (button) {
    button.addEventListener("click", function () {
        const tabName = this.getAttribute("data-tab");
        openTab(tabName);
    });
});
