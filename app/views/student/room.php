<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= Csrf::tokenMeta(); ?>
    <title><?= htmlspecialchars($page_title ?? 'Ruang Ujian Online'); ?> - <?= APP_NAME; ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL; ?>/public/css/style.css">
</head>
<body class="exam-layout">

    <!-- 1. Header Ujian (Sticky Top) -->
    <header class="exam-header py-2 px-3 px-md-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <!-- Left: Exam Title & Student Info -->
            <div class="d-flex align-items-center gap-3">
                <div class="brand-icon d-none d-sm-flex" style="width: 38px; height: 38px; font-size: 1.1rem;">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark text-sm lh-1 mb-1">
                        <?= htmlspecialchars($exam['title']); ?>
                        <span class="badge bg-primary-subtle text-primary text-xs ms-1"><?= htmlspecialchars($exam['subject_name']); ?></span>
                    </div>
                    <div class="text-xs text-muted">
                        <?= htmlspecialchars($user['full_name']); ?> | NIS: <?= htmlspecialchars($student['nis'] ?? '-'); ?> (<?= htmlspecialchars($exam['class_name']); ?>)
                    </div>
                </div>
            </div>

            <!-- Right: Timer & Save Indicator & Mobile Nav Toggle -->
            <div class="d-flex align-items-center gap-2 gap-md-3">
                <!-- Save Status Indicator -->
                <div id="saveIndicator" class="save-indicator me-1">
                    <i class="bi bi-cloud-check-fill text-success fs-6"></i>
                    <span class="d-none d-sm-inline text-muted text-xs">Jawaban tersimpan</span>
                </div>

                <!-- Countdown Timer -->
                <div id="examTimer" class="exam-timer" title="Sisa Waktu Ujian">
                    <i class="bi bi-clock-history"></i>
                    <span id="timerDisplay">--:--:--</span>
                </div>

                <!-- Question Navigator Drawer Toggle (Mobile) -->
                <button class="btn btn-outline-secondary btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#navDrawer" aria-controls="navDrawer">
                    <i class="bi bi-grid-3x3-gap-fill"></i> Soal
                </button>
            </div>
        </div>
    </header>

    <!-- Camera Disconnect Warning Banner -->
    <div id="cameraDisconnectBanner" class="alert alert-danger text-sm rounded-0 mb-0 d-none text-center py-2 border-0" style="position: sticky; top: 58px; z-index: 1025;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Peringatan Pengawasan:</strong> Kamera utama Anda terputus! Harap pastikan kamera tetap aktif selama sesi ujian berlangsung.
    </div>

    <!-- Main Container -->
    <main class="container-fluid py-4 px-3 px-md-4 flex-grow-1">
        <div class="row g-4 justify-content-center">
            
            <!-- Question Content Area (Left / Center) -->
            <div class="col-12 col-lg-8 col-xl-8">
                <div class="custom-card p-4 p-md-5 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <!-- Question Header Meta -->
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4 gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="h4 fw-bold text-dark mb-0" id="currentQNumberDisplay">Soal No. 1</span>
                                <span class="text-xs text-muted" id="totalQDisplay">dari <?= count($questions); ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-secondary border text-xs" id="qTypeBadge">Pilihan Ganda</span>
                                <span class="badge bg-primary-subtle text-primary text-xs" id="qScoreBadge">Poin: 0</span>
                            </div>
                        </div>

                        <!-- Question Text Display -->
                        <div class="question-body mb-4">
                            <div id="questionText" class="text-dark fs-6 leading-relaxed mb-4">
                                <!-- Dynamic Question Text -->
                            </div>

                            <!-- Options Container for Multiple Choice -->
                            <div id="optionsContainer" class="d-flex flex-column gap-2 mb-3">
                                <!-- Dynamic Options -->
                            </div>

                            <!-- Essay Textarea Container -->
                            <div id="essayContainer" class="d-none">
                                <label for="essayInput" class="form-label text-sm fw-semibold text-dark mb-2">Tuliskan Jawaban Essay Anda:</label>
                                <textarea id="essayInput" class="form-control" rows="8" placeholder="Ketikkan jawaban Anda secara rinci di sini..."></textarea>
                                <div class="d-flex justify-content-between text-xs text-muted mt-2">
                                    <span>Jawaban tersimpan otomatis saat Anda mengetik.</span>
                                    <span id="essayCharCount">0 Karakter</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Question Navigation Footer -->
                    <div class="pt-4 border-top mt-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <!-- Previous Button -->
                            <button type="button" class="btn btn-outline-secondary px-3 py-2 text-sm fw-semibold" id="btnPrev">
                                <i class="bi bi-chevron-left me-1"></i> Soal Sebelumnya
                            </button>

                            <!-- Doubt (Ragu-ragu) Checkbox -->
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="doubtSwitch" style="cursor: pointer;">
                                <label class="form-check-label text-sm fw-semibold text-warning" for="doubtSwitch" style="cursor: pointer;">
                                    <i class="bi bi-flag-fill text-warning me-1"></i> Ragu-ragu
                                </label>
                            </div>

                            <!-- Next or Finish Button -->
                            <button type="button" class="btn btn-primary px-3 py-2 text-sm fw-semibold" id="btnNext">
                                Soal Berikutnya <i class="bi bi-chevron-right ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar: Question Navigator (Desktop) -->
            <div class="col-12 col-lg-4 col-xl-3 d-none d-lg-block">
                <div class="custom-card p-4 sticky-top" style="top: 80px;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0">Nomor Soal Ujian</h6>
                        <span class="badge bg-secondary-subtle text-dark text-xs" id="summaryCounter">0 / <?= count($questions); ?> Dijawab</span>
                    </div>

                    <!-- Status Legend -->
                    <div class="p-2 bg-light rounded-3 text-xs mb-3 d-flex justify-content-around">
                        <div class="d-flex align-items-center gap-1">
                            <span style="width: 10px; height: 10px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
                            <span class="text-muted">Dijawab</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span style="width: 10px; height: 10px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span>
                            <span class="text-muted">Ragu</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span style="width: 10px; height: 10px; border-radius: 50%; background: #e2e8f0; display: inline-block;"></span>
                            <span class="text-muted">Belum</span>
                        </div>
                    </div>

                    <!-- Navigator Grid -->
                    <div class="q-nav-grid mb-4" id="desktopNavGrid">
                        <!-- Generated by JS -->
                    </div>

                    <!-- Submit Button -->
                    <button type="button" class="btn btn-success w-100 py-2 fw-bold" id="btnOpenSubmitModal">
                        <i class="bi bi-cloud-upload-fill me-1"></i> Selesai & Kumpulkan
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Offcanvas Question Navigator for Mobile -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="navDrawer" aria-labelledby="navDrawerLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="navDrawerLabel">Navigasi Soal</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <!-- Mobile Grid -->
            <div class="q-nav-grid mb-4" id="mobileNavGrid">
                <!-- Generated by JS -->
            </div>
            <button type="button" class="btn btn-success w-100 py-2 fw-bold" id="btnOpenSubmitModalMobile" data-bs-dismiss="offcanvas">
                <i class="bi bi-cloud-upload-fill me-1"></i> Selesai & Kumpulkan
            </button>
        </div>
    </div>

    <!-- Submit Confirmation Modal -->
    <div class="modal fade" id="submitConfirmModal" tabindex="-1" aria-labelledby="submitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold text-dark" id="submitModalLabel">
                        <i class="bi bi-check2-circle text-success me-2"></i>Konfirmasi Kumpulkan Ujian
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="text-sm text-dark mb-3">Pastikan seluruh soal telah Anda jawab dengan teliti sebelum mengirimkan lembar ujian.</p>
                    
                    <div class="p-3 bg-light rounded-3 mb-3 text-sm">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Total Soal:</span>
                            <strong><?= count($questions); ?> Soal</strong>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Sudah Dijawab:</span>
                            <span class="fw-bold text-success" id="modalAnsweredCount">0</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Ragu-ragu:</span>
                            <span class="fw-bold text-warning" id="modalDoubtCount">0</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">Belum Dijawab:</span>
                            <span class="fw-bold text-danger" id="modalUnansweredCount">0</span>
                        </div>
                    </div>

                    <div class="alert alert-warning border-0 text-xs mb-0">
                        <i class="bi bi-exclamation-circle-fill me-1"></i>
                        Setelah dikumpulkan, Anda <strong>tidak dapat lagi mengubah jawaban</strong> atau mengakses kembali ruang ujian ini.
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Lanjutkan Pengerjaan</button>
                    <form action="<?= BASE_URL; ?>/student/submitExam/<?= $exam['exam_id']; ?>" method="POST" id="submitExamForm" class="m-0">
                        <?= Csrf::tokenField(); ?>
                        <button type="submit" class="btn btn-success btn-sm px-4 fw-bold" id="btnConfirmSubmit">
                            <i class="bi bi-check-lg me-1"></i> Ya, Kumpulkan Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($exam['enable_proctoring'])): ?>
        <!-- Floating Live Camera Proctoring Monitor -->
        <div class="proctoring-pip-widget d-none d-sm-block shadow" id="proctoringPipWidget">
            <div class="proctoring-pip-header">
                <span class="d-flex align-items-center gap-1">
                    <span class="camera-status-dot dot-online" id="proctoringDot"></span>
                    <span id="proctoringStatusText" style="font-size: 0.7rem;">Proctoring Aktif</span>
                </span>
                <span id="aiFaceBadge" class="badge bg-success text-white" style="font-size: 0.65rem;">Wajah OK</span>
            </div>
            <video id="roomCameraVideo" class="proctoring-pip-video mirror" autoplay playsinline muted></video>
        </div>
    <?php endif; ?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- AI Proctoring Client Engine -->
    <script src="<?= BASE_URL; ?>/public/js/ai-proctoring.js"></script>

    <!-- Exam Engine Client Script -->
    <script>
    // Configuration and server state
    const EXAM_ID = <?= (int)$exam['exam_id']; ?>;
    const BASE_URL = '<?= BASE_URL; ?>';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '<?= Csrf::getToken(); ?>';
    let questionsData = <?= json_encode($questions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    let currentIdx = 0;
    let remainingSeconds = <?= (int)$remaining_seconds; ?>;
    let isSubmitting = false;
    let essayDebounceTimer = null;

    // Elements
    const timerDisplay = document.getElementById('timerDisplay');
    const examTimerBadge = document.getElementById('examTimer');
    const saveIndicator = document.getElementById('saveIndicator');
    const currentQNumberDisplay = document.getElementById('currentQNumberDisplay');
    const totalQDisplay = document.getElementById('totalQDisplay');
    const qTypeBadge = document.getElementById('qTypeBadge');
    const qScoreBadge = document.getElementById('qScoreBadge');
    const questionText = document.getElementById('questionText');
    const optionsContainer = document.getElementById('optionsContainer');
    const essayContainer = document.getElementById('essayContainer');
    const essayInput = document.getElementById('essayInput');
    const essayCharCount = document.getElementById('essayCharCount');
    const doubtSwitch = document.getElementById('doubtSwitch');
    const btnPrev = document.getElementById('btnPrev');
    const btnNext = document.getElementById('btnNext');
    const desktopNavGrid = document.getElementById('desktopNavGrid');
    const mobileNavGrid = document.getElementById('mobileNavGrid');
    const summaryCounter = document.getElementById('summaryCounter');

    // Modal elements
    const submitModal = new bootstrap.Modal(document.getElementById('submitConfirmModal'));
    const modalAnsweredCount = document.getElementById('modalAnsweredCount');
    const modalDoubtCount = document.getElementById('modalDoubtCount');
    const modalUnansweredCount = document.getElementById('modalUnansweredCount');

    // Initialize Navigator Grid
    function renderNavigator() {
        desktopNavGrid.innerHTML = '';
        mobileNavGrid.innerHTML = '';

        let answeredCount = 0;
        let doubtCount = 0;

        questionsData.forEach((q, idx) => {
            const hasAns = isQuestionAnswered(q);
            const isDoubt = isQuestionDoubt(q);

            if (hasAns) answeredCount++;
            if (isDoubt) doubtCount++;

            ['desktop', 'mobile'].forEach(target => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'q-nav-btn';
                btn.textContent = idx + 1;

                if (idx === currentIdx) {
                    btn.classList.add('current');
                }
                if (isDoubt) {
                    btn.classList.add('doubt');
                } else if (hasAns) {
                    btn.classList.add('answered');
                }

                btn.addEventListener('click', () => {
                    goToQuestion(idx);
                    if (target === 'mobile') {
                        const offcanvasEl = document.getElementById('navDrawer');
                        const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                        if (offcanvas) offcanvas.hide();
                    }
                });

                if (target === 'desktop') {
                    desktopNavGrid.appendChild(btn);
                } else {
                    mobileNavGrid.appendChild(btn);
                }
            });
        });

        summaryCounter.textContent = answeredCount + ' / ' + questionsData.length + ' Dijawab';
        modalAnsweredCount.textContent = answeredCount;
        modalDoubtCount.textContent = doubtCount;
        modalUnansweredCount.textContent = questionsData.length - answeredCount;
    }

    function isQuestionAnswered(q) {
        if (!q.saved_answer) return false;
        if (q.question_type === 'multiple_choice') {
            return q.saved_answer.selected_option_id !== null && q.saved_answer.selected_option_id !== undefined && q.saved_answer.selected_option_id !== '';
        } else {
            return q.saved_answer.essay_answer && q.saved_answer.essay_answer.trim().length > 0;
        }
    }

    function isQuestionDoubt(q) {
        return q.saved_answer && parseInt(q.saved_answer.is_flagged_doubt, 10) === 1;
    }

    // Render current active question
    function renderCurrentQuestion() {
        const q = questionsData[currentIdx];
        if (!q) return;

        currentQNumberDisplay.textContent = 'Soal No. ' + (currentIdx + 1);
        totalQDisplay.textContent = 'dari ' + questionsData.length;
        qTypeBadge.textContent = (q.question_type === 'multiple_choice') ? 'Pilihan Ganda' : 'Essay';
        qScoreBadge.textContent = 'Bobot: ' + (q.question_score || 0) + ' Poin';
        questionText.innerHTML = q.question_text;

        // Doubt status switch
        doubtSwitch.checked = isQuestionDoubt(q);

        // Render input based on type
        if (q.question_type === 'multiple_choice') {
            essayContainer.classList.add('d-none');
            optionsContainer.classList.remove('d-none');
            optionsContainer.innerHTML = '';

            const selectedOptId = q.saved_answer ? parseInt(q.saved_answer.selected_option_id, 10) : null;

            (q.options || []).forEach((opt, optIdx) => {
                const optLabel = opt.option_label || String.fromCharCode(65 + optIdx);
                const optId = parseInt(opt.id, 10);
                const isSelected = (selectedOptId === optId);

                const item = document.createElement('label');
                item.className = 'option-item';

                item.innerHTML = `
                    <input type="radio" 
                           class="d-none option-radio" 
                           name="option_choice" 
                           value="${optId}" 
                           ${isSelected ? 'checked' : ''}>
                    <div class="option-card">
                        <div class="option-badge">${optLabel}</div>
                        <div class="option-text text-sm text-dark pt-1">${opt.option_text}</div>
                    </div>
                `;

                const radio = item.querySelector('input[type="radio"]');
                radio.addEventListener('change', function() {
                    handleOptionSelect(q.id, optId);
                });

                optionsContainer.appendChild(item);
            });
        } else {
            // Essay question
            optionsContainer.classList.add('d-none');
            essayContainer.classList.remove('d-none');
            const currentVal = q.saved_answer ? (q.saved_answer.essay_answer || '') : '';
            essayInput.value = currentVal;
            essayCharCount.textContent = currentVal.length + ' Karakter';

            essayInput.oninput = function() {
                essayCharCount.textContent = this.value.length + ' Karakter';
                clearTimeout(essayDebounceTimer);
                essayDebounceTimer = setTimeout(() => {
                    handleEssayInput(q.id, essayInput.value);
                }, 800);
            };

            essayInput.onblur = function() {
                handleEssayInput(q.id, essayInput.value);
            };
        }

        // Previous / Next button state
        btnPrev.disabled = (currentIdx === 0);
        if (currentIdx === questionsData.length - 1) {
            btnNext.innerHTML = 'Kumpulkan <i class="bi bi-cloud-upload-fill ms-1"></i>';
            btnNext.className = 'btn btn-success px-3 py-2 text-sm fw-semibold';
        } else {
            btnNext.innerHTML = 'Soal Berikutnya <i class="bi bi-chevron-right ms-1"></i>';
            btnNext.className = 'btn btn-primary px-3 py-2 text-sm fw-semibold';
        }

        renderNavigator();
    }

    function goToQuestion(idx) {
        if (idx >= 0 && idx < questionsData.length) {
            currentIdx = idx;
            renderCurrentQuestion();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    // Save answer via AJAX
    function saveAnswerToServer(questionId, optionId, essayAnswer, isDoubt) {
        showSavingIndicator(true);

        const formData = new FormData();
        formData.append('exam_id', EXAM_ID);
        formData.append('question_id', questionId);
        if (optionId !== null && optionId !== undefined) {
            formData.append('option_id', optionId);
        }
        if (essayAnswer !== null && essayAnswer !== undefined) {
            formData.append('essay_answer', essayAnswer);
        }
        formData.append('is_doubt', isDoubt ? 1 : 0);
        formData.append('csrf_token', CSRF_TOKEN);

        fetch(`${BASE_URL}/student/saveAnswer`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': CSRF_TOKEN
            }
        })
        .then(res => res.json())
        .then(data => {
            showSavingIndicator(false);
            if (data.timeout) {
                alert('Waktu ujian telah habis. Jawaban Anda akan otomatis dikumpulkan.');
                submitExamFinal();
            }
        })
        .catch(err => {
            showSavingIndicator(false);
            console.error('Error saving answer:', err);
        });
    }

    function showSavingIndicator(saving) {
        if (saving) {
            saveIndicator.innerHTML = '<i class="bi bi-arrow-repeat text-primary spinner-border spinner-border-sm"></i> <span class="d-none d-sm-inline text-muted text-xs">Menyimpan...</span>';
        } else {
            saveIndicator.innerHTML = '<i class="bi bi-cloud-check-fill text-success fs-6"></i> <span class="d-none d-sm-inline text-muted text-xs">Jawaban tersimpan</span>';
        }
    }

    function handleOptionSelect(questionId, optionId) {
        const q = questionsData[currentIdx];
        if (!q.saved_answer) {
            q.saved_answer = {};
        }
        q.saved_answer.selected_option_id = optionId;
        renderNavigator();
        saveAnswerToServer(questionId, optionId, null, doubtSwitch.checked);
    }

    function handleEssayInput(questionId, text) {
        const q = questionsData[currentIdx];
        if (!q.saved_answer) {
            q.saved_answer = {};
        }
        q.saved_answer.essay_answer = text;
        renderNavigator();
        saveAnswerToServer(questionId, null, text, doubtSwitch.checked);
    }

    // Doubt Switch Toggle
    doubtSwitch.addEventListener('change', function() {
        const q = questionsData[currentIdx];
        if (!q.saved_answer) {
            q.saved_answer = {};
        }
        q.saved_answer.is_flagged_doubt = this.checked ? 1 : 0;
        renderNavigator();

        const optId = q.saved_answer.selected_option_id || null;
        const essayVal = q.saved_answer.essay_answer || null;
        saveAnswerToServer(q.id, optId, essayVal, this.checked);
    });

    // Navigation buttons
    btnPrev.addEventListener('click', () => {
        if (currentIdx > 0) goToQuestion(currentIdx - 1);
    });

    btnNext.addEventListener('click', () => {
        if (currentIdx < questionsData.length - 1) {
            goToQuestion(currentIdx + 1);
        } else {
            // Last question clicked "Kumpulkan"
            renderNavigator();
            submitModal.show();
        }
    });

    // Open Modal Triggers
    document.getElementById('btnOpenSubmitModal').addEventListener('click', () => {
        renderNavigator();
        submitModal.show();
    });
    document.getElementById('btnOpenSubmitModalMobile').addEventListener('click', () => {
        renderNavigator();
        submitModal.show();
    });

    // Timer Countdown Logic (Synchronized with Server)
    function formatTime(seconds) {
        if (seconds < 0) seconds = 0;
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return [h, m, s].map(v => v.toString().padStart(2, '0')).join(':');
    }

    function updateTimerUI() {
        timerDisplay.textContent = formatTime(remainingSeconds);

        if (remainingSeconds <= 60) {
            examTimerBadge.classList.remove('timer-warning');
            examTimerBadge.classList.add('timer-critical');
        } else if (remainingSeconds <= 300) { // 5 minutes warning
            examTimerBadge.classList.remove('timer-critical');
            examTimerBadge.classList.add('timer-warning');
        } else {
            examTimerBadge.classList.remove('timer-warning');
            examTimerBadge.classList.remove('timer-critical');
        }

        if (remainingSeconds <= 0 && !isSubmitting) {
            isSubmitting = true;
            timerDisplay.textContent = '00:00:00';
            alert('Waktu ujian telah berakhir! Sistem akan mengumpulkan jawaban Anda secara otomatis.');
            submitExamFinal();
        }
    }

    // Timer ticker
    const timerInterval = setInterval(() => {
        if (remainingSeconds > 0) {
            remainingSeconds--;
            updateTimerUI();
        }
    }, 1000);

    // Heartbeat Sync with Server every 30 seconds
    const syncInterval = setInterval(() => {
        fetch(`${BASE_URL}/student/examTime/${EXAM_ID}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    remainingSeconds = data.remaining_seconds;
                    updateTimerUI();
                    if (data.is_expired && !isSubmitting) {
                        isSubmitting = true;
                        submitExamFinal();
                    }
                }
            })
            .catch(err => console.warn('Timer sync check blip:', err));
    }, 30000);

    // Submit Action Finalizer
    function submitExamFinal() {
        clearInterval(timerInterval);
        clearInterval(syncInterval);
        isSubmitting = true;
        document.getElementById('submitExamForm').submit();
    }

    document.getElementById('submitExamForm').addEventListener('submit', function() {
        isSubmitting = true;
        const btn = document.getElementById('btnConfirmSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengumpulkan...';
    });

    // Prevent accidental page leave warning
    window.addEventListener('beforeunload', function(e) {
        if (!isSubmitting) {
            e.preventDefault();
            e.returnValue = 'Ujian sedang berlangsung. Apakah Anda yakin ingin meninggalkan halaman?';
        }
    });

    // Initial render
    updateTimerUI();
    renderCurrentQuestion();

    // ==========================================================
    // Proctoring Live Camera Stream & AI Engine in Exam Room
    // ==========================================================
    const ENABLE_PROCTORING = <?= !empty($exam['enable_proctoring']) ? 'true' : 'false'; ?>;
    let roomCameraStream = null;
    let aiEngine = null;

    if (ENABLE_PROCTORING && navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user' },
            audio: false
        })
        .then(stream => {
            roomCameraStream = stream;
            const videoEl = document.getElementById('roomCameraVideo');
            if (videoEl) {
                videoEl.srcObject = stream;
            }

            // Initialize AI Proctoring Engine
            if (window.AiProctoringEngine && videoEl) {
                aiEngine = new AiProctoringEngine({
                    examId: EXAM_ID,
                    participantId: <?= (int)$exam['participant_id']; ?>,
                    videoElement: videoEl,
                    statusElement: document.getElementById('aiFaceBadge'),
                    apiEndpoint: BASE_URL + '/student/logProctoringEvent'
                });
                aiEngine.start();
            }

            // Listen for disconnect / track ended
            const track = stream.getVideoTracks()[0];
            if (track) {
                track.onended = () => {
                    console.warn('[Room Proctoring] Camera disconnected!');
                    const banner = document.getElementById('cameraDisconnectBanner');
                    if (banner) banner.classList.remove('d-none');
                    const dot = document.getElementById('proctoringDot');
                    if (dot) dot.className = 'camera-status-dot dot-offline';
                    const text = document.getElementById('proctoringStatusText');
                    if (text) text.textContent = 'Kamera Terputus';

                    // Log CAMERA_DISCONNECTED event to server
                    if (aiEngine) {
                        aiEngine.sendEvent('CAMERA_DISCONNECTED', 'WARNING', { detail: 'Camera video track ended or was unplugged' });
                    }
                };
            }
        })
        .catch(err => {
            console.warn('[Room Proctoring] Camera access denied or not found:', err);
            const banner = document.getElementById('cameraDisconnectBanner');
            if (banner) banner.classList.remove('d-none');
            const dot = document.getElementById('proctoringDot');
            if (dot) dot.className = 'camera-status-dot dot-offline';
            const text = document.getElementById('proctoringStatusText');
            if (text) text.textContent = 'Kamera Tidak Aktif';
        });

        // Hardware device changes
        navigator.mediaDevices.addEventListener('devicechange', () => {
            console.log('[Room Proctoring] Device change detected.');
        });
    }

    // Stop streams & AI engine before submit or unload
    function stopRoomCamera() {
        if (aiEngine) {
            aiEngine.stop();
            aiEngine = null;
        }
        if (roomCameraStream) {
            roomCameraStream.getTracks().forEach(t => t.stop());
            roomCameraStream = null;
        }
    }

    document.getElementById('submitExamForm').addEventListener('submit', stopRoomCamera);
    window.addEventListener('beforeunload', stopRoomCamera);
    </script>
</body>
</html>
