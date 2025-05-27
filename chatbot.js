document.addEventListener("DOMContentLoaded", function () {
    fetch('/B/check_session.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.logged_in) {
                const chatbotContainer = document.createElement('div');
                chatbotContainer.id = "chatbot-container";
                chatbotContainer.innerHTML = `
                    <!-- Chat Icon -->
                    <div class="chat-icon" id="auraAI-chat-icon">
                        <i class="fas fa-comments"></i>
                    </div>

                    <!-- AuraAI Chat Container (iframe) -->
                    <div id="auraAI-container" style="display: none; position: fixed; bottom: 30px; right: 30px; z-index: 1000; width: 450px; height: 650px; border: none; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border-radius: 12px; overflow: hidden;">
                        <iframe id="auraAI-iframe" src="/B/AuraAI/index.html" style="width: 100%; height: 100%; border: none;"></iframe>
                    </div>
                `;
                document.body.appendChild(chatbotContainer);

                const chatIcon = document.querySelector('#auraAI-chat-icon');
                const chatContainer = document.getElementById('auraAI-container');

                // Chat icon click handler
                chatIcon.addEventListener('click', function () {
                    if (chatContainer.style.display === 'none' || chatContainer.style.display === '') {
                        chatContainer.style.display = 'block';
                        chatIcon.style.opacity = '0';
                        chatIcon.style.visibility = 'hidden';
                    } else {
                        chatContainer.style.display = 'none';
                        chatIcon.style.opacity = '1';
                        chatIcon.style.visibility = 'visible';
                    }
                });

                // 🌟 Listen to messages from iframe
                window.addEventListener('message', function (event) {
                    // Check if message is "closeAuraAI"
                    if (event.data === 'closeAuraAI') {
                        chatContainer.style.display = 'none';
                        chatIcon.style.opacity = '1';
                        chatIcon.style.visibility = 'visible';
                    }
                });
            }
        })
        .catch(error => {
            console.error('Session check failed:', error.message);
        });
});
