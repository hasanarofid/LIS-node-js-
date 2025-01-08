<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-Time Chat</title>
    <script src="/socket.io/socket.io.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            const socket = io();

            socket.on('message', (msg) => {
                const messages = document.getElementById('messages');
                const messageItem = document.createElement('li');
                messageItem.textContent = msg;
                messages.appendChild(messageItem);
            });

            function sendMessage() {
                const input = document.getElementById('messageInput');
                const message = input.value;
                socket.emit('message', message);
                input.value = '';
            }

            document.getElementById('sendButton').addEventListener('click', sendMessage);
        });
    </script>
</head>
<body>
    <ul id="messages"></ul>
    <input id="messageInput" type="text" autocomplete="off">
    <button id="sendButton">Send</button>
</body>
</html>
