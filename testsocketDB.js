const WebSocket = require('ws');
const { Client } = require('pg');

const client = new Client({
    user: 'itki',
    host: '10.1.1.140',
    database: 'koneksi-alat',
    password: 'soetomo_dr',
    port: 5432,
});

// Membuat server WebSocket
const wss = new WebSocket.Server({ port: 1234 });

async function main() {
    try {
        await client.connect();
        console.log("Berhasil terhubung ke database.");
  
        // Dengarkan notifikasi di channel
        await client.query('LISTEN data_tabel_channel');
  
        // Tangkap notifikasi dan kirim pesan melalui socket
        client.on('notification', (msg) => {
            console.log('Notifikasi diterima:', msg.payload);
            
            // Kirim notifikasi ke semua klien WebSocket yang terhubung
            wss.clients.forEach((client) => {
                if (client.readyState === WebSocket.OPEN) {
                    client.send(JSON.stringify({
                        type: 'data_change',
                        payload: msg.payload
                    }));
                }
            });
        });
    } catch (error) {
        console.error('Gagal terhubung ke database:', error);
    }
}
  
main().catch(console.error);

// Menangani koneksi WebSocket
wss.on('connection', (ws) => {
    console.log(' :-> Klien terhubung ke server socket');

    // Menangani pesan yang diterima dari klien
    ws.on('message', (message) => {
        console.log(` :-> Pesan diterima: ${message}`);
    
        // Logika pemrosesan pesan
        try {
            const parsedMessage = JSON.parse(message);
            
            // Tambahkan logika untuk menangani berbagai jenis pesan
            switch (parsedMessage.type) {
                case 'query':
                    // Contoh: Jalankan query database
                    client.query(parsedMessage.query)
                        .then(result => {
                            ws.send(JSON.stringify({ 
                                status: 'berhasil', 
                                data: result.rows 
                            }));
                        })
                        .catch(error => {
                            ws.send(JSON.stringify({ 
                                status: 'gagal', 
                                pesan: error.message 
                            }));
                        });
                    break;
                default:
                    ws.send(JSON.stringify({ 
                        status: 'gagal', 
                        pesan: 'Perintah tidak dikenali' 
                    }));
            }
        } catch (error) {
            console.error(' :-> Kesalahan parsing pesan:', error);
            ws.send(JSON.stringify({ 
                status: 'gagal', 
                pesan: 'Format pesan tidak valid' 
            }));
        }
    });

    // Menangani penutupan koneksi
    ws.on('close', () => {
        console.log(' :-> Klien terputus');
    });
});

// Menangani kesalahan server
wss.on('error', (error) => {
    console.error(' :-> Kesalahan server WebSocket:', error);
});
