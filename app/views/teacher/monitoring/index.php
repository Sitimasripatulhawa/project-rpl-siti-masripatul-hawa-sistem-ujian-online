<?php
require_once __DIR__ . '/../../layouts/header.php';
$selectedId = (int)($selected_exam['id'] ?? 0);
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

            <!-- Page Header & Controls -->
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h1 class="h4 fw-bold text-dark mb-0">Live Monitoring Ujian</h1>
                        <span class="badge bg-danger text-white text-xs d-flex align-items-center gap-1">
                            <span class="camera-status-dot dot-online bg-white"></span> LIVE
                        </span>
                    </div>
                    <p class="text-muted text-sm mb-0">Pemantauan aktivitas, status kamera, dan integritas peserta secara real-time.</p>
                </div>

                <!-- Exam Selector & Live Controls -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- Exam Selector Dropdown -->
                    <div class="d-flex align-items-center gap-2">
                        <label for="selectExam" class="text-xs fw-semibold text-muted text-nowrap d-none d-sm-inline">Pilih Ujian:</label>
                        <select id="selectExam" class="form-select form-select-sm" style="min-width: 240px;" onchange="changeExam(this.value)">
                            <?php if (empty($exams)): ?>
                                <option value="">Belum ada ujian</option>
                            <?php else: ?>
                                <?php foreach ($exams as $ex): ?>
                                    <option value="<?= $ex['id']; ?>" <?= ($ex['id'] == $selectedId) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($ex['title']); ?> (<?= htmlspecialchars($ex['class_name']); ?>)
                                        <?php if (!empty($ex['active_count'])): ?> • <?= $ex['active_count']; ?> Aktif<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Auto Refresh Toggle Button -->
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnToggleRefresh" onclick="toggleAutoRefresh()">
                        <i class="bi bi-arrow-repeat me-1" id="refreshIcon"></i>
                        <span id="refreshBtnText">Auto-Refresh (5s)</span>
                    </button>

                    <!-- Manual Refresh Button -->
                    <button type="button" class="btn btn-sm btn-light border" id="btnManualRefresh" onclick="fetchMonitoringData(true)" title="Refresh Data Sekarang">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>

            <?php if (!$selected_exam): ?>
                <!-- Empty State -->
                <div class="custom-card text-center py-5">
                    <div class="stat-icon stat-icon-indigo mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.6rem;">
                        <i class="bi bi-broadcast"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Belum Ada Ujian yang Dipilih</h5>
                    <p class="text-muted text-sm mb-3">Silakan buat ujian terlebih dahulu atau pilih ujian aktif melalui dropdown di atas.</p>
                    <a href="<?= BASE_URL; ?>/teacher/examCreate" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-plus-lg me-1"></i> Buat Ujian Baru
                    </a>
                </div>
            <?php else: ?>

                <!-- Aggregated Stat Summary Cards -->
                <div class="row g-3 mb-4" id="statsRow">
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="custom-card p-3 text-center">
                            <div class="text-xs text-muted fw-semibold text-uppercase">Total Peserta</div>
                            <div class="h3 fw-bold text-dark mb-0 mt-1" id="statTotal"><?= $summary['total']; ?></div>
                            <small class="text-muted" style="font-size: 0.72rem;">Terdaftar</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="custom-card p-3 text-center border-success-subtle bg-success-subtle bg-opacity-10">
                            <div class="text-xs text-success fw-semibold text-uppercase">Online / Aktif</div>
                            <div class="h3 fw-bold text-success mb-0 mt-1" id="statOnline"><?= $summary['online']; ?></div>
                            <small class="text-muted" style="font-size: 0.72rem;">Sedang Mengerjakan</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="custom-card p-3 text-center">
                            <div class="text-xs text-primary fw-semibold text-uppercase">Selesai</div>
                            <div class="h3 fw-bold text-primary mb-0 mt-1" id="statCompleted"><?= $summary['completed']; ?></div>
                            <small class="text-muted" style="font-size: 0.72rem;">Sudah Submit</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="custom-card p-3 text-center">
                            <div class="text-xs text-secondary fw-semibold text-uppercase">Belum Mulai</div>
                            <div class="h3 fw-bold text-secondary mb-0 mt-1" id="statNotStarted"><?= $summary['not_started']; ?></div>
                            <small class="text-muted" style="font-size: 0.72rem;">Menunggu Masuk</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="custom-card p-3 text-center border-warning-subtle bg-warning-subtle bg-opacity-25">
                            <div class="text-xs text-warning-emphasis fw-semibold text-uppercase">Warning (Indikasi)</div>
                            <div class="h3 fw-bold text-warning-emphasis mb-0 mt-1" id="statWarning"><?= $summary['warning']; ?></div>
                            <small class="text-muted" style="font-size: 0.72rem;">Perlu Pantauan</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="custom-card p-3 text-center border-danger-subtle bg-danger-subtle bg-opacity-25">
                            <div class="text-xs text-danger fw-semibold text-uppercase">High Risk</div>
                            <div class="h3 fw-bold text-danger mb-0 mt-1" id="statHighRisk"><?= $summary['high_risk']; ?></div>
                            <small class="text-muted" style="font-size: 0.72rem;">Needs Review</small>
                        </div>
                    </div>
                </div>

                <!-- Filter Tabs & Search Row -->
                <div class="custom-card p-3 mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <!-- Filter Tabs -->
                        <ul class="nav nav-pills nav-fill flex-nowrap overflow-auto text-sm" id="filterTabs">
                            <li class="nav-item">
                                <button class="nav-link py-1 px-3 active" data-filter="all" onclick="setFilter('all', this)">
                                    Semua Peserta (<span id="countFilterAll"><?= count($participants); ?></span>)
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-1 px-3" data-filter="active" onclick="setFilter('active', this)">
                                    Sedang Ujian (<span id="countFilterActive"><?= $summary['online']; ?></span>)
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-1 px-3 text-warning-emphasis" data-filter="attention" onclick="setFilter('attention', this)">
                                    Perlu Review (<span id="countFilterAttention"><?= $summary['warning'] + $summary['high_risk']; ?></span>)
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-1 px-3" data-filter="completed" onclick="setFilter('completed', this)">
                                    Selesai (<span id="countFilterCompleted"><?= $summary['completed']; ?></span>)
                                </button>
                            </li>
                        </ul>

                        <!-- Search Box -->
                        <div class="input-group input-group-sm" style="max-width: 250px;">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="searchStudent" class="form-control border-start-0" placeholder="Cari nama atau NIS..." onkeyup="filterParticipants()">
                        </div>
                    </div>
                </div>

                <!-- Participant Monitoring Cards Grid -->
                <div class="row g-3" id="participantsGrid">
                    <!-- Cards are dynamically rendered via JS / initial PHP below -->
                </div>

                <!-- Empty Search / Filter State -->
                <div id="noResultsState" class="custom-card text-center py-5 d-none">
                    <i class="bi bi-person-x text-muted fs-1 mb-2 d-block"></i>
                    <h6 class="fw-bold text-dark mb-1">Tidak Ada Peserta yang Sesuai</h6>
                    <p class="text-muted text-xs mb-0">Coba ubah kata kunci pencarian atau tab filter di atas.</p>
                </div>

            <?php endif; ?>
        </main>
    </div>
