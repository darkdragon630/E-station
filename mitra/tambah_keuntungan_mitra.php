<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Cek authentication mitra
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mitra') {
    header('Location: ../auth/login.php');
    exit;
}

$id_mitra = $_SESSION['user_id'];

// Get all stasiun milik mitra ini dengan info stok baterai
$sql_stasiun = "SELECT 
                    sp.id_stasiun, 
                    sp.nama_stasiun, 
                    sp.alamat, 
                    sp.status, 
                    sp.tarif_per_kwh,
                    COALESCE(SUM(sb.jumlah), 0) as total_stok_baterai,
                    GROUP_CONCAT(CONCAT(sb.tipe_baterai, ' (', sb.jumlah, ')') SEPARATOR ', ') as detail_stok
                FROM stasiun_pengisian sp
                LEFT JOIN stok_baterai sb ON sp.id_stasiun = sb.id_stasiun
                WHERE sp.id_mitra = ?
                GROUP BY sp.id_stasiun
                ORDER BY sp.nama_stasiun";
$stmt_stasiun = $koneksi->prepare($sql_stasiun);
$stmt_stasiun->execute([$id_mitra]);
$stasiun_list = $stmt_stasiun->fetchAll();

if (!$stasiun_list || count($stasiun_list) == 0) {
    set_flash_message('danger', 'Anda belum memiliki stasiun terdaftar! Silakan tambah stasiun terlebih dahulu.');
    header('Location: dashboard.php');
    exit;
}

