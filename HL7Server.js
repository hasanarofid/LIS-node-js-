const net = require('net');

// Tentukan IP dan port untuk server
const ip = '0.0.0.0'; // Mendengarkan semua alamat IP yang tersedia pada server
const port = 6666;

const MAX_DATA_SIZE = 10245;

// Buat server TCP
const server = net.createServer((socket) => {
    // Event listener ketika klien terhubung
    console.log('Client connected');

    socket.setTimeout(60000000);

    // Event listener ketika server menerima data dari klien
    socket.on('data', (data) => {
        console.log(`Received message from client: ${data}`);
        // if (data.length > MAX_DATA_SIZE) {
        //     console.log('Data size exceeds the limit. Truncating data.');
        //     data = data.slice(0, MAX_DATA_SIZE); // Potong data menjadi ukuran maksimum yang diizinkan
        // }

        // Kirim balik pesan ke klien
        socket.write(`Server received message: ${data}`);
    });

    // Event listener ketika klien terputus
    socket.on('end', () => {
        console.log('Client disconnected');
    });
      // Event listener untuk timeout pada koneksi socket
      socket.on('timeout', () => {
        console.log('Socket timeout. Closing connection.');
        socket.end(); // Menutup koneksi saat timeout tercapai
    });
});

// Event listener untuk menangani kesalahan server
server.on('error', (err) => {
    // console.error('Server error:', err);
    console.error('Socket error - Code:', err.code, 'Message:', err.message);

});

// Mulai server dan dengarkan koneksi pada IP dan port yang ditentukan
server.listen(port, ip, () => {
    console.log(`Server is running on ${ip}:${port}`);
});
