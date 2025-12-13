<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Cek authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mitra') {
    header('Location: ../auth/login.php');
    exit;
}

$id_mitra = $_SESSION['user_id'];

try {
    // Ambil data mitra
    $stmt = $koneksi->prepare("SELECT nama_mitra FROM mitra WHERE id_mitra = ?");
    $stmt->execute([$id_mitra]);
    $dataMitra = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ambil daftar stasiun milik mitra dengan stok baterai
    $stmt = $koneksi->prepare("
        SELECT sp.id_stasiun, sp.nama_stasiun, sp.alamat, sp.status,
               COALESCE(SUM(sb.jumlah), 0) as total_stok
        FROM stasiun_pengisian sp
        LEFT JOIN stok_baterai sb ON sp.id_stasiun = sb.id_stasiun
        WHERE sp.id_mitra = ?
        GROUP BY sp.id_stasiun, sp.nama_stasiun, sp.alamat, sp.status
        ORDER BY sp.nama_stasiun
    ");
    $stmt->execute([$id_mitra]);
    $daftarStasiun = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil detail stok baterai per tipe jika stasiun dipilih
    $id_stasiun_selected = $_GET['stasiun'] ?? null;
    $detailStok = [];
    
    if ($id_stasiun_selected) {
        $stmt = $koneksi->prepare("
            SELECT sb.id_stok, sb.tipe_baterai, sb.jumlah, sb.terakhir_update,
                   sp.nama_stasiun
            FROM stok_baterai sb
            JOIN stasiun_pengisian sp ON sb.id_stasiun = sp.id_stasiun
            WHERE sb.id_stasiun = ? AND sp.id_mitra = ?
            ORDER BY sb.tipe_baterai
        ");
        $stmt->execute([$id_stasiun_selected, $id_mitra]);
        $detailStok = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Stok Baterai Error: " . $e->getMessage());
    $dataMitra = ['nama_mitra' => 'Mitra'];
    $daftarStasiun = [];
    $detailStok = [];
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#0f2746">
  <title>Kelola Stok Baterai — E-Station</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/mitra-style.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="../css/alert.css?v=<?= time(); ?>">
  <link rel="icon" type="image/png" href="../images/Logo_1.png">

  <style>
    .page-header {
      margin-bottom: 30px;
    }

    .page-header h2 {
      background: linear-gradient(90deg, #b98cff, #ff6fa6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-weight: 800;
      margin-bottom: 5px;
    }

    .btn-back {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #dff2ff;
      padding: 10px 20px;
      border-radius: 10px;
      font-weight: 600;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      margin-bottom: 20px;
    }

    .btn-back:hover {
      background: rgba(255, 255, 255, 0.15);
      color: white;
      transform: translateX(-3px);
    }

    body.light .btn-back {
      background: rgba(0, 0, 0, 0.05);
      border-color: rgba(0, 0, 0, 0.1);
      color: #1e293b;
    }

    .stok-card {
      background: var(--panel-bg);
      border: var(--border);
      border-radius: 16px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: var(--shadow);
      transition: all 0.3s;
    }

    .stok-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 16px 40px rgba(8, 20, 40, 0.8);
    }

    .stok-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .stok-header h5 {
      color: #cfe6ff;
      font-weight: 700;
      margin: 0;
    }

    body.light .stok-header h5 {
      color: #1e293b;
    }

    .stok-number {
      font-size: 2.5rem;
      font-weight: 800;
      background: linear-gradient(135deg, #7b61ff, #ff6b9a);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .stok-low {
      background: linear-gradient(135deg, #ff6b9a, #ffb166);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .filter-section {
      background: var(--panel-bg);
      border: var(--border);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 25px;
    }

    .detail-table {
      width: 100%;
      border-collapse: collapse;
      background: rgba(255, 255, 255, 0.02);
      border-radius: 12px;
      overflow: hidden;
    }

    .detail-table thead tr {
      background: rgba(123, 97, 255, 0.1);
    }

    .detail-table th {
      padding: 15px;
      text-align: left;
      border-bottom: 2px solid rgba(123, 97, 255, 0.3);
      color: #dff2ff;
      font-weight: 700;
    }

    body.light .detail-table th {
      color: #1e293b;
    }

    .detail-table td {
      padding: 15px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      color: var(--muted);
    }

    body.light .detail-table td {
      border-bottom-color: rgba(226, 232, 240, 0.5);
    }

    .detail-table tbody tr:hover {
      background: rgba(255, 255, 255, 0.03);
    }

    body.light .detail-table tbody tr:hover {
      background: rgba(123, 97, 255, 0.05);
    }

    .badge-stok {
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 700;
    }

    .badge-aman {
      background: rgba(49, 210, 138, 0.2);
      color: #31d28a;
      border: 1px solid rgba(49, 210, 138, 0.4);
    }

    .badge-rendah {
      background: rgba(255, 184, 107, 0.2);
      color: #ffb86b;
      border: 1px solid rgba(255, 184, 107, 0.4);
    }

    .badge-habis {
      background: rgba(255, 107, 154, 0.2);
      color: #ff6b9a;
      border: 1px solid rgba(255, 107, 154, 0.4);
    }

    @media (max-width: 768px) {
      .stok-card {
        padding: 18px;
      }

      .stok-number {
        font-size: 2rem;
      }

      .detail-table {
        font-size: 0.85rem;
      }

      .detail-table th,
      .detail-table td {
        padding: 10px;
      }
    }
  </style>
</head>
<body>

<!-- DESKTOP THEME TOGGLE -->
<div class="theme-toggle">
    <button id="toggleTheme">🌙</button>
</div>

<!-- DESKTOP NAVBAR -->
<?php include '../components/navbar-mitra.php'; ?>

<!-- MOBILE HEADER -->
<div class="mobile-header d-md-none">
    <div class="header-top">
        <a href="dashboard.php" class="btn-back" style="margin: 0; padding: 8px 15px;">
            <i class="fas fa-arrow-left"></i>
            Kembali
        </a>
        <div class="header-actions">
            <button id="mobileThemeToggle">🌙</button>
        </div>
    </div>
</div>

<!-- CONTENT -->
<div class="container mt-4 mb-5">
    <?php tampilkan_alert(); ?>

    <!-- Back Button Desktop -->
    <a href="dashboard.php" class="btn-back d-none d-md-inline-flex">
        <i class="fas fa-arrow-left"></i>
        Kembali ke Dashboard
    </a>

    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fas fa-battery-three-quarters me-2"></i>Kelola Stok Baterai</h2>
        <p style="color: var(--muted);">Pantau dan kelola stok baterai di semua stasiun Anda</p>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <?php if (empty($daftarStasiun)): ?>
        <div class="col-12">
            <div class="stok-card text-center">
                <i class="fas fa-battery-empty fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                <h5 class="text-muted">Belum ada stasiun terdaftar</h5>
                <p class="text-muted mb-3">Daftarkan stasiun terlebih dahulu untuk mengelola stok baterai</p>
                <a href="tambah_stasiun.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Tambah Stasiun
                </a>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($daftarStasiun as $stasiun): ?>
        <div class="col-md-4 mb-3">
            <div class="stok-card">
                <div class="stok-header">
                    <div>
                        <h5><?= htmlspecialchars($stasiun['nama_stasiun']); ?></h5>
                        <small style="color: var(--muted);">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            <?= htmlspecialchars(substr($stasiun['alamat'], 0, 30)); ?>...
                        </small>
                    </div>
                </div>
                <div class="text-center my-3">
                    <div class="stok-number <?= $stasiun['total_stok'] < 10 ? 'stok-low' : ''; ?>">
                        <?= $stasiun['total_stok']; ?>
                    </div>
                    <small style="color: var(--muted);">Unit Baterai</small>
                </div>
                <div class="d-grid gap-2">
                    <a href="?stasiun=<?= $stasiun['id_stasiun']; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye me-1"></i>Lihat Detail
                    </a>
                    <button type="button" class="btn btn-sm btn-primary" 
                            onclick="showTambahModal(<?= $stasiun['id_stasiun']; ?>, '<?= htmlspecialchars($stasiun['nama_stasiun']); ?>')">
                        <i class="fas fa-plus me-1"></i>Tambah Stok
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Detail Stok per Tipe -->
    <?php if (!empty($detailStok)): ?>
    <div class="station-form">
        <h4><i class="fas fa-list me-2"></i>Detail Stok: <?= htmlspecialchars($detailStok[0]['nama_stasiun']); ?></h4>
        <p class="form-description">Daftar stok baterai berdasarkan tipe/jenis</p>

        <div class="table-responsive">
            <table class="detail-table">
                <thead>
                    <tr>
                        <th>Tipe Baterai</th>
                        <th class="text-center">Jumlah</th>
                        <th class="text-center">Status</th>
                        <th>Terakhir Update</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detailStok as $stok): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($stok['tipe_baterai']); ?></strong>
                        </td>
                        <td class="text-center">
                            <strong style="font-size: 1.2rem; color: #44d8ff;"><?= $stok['jumlah']; ?></strong>
                        </td>
                        <td class="text-center">
                            <?php if ($stok['jumlah'] == 0): ?>
                                <span class="badge-stok badge-habis">Habis</span>
                            <?php elseif ($stok['jumlah'] < 10): ?>
                                <span class="badge-stok badge-rendah">Rendah</span>
                            <?php else: ?>
                                <span class="badge-stok badge-aman">Aman</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?= date('d/m/Y H:i', strtotime($stok['terakhir_update'])); ?></small>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-warning" 
                                    onclick="showEditModal(<?= $stok['id_stok']; ?>, '<?= htmlspecialchars($stok['tipe_baterai']); ?>', <?= $stok['jumlah']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php elseif ($id_stasiun_selected): ?>
    <div class="station-form text-center">
        <i class="fas fa-battery-empty fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
        <h5 class="text-muted">Belum ada stok baterai</h5>
        <p class="text-muted mb-3">Tambahkan stok baterai untuk stasiun ini</p>
    </div>
    <?php endif; ?>

</div>

<!-- Modal Tambah Stok -->
<div class="modal fade" id="tambahModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background: var(--panel-bg); border: var(--border);">
            <form action="proses_stok_baterai.php" method="POST">
                <input type="hidden" name="action" value="tambah">
                <input type="hidden" name="id_stasiun" id="tambah_id_stasiun">
                
                <div class="modal-header" style="border-bottom: var(--border);">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Stok Baterai
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <p style="color: var(--muted);">Stasiun: <strong id="tambah_nama_stasiun"></strong></p>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipe Baterai <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="tipe_baterai" placeholder="Contoh: Lithium-Ion 48V" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="jumlah" placeholder="10" min="1" required>
                    </div>
                </div>
                
                <div class="modal-footer" style="border-top: var(--border);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Stok -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background: var(--panel-bg); border: var(--border);">
            <form action="proses_stok_baterai.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_stok" id="edit_id_stok">
                
                <div class="modal-header" style="border-bottom: var(--border);">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Stok Baterai
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <p style="color: var(--muted);">Tipe: <strong id="edit_tipe_baterai"></strong></p>
                    
                    <div class="mb-3">
                        <label class="form-label">Jumlah Baru <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="jumlah" id="edit_jumlah" min="0" required>
                    </div>
                </div>
                
                <div class="modal-footer" style="border-top: var(--border);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i>Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- BOTTOM NAVIGATION (MOBILE) -->
<?php include '../components/bottom-nav-mitra.php'; ?>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Theme Toggle
function initTheme(btnId) {
    const btn = document.getElementById(btnId);
    if (!btn) return;
    
    const saved = localStorage.getItem("theme");
    if (saved === "light") {
        document.body.classList.add("light");
        btn.textContent = "☀️";
    } else {
        btn.textContent = "🌙";
    }

    btn.addEventListener("click", () => {
        document.body.classList.toggle("light");
        const isLight = document.body.classList.contains("light");
        btn.textContent = isLight ? "☀️" : "🌙";
        localStorage.setItem("theme", isLight ? "light" : "dark");
        
        const other = btnId === "toggleTheme" ? "mobileThemeToggle" : "toggleTheme";
        const otherBtn = document.getElementById(other);
        if (otherBtn) otherBtn.textContent = isLight ? "☀️" : "🌙";
    });
}

initTheme("toggleTheme");
initTheme("mobileThemeToggle");

// Show Tambah Modal
function showTambahModal(idStasiun, namaStasiun) {
    document.getElementById('tambah_id_stasiun').value = idStasiun;
    document.getElementById('tambah_nama_stasiun').textContent = namaStasiun;
    new bootstrap.Modal(document.getElementById('tambahModal')).show();
}

// Show Edit Modal
function showEditModal(idStok, tipeBaterai, jumlah) {
    document.getElementById('edit_id_stok').value = idStok;
    document.getElementById('edit_tipe_baterai').textContent = tipeBaterai;
    document.getElementById('edit_jumlah').value = jumlah;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
<script src="../js/clean-url.js?v=<?= time(); ?>"></script>
</body>
</html>