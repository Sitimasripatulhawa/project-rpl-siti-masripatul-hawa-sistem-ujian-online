<?php
require_once __DIR__ . '/../../layouts/header.php';

$q = $question ?? [
    'id' => '',
    'subject_id' => '',
    'question_text' => '',
    'question_type' => 'multiple_choice',
    'difficulty' => 'medium',
    'default_score' => '20',
    'explanation' => '',
    'options' => []
];

// Map options indexed by label
$optMap = [];
$correctOption = 'A';
if (!empty($q['options'])) {
    foreach ($q['options'] as $opt) {
        $optMap[$opt['option_label']] = $opt['option_text'];
        if (!empty($opt['is_correct'])) {
            $correctOption = $opt['option_label'];
        }
    }
}

$actionUrl = $is_edit ? BASE_URL . '/teacher/questionEdit/' . $q['id'] : BASE_URL . '/teacher/questionCreate';
?>

<div class="app-wrapper">
    <?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

    <div class="app-main">
        <?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1"><?= $is_edit ? 'Edit Butir Soal #' . $q['id'] : 'Tambah Butir Soal Baru'; ?></h1>
                    <p class="text-muted mb-0 text-sm"><?= $is_edit ? 'Perbarui konten pertanyaan, opsi pilihan jawaban, atau kunci pembahasan.' : 'Susun butir pertanyaan baru ke dalam bank soal Anda.'; ?></p>
                </div>
                <a href="<?= BASE_URL; ?>/teacher/questions" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Bank Soal
                </a>
            </div>

            <!-- Form Card -->
            <form action="<?= $actionUrl; ?>" method="POST">
                <?= Csrf::tokenField(); ?>
                <div class="row g-4">
                    <div class="col-12 col-lg-8">
                        <div class="custom-card p-4 mb-4">
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-pencil-square me-1"></i> 1. Butir Pertanyaan</h6>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-6">
                                    <label for="subjectSelect" class="form-label text-sm fw-semibold required">Mata Pelajaran</label>
                                    <select name="subject_id" class="form-select text-sm" id="subjectSelect" required>
                                        <option value="">-- Pilih Mata Pelajaran --</option>
                                        <?php foreach ($subjects as $sub): ?>
                                            <option value="<?= $sub['id']; ?>" <?= $q['subject_id'] == $sub['id'] ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($sub['name']); ?> (<?= htmlspecialchars($sub['code']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="typeSelect" class="form-label text-sm fw-semibold">Tipe Soal <span class="text-danger">*</span></label>
                                    <select name="question_type" class="form-select text-sm" id="typeSelect" onchange="toggleQuestionType(this.value)" required>
                                        <option value="multiple_choice" <?= $q['question_type'] === 'multiple_choice' ? 'selected' : ''; ?>>Pilihan Ganda (Multiple Choice)</option>
                                        <option value="essay" <?= $q['question_type'] === 'essay' ? 'selected' : ''; ?>>Essay / Uraian</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="questionTextInput" class="form-label text-sm fw-semibold">Teks Pertanyaan <span class="text-danger">*</span></label>
                                <textarea name="question_text" class="form-control text-sm" id="questionTextInput" rows="5" placeholder="Tuliskan butir soal atau narasi pertanyaan secara jelas..." required><?= htmlspecialchars($q['question_text']); ?></textarea>
                            </div>
                        </div>

                        <!-- Options Section (For Multiple Choice) -->
                        <div class="custom-card p-4 mb-4" id="optionsSection" style="<?= $q['question_type'] === 'essay' ? 'display: none;' : ''; ?>">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-primary mb-0"><i class="bi bi-list-check me-1"></i> 2. Pilihan Jawaban & Kunci Jawaban Benar</h6>
                                <span class="badge bg-light text-muted border text-xs">Pilih radio button pada jawaban yang benar</span>
                            </div>

                            <div class="d-flex flex-column gap-3">
                                <!-- Option A -->
                                <div class="p-3 border rounded-3 bg-light">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="correct_option" id="radioOptA" value="A" <?= $correctOption === 'A' ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark text-sm" for="radioOptA">
                                                Pilihan A (Kunci Benar)
                                            </label>
                                        </div>
                                    </div>
                                    <input type="text" name="options[A]" class="form-control text-sm" placeholder="Teks jawaban pilihan A..." value="<?= htmlspecialchars($optMap['A'] ?? ''); ?>">
                                </div>

                                <!-- Option B -->
                                <div class="p-3 border rounded-3 bg-light">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="correct_option" id="radioOptB" value="B" <?= $correctOption === 'B' ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark text-sm" for="radioOptB">
                                                Pilihan B (Kunci Benar)
                                            </label>
                                        </div>
                                    </div>
                                    <input type="text" name="options[B]" class="form-control text-sm" placeholder="Teks jawaban pilihan B..." value="<?= htmlspecialchars($optMap['B'] ?? ''); ?>">
                                </div>

                                <!-- Option C -->
                                <div class="p-3 border rounded-3 bg-light">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="correct_option" id="radioOptC" value="C" <?= $correctOption === 'C' ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark text-sm" for="radioOptC">
                                                Pilihan C (Kunci Benar)
                                            </label>
                                        </div>
                                    </div>
                                    <input type="text" name="options[C]" class="form-control text-sm" placeholder="Teks jawaban pilihan C..." value="<?= htmlspecialchars($optMap['C'] ?? ''); ?>">
                                </div>

                                <!-- Option D -->
                                <div class="p-3 border rounded-3 bg-light">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="correct_option" id="radioOptD" value="D" <?= $correctOption === 'D' ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark text-sm" for="radioOptD">
                                                Pilihan D (Kunci Benar)
                                            </label>
                                        </div>
                                    </div>
                                    <input type="text" name="options[D]" class="form-control text-sm" placeholder="Teks jawaban pilihan D..." value="<?= htmlspecialchars($optMap['D'] ?? ''); ?>">
                                </div>

                                <!-- Option E (Optional) -->
                                <div class="p-3 border rounded-3 bg-light">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="correct_option" id="radioOptE" value="E" <?= $correctOption === 'E' ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark text-sm" for="radioOptE">
                                                Pilihan E (Opsional)
                                            </label>
                                        </div>
                                    </div>
                                    <input type="text" name="options[E]" class="form-control text-sm" placeholder="Teks jawaban pilihan E (kosongkan jika hanya 4 opsi)..." value="<?= htmlspecialchars($optMap['E'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Explanation Section -->
                        <div class="custom-card p-4">
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-lightbulb me-1"></i> 3. Pembahasan / Kunci Referensi (Opsional)</h6>
                            <textarea name="explanation" class="form-control text-sm" rows="3" placeholder="Tuliskan langkah penyelesaian, kunci uraian, atau penjelasan pembahasan..."><?= htmlspecialchars($q['explanation'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Side Settings -->
                    <div class="col-12 col-lg-4">
                        <div class="custom-card p-4 mb-4">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-gear me-1"></i> Pengaturan Soal</h6>

                            <div class="mb-3">
                                <label for="scoreInput" class="form-label text-sm fw-semibold">Bobot Nilai Default <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.5" min="0.5" name="default_score" class="form-control text-sm" id="scoreInput" value="<?= htmlspecialchars($q['default_score']); ?>" required>
                                    <span class="input-group-text bg-light text-sm">Poin</span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="diffSelect" class="form-label text-sm fw-semibold">Tingkat Kesulitan <span class="text-danger">*</span></label>
                                <select name="difficulty" class="form-select text-sm" id="diffSelect" required>
                                    <option value="easy" <?= $q['difficulty'] === 'easy' ? 'selected' : ''; ?>>Mudah (Easy)</option>
                                    <option value="medium" <?= $q['difficulty'] === 'medium' ? 'selected' : ''; ?>>Sedang (Medium)</option>
                                    <option value="hard" <?= $q['difficulty'] === 'hard' ? 'selected' : ''; ?>>Sukar (Hard)</option>
                                </select>
                            </div>

                            <hr>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                                    <i class="bi bi-save me-1"></i> <?= $is_edit ? 'Simpan Perubahan' : 'Simpan Butir Soal'; ?>
                                </button>
                                <a href="<?= BASE_URL; ?>/teacher/questions" class="btn btn-outline-secondary btn-sm py-2">Batal</a>
                            </div>
                        </div>

                        <div class="custom-card p-4">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-shield-check text-success me-1"></i> Aturan Kepemilikan</h6>
                            <p class="text-xs text-muted mb-0">
                                Butir soal ini akan disimpan secara eksklusif ke dalam Bank Soal Anda dan hanya dapat dikelola oleh Anda.
                            </p>
                        </div>
                    </div>
                </div>
            </form>
        </main>

        <?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
    </div>
</div>

<script>
function toggleQuestionType(type) {
    const optSection = document.getElementById('optionsSection');
    if (type === 'multiple_choice') {
        optSection.style.display = 'block';
    } else {
        optSection.style.display = 'none';
    }
}
</script>