// Get all pengendara untuk dropdown
$sql_pengendara = "SELECT id_pengendara, nama, email FROM pengendara ORDER BY nama";
$stmt_pengendara = $koneksi->prepare($sql_pengendara);
$stmt_pengendara->execute();
$pengendara_list = $stmt_pengendara->fetchAll();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $koneksi->beginTransaction();
        
        // Collect form data
        $stasiun_id = intval($_POST['stasiun_id'] ?? 0);
        $pengendara_option = $_POST['pengendara_option'] ?? 'existing'; // 'existing' atau 'new'
        $jumlah_kwh = floatval($_POST['jumlah_kwh'] ?? 0);
        $total_harga = floatval($_POST['total_harga'] ?? 0);
        $tanggal_transaksi = $_POST['tanggal_transaksi'] ?? date('Y-m-d H:i:s');
        $baterai_terpakai = floatval($_POST['baterai_terpakai'] ?? 0); // Persentase baterai terpakai
        
        // Validation
        if ($stasiun_id <= 0) {
            throw new Exception('Pilih stasiun terlebih dahulu');
        }
        
        // Verify stasiun belongs to this mitra
        $sql_verify = "SELECT id_stasiun, nama_stasiun FROM stasiun_pengisian WHERE id_stasiun = ? AND id_mitra = ?";
        $stmt_verify = $koneksi->prepare($sql_verify);
        $stmt_verify->execute([$stasiun_id, $id_mitra]);
        $verify = $stmt_verify->fetch();
        
        if (!$verify) {
            throw new Exception('Stasiun tidak valid atau bukan milik Anda');
        }
        
        if ($jumlah_kwh <= 0) {
            throw new Exception('Jumlah kWh harus lebih dari 0');
        }
        
        if ($total_harga <= 0) {
            throw new Exception('Total harga harus lebih dari 0');
        }
        
        if ($baterai_terpakai < 0 || $baterai_terpakai > 100) {
            throw new Exception('Persentase baterai terpakai harus antara 0-100%');
        }
        
        // Hitung jumlah baterai yang digunakan (asumsi: 1 baterai = 100%)
        $baterai_digunakan = ceil($baterai_terpakai / 100); // Pembulatan ke atas
        
        // Cek stok baterai di stasiun
        $sql_check_stok = "SELECT sb.id_stok, sb.jumlah, sb.tipe_baterai 
                           FROM stok_baterai sb 
                           WHERE sb.id_stasiun = ? AND sb.jumlah > 0 
                           ORDER BY sb.terakhir_update ASC 
                           LIMIT 1";
        $stmt_check_stok = $koneksi->prepare($sql_check_stok);
        $stmt_check_stok->execute([$stasiun_id]);
        $stok_baterai = $stmt_check_stok->fetch();
        
        if (!$stok_baterai) {
            throw new Exception('Stok baterai di stasiun ini habis atau tidak tersedia!');
        }
        
        if ($stok_baterai['jumlah'] < $baterai_digunakan) {
            throw new Exception('Stok baterai tidak mencukupi! Tersedia: ' . $stok_baterai['jumlah'] . ', Dibutuhkan: ' . $baterai_digunakan);
        }
        
        // Handle pengendara/kendaraan
        if ($pengendara_option === 'new') {
            // Buat pengendara baru
            $nama_pengendara = trim($_POST['nama_pengendara'] ?? '');
            $email_pengendara = trim($_POST['email_pengendara'] ?? '');
            $no_telepon = trim($_POST['no_telepon'] ?? '');
            $no_plat = strtoupper(trim($_POST['no_plat'] ?? ''));
            $merk_kendaraan = trim($_POST['merk_kendaraan'] ?? '');
            $model_kendaraan = trim($_POST['model_kendaraan'] ?? '');
            
            if (empty($nama_pengendara)) {
                throw new Exception('Nama pengendara wajib diisi');
            }
            
            if (empty($email_pengendara)) {
                throw new Exception('Email pengendara wajib diisi');
            }
            
            if (empty($no_plat)) {
                throw new Exception('Nomor plat kendaraan wajib diisi');
            }
            
            // Cek apakah email sudah terdaftar
            $sql_check = "SELECT id_pengendara FROM pengendara WHERE email = ?";
            $stmt_check = $koneksi->prepare($sql_check);
            $stmt_check->execute([$email_pengendara]);
            if ($stmt_check->fetch()) {
                throw new Exception('Email sudah terdaftar. Pilih dari daftar pengendara atau gunakan email lain.');
            }
            
            // Insert pengendara baru
            $password_default = password_hash('default123', PASSWORD_DEFAULT);
            $sql_pengendara = "INSERT INTO pengendara (nama, email, no_telepon, password) VALUES (?, ?, ?, ?)";
            $stmt_pg = $koneksi->prepare($sql_pengendara);
            $stmt_pg->execute([$nama_pengendara, $email_pengendara, $no_telepon, $password_default]);
            $id_pengendara = $koneksi->lastInsertId();
            
            // Insert kendaraan baru
            $sql_kendaraan = "INSERT INTO kendaraan (id_pengendara, merk, model, no_plat) VALUES (?, ?, ?, ?)";
            $stmt_kend = $koneksi->prepare($sql_kendaraan);
            $stmt_kend->execute([$id_pengendara, $merk_kendaraan, $model_kendaraan, $no_plat]);
            
        } else {
            // Gunakan pengendara existing
            $id_pengendara = intval($_POST['id_pengendara'] ?? 0);
            
            if ($id_pengendara <= 0) {
                throw new Exception('Pilih pengendara atau tambah pengendara baru');
            }
            
            // Verify pengendara exists
            $sql_check_pg = "SELECT id_pengendara, nama FROM pengendara WHERE id_pengendara = ?";
            $stmt_check_pg = $koneksi->prepare($sql_check_pg);
            $stmt_check_pg->execute([$id_pengendara]);
            $pengendara_data = $stmt_check_pg->fetch();
            
            if (!$pengendara_data) {
                throw new Exception('Pengendara tidak ditemukan');
            }
        }
        
        // Format tanggal untuk MySQL
        if (strpos($tanggal_transaksi, 'T') !== false) {
            $tanggal_transaksi = str_replace('T', ' ', $tanggal_transaksi) . ':00';
        }
        
        // Insert transaksi
        $koneksi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "INSERT INTO transaksi 
                (id_pengendara, id_stasiun, tanggal_transaksi, jumlah_kwh, total_harga, baterai_terpakai, status_transaksi) 
                VALUES (?, ?, ?, ?, ?, ?, 'berhasil')";
        
        $stmt = $koneksi->prepare($sql);
        $result = $stmt->execute([$id_pengendara, $stasiun_id, $tanggal_transaksi, $jumlah_kwh, $total_harga, $baterai_terpakai]);
        
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception('Gagal menyimpan transaksi: ' . ($errorInfo[2] ?? 'Unknown error'));
        }
        
        $transaksi_id = $koneksi->lastInsertId();
        
        if (!$transaksi_id || $transaksi_id <= 0) {
            throw new Exception('Gagal mendapatkan ID transaksi');
        }
        
        // Update stok baterai (kurangi jumlah)
        $sql_update_stok = "UPDATE stok_baterai 
                            SET jumlah = jumlah - ?,
                                terakhir_update = NOW()
                            WHERE id_stok = ?";
        $stmt_update_stok = $koneksi->prepare($sql_update_stok);
        $stmt_update_stok->execute([$baterai_digunakan, $stok_baterai['id_stok']]);
        
        if ($stmt_update_stok->rowCount() === 0) {
            throw new Exception('Gagal mengupdate stok baterai');
        }
        
        $koneksi->commit();
        
        // Success message
        $pengendara_name = $pengendara_option === 'new' ? $nama_pengendara : ($pengendara_data['nama'] ?? 'Unknown');
        $stok_sisa = $stok_baterai['jumlah'] - $baterai_digunakan;
        set_flash_message('success', "✅ Transaksi berhasil dicatat!<br>📊 Transaksi ID: #{$transaksi_id}<br>👤 Pengendara: {$pengendara_name}<br>🏢 Stasiun: {$verify['nama_stasiun']}<br>⚡ " . number_format($jumlah_kwh, 2) . " kWh<br>🔋 Baterai: " . number_format($baterai_terpakai, 1) . "%<br>📦 Stok Baterai Digunakan: {$baterai_digunakan} unit ({$stok_baterai['tipe_baterai']})<br>📊 Sisa Stok: {$stok_sisa} unit<br>💰 Total: Rp " . number_format($total_harga, 0, ',', '.'));
        header('Location: dashboard.php');
        exit;
        
    } catch (Exception $e) {
        if ($koneksi->inTransaction()) {
            $koneksi->rollBack();
        }
        set_flash_message('danger', '❌ ' . $e->getMessage());
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#0f2746">
  <title>Catat Transaksi — E-Station</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/mitra-style.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="../css/alert.css?v=<?= time(); ?>">

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

    .page-header p {
      color: var(--muted);
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

    .info-alert {
      background: rgba(68, 216, 255, 0.1);
      border: 1px solid rgba(68, 216, 255, 0.3);
      border-radius: 12px;
      padding: 15px 20px;
      margin-bottom: 25px;
      display: flex;
      align-items: flex-start;
      gap: 15px;
    }

    .info-alert i {
      color: #44d8ff;
      font-size: 1.5rem;
      margin-top: 2px;
    }

    .info-alert .content h6 {
      color: #44d8ff;
      font-weight: 700;
      margin-bottom: 5px;
    }

    .info-alert .content p {
      color: var(--muted);
      margin: 0;
      font-size: 0.9rem;
    }

    .form-container {
      max-width: 900px;
      margin: 0 auto;
    }

    .total-display {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 15px;
      padding: 25px;
      color: white;
      text-align: center;
      margin: 20px 0;
      box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }

    .total-display .label {
      font-size: 0.9rem;
      opacity: 0.9;
      margin-bottom: 8px;
    }

    .total-display .amount {
      font-size: 2.5rem;
      font-weight: 800;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    .total-display .currency {
      font-size: 1.5rem;
      margin-right: 5px;
    }

    .pengendara-toggle {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      background: rgba(255, 255, 255, 0.05);
      padding: 5px;
      border-radius: 10px;
    }

    .pengendara-toggle button {
      flex: 1;
      padding: 12px;
      border: none;
      background: transparent;
      color: var(--muted);
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }

    .pengendara-toggle button.active {
      background: linear-gradient(135deg, #b98cff, #44d8ff);
      color: white;
    }

    .pengendara-section {
      display: none;
    }

    .pengendara-section.active {
      display: block;
    }

    .btn-preset {
      padding: 8px 15px;
      border-radius: 8px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      background: rgba(255, 255, 255, 0.1);
      color: var(--text);
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 0.85rem;
    }

    .btn-preset:hover {
      background: linear-gradient(135deg, #b98cff, #44d8ff);
      color: white;
      border-color: transparent;
      transform: translateY(-2px);
    }

    body.light .btn-preset {
      background: rgba(0, 0, 0, 0.05);
      border-color: rgba(0, 0, 0, 0.1);
    }

    body.light .btn-preset:hover {
      background: linear-gradient(135deg, #b98cff, #44d8ff);
      color: white;
    }

    #baterai_display {
      min-width: 60px;
      text-align: center;
    }

    @media (max-width: 768px) {
      .form-container {
        padding: 0;
      }

      .total-display .amount {
        font-size: 2rem;
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

    <div class="form-container">

        <!-- Back Button Desktop -->
        <a href="dashboard.php" class="btn-back d-none d-md-inline-flex">
            <i class="fas fa-arrow-left"></i>
            Kembali ke Dashboard
        </a>

        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-plus-circle me-2"></i>Catat Transaksi</h2>
            <p>Input transaksi pengisian daya listrik pelanggan</p>
        </div>

        <!-- Info Alert -->
        <div class="info-alert">
            <i class="fas fa-info-circle"></i>
            <div class="content">
                <h6>Cara Mencatat Transaksi</h6>
                <p>Pilih atau tambah data pengendara/pelanggan, lalu masukkan jumlah kWh yang diisi. Transaksi akan langsung tercatat dengan status <strong>"Berhasil"</strong>.</p>
            </div>
        </div>

        <!-- FORMULIR -->
        <div class="station-form">
            <h4><i class="fas fa-file-invoice-dollar me-2"></i>Formulir Transaksi</h4>
            
            <form id="profitForm" method="POST" action="">
                
                <!-- Pilih Stasiun -->
                <div class="form-group">
                    <label for="stasiun_id">Pilih Stasiun <span class="text-danger">*</span></label>
                    <select id="stasiun_id" name="stasiun_id" required>
                        <option value="">-- Pilih Stasiun --</option>
                        <?php foreach ($stasiun_list as $stasiun): ?>
                            <option value="<?= $stasiun['id_stasiun'] ?>" 
                                    data-tarif="<?= $stasiun['tarif_per_kwh'] ?? 2500 ?>"
                                    data-stok="<?= $stasiun['total_stok_baterai'] ?>"
                                    data-detail="<?= htmlspecialchars($stasiun['detail_stok'] ?? 'Tidak ada stok') ?>">
                                <?= htmlspecialchars($stasiun['nama_stasiun']) ?>
                                - Stok Baterai: <?= $stasiun['total_stok_baterai'] ?> unit
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted" id="stok-info">
                        <i class="fas fa-battery-full"></i> Pilih stasiun untuk melihat detail stok baterai
                    </small>
                    <div id="stok-warning" style="display: none; margin-top: 10px; padding: 10px; background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); border-radius: 8px; color: #ffc107;">
                        <i class="fas fa-exclamation-triangle"></i> <span id="warning-text"></span>
                    </div>
                </div>

                <!-- Toggle Pengendara -->
                <div class="form-group">
                    <label>Pilih Pengendara/Pelanggan <span class="text-danger">*</span></label>
                    <div class="pengendara-toggle">
                        <button type="button" class="toggle-btn active" data-target="existing">
                            <i class="fas fa-list"></i> Pilih dari Daftar
                        </button>
                        <button type="button" class="toggle-btn" data-target="new">
                            <i class="fas fa-user-plus"></i> Tambah Baru
                        </button>
                    </div>
                </div>

                <input type="hidden" name="pengendara_option" id="pengendara_option" value="existing">

                <!-- Section: Pilih Pengendara Existing -->
                <div id="section-existing" class="pengendara-section active">
                    <div class="form-group">
                        <label for="id_pengendara">Pilih Pengendara</label>
                        <select id="id_pengendara" name="id_pengendara">
                            <option value="">-- Pilih Pengendara --</option>
                            <?php foreach ($pengendara_list as $pg): ?>
                                <option value="<?= $pg['id_pengendara'] ?>">
                                    <?= htmlspecialchars($pg['nama']) ?> (<?= htmlspecialchars($pg['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Section: Tambah Pengendara Baru -->
                <div id="section-new" class="pengendara-section">
                    <div class="form-group">
                        <label for="nama_pengendara">Nama Pengendara <span class="text-danger">*</span></label>
                        <input type="text" id="nama_pengendara" name="nama_pengendara" placeholder="Nama lengkap pelanggan">
                    </div>

                    <div class="form-group">
                        <label for="email_pengendara">Email <span class="text-danger">*</span></label>
                        <input type="email" id="email_pengendara" name="email_pengendara" placeholder="email@example.com">
                        <small class="form-text text-muted">Email harus unik untuk setiap pengendara</small>
                    </div>

                    <div class="form-group">
                        <label for="no_telepon">No. Telepon</label>
                        <input type="text" id="no_telepon" name="no_telepon" placeholder="08xxxxxxxxxx">
                    </div>

                    <div class="form-group">
                        <label for="no_plat">Nomor Plat Kendaraan <span class="text-danger">*</span></label>
                        <input type="text" id="no_plat" name="no_plat" placeholder="B 1234 XYZ" style="text-transform: uppercase;">
                    </div>

                    <div class="form-group">
                        <label for="merk_kendaraan">Merk Kendaraan</label>
                        <input type="text" id="merk_kendaraan" name="merk_kendaraan" placeholder="Tesla, BYD, Hyundai, dll">
                    </div>

                    <div class="form-group">
                        <label for="model_kendaraan">Model Kendaraan</label>
                        <input type="text" id="model_kendaraan" name="model_kendaraan" placeholder="Model 3, Seal, Ioniq 5, dll">
                    </div>
                </div>

                <div class="form-group">
                    <label for="jumlah_kwh">Jumlah kWh <span class="text-danger">*</span></label>
                    <input type="number" 
                           id="jumlah_kwh" 
                           name="jumlah_kwh" 
                           step="0.01"
                           min="0.01"
                           placeholder="Contoh: 50.5" 
                           required>
                </div>

                <div class="form-group">
                    <label for="harga_per_kwh">Harga per kWh (Rp) <span class="text-danger">*</span></label>
                    <input type="number" 
                           id="harga_per_kwh" 
                           name="harga_per_kwh" 
                           step="0.01"
                           min="0.01"
                           placeholder="Otomatis dari tarif stasiun" 
                           value="2500"
                           required>
                </div>

                <div class="form-group">
                    <label for="baterai_terpakai">Persentase Baterai Terpakai (%) <span class="text-danger">*</span></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="number" 
                               id="baterai_terpakai" 
                               name="baterai_terpakai" 
                               step="0.1"
                               min="0"
                               max="100"
                               placeholder="Contoh: 45.5" 
                               value="0"
                               required
                               style="flex: 1;">
                        <span style="font-weight: 700; color: var(--text); font-size: 1.2rem;" id="baterai_display">0%</span>
                    </div>
                    <small class="form-text text-muted">
                        <i class="fas fa-battery-half"></i> Persentase baterai kendaraan yang terpakai/diisi (0-100%)
                    </small>
                    <div style="margin-top: 10px;">
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <button type="button" class="btn-preset" data-value="25">25%</button>
                            <button type="button" class="btn-preset" data-value="50">50%</button>
                            <button type="button" class="btn-preset" data-value="75">75%</button>
                            <button type="button" class="btn-preset" data-value="100">100%</button>
                        </div>
                    </div>
                </div>

                <!-- Total Harga Display -->
                <div class="total-display">
                    <div class="label">💰 Total Harga Transaksi</div>
                    <div class="amount">
                        <span class="currency">Rp</span>
                        <span id="totalAmount">0</span>
                    </div>
                </div>

                <!-- Battery Info Display -->
                <div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border-radius: 15px; padding: 20px; color: white; text-align: center; margin: 20px 0; box-shadow: 0 8px 20px rgba(17, 153, 142, 0.3);">
                    <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 8px;">🔋 Persentase Baterai Terpakai</div>
                    <div style="font-size: 2.5rem; font-weight: 800; text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);">
                        <span id="baterai_info_display">0%</span>
                    </div>
                    <div style="font-size: 0.85rem; opacity: 0.8; margin-top: 5px;">
                        <span id="kwh_info_display">0 kWh</span> energi terisi
                    </div>
                </div>

                <input type="hidden" id="total_harga" name="total_harga" value="0" required>

                <div class="form-group">
                    <label for="tanggal_transaksi">Tanggal & Waktu Transaksi <span class="text-danger">*</span></label>
                    <input type="datetime-local" 
                           id="tanggal_transaksi" 
                           name="tanggal_transaksi" 
                           value="<?= date('Y-m-d\TH:i') ?>"
                           required>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-save me-2"></i>Simpan Transaksi
                </button>
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

// Toggle between existing and new pengendara
document.querySelectorAll('.toggle-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const target = this.dataset.target;
        document.getElementById('pengendara_option').value = target;
        
        document.querySelectorAll('.pengendara-section').forEach(section => {
            section.classList.remove('active');
        });
        document.getElementById('section-' + target).classList.add('active');
    });
});

// Auto calculate total
function calculateTotal() {
    const kwh = parseFloat(document.getElementById('jumlah_kwh').value) || 0;
    const harga = parseFloat(document.getElementById('harga_per_kwh').value) || 0;
    const total = kwh * harga;
    
    document.getElementById('totalAmount').textContent = 
        total.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    
    document.getElementById('total_harga').value = total.toFixed(2);
}

document.getElementById('jumlah_kwh').addEventListener('input', calculateTotal);
document.getElementById('harga_per_kwh').addEventListener('input', calculateTotal);

// Battery percentage display update
document.getElementById('baterai_terpakai').addEventListener('input', function() {
    const value = parseFloat(this.value) || 0;
    const clamped = Math.max(0, Math.min(100, value));
    const kwh = parseFloat(document.getElementById('jumlah_kwh').value) || 0;
    
    document.getElementById('baterai_display').textContent = clamped.toFixed(1) + '%';
    document.getElementById('baterai_info_display').textContent = clamped.toFixed(1) + '%';
    document.getElementById('kwh_info_display').textContent = kwh.toFixed(2) + ' kWh';
});

// Update battery info when kWh changes
document.getElementById('jumlah_kwh').addEventListener('input', function() {
    const kwh = parseFloat(this.value) || 0;
    document.getElementById('kwh_info_display').textContent = kwh.toFixed(2) + ' kWh';
});

// Battery preset buttons
document.querySelectorAll('.btn-preset').forEach(btn => {
    btn.addEventListener('click', function() {
        const value = this.dataset.value;
        const kwh = parseFloat(document.getElementById('jumlah_kwh').value) || 0;
        
        document.getElementById('baterai_terpakai').value = value;
        document.getElementById('baterai_display').textContent = value + '%';
        document.getElementById('baterai_info_display').textContent = value + '%';
        document.getElementById('kwh_info_display').textContent = kwh.toFixed(2) + ' kWh';
    });
});

// Auto-fill tarif when stasiun selected
document.getElementById('stasiun_id').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const tarif = parseFloat(selected.getAttribute('data-tarif')) || 2500;
    const stok = parseInt(selected.getAttribute('data-stok')) || 0;
    const detail = selected.getAttribute('data-detail') || 'Tidak ada stok';
    
    // Update harga per kwh
    document.getElementById('harga_per_kwh').value = tarif;
    
    // Update stok info
    const stokInfo = document.getElementById('stok-info');
    const stokWarning = document.getElementById('stok-warning');
    const warningText = document.getElementById('warning-text');
    
    if (selected.value) {
        stokInfo.innerHTML = '<i class="fas fa-battery-full"></i> Detail stok: ' + detail;
        
        if (stok === 0) {
            stokWarning.style.display = 'block';
            warningText.textContent = 'Stok baterai habis! Silakan isi ulang stok baterai di stasiun ini.';
        } else if (stok < 5) {
            stokWarning.style.display = 'block';
            warningText.textContent = 'Stok baterai hampir habis! Tersisa ' + stok + ' unit.';
        } else {
            stokWarning.style.display = 'none';
        }
    } else {
        stokInfo.innerHTML = '<i class="fas fa-battery-full"></i> Pilih stasiun untuk melihat detail stok baterai';
        stokWarning.style.display = 'none';
    }
    
    calculateTotal();
});

// Form Validation
document.getElementById('profitForm').addEventListener('submit', function(e) {
    const stasiun = document.getElementById('stasiun_id');
    const selected = stasiun.options[stasiun.selectedIndex];
    const stok = parseInt(selected.getAttribute('data-stok')) || 0;
    
    // Check stok baterai
    if (stok === 0) {
        e.preventDefault();
        alert('⚠️ Stok baterai di stasiun ini habis! Silakan isi ulang stok terlebih dahulu.');
        return;
    }
    
    const option = document.getElementById('pengendara_option').value;
    
    if (option === 'existing') {
        const pengendara = document.getElementById('id_pengendara').value;
        if (!pengendara) {
            e.preventDefault();
            alert('⚠️ Pilih pengendara dari daftar atau tambah pengendara baru!');
            return;
        }
    } else {
        const nama = document.getElementById('nama_pengendara').value.trim();
        const email = document.getElementById('email_pengendara').value.trim();
        const noplat = document.getElementById('no_plat').value.trim();
        
        if (!nama || !email || !noplat) {
            e.preventDefault();
            alert('⚠️ Nama, Email, dan Nomor Plat wajib diisi untuk pengendara baru!');
            return;
        }
    }
    
    const kwh = parseFloat(document.getElementById('jumlah_kwh').value);
    const total = parseFloat(document.getElementById('total_harga').value);
    const baterai = parseFloat(document.getElementById('baterai_terpakai').value);
    
    if (kwh <= 0 || total <= 0) {
        e.preventDefault();
        alert('⚠️ Data transaksi tidak valid!');
        return;
    }
    
    if (baterai < 0 || baterai > 100) {
        e.preventDefault();
        alert('⚠️ Persentase baterai harus antara 0-100%!');
        return;
    }
});

// Initial calculation
calculateTotal();
</script>
<script src="../js/clean-url.js?v=<?= time(); ?>"></script>
</body>
</html>