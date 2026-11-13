<?php
require_once __DIR__ . '/../../layouts/header.php';

$exam = $exam ?? [];
$isEdit = !empty($is_edit);
$selectedQuestions = $selected_questions ?? [];

// Helper for default datetime values
$defaultStartTime = !empty($exam['start_time']) ? date('Y-m-d\TH:i', strtotime($exam['start_time'])) : date('Y-m-d\TH:i', strtotime('+1 hour'));
$defaultEndTime = !empty($exam['end_time']) ? date('Y-m-d\TH:i', strtotime($exam['end_time'])) : date('Y-m-d\TH:i', strtotime('+3 hours'));
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

            <!-- Page Header -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <a href="<?= BASE_URL; ?>/teacher/exams" class="btn btn-outline-secondary btn-sm p-1 px-2">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <h1 class="h3 fw-bold text-dark mb-0"><?= $isEdit ? 'Edit Sesi Ujian' : 'Buat Sesi Ujian Baru'; ?></h1>
                    </div>
                    <p class="text-muted mb-0 text-sm">Lengkapi pengaturan ujian dan pilih butir-butir soal dari Bank Soal Anda.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL; ?>/teacher/exams" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="bi bi-x-circle me-1"></i> Batal
                    </a>
                </div>
            </div>

            <!-- Form -->
            <form action="<?= $isEdit ? BASE_URL . '/teacher/examEdit/' . $exam['id'] : BASE_URL . '/teacher/examCreate'; ?>" method="POST" id="examForm">
                <?= Csrf::tokenField(); ?>
                <div class="row g-4">
                    <!-- Left Column: Exam Basic & Schedule Settings -->
                    <div class="col-12 col-lg-7">
                        <div class="custom-card p-4 mb-4">
                            <h2 class="h6 fw-bold text-dark mb-3">
                                <i class="bi bi-info-circle text-primary me-2"></i>Informasi & Pengaturan Dasar
                            </h2>

                            <div class="mb-3">
                                <label for="title" class="form-label text-sm fw-semibold required">Judul Sesi Ujian</label>
                                <input type="text" class="form-control" id="title" name="title" required
                                       placeholder="Contoh: Penilaian Harian Bersama Matematika Bab 1"
                                       value="<?= htmlspecialchars($exam['title'] ?? ''); ?>">
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-6">
                                    <label for="subject_id" class="form-label text-sm fw-semibold">Mata Pelajaran <span class="text-danger">*</span></label>
                                    <select class="form-select" id="subject_id" name="subject_id" required>
                                        <option value="">-- Pilih Mata Pelajaran --</option>
                                        <?php foreach ($subjects as $s): ?>
                                            <option value="<?= $s['id']; ?>" <?= (!empty($exam['subject_id']) && (int)$exam['subject_id'] === (int)$s['id']) ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($s['name']); ?> (<?= htmlspecialchars($s['code']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="class_id" class="form-label text-sm fw-semibold">Kelas Sasaran <span class="text-danger">*</span></label>
                                    <select class="form-select" id="class_id" name="class_id" required>
                                        <option value="">-- Pilih Kelas --</option>
                                        <?php foreach ($classes as $c): ?>
                                            <option value="<?= $c['id']; ?>" <?= (!empty($exam['class_id']) && (int)$exam['class_id'] === (int)$c['id']) ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($c['name']); ?> (Tingkat <?= htmlspecialchars($c['level']); ?> - <?= htmlspecialchars($c['academic_year']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label text-sm fw-semibold">Deskripsi & Petunjuk Pengerjaan</label>
                                <textarea class="form-control" id="description" name="description" rows="3"
                                          placeholder="Tuliskan petunjuk pengerjaan ujian bagi peserta didik..."><?= htmlspecialchars($exam['description'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- Schedule & Duration Settings -->
                        <div class="custom-card p-4 mb-4">
                            <h2 class="h6 fw-bold text-dark mb-3">
                                <i class="bi bi-clock-history text-primary me-2"></i>Jadwal & Waktu Pengerjaan
                            </h2>

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-4">
                                    <label for="duration_minutes" class="form-label text-sm fw-semibold">Durasi Ujian <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" min="1" max="600" required
                                               value="<?= htmlspecialchars($exam['duration_minutes'] ?? '90'); ?>">
                                        <span class="input-group-text bg-light text-sm">Menit</span>
                                    </div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label for="start_time" class="form-label text-sm fw-semibold">Waktu Mulai <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" id="start_time" name="start_time" required
                                           value="<?= $defaultStartTime; ?>">
                                </div>

                                <div class="col-12 col-md-4">
                                    <label for="end_time" class="form-label text-sm fw-semibold">Waktu Selesai <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" id="end_time" name="end_time" required
                                           value="<?= $defaultEndTime; ?>">
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="passing_score" class="form-label text-sm fw-semibold">Nilai Kelulusan (KKM)</label>
                                    <input type="number" step="0.1" class="form-control" id="passing_score" name="passing_score" min="0" max="100"
                                           value="<?= htmlspecialchars($exam['passing_score'] ?? '75.00'); ?>">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="status" class="form-label text-sm fw-semibold">Status Awal Ujian</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="draft" <?= (!empty($exam['status']) && $exam['status'] === 'draft') ? 'selected' : ''; ?>>Draft (Belum dapat diakses siswa)</option>
                                        <option value="published" <?= (!empty($exam['status']) && $exam['status'] === 'published') ? 'selected' : ''; ?>>Published (Siap diakses sesuai jadwal)</option>
                                        <?php if ($isEdit): ?>
                                            <option value="running" <?= (!empty($exam['status']) && $exam['status'] === 'running') ? 'selected' : ''; ?>>Running (Sedang Berlangsung)</option>
                                            <option value="finished" <?= (!empty($exam['status']) && $exam['status'] === 'finished') ? 'selected' : ''; ?>>Finished (Ujian Selesai)</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Randomization & Security Options -->
                        <div class="custom-card p-4">
                            <h2 class="h6 fw-bold text-dark mb-3">
                                <i class="bi bi-shield-check text-primary me-2"></i>Keamanan & Pengacakan
                            </h2>

                            <div class="d-flex flex-column gap-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_random_question" name="is_random_question" value="1"
                                           <?= (!isset($exam['is_random_question']) || !empty($exam['is_random_question'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label text-sm fw-semibold" for="is_random_question">
                                        Acak Urutan Soal (Random Questions)
                                    </label>
                                    <div class="text-xs text-muted">Setiap peserta didik akan menerima urutan nomor soal yang berbeda.</div>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_random_option" name="is_random_option" value="1"
                                           <?= (!isset($exam['is_random_option']) || !empty($exam['is_random_option'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label text-sm fw-semibold" for="is_random_option">
                                        Acak Opsi Pilihan Ganda (Random Options)
                                    </label>
                                    <div class="text-xs text-muted">Pilihan A, B, C, D diacak secara otomatis untuk setiap peserta didik.</div>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enable_proctoring" name="enable_proctoring" value="1"
                                           <?= (!isset($exam['enable_proctoring']) || !empty($exam['enable_proctoring'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label text-sm fw-semibold" for="enable_proctoring">
                                        Aktifkan AI Proctoring & Monitoring
                                    </label>
                                    <div class="text-xs text-muted">Merekam pelanggaran, pergantian tab browser, dan wajah selama ujian.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Question Selection from Question Bank -->
                    <div class="col-12 col-lg-5">
                        <div class="custom-card p-4 h-100 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h2 class="h6 fw-bold text-dark mb-0">
                                        <i class="bi bi-collection-fill text-primary me-1"></i>Pilih Soal dari Bank Soal <span class="text-danger">*</span>
                                    </h2>
                                    <div class="text-xs text-muted mt-1">Pilih minimal 1 butir soal untuk sesi ini.</div>
                                </div>
                                <span class="badge bg-primary text-white rounded-pill px-3 py-2 text-xs" id="selectedCountBadge">
                                    0 Soal Dipilih
                                </span>
                            </div>

                            <!-- Live Filter for questions -->
                            <div class="mb-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" id="questionSearch" class="form-control border-start-0" placeholder="Cari teks soal dalam bank soal...">
                                </div>
                            </div>

                            <!-- Selection Control Buttons -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary text-xs" id="btnSelectAll">Pilih Semua</button>
                                    <button type="button" class="btn btn-outline-secondary text-xs" id="btnDeselectAll">Batal Pilih</button>
                                </div>
                                <span class="text-xs text-muted">Total: <?= count($available_questions); ?> Soal</span>
                            </div>

                            <!-- Questions List Box -->
                            <div class="border rounded-3 p-2 flex-grow-1 overflow-auto bg-light" style="max-height: 480px;" id="questionsList">
                                <?php if (empty($available_questions)): ?>
                                    <div class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        Bank Soal Anda masih kosong.
                                        <div class="mt-2">
                                            <a href="<?= BASE_URL; ?>/teacher/questionCreate" target="_blank" class="btn btn-primary btn-sm text-xs">
                                                <i class="bi bi-plus-circle me-1"></i> Buat Butir Soal Dulu
                                            </a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($available_questions as $q): ?>
                                        <?php $isChecked = in_array((int)$q['id'], array_map('intval', $selectedQuestions)); ?>
                                        <div class="question-item card mb-2 p-2.5 border-0 shadow-sm rounded-3 <?= $isChecked ? 'border-primary bg-white' : 'bg-white'; ?>" data-text="<?= htmlspecialchars(strtolower($q['question_text'])); ?>" data-subject="<?= $q['subject_id']; ?>">
                                            <div class="form-check d-flex align-items-start gap-2">
                                                <input class="form-check-input question-checkbox mt-1 flex-shrink-0" type="checkbox" name="questions[]" value="<?= $q['id']; ?>" id="q_<?= $q['id']; ?>" <?= $isChecked ? 'checked' : ''; ?>>
                                                <label class="form-check-label w-100 cursor-pointer" for="q_<?= $q['id']; ?>">
                                                    <div class="d-flex justify-content-between align-items-center gap-1 mb-1">
                                                        <span class="badge bg-light text-dark border text-xs"><?= htmlspecialchars($q['subject_name']); ?></span>
                                                        <div class="d-flex gap-1">
                                                            <span class="badge bg-info-subtle text-info text-xs"><?= ($q['question_type'] === 'multiple_choice') ? 'Pilihan Ganda' : 'Essay'; ?></span>
                                                            <span class="badge bg-secondary-subtle text-secondary text-xs"><?= ucfirst($q['difficulty']); ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="text-xs text-dark fw-medium lh-sm text-clamp-2">
                                                        <?= htmlspecialchars($q['question_text']); ?>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                                <a href="<?= BASE_URL; ?>/teacher/exams" class="btn btn-outline-secondary btn-sm px-3">Batal</a>
                                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm" id="btnSubmitExam">
                                    <i class="bi bi-check2-circle me-1"></i> <?= $isEdit ? 'Simpan Perubahan' : 'Simpan & Terbitkan Ujian'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>

        <!-- Footer -->
        <?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.question-checkbox');
    const badge = document.getElementById('selectedCountBadge');
    const searchInput = document.getElementById('questionSearch');
    const questionItems = document.querySelectorAll('.question-item');
    const btnSelectAll = document.getElementById('btnSelectAll');
    const btnDeselectAll = document.getElementById('btnDeselectAll');
    const examForm = document.getElementById('examForm');

    function updateSelectedCount() {
        const count = document.querySelectorAll('.question-checkbox:checked').length;
        if (badge) {
            badge.textContent = count + ' Soal Dipilih';
            if (count === 0) {
                badge.className = 'badge bg-danger text-white rounded-pill px-3 py-2 text-xs';
            } else {
                badge.className = 'badge bg-primary text-white rounded-pill px-3 py-2 text-xs';
            }
        }
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });

    if (btnSelectAll) {
        btnSelectAll.addEventListener('click', function() {
            questionItems.forEach(item => {
                if (item.style.display !== 'none') {
                    const cb = item.querySelector('.question-checkbox');
                    if (cb) cb.checked = true;
                }
            });
            updateSelectedCount();
        });
    }

    if (btnDeselectAll) {
        btnDeselectAll.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = false);
            updateSelectedCount();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            questionItems.forEach(item => {
                const text = item.getAttribute('data-text') || '';
                if (query === '' || text.includes(query)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Client-side validation before submit
    if (examForm) {
        examForm.addEventListener('submit', function(e) {
            const checkedCount = document.querySelectorAll('.question-checkbox:checked').length;
            if (checkedCount === 0) {
                e.preventDefault();
                alert('Peringatan: Minimal pilih 1 butir soal dari Bank Soal untuk ujian ini.');
                return false;
            }

            const startTime = new Date(document.getElementById('start_time').value);
            const endTime = new Date(document.getElementById('end_time').value);

            if (startTime >= endTime) {
                e.preventDefault();
                alert('Peringatan: Waktu mulai ujian harus lebih awal daripada waktu selesai.');
                return false;
            }
        });
    }

    updateSelectedCount();
});
</script>
