<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="app-wrapper">
    <!-- Sidebar Navigation -->
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="app-main">
        <!-- Top Navbar -->
        <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

        <!-- Content Area -->
        <main class="app-content">
            <!-- Flash Message -->
            <?php if (!empty($flash_message)): ?>
                <div class="alert alert-<?= htmlspecialchars($flash_message['type']); ?> alert-dismissible fade show text-sm py-2 px-3 mb-4 rounded-3" role="alert">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    <?= htmlspecialchars($flash_message['message']); ?>
                    <button type="button" class="btn-close py-2 px-3" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h1 class="h3 fw-bold text-dark mb-0">Pusat Notifikasi</h1>
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-danger rounded-pill px-2 py-1 text-xs"><?= $unread_count; ?> Belum Dibaca</span>
                        <?php else: ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 text-xs">Semua Terbaca</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted mb-0 text-sm">Pemberitahuan aktivitas ujian, perubahan jadwal, dan informasi sistem resmi akun Anda.</p>
                </div>

                <div class="d-flex gap-2">
                    <?php if ($unread_count > 0): ?>
                        <form action="<?= BASE_URL; ?>/notification/markAllAsRead" method="POST" class="d-inline">
                            <?= Csrf::tokenField(); ?>
                            <button type="submit" class="btn btn-outline-primary btn-sm px-3 shadow-sm d-flex align-items-center gap-2">
                                <i class="bi bi-check2-all fs-6"></i>
                                <span>Tandai Semua Dibaca</span>
                            </button>
                        </form>
                    <?php endif; ?>
                    <button class="btn btn-light btn-sm px-3 border shadow-sm" type="button" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i> Muat Ulang
                    </button>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-2 d-flex gap-2 flex-wrap align-items-center">
                    <a href="<?= BASE_URL; ?>/notification?filter=all" class="btn btn-sm <?= ($current_filter === 'all') ? 'btn-primary shadow-sm' : 'btn-light text-muted'; ?> rounded-pill px-3 fw-medium">
                        <i class="bi bi-inbox me-1"></i> Semua Notifikasi
                    </a>
                    <a href="<?= BASE_URL; ?>/notification?filter=unread" class="btn btn-sm <?= ($current_filter === 'unread') ? 'btn-primary shadow-sm' : 'btn-light text-muted'; ?> rounded-pill px-3 fw-medium">
                        <i class="bi bi-envelope-exclamation me-1"></i> Belum Dibaca (<?= $unread_count; ?>)
                    </a>
                </div>
            </div>

            <!-- Notification List -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <?php if (empty($notifications)): ?>
                    <div class="card-body text-center py-5">
                        <div class="d-inline-flex p-3 rounded-circle bg-light text-muted mb-3">
                            <i class="bi bi-bell-slash fs-1"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Tidak Ada Notifikasi</h5>
                        <p class="text-muted text-sm max-w-md mx-auto mb-3">
                            <?= ($current_filter === 'unread') ? 'Bagus! Anda tidak memiliki notifikasi baru yang belum dibaca.' : 'Belum ada riwayat notifikasi untuk akun Anda saat ini.'; ?>
                        </p>
                        <?php if ($current_filter === 'unread'): ?>
                            <a href="<?= BASE_URL; ?>/notification?filter=all" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                Lihat Semua Riwayat
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $n): ?>
                            <?php
                            $isRead = (bool)$n['is_read'];
                            $typeClass = [
                                'success' => 'border-start-success bg-success-subtle text-success',
                                'warning' => 'border-start-warning bg-warning-subtle text-warning',
                                'danger' => 'border-start-danger bg-danger-subtle text-danger',
                                'info' => 'border-start-primary bg-primary-subtle text-primary'
                            ][$n['type']] ?? 'border-start-primary bg-primary-subtle text-primary';

                            $iconClass = [
                                'exam_published' => 'bi-file-earmark-plus',
                                'exam_schedule' => 'bi-calendar-event',
                                'exam_cancel' => 'bi-x-octagon',
                                'exam_result' => 'bi-trophy',
                                'exam_reminder' => 'bi-alarm',
                                'system' => 'bi-gear-wide-connected'
                            ][$n['category']] ?? 'bi-bell';

                            $catLabels = [
                                'exam_published' => 'Ujian Diterbitkan',
                                'exam_schedule' => 'Jadwal Ujian',
                                'exam_cancel' => 'Pembatalan Ujian',
                                'exam_result' => 'Hasil Ujian',
                                'exam_reminder' => 'Pengingat Ujian',
                                'system' => 'Sistem'
                            ];
                            $catLabel = $catLabels[$n['category']] ?? ucfirst($n['category']);
                            ?>
                            <div class="list-group-item p-3 p-md-4 transition-all <?= !$isRead ? 'bg-light-subtle border-start border-4 border-primary' : ''; ?>" style="border-bottom: 1px solid #f1f5f9;">
                                <div class="d-flex align-items-start gap-3">
                                    <!-- Category Icon -->
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 44px; height: 44px; background: <?= !$isRead ? '#eff6ff' : '#f8fafc'; ?>; border: 1px solid <?= !$isRead ? '#bfdbfe' : '#e2e8f0'; ?>;">
                                        <i class="bi <?= $iconClass; ?> fs-5 <?= !$isRead ? 'text-primary' : 'text-muted'; ?>"></i>
                                    </div>

                                    <!-- Content Body -->
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-1 mb-1">
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <h6 class="fw-bold mb-0 text-dark <?= !$isRead ? 'text-primary' : ''; ?>">
                                                    <?= htmlspecialchars($n['title']); ?>
                                                </h6>
                                                <span class="badge bg-light text-secondary border text-xs"><?= $catLabel; ?></span>
                                                <?php if (!$isRead): ?>
                                                    <span class="badge bg-primary text-xs">Baru</span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="text-xs text-muted flex-shrink-0">
                                                <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($n['created_at'])); ?> WIB
                                            </span>
                                        </div>

                                        <p class="text-muted text-sm mb-2" style="line-height: 1.5;">
                                            <?= nl2br(htmlspecialchars($n['message'])); ?>
                                        </p>

                                        <!-- Action Links & Marking -->
                                        <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
                                            <?php if (!empty($n['link_url'])): ?>
                                                <a href="<?= (strpos($n['link_url'], 'http') === 0) ? htmlspecialchars($n['link_url']) : BASE_URL . '/' . ltrim(htmlspecialchars($n['link_url']), '/'); ?>" class="btn btn-primary btn-sm rounded-pill px-3 py-1 text-xs d-flex align-items-center gap-1 shadow-sm">
                                                    <span>Buka Halaman Terkait</span>
                                                    <i class="bi bi-arrow-right text-xs"></i>
                                                </a>
                                            <?php endif; ?>

                                            <?php if (!$isRead): ?>
                                                <form action="<?= BASE_URL; ?>/notification/markAsRead/<?= $n['id']; ?>" method="POST" class="d-inline">
                                                    <?= Csrf::tokenField(); ?>
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 text-xs">
                                                        <i class="bi bi-check2 me-1"></i> Tandai Dibaca
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <form action="<?= BASE_URL; ?>/notification/delete/<?= $n['id']; ?>" method="POST" class="d-inline ms-auto" onsubmit="return confirm('Hapus notifikasi ini?');">
                                                <?= Csrf::tokenField(); ?>
                                                <button type="submit" class="btn btn-link text-danger p-0 text-xs text-decoration-none">
                                                    <i class="bi bi-trash me-1"></i> Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- Footer -->
        <?php require_once __DIR__ . '/../layouts/footer.php'; ?>
    </div>
</div>
