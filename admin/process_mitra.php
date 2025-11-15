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
    
    if (empty($data['id_mitra']) || !is_numeric($data['id_mitra'])) {
        $errors[] = 'ID mitra tidak valid';
    }
    
    // Sesuaikan dengan enum database: pending, disetujui, ditolak
    if (empty($data['status']) || !in_array($data['status'], ['pending', 'disetujui', 'ditolak'])) {
        $errors[] = 'Status tidak valid';
    }
    
    return $errors;
}

// ==================== DATABASE OPERATIONS ====================
function toggleMitraStatus($koneksi, $id_mitra, $new_status) {
    try {
        $query = "UPDATE mitra SET status = :status WHERE id_mitra = :id";
        $stmt = $koneksi->prepare($query);
        $stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id_mitra, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error toggling status: " . $e->getMessage());
        return false;
    }
}

function checkMitraExists($koneksi, $id_mitra) {
    try {
        $query = "SELECT id_mitra FROM mitra WHERE id_mitra = :id";
        $stmt = $koneksi->prepare($query);
        $stmt->bindParam(':id', $id_mitra, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    } catch (PDOException $e) {
        error_log("Error checking mitra: " . $e->getMessage());
        return false;
    }
}

// ==================== ACTION HANDLERS ====================
function handleToggleStatus($koneksi) {
    $id_mitra = $_POST['id_mitra'] ?? '';
    $new_status = $_POST['status'] ?? '';
    
    // Validate input
    $errors = validateToggleStatusInput(['id_mitra' => $id_mitra, 'status' => $new_status]);
    if (!empty($errors)) {
        header("Location: mitra.php?error=invalid_input");
        exit();
    }
    
    // Check if mitra exists
    if (!checkMitraExists($koneksi, $id_mitra)) {
        header("Location: mitra.php?error=not_found");
        exit();
    }
    
    // Toggle status
    if (toggleMitraStatus($koneksi, $id_mitra, $new_status)) {
        // Sesuaikan pesan dengan status yang ada
        $success_message = match($new_status) {
            'disetujui' => 'mitra_approved',
            'ditolak' => 'mitra_rejected',
            'pending' => 'mitra_pending',
            default => 'status_updated'
        };
        header("Location: mitra.php?success=$success_message");
        exit();
    } else {
        header("Location: mitra.php?error=update_failed");
        exit();
    }
}

// ==================== MAIN PROCESS ====================
checkAdminAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: mitra.php?error=invalid_request");
    exit();
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'toggle_status':
        handleToggleStatus($koneksi);
        break;
    
    default:
        header("Location: mitra.php?error=unknown_action");
        exit();
}
?>