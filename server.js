const WebSocket = require('ws');

const wss = new WebSocket.Server({ port: 6666 });

wss.on('connection', (ws) => {
  console.log('Client connected');

  ws.on('message', (data) => {
    console.log('Received data:', data.toString());
    // Lakukan sesuatu dengan data yang diterima
  });

  ws.on('close', () => {
    console.log('Client disconnected');
  });
});

console.log('Server is running on port 6666');
