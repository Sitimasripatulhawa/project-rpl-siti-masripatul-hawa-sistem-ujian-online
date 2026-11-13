<?php
require_once __DIR__ . '/../layouts/header.php';
$isPassed = !empty($result['is_passed']);
$finalScore = (float)($result['final_score'] ?? 0);
$passingScore = (float)($result['passing_score'] ?? 75);
$totalQuestions = (int)($result['total_questions'] ?? 0);
$totalCorrect = (int)($result['total_correct'] ?? 0);
$totalIncorrect = (int)($result['total_incorrect'] ?? 0);
$totalUnanswered = (int)($result['total_unanswered'] ?? 0);
$hasEssay = !empty($result['total_essay_questions']) && (int)$result['total_essay_questions'] > 0;
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

            <div class="max-w-700 mx-auto" style="max-width: 800px;">
                <?php if (isset($show_result) && $show_result === 0): ?>
                    <!-- Restricted Result Notice -->
                    <div class="custom-card p-5 text-center mb-4">
                        <div class="stat-icon stat-icon-amber mx-auto mb-3" style="width: 64px; height: 64px; font-size: 2rem;">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <h2 class="h4 fw-bold text-dark mb-2">Hasil Ujian Belum Dipublikasikan</h2>
                        <p class="text-muted text-sm mb-4">
                            Ujian <strong><?= htmlspecialchars($exam['title']); ?></strong> telah berhasil Anda kumpulkan. Namun, pengumuman nilai dan evaluasi hasil ujian belum dibuka oleh guru penguji.
                        </p>
                        <div class="p-3 bg-light rounded-3 text-xs text-muted mb-4 d-inline-block text-start">
                            <div><i class="bi bi-clock me-1"></i> Dikumpulkan pada: <strong><?= !empty($exam['participant_submit_time']) ? date('d F Y, H:i', strtotime($exam['participant_submit_time'])) : '-'; ?> WIB</strong></div>
                            <div class="mt-1"><i class="bi bi-info-circle me-1"></i> Silakan pantau informasi lebih lanjut dari guru pengampu Anda.</div>
                        </div>
                        <div>
                            <a href="<?= BASE_URL; ?>/student/exams" class="btn btn-primary btn-sm px-4">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Ujian
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Result Card -->
                    <div class="custom-card p-4 p-md-5 mb-4">
                        <div class="text-center mb-4">
                            <div class="stat-icon <?= $isPassed ? 'stat-icon-emerald' : 'stat-icon-amber'; ?> mx-auto mb-3" style="width: 64px; height: 64px; font-size: 2rem;">
                                <i class="bi <?= $isPassed ? 'bi-trophy-fill' : 'bi-award'; ?>"></i>
                            </div>
                            <h1 class="h3 fw-bold text-dark mb-1">Hasil Ujian Anda</h1>
                            <p class="text-muted text-sm mb-0">Ujian: <strong><?= htmlspecialchars($result['title']); ?></strong></p>
                            <span class="badge bg-primary-subtle text-primary text-xs mt-1"><?= htmlspecialchars($result['subject_name']); ?></span>
                        </div>

                    <!-- Score Highlight Banner -->
                    <div class="p-4 rounded-4 mb-4 text-center <?= $isPassed ? 'bg-success-subtle border border-success-subtle' : 'bg-warning-subtle border border-warning-subtle'; ?>">
                        <span class="text-xs fw-bold text-uppercase <?= $isPassed ? 'text-success' : 'text-warning'; ?> letter-spacing-1">
                            <?= $isPassed ? 'SELAMAT, ANDA LULUS' : 'BELUM MENCAPAI KKM'; ?>
                        </span>
                        <div class="display-3 fw-bold my-1 <?= $isPassed ? 'text-success' : 'text-dark'; ?>">
                            <?= number_format($finalScore, 1); ?>
                        </div>
                        <div class="text-xs text-muted">
                            Passing Grade (KKM): <strong><?= number_format($passingScore, 1); ?> Poin</strong>
                        </div>
                    </div>

                    <?php if ($hasEssay): ?>
                        <div class="alert alert-info border-0 d-flex align-items-center gap-3 rounded-3 mb-4 text-sm">
                            <i class="bi bi-info-circle-fill fs-4 text-info"></i>
                            <div>
                                <strong>Catatan Penilaian Soal Essay:</strong>
                                <p class="mb-0 text-xs mt-1">Ujian ini mencakup soal essay yang sedang dalam proses peninjauan dan penilaian manual oleh guru penguji. Nilai akhir Anda akan diperbarui setelah guru selesai memeriksa.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Detailed Breakdown Cards -->
                    <h5 class="fw-bold text-dark mb-3">Ringkasan Pengerjaan</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-sm-3">
                            <div class="p-3 bg-light rounded-3 text-center border">
                                <span class="text-muted text-xs d-block mb-1">Total Soal</span>
                                <h4 class="fw-bold text-dark mb-0"><?= $totalQuestions; ?></h4>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="p-3 bg-success-subtle rounded-3 text-center border border-success-subtle">
                                <span class="text-success text-xs fw-semibold d-block mb-1"><i class="bi bi-check-circle me-1"></i> Benar</span>
                                <h4 class="fw-bold text-success mb-0"><?= $totalCorrect; ?></h4>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="p-3 bg-danger-subtle rounded-3 text-center border border-danger-subtle">
                                <span class="text-danger text-xs fw-semibold d-block mb-1"><i class="bi bi-x-circle me-1"></i> Salah</span>
                                <h4 class="fw-bold text-danger mb-0"><?= $totalIncorrect; ?></h4>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="p-3 bg-secondary-subtle rounded-3 text-center border border-secondary-subtle">
                                <span class="text-secondary text-xs fw-semibold d-block mb-1"><i class="bi bi-dash-circle me-1"></i> Kosong</span>
                                <h4 class="fw-bold text-secondary mb-0"><?= $totalUnanswered; ?></h4>
                            </div>
                        </div>
                    </div>

                    <!-- Meta Information Table -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered text-sm align-middle mb-0">
                            <tbody>
                                <tr>
                                    <th class="bg-light text-muted fw-semibold" style="width: 35%;">Nama Peserta</th>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($user['full_name']); ?> (NIS: <?= htmlspecialchars($student['nis'] ?? '-'); ?>)</td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-muted fw-semibold">Kelas</th>
                                    <td><?= htmlspecialchars($result['class_name']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-muted fw-semibold">Waktu Mulai</th>
                                    <td><?= !empty($result['participant_start_time']) ? date('d F Y, H:i:s', strtotime($result['participant_start_time'])) : '-'; ?> WIB</td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-muted fw-semibold">Waktu Pengumpulan</th>
                                    <td><?= !empty($result['participant_submit_time']) ? date('d F Y, H:i:s', strtotime($result['participant_submit_time'])) : '-'; ?> WIB</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($questions) && is_array($questions)): ?>
                        <!-- ================= QUESTIONS REVIEW & AI EXPLAINER SECTION ================= -->
                        <div class="mt-5 mb-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <div>
                                    <h4 class="fw-bold text-dark mb-1">
                                        <i class="bi bi-journal-text text-primary me-2"></i>Evaluasi & Pembahasan Soal
                                    </h4>
                                    <p class="text-muted text-xs mb-0">Tinjau jawaban Anda dan gunakan Asisten AI untuk memahami konsep tiap soal secara mendalam.</p>
                                </div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 text-xs rounded-pill">
                                    <i class="bi bi-robot me-1"></i> AI Question Tutor Aktif
                                </span>
                            </div>

                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($questions as $idx => $q): 
                                    $isMC = ($q['question_type'] === 'multiple_choice');
                                    $ans = $q['answer'] ?? null;
                                    $isCorrect = !empty($ans['is_correct']) && (int)$ans['is_correct'] === 1;
                                    $isAnswered = $isMC ? !empty($ans['selected_option_id']) : (!empty($ans['essay_answer']) && trim($ans['essay_answer']) !== '');
                                ?>
                                    <div class="card border rounded-3 shadow-none overflow-hidden" id="q-card-<?= $q['question_id']; ?>">
                                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-secondary rounded-pill px-2">Soal #<?= $q['question_order']; ?></span>
                                                <span class="badge bg-light text-dark border text-xs"><?= ucfirst($q['difficulty'] ?? 'normal'); ?></span>
                                                <span class="badge bg-light text-dark border text-xs"><?= $isMC ? 'Pilihan Ganda' : 'Essay'; ?></span>
                                            </div>
                                            <div>
                                                <?php if (!$isAnswered): ?>
                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle text-xs">
                                                        <i class="bi bi-dash-circle me-1"></i>Tidak Dijawab
                                                    </span>
                                                <?php elseif ($isCorrect): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle text-xs">
                                                        <i class="bi bi-check-circle-fill me-1"></i>Jawaban Benar
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle text-xs">
                                                        <i class="bi bi-x-circle-fill me-1"></i>Jawaban Salah
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="card-body p-3 p-md-4">
                                            <!-- Question Text -->
                                            <div class="fw-semibold text-dark text-sm mb-3 lh-base">
                                                <?= nl2br(htmlspecialchars($q['question_text'])); ?>
                                            </div>

                                            <?php if ($isMC && !empty($q['options'])): ?>
                                                <!-- Options List -->
                                                <div class="list-group list-group-flush border rounded-3 mb-3 text-sm">
                                                    <?php foreach ($q['options'] as $opt): 
                                                        $isSelected = ((int)$opt['id'] === (int)($q['selected_option_id'] ?? 0));
                                                        $isOptCorrect = ((int)$opt['is_correct'] === 1);
                                                        
                                                        $optClass = '';
                                                        $badge = '';
                                                        if ($isSelected && $isOptCorrect) {
                                                            $optClass = 'bg-success-subtle text-success fw-bold border-success-subtle';
                                                            $badge = '<span class="badge bg-success text-white ms-auto text-xs"><i class="bi bi-check-lg me-1"></i>Jawaban Anda (Benar)</span>';
                                                        } elseif ($isSelected && !$isOptCorrect) {
                                                            $optClass = 'bg-danger-subtle text-danger fw-bold border-danger-subtle';
                                                            $badge = '<span class="badge bg-danger text-white ms-auto text-xs"><i class="bi bi-x-lg me-1"></i>Jawaban Anda (Salah)</span>';
                                                        } elseif ($isOptCorrect) {
                                                            $optClass = 'border-success bg-success-subtle text-success fw-semibold';
                                                            $badge = '<span class="badge bg-success-subtle text-success border border-success ms-auto text-xs"><i class="bi bi-check-circle me-1"></i>Kunci Jawaban</span>';
                                                        }
                                                    ?>
                                                        <div class="list-group-item d-flex align-items-center gap-2 py-2 px-3 <?= $optClass; ?>">
                                                            <span class="badge rounded-circle bg-white text-dark border px-2 py-1"><?= htmlspecialchars($opt['option_label']); ?></span>
                                                            <span class="text-sm"><?= htmlspecialchars($opt['option_text']); ?></span>
                                                            <?= $badge; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php elseif (!$isMC): ?>
                                                <!-- Essay Response Review -->
                                                <div class="p-3 bg-light rounded-3 border mb-3 text-sm">
                                                    <div class="text-xs text-muted mb-1 fw-bold">Jawaban Anda:</div>
                                                    <div class="text-dark fst-italic">
                                                        <?= !empty($ans['essay_answer']) ? nl2br(htmlspecialchars($ans['essay_answer'])) : '<span class="text-muted">(Tidak ada jawaban essay yang dimasukkan)</span>'; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Official Explanation (if available) -->
                                            <?php if (!empty($q['explanation'])): ?>
                                                <div class="p-3 bg-primary-subtle border border-primary-subtle rounded-3 text-sm mb-3">
                                                    <div class="fw-bold text-primary text-xs mb-1">
                                                        <i class="bi bi-lightbulb-fill me-1"></i>Pembahasan Resmi Guru:
                                                    </div>
                                                    <div class="text-dark text-xs lh-base">
                                                        <?= nl2br(htmlspecialchars($q['explanation'])); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- AI Question Explainer Trigger Button -->
                                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                                <div class="text-xs text-muted">
                                                    <i class="bi bi-shield-check text-success me-1"></i>AI Question Explainer (Scoped Tutor)
                                                </div>
                                                <button type="button" 
                                                        class="btn btn-outline-primary btn-sm px-3 rounded-pill d-inline-flex align-items-center gap-2 btn-open-qexplainer"
                                                        data-participant-id="<?= (int)$exam['participant_id']; ?>"
                                                        data-question-id="<?= (int)$q['question_id']; ?>"
                                                        data-question-num="<?= $q['question_order']; ?>"
                                                        data-question-text="<?= htmlspecialchars($q['question_text']); ?>">
                                                    <i class="bi bi-stars text-warning"></i>
                                                    <span>Tanya Penjelasan AI</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Navigation Action Buttons -->
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 pt-3 border-top">
                        <a href="<?= BASE_URL; ?>/student/dashboard" class="btn btn-outline-primary px-4 py-2 text-sm fw-semibold">
                            <i class="bi bi-house-door me-1"></i> Kembali ke Dashboard
                        </a>
                        <a href="<?= BASE_URL; ?>/student/exams" class="btn btn-primary px-4 py-2 text-sm fw-semibold">
                            <i class="bi bi-journal-check me-1"></i> Lihat Daftar Ujian Lainnya
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- ================= MODAL / DRAWER AI QUESTION EXPLAINER (CHATBOT TIPE 2) ================= -->
        <div class="modal fade" id="qExplainerModal" tabindex="-1" aria-labelledby="qExplainerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <!-- Modal Header -->
                    <div class="modal-header bg-primary text-white py-3 px-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; font-size: 1.25rem;">
                                <i class="bi bi-robot"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold text-white mb-0" id="qExplainerModalLabel">
                                    AI Tutor: Pembahasan Soal
                                </h5>
                                <div class="text-white-50 text-xs">
                                    <i class="bi bi-shield-lock-fill text-warning"></i> Scoped Soal Ini Saja & Guardrail Ketat
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <!-- Modal Body: Question Snippet + Chat Messages -->
                    <div class="modal-body p-0 d-flex flex-column" style="height: 520px; background-color: #f8fafc;">
                        <!-- Question Context Snippet Box -->
                        <div class="p-3 bg-white border-bottom shadow-sm">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge bg-primary-subtle text-primary text-xs" id="modalQBadge">Soal</span>
                                <span class="text-xs text-muted" id="modalQStatus"></span>
                            </div>
                            <div class="text-xs fw-semibold text-dark text-truncate-2" id="modalQText"></div>
                            <div class="d-flex gap-3 text-xs mt-2 pt-2 border-top">
                                <div>Jawaban Anda: <strong id="modalQStudentAns" class="text-dark">-</strong></div>
                                <div>Kunci Benar: <strong id="modalQCorrectAns" class="text-success">-</strong></div>
                            </div>
                        </div>

                        <!-- Chat Messages Container -->
                        <div class="flex-grow-1 p-3 overflow-y-auto d-flex flex-column gap-3" id="qExplainerChatBody">
                            <!-- Messages will be rendered here dynamically -->
                        </div>

                        <!-- Quick Suggestions Chips -->
                        <div class="px-3 py-2 bg-white border-top d-flex gap-2 overflow-x-auto" id="qExplainerChips" style="white-space: nowrap;">
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill text-xs q-chip" data-prompt="Jelaskan konsep inti soal ini secara bertahap">
                                💡 Konsep Inti
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill text-xs q-chip" data-prompt="Mengapa jawaban saya salah dan apa letak kekeliruannya?">
                                ❓ Mengapa Salah?
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill text-xs q-chip" data-prompt="Berikan tips dan trik agar tidak keliru pada soal serupa">
                                🎯 Tips Soal Serupa
                            </button>
                        </div>

                        <!-- Chat Input Box -->
                        <div class="p-3 bg-white border-top">
                            <form id="qExplainerForm" class="d-flex gap-2 m-0">
                                <input type="text" 
                                       id="qExplainerInput" 
                                       class="form-control text-sm rounded-pill px-3 py-2" 
                                       placeholder="Tanyakan konsep atau langkah yang belum Anda pahami..." 
                                       autocomplete="off">
                                <button type="submit" 
                                        id="qExplainerSendBtn" 
                                        class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" 
                                        style="width: 42px; height: 42px; flex-shrink: 0;"
                                        aria-label="Kirim Pertanyaan">
                                    <i class="bi bi-send-fill"></i>
                                </button>
                            </form>
                            <div class="text-center text-xs text-muted mt-1" style="font-size: 0.72rem;">
                                <i class="bi bi-shield-check text-success"></i> Dilengkapi guardrail ketat: Hanya menjawab materi seputar soal ini
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Question Explainer Frontend Client Script -->
        <script src="<?= BASE_URL; ?>/public/js/question_explainer.js"></script>

        <!-- Footer -->
        <?php require_once __DIR__ . '/../layouts/footer.php'; ?>
    </div>
</div>
