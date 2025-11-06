<?php
header('Content-Type: application/json');
require_once "../../config/koneksi.php";

// Ambil parameter dari GET
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';

// Response default
$response = ['exists' => false];

// Validasi input
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode($response);
    exit();
}

// Validasi type
if (!in_array($type, ['pengendara', 'mitra'])) {
    echo json_encode($response);
    exit();
}

try {
    // Cek di semua tabel (admin, pengendara, mitra)
    $stmt = $koneksi->prepare("
        SELECT email FROM admin WHERE email = ? 
        UNION 
        SELECT email FROM pengendara WHERE email = ? 
        UNION 
        SELECT email FROM mitra WHERE email = ?
    ");
    $stmt->execute([$email, $email, $email]);
    
    if ($stmt->rowCount() > 0) {
        $response['exists'] = true;
    }
    
} catch (PDOException $e) {
    // Jangan tampilkan error ke client
    $response['exists'] = false;
}

echo json_encode($response);
exit();
?>