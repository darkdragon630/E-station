<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$id_pengendara = (int)$_GET['id'];

try {
    $stmt = $koneksi->prepare("SELECT path_file FROM foto_profil WHERE id_pengendara = ? LIMIT 1");
    $stmt->execute([$id_pengendara]);
    $foto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($foto) {
        header('Content-Type: image/jpeg');
        header('Cache-Control: public, max-age=3600'); // Cache 1 jam
        echo $foto['path_file'];
    } else {
        header('HTTP/1.0 404 Not Found');
    }
} catch (PDOException $e) {
    header('HTTP/1.0 500 Internal Server Error');
}
?>

// Kemudian di HTML gunakan:
// <img src="get_photo.php?id=<?= $id_pengendara ?>" alt="Profile" loading="lazy">
*/
?>
