<?php
// 1. KONEKSI DATABASE
require_once 'config/connect.php';

// 2. PANGGIL PHPMAILER SECARA MANUAL (Persis seperti di superadmin.php)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Atur zona waktu ke Makassar (WITA)
date_default_timezone_set('Asia/Makassar');

// 3. CARI EVENT ONLINE YANG MULAI BESOK (H-1)
// Logika: Cari event online yang tanggal start_date-nya adalah besok
$sql_events = "SELECT id_event, title, start_date, start_time, location_details 
               FROM events 
               WHERE location_type = 'online' 
               AND status = 'active' 
               AND DATE(start_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";

$result_events = $conn->query($sql_events);

if ($result_events && $result_events->num_rows > 0) {
    echo "Ditemukan " . $result_events->num_rows . " event online untuk besok.<br><hr>";
    
    while ($event = $result_events->fetch_assoc()) {
        $event_id = $event['id_event'];
        $event_title = htmlspecialchars($event['title']);
        $meet_link = $event['location_details'];
        
        // 4. CARI PESERTA YANG SUDAH APPROVED DI EVENT TERSEBUT
        $sql_attendees = "SELECT full_name, email, ticket_code FROM attendees WHERE id_event = $event_id AND status = 'approved'";
        $result_attendees = $conn->query($sql_attendees);
        
        if($result_attendees && $result_attendees->num_rows > 0){
            while ($att = $result_attendees->fetch_assoc()) {
                
                // 5. EKSEKUSI PENGIRIMAN EMAIL
                $mail = new PHPMailer(true);
                try {
                    // Konfigurasi Server (Sama persis dengan superadmin.php Anda)
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    // MASUKKAN EMAIL DAN APP PASSWORD ANDA DI SINI
                    $mail->Username   = 'basyirsinjai@gmail.com'; 
                    $mail->Password   = 'vxfj rntd snow ilve'; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;

                    $mail->setFrom('no-reply@securegate.com', 'SecureGate Events');
                    $mail->addAddress($att['email'], $att['full_name']);

                    $mail->isHTML(true);
                    $mail->Subject = 'Link Virtual Event Anda Tersedia: ' . $event_title;
                    
                    // Desain Isi Email yang Elegan
                    $mail->Body = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #121212; color: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #333;'>
                            <h2 style='text-align: center; color: #22c55e; margin-bottom: 20px;'>Virtual Room is Open!</h2>
                            <p style='color: #ccc; font-size: 15px;'>Halo <strong>{$att['full_name']}</strong>,</p>
                            <p style='color: #ccc; font-size: 15px; line-height: 1.6;'>Acara <strong>{$event_title}</strong> akan diselenggarakan BESOK! Gembok tautan E-Ticket Anda telah dibuka.</p>
                            
                            <div style='background: #1a1a1a; padding: 25px; border-radius: 10px; text-align: center; margin: 30px 0; border: 1px dashed #444;'>
                                <p style='color: #888; font-size: 13px; margin-bottom: 15px;'>Silakan klik tombol di bawah ini untuk bergabung ke ruang virtual:</p>
                                <a href='{$meet_link}' style='background: #22c55e; color: #000; padding: 14px 28px; text-decoration: none; font-weight: bold; border-radius: 8px; display: inline-block; font-size: 16px;'>Join Virtual Event</a>
                            </div>
                            
                            <p style='color: #ccc; font-size: 14px;'>Ticket ID Anda: <strong style='color: #f97316; font-family: monospace; font-size: 16px;'>{$att['ticket_code']}</strong></p>
                            <p style='color: #ccc; font-size: 14px;'>Pastikan koneksi internet Anda stabil. Sampai jumpa besok!</p>
                            
                            <hr style='border-color: #333; margin-top: 40px; margin-bottom: 20px;'>
                            <p style='text-align: center; color: #666; font-size: 12px;'>&copy; SecureGate Ticketing System</p>
                        </div>
                    ";
                    
                    $mail->send();
                    echo "<span style='color: green;'>[SUKSES]</span> Email H-1 terkirim ke: " . $att['email'] . "<br>";
                } catch (Exception $e) {
                    echo "<span style='color: red;'>[GAGAL]</span> Kirim ke " . $att['email'] . ". Error: {$mail->ErrorInfo}<br>";
                }
            }
        } else {
            echo "Tidak ada peserta yang berstatus approved di event ini.<br>";
        }
    }
} else {
    echo "Aman! Tidak ada jadwal event online untuk besok.";
}
?>