</div>

<!-- ============================================================== -->
<!-- Participant Detail & Timeline Modal                           -->
<!-- ============================================================== -->
<div class="modal fade" id="participantModal" tabindex="-1" aria-labelledby="participantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="modalStudentName">Detail & Timeline Pengawasan</h5>
                    <small class="text-muted" id="modalStudentSub">NIS: - | Kelas: -</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Summary Badges in Modal -->
                <div class="row g-2 mb-4">
                    <div class="col-6 col-sm-3">
                        <div class="p-2 border rounded-3 bg-light text-center">
                            <div class="text-xs text-muted">Status Sesi</div>
                            <span class="badge bg-secondary text-xs mt-1" id="modalStatusBadge">-</span>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="p-2 border rounded-3 bg-light text-center">
                            <div class="text-xs text-muted">Level Risiko</div>
                            <span class="badge bg-secondary text-xs mt-1" id="modalRiskBadge">-</span>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="p-2 border rounded-3 bg-light text-center">
                            <div class="text-xs text-muted">Camera 1 (Utama)</div>
                            <span class="badge bg-secondary text-xs mt-1" id="modalCam1Badge">-</span>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="p-2 border rounded-3 bg-light text-center">
                            <div class="text-xs text-muted">Camera 2 (Ruang)</div>
                            <span class="badge bg-secondary text-xs mt-1" id="modalCam2Badge">-</span>
                        </div>
                    </div>
                </div>

                <!-- Progress Details in Modal -->
                <div class="p-3 border rounded-3 bg-light mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-xs fw-semibold text-dark">Progres Pengerjaan Soal:</span>
                        <span class="text-xs fw-bold text-primary" id="modalProgressText">0 / 0 Soal (0%)</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" id="modalProgressBar" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 text-xs text-muted">
                        <span>Ditandai Ragu: <strong id="modalDoubtText">0</strong> soal</span>
                        <span>Sisa Waktu: <strong id="modalRemainingText">00:00:00</strong></span>
                    </div>
                </div>

                <!-- Event Summary Breakdown Box in Modal -->
                <div class="p-3 border rounded-3 bg-light mb-4" id="modalEventSummaryBox">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-xs fw-bold text-dark"><i class="bi bi-shield-shaded text-primary me-1"></i>Ringkasan Anomali / Event Terdeteksi:</span>
                        <span class="badge bg-light text-dark border text-xs" id="modalRiskScoreText">Skor Risiko: 0 / 100</span>
                    </div>
                    <div class="row g-2 text-xs" id="modalEventSummaryRow">
                        <!-- Populated dynamically -->
                    </div>
                </div>

                <!-- Timeline Section -->
                <div>
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history text-primary me-2"></i>Linimasa Aktivitas & Event Pengawasan</h6>
                    <div id="timelineContainer">
                        <div class="text-center py-4 text-muted text-sm" id="timelineLoading">
                            <span class="spinner-border spinner-border-sm me-2"></span> Memuat data linimasa...
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4 justify-content-between">
                <span class="text-xs text-muted" id="modalFooterHint">Indikasi dicatat untuk bahan evaluasi guru.</span>
                <div>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
                    <a href="#" class="btn btn-primary btn-sm px-3" id="modalReviewBtn" target="_blank">
                        <i class="bi bi-journal-text me-1"></i> Review Jawaban
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const CURRENT_EXAM_ID = <?= $selectedId; ?>;
const BASE_URL = '<?= BASE_URL; ?>';
let participantsData = <?= json_encode($participants, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
let activeFilter = 'all';
let isAutoRefresh = true;
let refreshTimer = null;
let countdownSec = 5;

// Change Selected Exam via dropdown
function changeExam(examId) {
    if (examId) {
        window.location.href = BASE_URL + '/teacher/monitoring/' + examId;
    }
}

// Set Filter Tab
function setFilter(filter, btn) {
    activeFilter = filter;
    document.querySelectorAll('#filterTabs .nav-link').forEach(el => el.classList.remove('active'));
    if (btn) btn.classList.add('active');
    filterParticipants();
}

// Render Participants Grid
function renderGrid(list) {
    const grid = document.getElementById('participantsGrid');
    const noResults = document.getElementById('noResultsState');
    if (!grid) return;

    if (!list || list.length === 0) {
        grid.innerHTML = '';
        if (noResults) noResults.classList.remove('d-none');
        return;
    }

    if (noResults) noResults.classList.add('d-none');

    let html = '';
    list.forEach(p => {
        // Border card by risk
        let borderClass = '';
        if (p.risk_level === 'HIGH RISK') borderClass = 'border-danger';
        else if (p.risk_level === 'WARNING') borderClass = 'border-warning';

        // Online dot & status badge
        let statusBadge = '';
        if (p.participant_status === 'submitted') {
            statusBadge = '<span class="badge bg-primary-subtle text-primary text-xs">Selesai</span>';
        } else if (p.is_online) {
            statusBadge = '<span class="badge bg-success-subtle text-success text-xs d-flex align-items-center gap-1"><span class="camera-status-dot dot-online"></span> Aktif</span>';
        } else if (p.participant_status === 'in_progress') {
            statusBadge = '<span class="badge bg-secondary-subtle text-secondary text-xs">Idle</span>';
        } else {
            statusBadge = '<span class="badge bg-light text-muted border text-xs">Belum Mulai</span>';
        }

        // Risk badge
        let riskBadge = '<span class="badge bg-success text-white text-xs">NORMAL</span>';
        if (p.risk_level === 'WARNING') {
            riskBadge = '<span class="badge bg-warning text-dark text-xs"><i class="bi bi-exclamation-triangle-fill me-1"></i>WARNING</span>';
        } else if (p.risk_level === 'HIGH RISK') {
            riskBadge = '<span class="badge bg-danger text-white text-xs"><i class="bi bi-shield-fill-exclamation me-1"></i>HIGH RISK</span>';
        }

        // Event summary pill in card
        let summaryPill = '';
        if (p.event_summary) {
            let pills = [];
            if (p.event_summary['Multiple Face'] > 0) pills.push(`Multiple Face: ${p.event_summary['Multiple Face']}`);
            if (p.event_summary['Face Missing'] > 0) pills.push(`Face Missing: ${p.event_summary['Face Missing']}`);
            if (p.event_summary['Camera Disconnect'] > 0) pills.push(`Cam Disconnect: ${p.event_summary['Camera Disconnect']}`);
            if (p.event_summary['Suspicious Gaze'] > 0) pills.push(`Gaze: ${p.event_summary['Suspicious Gaze']}`);
            if (p.event_summary['Face Changed'] > 0) pills.push(`Face Changed: ${p.event_summary['Face Changed']}`);
            if (p.event_summary['Camera Blocked'] > 0) pills.push(`Cam Blocked: ${p.event_summary['Camera Blocked']}`);
            if (pills.length > 0) {
                summaryPill = `
                <div class="mt-2 pt-1 border-top text-xs text-muted" style="font-size: 0.68rem;">
                    <i class="bi bi-shield-exclamation text-warning me-1"></i>${pills.join(' • ')}
                </div>`;
            }
        }

        // Camera badges
        let cam1Class = (p.camera1_status === 'Aktif') ? 'text-success' : ((p.camera1_status === 'Terputus') ? 'text-danger fw-bold' : 'text-muted');
        let cam2Class = (p.camera2_status === 'Aktif') ? 'text-success' : 'text-muted';

        html += `
        <div class="col-12 col-md-6 col-xl-4 participant-item" data-id="${p.participant_id}">
            <div class="monitoring-card ${borderClass}" onclick="openParticipantModal(${p.participant_id})">
                <div class="monitoring-card-header">
                    <div class="d-flex align-items-center gap-2">
                        <div class="monitoring-avatar-circle">
                            ${(p.student_name || 'U').substring(0, 1).toUpperCase()}
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0 text-truncate" style="max-width: 170px;">${escapeHtml(p.student_name)}</h6>
                            <span class="text-xs text-muted">NIS: ${escapeHtml(p.nis)}</span>
                        </div>
                    </div>
                    <div>
                        ${statusBadge}
                    </div>
                </div>
                <div class="monitoring-card-body">
                    <!-- Progress Bar -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center text-xs mb-1">
                            <span class="text-muted">Progres: <strong>${p.answered_count} / ${p.total_questions}</strong> Soal</span>
                            <span class="fw-bold text-primary">${p.progress_percent}%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar ${p.progress_percent === 100 ? 'bg-success' : 'bg-primary'}" role="progressbar" style="width: ${p.progress_percent}%;"></div>
                        </div>
                    </div>

                    <!-- Meta info row: Remaining Time & Cameras -->
                    <div class="d-flex justify-content-between align-items-center mb-2 text-xs">
                        <span class="text-muted">
                            <i class="bi bi-clock me-1"></i> Sisa: <strong class="text-dark">${p.participant_status === 'submitted' ? 'Selesai' : p.remaining_formatted}</strong>
                        </span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="${cam1Class}" title="Kamera Utama: ${p.camera1_status}">
                                <i class="bi bi-webcam"></i> Cam 1
                            </span>
                            <span class="${cam2Class}" title="Kamera Sekunder: ${p.camera2_status}">
                                <i class="bi bi-camera-video"></i> Cam 2
                            </span>
                        </div>
                    </div>

                    <!-- Proctoring Risk & Last Activity row -->
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top text-xs">
                        <div class="d-flex align-items-center gap-1">
                            ${riskBadge}
                            <span class="badge bg-light text-dark border" style="font-size: 0.68rem;" title="Skor Risiko (0-100)">
                                Skor: <strong>${p.risk_score}</strong>
                            </span>
                        </div>
                        <div class="text-muted" style="font-size: 0.72rem;">
                            <i class="bi bi-activity me-1"></i> Aktif: <strong>${p.last_activity}</strong>
                        </div>
                    </div>
                    ${summaryPill}
                </div>
            </div>
        </div>`;
    });

    grid.innerHTML = html;
}

// Filter and Search
function filterParticipants() {
    const searchVal = (document.getElementById('searchStudent')?.value || '').toLowerCase().trim();

    const filtered = participantsData.filter(p => {
        // Tab filter
        let matchTab = true;
        if (activeFilter === 'active') matchTab = (p.is_online && p.participant_status === 'in_progress');
        else if (activeFilter === 'attention') matchTab = (p.risk_level === 'WARNING' || p.risk_level === 'HIGH RISK');
        else if (activeFilter === 'completed') matchTab = (p.participant_status === 'submitted');

        // Search filter
        let matchSearch = true;
        if (searchVal) {
            const name = (p.student_name || '').toLowerCase();
            const nis = (p.nis || '').toLowerCase();
            matchSearch = name.includes(searchVal) || nis.includes(searchVal);
        }

        return matchTab && matchSearch;
    });

    renderGrid(filtered);
}

// Open Participant Modal & Load Timeline
function openParticipantModal(participantId) {
    const p = participantsData.find(item => item.participant_id == participantId);
    if (!p) return;

    // Fill Modal Header & Summary
    document.getElementById('modalStudentName').textContent = p.student_name;
    document.getElementById('modalStudentSub').textContent = `NIS: ${p.nis} | Kelas: ${p.class_name}`;
    document.getElementById('modalStatusBadge').textContent = p.online_status_text;
    document.getElementById('modalStatusBadge').className = 'badge text-xs mt-1 ' + (p.is_online ? 'bg-success' : (p.participant_status === 'submitted' ? 'bg-primary' : 'bg-secondary'));

    document.getElementById('modalRiskBadge').textContent = `${p.risk_label} (${p.risk_score} Poin)`;
    document.getElementById('modalRiskBadge').className = 'badge text-xs mt-1 ' + (p.risk_level === 'HIGH RISK' ? 'bg-danger' : (p.risk_level === 'WARNING' ? 'bg-warning text-dark' : 'bg-success'));

    document.getElementById('modalCam1Badge').textContent = p.camera1_status;
    document.getElementById('modalCam1Badge').className = 'badge text-xs mt-1 ' + (p.camera1_status === 'Aktif' ? 'bg-success' : (p.camera1_status === 'Terputus' ? 'bg-danger' : 'bg-secondary'));

    document.getElementById('modalCam2Badge').textContent = p.camera2_status;
    document.getElementById('modalCam2Badge').className = 'badge text-xs mt-1 ' + (p.camera2_status === 'Aktif' ? 'bg-success' : 'bg-secondary');

    document.getElementById('modalProgressText').textContent = `${p.answered_count} / ${p.total_questions} Soal (${p.progress_percent}%)`;
    document.getElementById('modalProgressBar').style.width = p.progress_percent + '%';
    document.getElementById('modalDoubtText').textContent = p.doubt_count;
    document.getElementById('modalRemainingText').textContent = p.participant_status === 'submitted' ? 'Selesai' : p.remaining_formatted;

    document.getElementById('modalRiskScoreText').textContent = `Skor Risiko: ${p.risk_score} / 100`;

    // Render Event Summary Row
    const summaryRow = document.getElementById('modalEventSummaryRow');
    if (summaryRow) {
        const evs = p.event_summary || {};
        summaryRow.innerHTML = `
            <div class="col-4 col-sm-2 text-center p-1 border rounded bg-white">
                <div class="text-muted" style="font-size: 0.68rem;">Multiple Face</div>
                <strong class="${(evs['Multiple Face'] || 0) > 0 ? 'text-danger' : 'text-dark'}">${evs['Multiple Face'] || 0}</strong>
            </div>
            <div class="col-4 col-sm-2 text-center p-1 border rounded bg-white">
                <div class="text-muted" style="font-size: 0.68rem;">Face Missing</div>
                <strong class="${(evs['Face Missing'] || 0) > 0 ? 'text-warning-emphasis' : 'text-dark'}">${evs['Face Missing'] || 0}</strong>
            </div>
            <div class="col-4 col-sm-2 text-center p-1 border rounded bg-white">
                <div class="text-muted" style="font-size: 0.68rem;">Cam Disconnect</div>
                <strong class="${(evs['Camera Disconnect'] || 0) > 0 ? 'text-danger' : 'text-dark'}">${evs['Camera Disconnect'] || 0}</strong>
            </div>
            <div class="col-4 col-sm-2 text-center p-1 border rounded bg-white">
                <div class="text-muted" style="font-size: 0.68rem;">Suspicious Gaze</div>
                <strong class="${(evs['Suspicious Gaze'] || 0) > 0 ? 'text-warning-emphasis' : 'text-dark'}">${evs['Suspicious Gaze'] || 0}</strong>
            </div>
            <div class="col-4 col-sm-2 text-center p-1 border rounded bg-white">
                <div class="text-muted" style="font-size: 0.68rem;">Camera Blocked</div>
                <strong class="${(evs['Camera Blocked'] || 0) > 0 ? 'text-warning-emphasis' : 'text-dark'}">${evs['Camera Blocked'] || 0}</strong>
            </div>
            <div class="col-4 col-sm-2 text-center p-1 border rounded bg-white">
                <div class="text-muted" style="font-size: 0.68rem;">Face Changed</div>
                <strong class="${(evs['Face Changed'] || 0) > 0 ? 'text-danger' : 'text-dark'}">${evs['Face Changed'] || 0}</strong>
            </div>
        `;
    }

    document.getElementById('modalReviewBtn').href = BASE_URL + '/teacher/reviewAnswers/' + p.participant_id;

    // Show modal
    const modalEl = document.getElementById('participantModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    // Fetch Timeline
    const container = document.getElementById('timelineContainer');
    container.innerHTML = '<div class="text-center py-4 text-muted text-sm"><span class="spinner-border spinner-border-sm me-2"></span> Memuat linimasa event...</div>';

    fetch(BASE_URL + '/teacher/participantTimelineData/' + participantId)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.timeline && data.timeline.length > 0) {
                let timelineHtml = '<div class="timeline-list">';
                data.timeline.forEach(ev => {
                    let markerClass = 'marker-success';
                    if (ev.severity === 'CRITICAL') markerClass = 'marker-danger';
                    else if (ev.severity === 'WARNING') markerClass = 'marker-warning';

                    let snapshotBtn = '';
                    if (ev.snapshot_path) {
                        snapshotBtn = `
                        <div class="mt-2">
                            <a href="${BASE_URL}/${escapeHtml(ev.snapshot_path)}" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2 text-xs">
                                <i class="bi bi-image me-1"></i>Lihat Foto Bukti
                            </a>
                        </div>`;
                    }

                    timelineHtml += `
                    <div class="timeline-item">
                        <div class="timeline-marker ${markerClass}">
                            <i class="bi bi-dot"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark text-xs">${escapeHtml(ev.title)}</span>
                                <span class="badge ${ev.badge_class} text-xs">${ev.time_str}</span>
                            </div>
                            <p class="text-muted text-xs mb-0">${escapeHtml(ev.description)}</p>
                            ${snapshotBtn}
                        </div>
                    </div>`;
                });
                timelineHtml += '</div>';
                container.innerHTML = timelineHtml;
            } else {
                container.innerHTML = `
                    <div class="alert alert-light border text-center py-3 text-muted text-xs rounded-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i> Belum ada aktivitas atau event pengawasan yang tercatat.
                    </div>`;
            }
        })
        .catch(err => {
            container.innerHTML = '<div class="alert alert-danger text-xs py-2 px-3">Gagal memuat linimasa peserta.</div>';
        });
}

