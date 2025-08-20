<?php

// Mengimpor autoloader Composer untuk mengelola dependensi
require 'vendor/autoload.php';

// Mengimpor kelas-kelas yang diperlukan dari React PHP
use React\EventLoop\Factory;
use React\Socket\ConnectionInterface;
use React\Socket\TcpServer;
use React\Http\HttpServer;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use Ratchet\Client\Connector as ClientRatchetConnector; // Updated import alias
use React\Socket\Connector as ReactConnector;

/**
 * Fungsi untuk mengambil pengaturan IP server dari API lokal
 * @return array Pengaturan server dalam bentuk array asosiatif
 */
function fetchServerIP()
{
    try {
        $curlCommand = 'curl -s --connect-timeout 5 --max-time 10 https://e-mon.rsudrsoetomo.jatimprov.go.id/api/settings';
        $ip_server_response = shell_exec($curlCommand);
        
        if ($ip_server_response === null || empty($ip_server_response)) {
            echo "#-> Warning: Tidak bisa mengakses API settings\n";
            return null;
        }
        
        $decoded = json_decode($ip_server_response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "#-> Warning: Response API tidak valid JSON\n";
            return null;
        }
        
        return $decoded;
    } catch (Exception $e) {
        echo "#-> Warning: Error mengakses API: " . $e->getMessage() . "\n";
        return null;
    }
}

/**
 * Fungsi untuk mengirim pesan ke API lokal
 * @param string $message Pesan yang akan dikirim
 * @param string $ip IP pengirim pesan
 * @return array Respons dari API dalam bentuk array asosiatif
 */
function kirimpesan($message, $ip)
{
    $postData = json_encode(['message_hasil' => $message, 'ip' => $ip]);
    $ch = curl_init('https://e-mon.rsudrsoetomo.jatimprov.go.id/api/getmessage');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    // echo '<pre>';
    // print_r($response);
    // echo '</pre>';
    $decodedResponse = json_decode($response, true);
    // print_r($decodedResponse);
    curl_close($ch);
    return $decodedResponse;
}

/**
 * Fungsi untuk memeriksa penggunaan port
 * @param int $port Nomor port yang akan diperiksa
 * @return array Hasil pemeriksaan port
 */
function checkPortUsage($port)
{
    exec("netstat -tuln | grep :$port", $output);
    return $output;
}

/**
 * Kelas untuk mengelola server TCP menggunakan React PHP
 */
class ReactTCPServer
{
    private $clients = [];
    private $server;
    private $httpServer;
    private $webSocketServer;

    /**
     * Konstruktor kelas ReactTCPServer
     * @param string $address Alamat server TCP
     * @param string $httpAddress Alamat server HTTP (default: '0.0.0.0:5678')
     * @param int $webSocketPort Port untuk WebSocket
     */
    public function __construct($address, $httpAddress = '0.0.0.0:5678')
    {
        $loop = Factory::create();
        $this->server = new TcpServer($address, $loop);

        $this->server->on('connection', function (ConnectionInterface $connection) {
            $clientIP = parse_url($connection->getRemoteAddress(), PHP_URL_HOST);
            $clientId = (int) $connection->stream;
            $this->clients[$clientId] = $clientIP;
            echo "#-> Client $clientId connected from IP $clientIP\n";

            $connection->on('data', function ($data) use ($connection, $clientId, $clientIP) {
                echo 'data diterima dari : ' . $clientIP;
                $message_hasil = null;
                $msgs = explode(chr(0x0b), $data);
                foreach ($msgs as $msg) {
                    $fields = explode(chr(0x0d), $msg);
                    foreach ($fields as $field) {
                        $values = explode(chr(0x1c), $field);
                        $message_hasil .= $values[0] . "\n";
                    }
                }
                $pesan = kirimpesan($message_hasil, $clientIP);
                // if ($clientIP == '10.1.28.222') {
                    // print_r($message_hasil);
                    // print_r()

                    echo '#-> Response :  ' . $clientIP . ' : ';
                    print_r($pesan);
                // }

                // if ($pesan['status'] == 'success') {
                //     echo "Pesan berhasil dikirim\n";
                // } else {
                //     echo "Pesan gagal dikirim\n";
                // }
                // print_r($pesan);
                // echo 'Pesan diterima dari : '.;
                // echo 'Pesan diterima dari : '.$clientIP;
                // if (!empty($pesan)) {
                //     echo '<pre>';
                //     // echo '#-> '.$clientIP.' : '.$pesan['message'];
                // }
                global $loop;
                $reactConnector = new ReactConnector($loop);
                $connector = new ClientRatchetConnector($loop, $reactConnector); // Updated to use alias
                $connector('ws://10.1.1.140:2222')->then(function ($conn) use ($clientIP) {
                    $conn->send("recived|" . $clientIP);
                    echo '#-> [' . $clientIP . ']Terkirim ke WS';
                    $conn->close();
                }, function ($e) {
                    echo "#-> Failed to connect to WebSocket: {$e->getMessage()}\n";
                });
            });

            $connection->on('close', function () use ($clientId) {
                unset($this->clients[$clientId]);
                echo "#-> Client $clientId disconnected\n";
            });
        });

        $this->httpServer = new HttpServer(function (ServerRequestInterface $request) {
            if ($request->getUri()->getPath() === '/connected-clients') {
                return new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    json_encode([
                        'client_IP' => array_values($this->clients),
                        'connected_clients' => array_keys($this->clients),
                        'client_count' => count($this->clients),
                    ])
                );
            }
            return new Response(404, ['Content-Type' => 'text/plain'], "Not Found\n");
        });
        $this->httpServer->listen(new \React\Socket\Server($httpAddress, $loop));
        $loop->run();
    }
}

// Mengambil pengaturan IP server dan port dari endpoint
$serverSettings = fetchServerIP();

// Fallback jika API tidak bisa diakses
if ($serverSettings === null || !isset($serverSettings['data'])) {
    echo "#-> Warning: Tidak bisa mengakses API, menggunakan konfigurasi default\n";
    $ip_server = '0.0.0.0';
    $port = 6666;
    $port_keluar = 6666;
} else {
    $ip_server = $serverSettings['data']['ip_server'] ?? '0.0.0.0';
    $port = $serverSettings['data']['port_terima'] ?? 6666;
    $port_keluar = $serverSettings['data']['port_keluar'] ?? 6666;
}

// Memeriksa apakah port sedang digunakan dan menanganinya
$output = checkPortUsage($port);
if (!empty($output)) {
    print_r($output);
    preg_match('/\s+([0-9]+)\/.*/', $output[0], $matches);
    if (isset($matches[1])) {
        $pid = $matches[1];
        exec("kill -9 $pid");
        echo "\n#-> Process using port $port has been terminated. \n";
    }
} else {
    echo "\n#-> Port $port is available for use. \n";
}

// Memulai server
$address = "$ip_server:$port";
$httpAddress = '0.0.0.0:5678';
$server = new ReactTCPServer($address, $httpAddress);
