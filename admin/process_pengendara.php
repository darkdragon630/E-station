<?php
session_start();
require_once "../config/koneksi.php";

// ==================== AUTHENTICATION ====================
function checkAdminAuth() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
        header("Location: ../auth/login.php?error=unauthorized");
        exit();
    }
}

// ==================== VALIDATION ====================
function validateToggleStatusInput($data) {
    $errors = [];
    
    if (empty($data['id_pengendara']) || !is_numeric($data['id_pengendara'])) {
        $errors[] = 'ID pengendara tidak valid';
    }
    
    if (empty($data['status']) || !in_array($data['status'], ['aktif', 'nonaktif'])) {
        $errors[] = 'Status tidak valid';
    }
    
    return $errors;
}

// ==================== DATABASE OPERATIONS ====================
function togglePengendaraStatus($koneksi, $id_pengendara, $new_status) {
    try {
        $query = "UPDATE pengendara SET status_akun = :status WHERE id_pengendara = :id";
        $stmt = $koneksi->prepare($query);
        $stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id_pengendara, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error toggling status: " . $e->getMessage());
        return false;
    }
}

function checkPengendaraExists($koneksi, $id_pengendara) {
    try {
        $query = "SELECT id_pengendara FROM pengendara WHERE id_pengendara = :id";
        $stmt = $koneksi->prepare($query);
        $stmt->bindParam(':id', $id_pengendara, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    } catch (PDOException $e) {
        error_log("Error checking pengendara: " . $e->getMessage());
        return false;
    }
}

// ==================== ACTION HANDLERS ====================
function handleToggleStatus($koneksi) {
    $id_pengendara = $_POST['id_pengendara'] ?? '';
    $new_status = $_POST['status'] ?? '';
    
    // Validate input
    $errors = validateToggleStatusInput(['id_pengendara' => $id_pengendara, 'status' => $new_status]);
    if (!empty($errors)) {
        header("Location: pengendara.php?error=invalid_input");
        exit();
    }
    
    // Check if pengendara exists
    if (!checkPengendaraExists($koneksi, $id_pengendara)) {
        header("Location: pengendara.php?error=not_found");
        exit();
    }
    
    // Toggle status
    if (togglePengendaraStatus($koneksi, $id_pengendara, $new_status)) {
        $success_message = $new_status === 'aktif' ? 'user_activated' : 'user_deactivated';
        header("Location: pengendara.php?success=$success_message");
        exit();
    } else {
        header("Location: pengendara.php?error=update_failed");
        exit();
    }
}

// ==================== MAIN PROCESS ====================
checkAdminAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pengendara.php?error=invalid_request");
    exit();
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'toggle_status':
        handleToggleStatus($koneksi);
        break;
    
    default:
        header("Location: pengendara.php?error=unknown_action");
        exit();
}
?>