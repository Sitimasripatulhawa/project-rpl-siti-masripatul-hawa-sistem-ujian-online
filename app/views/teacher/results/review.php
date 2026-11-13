<?php
require_once __DIR__ . '/../../layouts/header.php';
$isPassed = !empty($header['is_passed']);
$finalScore = (float)($header['final_score'] ?? 0);
?>

<div class="app-wrapper">
    <!-- Sidebar Navigation -->
    <?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="app-main">
        <!-- Top Navbar -->
        <?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

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

            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb text-sm mb-0">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>/teacher/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>/teacher/results">Hasil Ujian</a></li>
                    <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>/teacher/examResultDetail/<?= $header['exam_id']; ?>"><?= htmlspecialchars($header['exam_title']); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Review Jawaban: <?= htmlspecialchars($header['student_name']); ?></li>
                </ol>
            </nav>

            <!-- Student & Exam Header Info Card -->
            <div class="custom-card p-4 mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h1 class="h4 fw-bold text-dark mb-0"><?= htmlspecialchars($header['student_name']); ?></h1>
                            <span class="badge bg-primary-subtle text-primary text-xs">NIS: <?= htmlspecialchars($header['nis']); ?></span>
                            <span class="badge bg-light text-secondary border text-xs"><?= htmlspecialchars($header['class_name']); ?></span>
                        </div>
                        <p class="text-muted text-sm mb-0">
                            Ujian: <strong><?= htmlspecialchars($header['exam_title']); ?></strong> (<?= htmlspecialchars($header['subject_name']); ?>) | 
                            Submit: <?= !empty($header['participant_submit_time']) ? date('d F Y, H:i', strtotime($header['participant_submit_time'])) : '-'; ?> WIB
                        </p>
                    </div>

                    <!-- Final Score Badge -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-end">
                            <div class="text-xs text-muted">Nilai Akhir Peserta</div>
                            <div class="h3 fw-bold mb-0 <?= $isPassed ? 'text-success' : 'text-danger'; ?>" id="headerFinalScore">
                                <?= number_format($finalScore, 1); ?> <small class="text-muted fs-6 fw-normal">/ <?= number_format((float)$header['total_score'], 1); ?></small>
                            </div>
                            <span class="badge <?= $isPassed ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?> text-xs" id="headerPassBadge">
                                <?= $isPassed ? 'LULUS KKM' : 'BELUM LULUS'; ?> (KKM: <?= number_format((float)$header['passing_score'], 1); ?>)
                            </span>
                        </div>
                        <a href="<?= BASE_URL; ?>/teacher/examResultDetail/<?= $header['exam_id']; ?>" class="btn btn-outline-secondary btn-sm px-3">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>

            <!-- AI Proctoring Surveillance Log Card -->
            <?php 
            $proctoringEvents = $proctoring_events ?? [];
            $proctoringSummary = $proctoring_summary ?? ['total_events' => 0, 'warning_count' => 0, 'critical_count' => 0, 'risk_score' => 0, 'risk_level' => 'low'];
            $riskBadgeMap = [
                'low' => ['bg' => 'bg-success-subtle text-success border border-success-subtle', 'text' => 'Indikasi Risiko Rendah'],
                'medium' => ['bg' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'text' => 'Indikasi Risiko Sedang'],
                'high' => ['bg' => 'bg-danger-subtle text-danger border border-danger-subtle', 'text' => 'Indikasi Risiko Tinggi']
            ];
            $riskInfo = $riskBadgeMap[$proctoringSummary['risk_level']] ?? $riskBadgeMap['low'];
            ?>
            <div class="custom-card p-4 mb-4" id="proctoringLogSection">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-shield-shaded text-primary fs-5"></i>
                        <h2 class="h6 fw-bold text-dark mb-0">Log Pengawasan AI Proctoring</h2>
                        <span class="badge <?= $riskInfo['bg']; ?> text-xs">
                            <i class="bi bi-activity me-1"></i> <?= $riskInfo['text']; ?>
                        </span>
                    </div>
                    <div class="text-xs text-muted">
                        Total Catatan Event: <strong><?= count($proctoringEvents); ?></strong> 
                        (Peringatan: <strong><?= $proctoringSummary['warning_count']; ?></strong>, Kritis: <strong><?= $proctoringSummary['critical_count']; ?></strong>)
                    </div>
                </div>

                <?php if (empty($proctoringEvents)): ?>
                    <div class="alert alert-success text-xs py-2 px-3 rounded-3 mb-0">
                        <i class="bi bi-check-circle-fill me-1"></i> Tidak ada catatan anomali atau aktivitas mencurigakan yang terdeteksi selama sesi pengerjaan ujian.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 text-xs">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 130px;">Waktu</th>
                                    <th>Jenis Event</th>
                                    <th style="width: 90px;">Severity</th>
                                    <th>Kamera</th>
                                    <th>Detail Indikasi</th>
                                    <th style="width: 100px;">Bukti Snapshot</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($proctoringEvents as $ev): 
                                    $sev = strtoupper($ev['severity'] ?? 'WARNING');
                                    $sevBadge = 'bg-warning text-dark';
                                    if ($sev === 'CRITICAL') $sevBadge = 'bg-danger text-white';
                                    if ($sev === 'INFO') $sevBadge = 'bg-info text-dark';

                                    $meta = !empty($ev['metadata_json']) ? json_decode($ev['metadata_json'], true) : [];
                                    $metaStr = '';
                                    if ($meta) {
                                        $parts = [];
                                        foreach ($meta as $k => $v) {
                                            $parts[] = "$k: " . (is_array($v) ? json_encode($v) : $v);
                                        }
                                        $metaStr = implode(', ', $parts);
                                    }
                                ?>
                                    <tr>
                                        <td><?= date('H:i:s', strtotime($ev['event_time'])); ?> <span class="text-muted">(<?= date('d/m', strtotime($ev['event_time'])); ?>)</span></td>
                                        <td>
                                            <span class="fw-bold"><?= htmlspecialchars($ev['event_type']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $sevBadge; ?>"><?= htmlspecialchars($sev); ?></span>
                                        </td>
                                        <td class="text-muted"><?= htmlspecialchars($ev['camera_source']); ?></td>
                                        <td class="text-muted font-monospace" style="font-size: 0.72rem;">
                                            <?= $metaStr ? htmlspecialchars($metaStr) : '-'; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($ev['snapshot_path'])): ?>
                                                <a href="<?= BASE_URL . '/' . htmlspecialchars($ev['snapshot_path']); ?>" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-1" style="font-size: 0.7rem;">
                                                    <i class="bi bi-image me-1"></i>Lihat Foto
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Questions and Answers Review List -->
            <div class="d-flex flex-column gap-4 mb-4">
                <?php $qNum = 1; foreach ($questions as $q): 
                    $ans = $q['answer'];
                    $qType = $q['question_type'];
                    $maxScore = (float)$q['question_score'];
                    $awarded = $ans ? (float)$ans['awarded_score'] : 0.0;
                    $isCorrect = $ans ? $ans['is_correct'] : 0;
                    $isDoubt = $ans && (int)$ans['is_flagged_doubt'] === 1;
                ?>
                    <div class="custom-card p-4">
                        <!-- Question Header -->
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3 gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-dark fs-6">Soal No. <?= $qNum++; ?></span>
                                <span class="badge bg-light text-secondary border text-xs">
                                    <?= ($qType === 'multiple_choice') ? 'Pilihan Ganda' : 'Essay'; ?>
                                </span>
                                <?php if ($isDoubt): ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis text-xs">
                                        <i class="bi bi-flag-fill me-1"></i> Ditandai Ragu oleh Siswa
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if ($qType === 'multiple_choice'): ?>
                                    <?php if ($ans && (int)$ans['is_correct'] === 1): ?>
                                        <span class="badge bg-success text-white py-1 px-2 text-xs">
                                            <i class="bi bi-check-lg me-1"></i> Benar (+<?= number_format($maxScore, 1); ?> Poin)
                                        </span>
                                    <?php elseif (!$q['is_answered']): ?>
                                        <span class="badge bg-secondary text-white py-1 px-2 text-xs">
                                            <i class="bi bi-dash-circle me-1"></i> Tidak Dijawab (0.0 Poin)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger text-white py-1 px-2 text-xs">
                                            <i class="bi bi-x-lg me-1"></i> Salah (0.0 Poin)
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- Essay Status -->
                                    <?php if ($q['essay_status'] === 'pending'): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis py-1 px-2 text-xs">
                                            <i class="bi bi-clock-history me-1"></i> Menunggu Penilaian (Bobot: <?= number_format($maxScore, 1); ?> Poin)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success py-1 px-2 text-xs">
                                            <i class="bi bi-check2-circle me-1"></i> Sudah Dinilai: <?= number_format($awarded, 1); ?> / <?= number_format($maxScore, 1); ?> Poin
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Question Text -->
                        <div class="text-dark fs-6 mb-3 leading-relaxed">
                            <?= $q['question_text']; ?>
                        </div>

                        <!-- Options for Multiple Choice -->
                        <?php if ($qType === 'multiple_choice'): ?>
                            <div class="d-flex flex-column gap-2 mb-3">
                                <?php foreach ($q['options'] as $opt): 
                                    $isCorrectOpt = ((int)$opt['is_correct'] === 1);
                                    $isSelectedOpt = ((int)$opt['id'] === $q['selected_option_id']);
                                    
                                    $cardClass = 'border-light bg-light';
                                    if ($isCorrectOpt && $isSelectedOpt) {
                                        $cardClass = 'border-success bg-success-subtle';
                                    } elseif ($isCorrectOpt) {
                                        $cardClass = 'border-success bg-white';
                                    } elseif ($isSelectedOpt) {
                                        $cardClass = 'border-danger bg-danger-subtle';
                                    }
                                ?>
                                    <div class="p-3 border rounded-3 d-flex align-items-start gap-3 <?= $cardClass; ?>">
                                        <span class="badge <?= $isCorrectOpt ? 'bg-success text-white' : ($isSelectedOpt ? 'bg-danger text-white' : 'bg-secondary-subtle text-dark'); ?>">
                                            <?= htmlspecialchars($opt['option_label']); ?>
                                        </span>
                                        <div class="flex-grow-1 text-sm text-dark">
                                            <?= htmlspecialchars($opt['option_text']); ?>
                                        </div>
                                        <div>
                                            <?php if ($isSelectedOpt): ?>
                                                <span class="badge bg-primary text-xs me-1">Jawaban Siswa</span>
                                            <?php endif; ?>
                                            <?php if ($isCorrectOpt): ?>
                                                <span class="badge bg-success text-xs"><i class="bi bi-check-circle-fill me-1"></i> Kunci Benar</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (!empty($q['explanation'])): ?>
                                <div class="p-3 bg-light rounded-3 text-xs text-muted">
                                    <strong><i class="bi bi-lightbulb text-warning me-1"></i> Pembahasan:</strong> <?= htmlspecialchars($q['explanation']); ?>
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <!-- Essay Answer Review & Manual Grading Form -->
                            <div class="mb-3">
                                <label class="text-xs fw-bold text-muted text-uppercase mb-1">Jawaban Siswa:</label>
                                <div class="p-3 bg-light rounded-3 border text-sm text-dark font-monospace mb-3" style="white-space: pre-wrap; min-height: 80px;">
                                    <?= !empty($ans['essay_answer']) ? htmlspecialchars($ans['essay_answer']) : '<span class="text-muted fst-italic">Peserta tidak mengisi jawaban pada butir soal ini.</span>'; ?>
                                </div>

                                <!-- Manual Grading Form -->
                                <div class="p-3 border rounded-3 bg-white shadow-xs">
                                    <form action="<?= BASE_URL; ?>/teacher/saveEssayGrade" method="POST" class="row g-2 align-items-center">
                                        <?= Csrf::tokenField(); ?>
                                        <input type="hidden" name="participant_id" value="<?= $header['participant_id']; ?>">
                                        <input type="hidden" name="question_id" value="<?= $q['question_id']; ?>">

                                        <div class="col-12 col-sm-auto">
                                            <label class="form-label text-xs fw-bold text-dark mb-0">
                                                <i class="bi bi-pencil-square text-primary me-1"></i> Nilai Manual (Max: <?= number_format($maxScore, 1); ?> Poin):
                                            </label>
                                        </div>
                                        <div class="col-6 col-sm-3 col-md-2">
                                            <input type="number" 
                                                   class="form-control form-control-sm text-center fw-bold" 
                                                   name="score" 
                                                   value="<?= number_format($awarded, 1); ?>" 
                                                   min="0" 
                                                   max="<?= $maxScore; ?>" 
                                                   step="0.5" 
                                                   required>
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">
                                                <i class="bi bi-save me-1"></i> Simpan Nilai
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>

        <!-- Footer -->
        <?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
    </div>
</div>
