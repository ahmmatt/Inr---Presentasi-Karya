<?php
require_once 'config/connect.php';
require_once 'midtrans-php-master/Midtrans.php';

\Midtrans\Config::$isProduction = false;
// Panggil file rahasia
require_once 'config/secret_keys.php';

// Gunakan variabel dari file rahasia tersebut
\Midtrans\Config::$serverKey = $MIDTRANS_SERVER_KEY;

$notif = new \Midtrans\Notification();

$transaction_status = $notif->transaction_status;
$order_id = $notif->order_id; 

if ($transaction_status == 'capture' || $transaction_status == 'settlement') {
    // Pembayaran Berhasil!
    $conn->query("UPDATE attendees SET status = 'approved' WHERE ticket_code = '$order_id'");
} else if ($transaction_status == 'cancel' || $transaction_status == 'deny' || $transaction_status == 'expire') {
    // Pembayaran Gagal / Batal
    $conn->query("DELETE FROM attendees WHERE ticket_code = '$order_id'");
}

http_response_code(200);
echo "OK";
?>