// Fetch Latest Monitoring Data from Server (Polling)
function fetchMonitoringData(isManual = false) {
    if (!CURRENT_EXAM_ID) return;
    // Performance optimization: skip auto-polling if tab is hidden/minimized
    if (!isManual && document.hidden) return;

    if (isManual) {
        const icon = document.getElementById('btnManualRefresh')?.querySelector('i');
        if (icon) icon.classList.add('spin');
    }

    fetch(BASE_URL + '/teacher/monitoringData/' + CURRENT_EXAM_ID)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.participants) {
                participantsData = data.participants;

                // Update summary stats
                if (data.summary) {
                    document.getElementById('statTotal').textContent = data.summary.total;
                    document.getElementById('statOnline').textContent = data.summary.online;
                    document.getElementById('statCompleted').textContent = data.summary.completed;
                    document.getElementById('statNotStarted').textContent = data.summary.not_started;
                    document.getElementById('statWarning').textContent = data.summary.warning;
                    document.getElementById('statHighRisk').textContent = data.summary.high_risk;

                    document.getElementById('countFilterAll').textContent = data.summary.total;
                    document.getElementById('countFilterActive').textContent = data.summary.online;
                    document.getElementById('countFilterAttention').textContent = data.summary.warning + data.summary.high_risk;
                    document.getElementById('countFilterCompleted').textContent = data.summary.completed;
                }

                // Re-render grid with current filter
                filterParticipants();
            }
        })
        .catch(err => {
            console.warn('[LiveMonitoring] Polling error:', err);
        })
        .finally(() => {
            if (isManual) {
                const icon = document.getElementById('btnManualRefresh')?.querySelector('i');
                if (icon) icon.classList.remove('spin');
            }
        });
}

// Toggle Auto Refresh
function toggleAutoRefresh() {
    isAutoRefresh = !isAutoRefresh;
    const btn = document.getElementById('btnToggleRefresh');
    const text = document.getElementById('refreshBtnText');
    if (isAutoRefresh) {
        btn.className = 'btn btn-sm btn-outline-primary';
        text.textContent = 'Auto-Refresh (5s)';
        startPolling();
    } else {
        btn.className = 'btn btn-sm btn-outline-secondary';
        text.textContent = 'Auto-Refresh: Jeda';
        stopPolling();
    }
}

function startPolling() {
    stopPolling();
    refreshTimer = setInterval(() => {
        fetchMonitoringData(false);
    }, 5000);
}

function stopPolling() {
    if (refreshTimer) {
        clearInterval(refreshTimer);
        refreshTimer = null;
    }
}

// Helper escape HTML
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    filterParticipants();
    startPolling();
});

window.addEventListener('beforeunload', stopPolling);
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
