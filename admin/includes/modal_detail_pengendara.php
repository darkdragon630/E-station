<div class="modal fade" id="detailModal<?php echo $pengendara['id_pengendara']; ?>" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="fas fa-user-circle me-2"></i>Detail Pengendara
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="detail-item">
                    <div class="detail-label">Nama</div>
                    <div class="detail-value"><?php echo htmlspecialchars($pengendara['nama']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Email</div>
                    <div class="detail-value"><?php echo htmlspecialchars($pengendara['email']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">No. Telepon</div>
                    <div class="detail-value"><?php echo htmlspecialchars($pengendara['no_telepon'] ?? '-'); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Alamat</div>
                    <div class="detail-value"><?php echo htmlspecialchars($pengendara['alamat'] ?? '-'); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Status Akun</div>
                    <div class="detail-value">
                        <span class="badge bg-<?php echo getStatusBadgeClass($pengendara['status_akun']); ?>">
                            <?php echo ucfirst($pengendara['status_akun']); ?>
                        </span>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Tanggal Daftar</div>
                    <div class="detail-value"><?php echo formatTanggal($pengendara['created_at'], 'd F Y H:i'); ?></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>