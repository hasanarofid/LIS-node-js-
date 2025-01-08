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
    // $curlCommand = 'curl https://e-mondev.rsudrsoetomo.jatimprov.go.id/api/settings';
    // $ip_server_response = shell_exec($curlCommand);
    // return json_decode($ip_server_response, true);
    //     $ip_server = $serverSettings['data']['ip_server'];
    // $port = $serverSettings['data']['port_terima'];
    // $port_keluar = $serverSettings['data']['port_keluar'];
    $ip_server_response = [
        'data' =>
        [
            'ip_server' => '10.1.1.140',
            'port_terima' => '6677',
            'port_keluar' => '8989',
        ]
    ];
    return $ip_server_response;
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
    $ch = curl_init('https://e-mondev.rsudrsoetomo.jatimprov.go.id/api/getmessage');
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
    public function __construct($address)
    {
        $loop = Factory::create();
        $this->server = new TcpServer($address, $loop);

        $this->server->on('connection', function (ConnectionInterface $connection) {
            $clientIP = parse_url($connection->getRemoteAddress(), PHP_URL_HOST);
            $clientId = (int) $connection->stream;
            $this->clients[$clientId] = $clientIP;
            echo "#-> Client $clientId connected from IP $clientIP\n";
            // Membuat input stream untuk membaca input dari terminal
            $inputStream = new React\Stream\ReadableResourceStream(STDIN, $loop);

            // Menangani input dari terminal
            $inputStream->on('data', function ($input) use ($connection) {
                $input = trim($input); // Menghapus karakter newline dari input
                if (!empty($input)) {
                    $connection->write($input);
                    echo "Pesan terkirim ke client: $input\n";
                }
            });

            // $message = "MSH|^~\\&|VSP^080019FFFE4E0DC4^EUI-64|GE Healthcare|||20240807133500+0100||ORU^R01^ORU_R01|0040974E0DC4|P|2.6|||AL|NE||UNICODE UTF-8|||PCD_DEC_001^IHE PCD^1.3.6.1.4.1.19376.1.6.1.1.1^ISO\n";
            // $message .= "PID|||13089505^^^PID^MR||tn^yudianto^^^^^L|||\n";
            // $message .= "PV1||E|RIK^^103\n";
            // $message .= "OBR|1|080019FFFE4E0DC420240807133500^VSP^080019FFFE4E0DC4^EUI-64|080019FFFE4E0DC420240807133500^VSP^080019FFFE4E0DC4^EUI-64|182777000^monitoring of patient^SCT|||20240807133500+0100\n";
            // $connection->write($message);
            // echo 'terkirim';

            $connection->on('data', function ($data) use ($connection, $clientId, $clientIP) {
                $message_hasil = null;
                $msgs = explode(chr(0x0b), $data);
                foreach ($msgs as $msg) {
                    $fields = explode(chr(0x0d), $msg);
                    foreach ($fields as $field) {
                        $values = explode(chr(0x1c), $field);
                        $message_hasil .= $values[0] . "\n";
                    }
                }
                // $pesan = kirimpesan($message_hasil, $clientIP);

                // if ($pesan['status'] == 'success') {
                //     echo "Pesan berhasil dikirim\n";
                // } else {
                //     echo "Pesan gagal dikirim\n";
                // }
                // print_r($pesan);
                // echo 'Pesan diterima dari : '.;
                // echo 'Pesan diterima dari : '.$clientIP;
                // if (!empty($pesan['message'])) {
                //     echo '#-> ' . $clientIP . ' : ' . $pesan['message'];
                // }
                // global $loop;
                // $reactConnector = new ReactConnector($loop);
                // $connector = new ClientRatchetConnector($loop, $reactConnector); // Updated to use alias
                // $connector('ws://10.1.1.140:2222')->then(function ($conn) use ($clientIP) {
                //     $conn->send("recived|" . $clientIP);
                //     echo '#-> Terkirim ke WS';
                //     $conn->close();
                // }, function ($e) {
                //     echo "#-> Failed to connect to WebSocket: {$e->getMessage()}\n";
                // });
            });

            $connection->on('close', function () use ($clientId) {
                unset($this->clients[$clientId]);
                echo "#-> Client $clientId disconnected\n";
            });
        });

        
        $loop->run();
    }
}

// Mengambil pengaturan IP server dan port dari endpoint
$serverSettings = fetchServerIP();
$ip_server = $serverSettings['data']['ip_server'];
$port = $serverSettings['data']['port_terima'];
$port_keluar = $serverSettings['data']['port_keluar'];

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
// $httpAddress = '0.0.0.0:5678';
$server = new ReactTCPServer($address);
