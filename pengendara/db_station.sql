-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 09 Nov 2025 pada 14.16
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_station`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_admin` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `password`, `nama_admin`, `email`, `created_at`) VALUES
(1, 'admin', 'admin123', 'Administrator', 'admin@estation.com', '2025-10-17 23:10:34');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal_maintenance`
--

CREATE TABLE `jadwal_maintenance` (
  `id_maintenance` int(11) NOT NULL,
  `id_admin` int(11) DEFAULT NULL,
  `id_stasiun` int(11) DEFAULT NULL,
  `tanggal_mulai` datetime DEFAULT NULL,
  `tanggal_selesai` datetime DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `status` enum('terjadwal','berlangsung','selesai') DEFAULT 'terjadwal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kendaraan`
--

CREATE TABLE `kendaraan` (
  `id_kendaraan` int(11) NOT NULL,
  `id_pengendara` int(11) NOT NULL,
  `merk` varchar(100) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `no_plat` varchar(20) DEFAULT NULL,
  `tahun` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `laporan`
--

CREATE TABLE `laporan` (
  `id_laporan` int(11) NOT NULL,
  `id_admin` int(11) DEFAULT NULL,
  `tipe_laporan` enum('keuangan','operasional','transaksi') NOT NULL,
  `periode_awal` date DEFAULT NULL,
  `periode_akhir` date DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `login_logs`
--

CREATE TABLE `login_logs` (
  `id_log` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `tipe_user` enum('pengendara','mitra','admin') NOT NULL,
  `aksi` enum('login','logout') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_admin`
--

CREATE TABLE `log_admin` (
  `id_log` int(11) NOT NULL,
  `id_admin` int(11) DEFAULT NULL,
  `aktivitas` text NOT NULL,
  `waktu` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `mitra`
--

CREATE TABLE `mitra` (
  `id_mitra` int(11) NOT NULL,
  `nama_mitra` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `status` enum('pending','disetujui','ditolak') DEFAULT 'pending',
  `verifikasi_token` varchar(255) DEFAULT NULL,
  `email_terverifikasi` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notifikasi` int(11) NOT NULL,
  `id_penerima` int(11) DEFAULT NULL,
  `tipe_penerima` enum('pengendara','mitra') DEFAULT NULL,
  `judul` varchar(150) DEFAULT NULL,
  `pesan` text DEFAULT NULL,
  `status_baca` tinyint(1) DEFAULT 0,
  `dikirim_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengendara`
--

CREATE TABLE `pengendara` (
  `id_pengendara` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `status_akun` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengendara`
--

INSERT INTO `pengendara` (`id_pengendara`, `nama`, `email`, `password`, `no_telepon`, `alamat`, `status_akun`, `created_at`) VALUES
(1, 'zikri', 'muhammadzikrialfadani01@gmail.com', 'Inc_1019', '082283405194', 'kudus', 'aktif', '2025-11-08 23:06:59');

-- --------------------------------------------------------

--
-- Struktur dari tabel `promo`
--

CREATE TABLE `promo` (
  `id_promo` int(11) NOT NULL,
  `nama_promo` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `nilai_diskon` decimal(5,2) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'nonaktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `saldo_pengendara`
--

CREATE TABLE `saldo_pengendara` (
  `id_saldo` int(11) NOT NULL,
  `id_pengendara` int(11) NOT NULL,
  `saldo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `poin` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `stasiun_pengisian`
--

CREATE TABLE `stasiun_pengisian` (
  `id_stasiun` int(11) NOT NULL,
  `id_mitra` int(11) NOT NULL,
  `nama_stasiun` varchar(100) NOT NULL,
  `alamat` text NOT NULL,
  `kapasitas` int(11) NOT NULL,
  `status_operasional` enum('aktif','nonaktif','maintenance') DEFAULT 'nonaktif',
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `jam_operasional` varchar(100) DEFAULT '24 Jam',
  `nomor_kontak` varchar(20) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `stok_baterai`
--

CREATE TABLE `stok_baterai` (
  `id_stok` int(11) NOT NULL,
  `id_stasiun` int(11) NOT NULL,
  `tipe_baterai` varchar(50) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `id_pengendara` int(11) NOT NULL,
  `id_stasiun` int(11) NOT NULL,
  `tanggal_transaksi` datetime DEFAULT current_timestamp(),
  `jumlah_kwh` decimal(10,2) NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `status_transaksi` enum('berhasil','pending','gagal') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `jadwal_maintenance`
--
ALTER TABLE `jadwal_maintenance`
  ADD PRIMARY KEY (`id_maintenance`),
  ADD KEY `id_admin` (`id_admin`),
  ADD KEY `id_stasiun` (`id_stasiun`);

--
-- Indeks untuk tabel `kendaraan`
--
ALTER TABLE `kendaraan`
  ADD PRIMARY KEY (`id_kendaraan`),
  ADD UNIQUE KEY `no_plat` (`no_plat`),
  ADD KEY `id_pengendara` (`id_pengendara`);

--
-- Indeks untuk tabel `laporan`
--
ALTER TABLE `laporan`
  ADD PRIMARY KEY (`id_laporan`),
  ADD KEY `id_admin` (`id_admin`);

--
-- Indeks untuk tabel `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id_log`);

--
-- Indeks untuk tabel `log_admin`
--
ALTER TABLE `log_admin`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_admin` (`id_admin`);

--
-- Indeks untuk tabel `mitra`
--
ALTER TABLE `mitra`
  ADD PRIMARY KEY (`id_mitra`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notifikasi`);

--
-- Indeks untuk tabel `pengendara`
--
ALTER TABLE `pengendara`
  ADD PRIMARY KEY (`id_pengendara`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `promo`
--
ALTER TABLE `promo`
  ADD PRIMARY KEY (`id_promo`);

--
-- Indeks untuk tabel `saldo_pengendara`
--
ALTER TABLE `saldo_pengendara`
  ADD PRIMARY KEY (`id_saldo`),
  ADD UNIQUE KEY `id_pengendara` (`id_pengendara`);

--
-- Indeks untuk tabel `stasiun_pengisian`
--
ALTER TABLE `stasiun_pengisian`
  ADD PRIMARY KEY (`id_stasiun`),
  ADD KEY `id_mitra` (`id_mitra`);

--
-- Indeks untuk tabel `stok_baterai`
--
ALTER TABLE `stok_baterai`
  ADD PRIMARY KEY (`id_stok`),
  ADD KEY `id_stasiun` (`id_stasiun`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_pengendara` (`id_pengendara`),
  ADD KEY `id_stasiun` (`id_stasiun`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `jadwal_maintenance`
--
ALTER TABLE `jadwal_maintenance`
  MODIFY `id_maintenance` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kendaraan`
--
ALTER TABLE `kendaraan`
  MODIFY `id_kendaraan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `laporan`
--
ALTER TABLE `laporan`
  MODIFY `id_laporan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `log_admin`
--
ALTER TABLE `log_admin`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `mitra`
--
ALTER TABLE `mitra`
  MODIFY `id_mitra` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pengendara`
--
ALTER TABLE `pengendara`
  MODIFY `id_pengendara` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `promo`
--
ALTER TABLE `promo`
  MODIFY `id_promo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `saldo_pengendara`
--
ALTER TABLE `saldo_pengendara`
  MODIFY `id_saldo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `stasiun_pengisian`
--
ALTER TABLE `stasiun_pengisian`
  MODIFY `id_stasiun` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `stok_baterai`
--
ALTER TABLE `stok_baterai`
  MODIFY `id_stok` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `jadwal_maintenance`
--
ALTER TABLE `jadwal_maintenance`
  ADD CONSTRAINT `jadwal_maintenance_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`),
  ADD CONSTRAINT `jadwal_maintenance_ibfk_2` FOREIGN KEY (`id_stasiun`) REFERENCES `stasiun_pengisian` (`id_stasiun`);

--
-- Ketidakleluasaan untuk tabel `kendaraan`
--
ALTER TABLE `kendaraan`
  ADD CONSTRAINT `kendaraan_ibfk_1` FOREIGN KEY (`id_pengendara`) REFERENCES `pengendara` (`id_pengendara`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `laporan`
--
ALTER TABLE `laporan`
  ADD CONSTRAINT `laporan_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`);

--
-- Ketidakleluasaan untuk tabel `log_admin`
--
ALTER TABLE `log_admin`
  ADD CONSTRAINT `log_admin_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`);

--
-- Ketidakleluasaan untuk tabel `saldo_pengendara`
--
ALTER TABLE `saldo_pengendara`
  ADD CONSTRAINT `saldo_pengendara_ibfk_1` FOREIGN KEY (`id_pengendara`) REFERENCES `pengendara` (`id_pengendara`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `stasiun_pengisian`
--
ALTER TABLE `stasiun_pengisian`
  ADD CONSTRAINT `stasiun_pengisian_ibfk_1` FOREIGN KEY (`id_mitra`) REFERENCES `mitra` (`id_mitra`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `stok_baterai`
--
ALTER TABLE `stok_baterai`
  ADD CONSTRAINT `stok_baterai_ibfk_1` FOREIGN KEY (`id_stasiun`) REFERENCES `stasiun_pengisian` (`id_stasiun`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_pengendara`) REFERENCES `pengendara` (`id_pengendara`),
  ADD CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`id_stasiun`) REFERENCES `stasiun_pengisian` (`id_stasiun`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
