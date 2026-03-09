<?php
require_once 'config/connect.php'; // Sesuaikan path file koneksi Anda
header('Content-Type: application/json');

// Tangkap data JSON dari JavaScript
$data = json_decode(file_get_contents('php://input'), true);
$action = isset($data['action']) ? $data['action'] : '';
$token = isset($data['token']) ? $data['token'] : '';
$event_id = isset($data['event_id']) ? (int)$data['event_id'] : 0;

if (empty($token) || $event_id == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Token QR atau ID Event tidak valid!']);
    exit;
}

$token_esc = $conn->real_escape_string($token);

if ($action == 'verify') {
    // 1. CARI TIKET DI DATABASE
    $sql = "SELECT a.*, t.tier_name, u.profile_picture 
        FROM attendees a 
        JOIN ticket_tiers t ON a.id_tier = t.id_tier 
        LEFT JOIN users u ON a.id_user = u.id_user 
        WHERE a.qr_token = '$token' AND a.id_event = $event_id";
            
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $ticket = $result->fetch_assoc();
        echo json_encode(['status' => 'success', 'data' => $ticket]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Tiket TIDAK DITEMUKAN atau BUKAN untuk event ini!']);
    }

} elseif ($action == 'checkin') {
    // 2. LAKUKAN CHECK-IN (Hanya jika statusnya Approved)
    $sql_update = "UPDATE attendees SET status = 'checked_in' 
                   WHERE qr_token = '$token_esc' AND id_event = $event_id AND status = 'approved'";
                   
    $conn->query($sql_update);

    if ($conn->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Check-In Berhasil!']);
    } else {
        // Jika gagal update, kita cek kenapa
        $check = $conn->query("SELECT status FROM attendees WHERE qr_token = '$token_esc'")->fetch_assoc();
        if ($check['status'] == 'checked_in') {
            echo json_encode(['status' => 'error', 'message' => 'Tamu ini SUDAH Check-In sebelumnya!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Tiket belum dibayar / belum di-Approve!']);
        }
    }
}
?>