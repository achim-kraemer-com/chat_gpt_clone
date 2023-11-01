/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

document.getElementById('symplr-chat-button').addEventListener('click', function() {
    let symplrChatUrl = document.getElementById('symplr-chat-answer-url').value;
    let symplrChatPrompt = document.getElementById('chat_prompt');
    let symplrChatAnswerContainer = document.querySelector('.symplr-chat-answer-container');
    let symplrChatAnswerContainerClone = symplrChatAnswerContainer.cloneNode(true);
    fetch(symplrChatUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            prompt: symplrChatPrompt.value,
        })
    })
    .then(response => response.json())
    .then(data => {
        let firstElement = document.querySelector('.symplr-chat-answer-text');
        console.log(data);
        const optimisedResult = toggleDivsWithTripleBackticks('<div class="symplr-chat-question">Frage: '+data['question']+'</div><hr>\r\n'+'\r\n'+data['answer']);
        firstElement.appendChild(optimisedResult);
        symplrChatAnswerContainer.insertAdjacentElement('beforebegin', symplrChatAnswerContainerClone);
        symplrChatPrompt.value = '';
    })
    .catch(error => {
        console.error('Fehler beim Abrufen der Daten:', error);
    });
});

function toggleDivsWithTripleBackticks(input) {
    // Trennt den String an jedem Vorkommen von ```
    const segments = input.split("```");

    // Erstellt ein Fragment, um DOM-Operationen zu optimieren
    const fragment = document.createDocumentFragment();

    segments.forEach((segment, index) => {
        if (index % 2 === 1) {
            // Für Segmente zwischen ``` wird ein div erstellt
            const div = document.createElement('div');
            div.classList.add('symplr-chat-answer-code');
            // Ersetzt Zeilenumbrüche durch <br> und fügt sie als HTML ein
            div.innerHTML = segment.replace(/\n/g, "<br>");
            fragment.appendChild(div);
        } else {
            // Für Segmente außerhalb von ``` fügt man den Text mit Zeilenumbrüchen hinzu
            const textWithBreaks = segment.replace(/\n/g, "<br>");
            const span = document.createElement('span'); // Verwenden eines Span für diesen Teil
            span.innerHTML = textWithBreaks;
            fragment.appendChild(span);
        }
    });
    return fragment;
}

