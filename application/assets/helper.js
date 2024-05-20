export function showNotification(message) {
    const notification = document.getElementById('symplr-notification');
    const messageElement = document.getElementById('symplr-message');

    messageElement.textContent = message;
    notification.classList.add('show');

    setTimeout(() => {
        notification.classList.remove('show');
    }, 5000);
}
