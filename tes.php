<?php 
$ip_server = '10.1.1.140';
$port = 6666;

$socket = fsockopen($ip_server, $port, $errno, $errstr, 30);
if (!$socket) {
    echo "Failed to connect to server: $errstr ($errno)";
} else {
    // Kirim pesan ke server
    $message = "Hello, server!";
    fwrite($socket, $message);
    echo "Sent message to server: $message";

    // Tutup koneksi
    fclose($socket);
}

?>