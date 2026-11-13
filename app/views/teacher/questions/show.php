<?php
require_once __DIR__ . '/../../layouts/header.php';
$q = $question;
?>

<div class="app-wrapper">
    <?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

    <div class="app-main">
        <?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

        <main class="app-content">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">Detail Butir Soal #<?= $q['id']; ?></h1>
                    <p class="text-muted mb-0 text-sm">Pratinjau butir soal, kunci jawaban, dan pembahasan kurikulum.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL; ?>/teacher/questions" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                    <a href="<?= BASE_URL; ?>/teacher/questionEdit/<?= $q['id']; ?>" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-pencil me-1"></i> Edit Soal
                    </a>
                </div>
            </div>

            <!-- Detail Card -->
            <div class="row g-4">
                <div class="col-12 col-lg-8">
                    <div class="custom-card p-4 mb-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-3 border-bottom">
                            <div>
                                <span class="badge bg-light text-dark border me-2"><?= htmlspecialchars($q['subject_name']); ?> (<?= htmlspecialchars($q['subject_code']); ?>)</span>
                                <?php if ($q['question_type'] === 'multiple_choice'): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Pilihan Ganda</span>
                                <?php else: ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">Essay / Uraian</span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge <?= $q['difficulty'] === 'easy' ? 'bg-success-subtle text-success' : ($q['difficulty'] === 'medium' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger'); ?>">
                                    Kesulitan: <?= ucfirst($q['difficulty']); ?>
                                </span>
                                <span class="badge bg-secondary-subtle text-dark">
                                    Bobot: <?= number_format((float)$q['default_score'], 0); ?> Poin
                                </span>
                            </div>
                        </div>

                        <!-- Question Narration -->
                        <div class="mb-4">
                            <h6 class="text-muted text-xs fw-semibold text-uppercase mb-2">Teks Pertanyaan:</h6>
                            <div class="p-3 rounded-3 bg-light border text-dark fs-6" style="white-space: pre-line;">
                                <?= htmlspecialchars($q['question_text']); ?>
                            </div>
                        </div>

                        <!-- Options Display (For Multiple Choice) -->
                        <?php if ($q['question_type'] === 'multiple_choice' && !empty($q['options'])): ?>
                            <div class="mb-4">
                                <h6 class="text-muted text-xs fw-semibold text-uppercase mb-2">Pilihan Jawaban:</h6>
                                <div class="d-flex flex-column gap-2">
                                    <?php foreach ($q['options'] as $opt): ?>
                                        <div class="p-3 rounded-3 border d-flex align-items-start gap-3 <?= !empty($opt['is_correct']) ? 'bg-success-subtle border-success' : 'bg-light'; ?>">
                                            <span class="badge <?= !empty($opt['is_correct']) ? 'bg-success text-white' : 'bg-secondary text-white'; ?> fs-6 rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                <?= htmlspecialchars($opt['option_label']); ?>
                                            </span>
                                            <div class="flex-grow-1 pt-1 text-sm <?= !empty($opt['is_correct']) ? 'fw-bold text-success' : 'text-dark'; ?>">
                                                <?= htmlspecialchars($opt['option_text']); ?>
                                                <?php if (!empty($opt['is_correct'])): ?>
                                                    <span class="badge bg-success text-white ms-2 text-xs"><i class="bi bi-check-circle-fill me-1"></i> Kunci Jawaban Benar</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Explanation / Reference -->
                        <?php if (!empty($q['explanation'])): ?>
                            <div class="mt-4 pt-3 border-top">
                                <h6 class="text-muted text-xs fw-semibold text-uppercase mb-2"><i class="bi bi-lightbulb text-warning me-1"></i> Pembahasan / Kunci Referensi:</h6>
                                <div class="p-3 rounded-3 bg-warning-subtle border border-warning-subtle text-sm text-dark" style="white-space: pre-line;">
                                    <?= htmlspecialchars($q['explanation']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="custom-card p-4">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle text-primary me-1"></i> Informasi Meta Soal</h6>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2 text-sm">
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">ID Soal:</span>
                                <strong>#<?= $q['id']; ?></strong>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Mata Pelajaran:</span>
                                <span><?= htmlspecialchars($q['subject_name']); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Tipe Soal:</span>
                                <span class="text-capitalize"><?= $q['question_type'] === 'multiple_choice' ? 'Pilihan Ganda' : 'Essay'; ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Dibuat Pada:</span>
                                <span><?= date('d M Y, H:i', strtotime($q['created_at'])); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-1">
                                <span class="text-muted">Terakhir Diupdate:</span>
                                <span><?= !empty($q['updated_at']) ? date('d M Y, H:i', strtotime($q['updated_at'])) : '-'; ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>

        <?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
    </div>
</div>
