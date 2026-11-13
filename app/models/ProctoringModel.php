<?php
/**
 * Proctoring Model
 * AI Online Exam & Proctoring System (Tahap 10 & 12)
 */

require_once __DIR__ . '/../services/ProctoringRiskService.php';

class ProctoringModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Log a proctoring event with server-side debounce check
     *
     * @param int $examParticipantId
     * @param int|null $examId
     * @param int|null $studentId
     * @param string $eventType
     * @param string $severity INFO | WARNING | CRITICAL
     * @param array|null $metadata
     * @param string|null $snapshotPath
     * @param float $riskWeight
     * @param string $cameraSource
     * @param int $cooldownSeconds Cooldown window to prevent event spam
     * @return array
     */
    public function logEvent(
        int $examParticipantId,
        ?int $examId,
        ?int $studentId,
        string $eventType,
        string $severity = 'WARNING',
        ?array $metadata = null,
        ?string $snapshotPath = null,
        float $riskWeight = 1.00,
        string $cameraSource = 'primary_camera',
        int $cooldownSeconds = 10
    ): array {
        $severity = strtoupper($severity);
        if (!in_array($severity, ['INFO', 'WARNING', 'CRITICAL'])) {
            $severity = 'WARNING';
        }

        $allowedEvents = [
            'FACE_NOT_DETECTED',
            'MULTIPLE_FACE',
            'CAMERA_DISCONNECTED',
            'CAMERA_BLOCKED',
            'SUSPICIOUS_GAZE',
            'FACE_CHANGED',
            'TAB_SWITCH'
        ];
        if (!in_array($eventType, $allowedEvents, true)) {
            return [
                'logged' => false,
                'reason' => 'invalid_event_type',
                'message' => 'Event type not permitted'
            ];
        }

        // 1. Server-side Debounce: check if same event occurred recently
        if ($cooldownSeconds === 10 && class_exists('SettingModel')) {
            $cooldownSeconds = (int)SettingModel::get('proctoring_cooldown_seconds', 10);
        }

        if ($cooldownSeconds > 0) {
            $recent = $this->db->query("
                SELECT id, event_time, TIMESTAMPDIFF(SECOND, event_time, NOW()) AS elapsed_seconds
                FROM proctoring_events
                WHERE exam_participant_id = :pid AND event_type = :etype
                ORDER BY event_time DESC, id DESC
                LIMIT 1
            ")
            ->bind(':pid', $examParticipantId)
            ->bind(':etype', $eventType)
            ->single();

            if ($recent && isset($recent['elapsed_seconds']) && (int)$recent['elapsed_seconds'] < $cooldownSeconds) {
                return [
                    'logged' => false,
                    'reason' => 'cooldown_active',
                    'elapsed_seconds' => (int)$recent['elapsed_seconds'],
                    'cooldown_required' => $cooldownSeconds
                ];
            }
        }

        // 2. Insert event record
        $metadataJson = $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;
        $eventTime = date('Y-m-d H:i:s');

        $this->db->query("
            INSERT INTO proctoring_events (
                exam_id,
                student_id,
                exam_participant_id,
                event_type,
                severity,
                risk_weight,
                snapshot_path,
                camera_source,
                metadata_json,
                event_time
            ) VALUES (
                :exam_id,
                :student_id,
                :participant_id,
                :event_type,
                :severity,
                :risk_weight,
                :snapshot_path,
                :camera_source,
                :metadata_json,
                :event_time
            )
        ")
        ->bind(':exam_id', $examId)
        ->bind(':student_id', $studentId)
        ->bind(':participant_id', $examParticipantId)
        ->bind(':event_type', $eventType)
        ->bind(':severity', $severity)
        ->bind(':risk_weight', $riskWeight)
        ->bind(':snapshot_path', $snapshotPath)
        ->bind(':camera_source', $cameraSource)
        ->bind(':metadata_json', $metadataJson)
        ->bind(':event_time', $eventTime)
        ->execute();

        $eventId = (int)$this->db->lastInsertId();

        // 3. Update participant risk_score using centralized ProctoringRiskService
        $riskService = new ProctoringRiskService();
        $riskData = $riskService->calculateRiskByParticipantId($examParticipantId, true);

        return [
            'logged' => true,
            'event_id' => $eventId,
            'event_type' => $eventType,
            'severity' => $severity,
            'risk_score' => $riskData['risk_score'],
            'risk_level' => $riskData['risk_level'],
            'timestamp' => $eventTime
        ];
    }

    /**
     * Get all logged proctoring events for a specific participant
     */
    public function getEventsByParticipant(int $examParticipantId): array
    {
        return $this->db->query("
            SELECT pe.*, s.nis, u.full_name AS student_name
            FROM proctoring_events pe
            JOIN exam_participants ep ON pe.exam_participant_id = ep.id
            JOIN students s ON ep.student_id = s.id
            JOIN users u ON s.user_id = u.id
            WHERE pe.exam_participant_id = :pid
            ORDER BY pe.event_time ASC, pe.id ASC
        ")
        ->bind(':pid', $examParticipantId)
        ->resultSet();
    }

    /**
     * Get all events for an exam
     */
    public function getEventsByExam(int $examId, int $limit = 200): array
    {
        return $this->db->query("
            SELECT pe.*, s.nis, u.full_name AS student_name, ep.status AS participant_status
            FROM proctoring_events pe
            JOIN exam_participants ep ON pe.exam_participant_id = ep.id
            JOIN students s ON ep.student_id = s.id
            JOIN users u ON s.user_id = u.id
            WHERE pe.exam_id = :exam_id
            ORDER BY pe.event_time DESC, pe.id DESC
            LIMIT :lim
        ")
        ->bind(':exam_id', $examId)
        ->bind(':lim', $limit)
        ->resultSet();
    }

    /**
     * Get risk summary statistics for a participant
     */
    public function getRiskSummary(int $examParticipantId): array
    {
        $counts = $this->db->query("
            SELECT 
                COUNT(*) AS total_events,
                SUM(CASE WHEN severity = 'INFO' THEN 1 ELSE 0 END) AS info_count,
                SUM(CASE WHEN severity = 'WARNING' THEN 1 ELSE 0 END) AS warning_count,
                SUM(CASE WHEN severity = 'CRITICAL' THEN 1 ELSE 0 END) AS critical_count
            FROM proctoring_events
            WHERE exam_participant_id = :pid
        ")
        ->bind(':pid', $examParticipantId)
        ->single();

        $riskService = new ProctoringRiskService();
        $riskData = $riskService->calculateRiskByParticipantId($examParticipantId, false);

        $totalEvents = (int)($counts['total_events'] ?? 0);
        $infoCount = (int)($counts['info_count'] ?? 0);
        $warningCount = (int)($counts['warning_count'] ?? 0);
        $criticalCount = (int)($counts['critical_count'] ?? 0);

        // Compatibility mapping for existing review view
        $riskLevelCompat = 'low';
        if ($riskData['risk_level'] === 'HIGH RISK') {
            $riskLevelCompat = 'high';
        } elseif ($riskData['risk_level'] === 'WARNING') {
            $riskLevelCompat = 'medium';
        }

        return [
            'total_events' => $totalEvents,
            'info_count' => $infoCount,
            'warning_count' => $warningCount,
            'critical_count' => $criticalCount,
            'risk_score' => $riskData['risk_score'],
            'risk_level' => $riskLevelCompat,
            'risk_level_standard' => $riskData['risk_level'],
            'risk_label' => $riskData['risk_label'],
            'event_summary' => $riskData['event_summary'],
            'needs_review' => $riskData['needs_review']
        ];
    }

    /**
     * Get live monitoring participants data for an exam
     */
    public function getExamLiveParticipants(int $examId): array
    {
        // 1. Get exam info
        $exam = $this->db->query("SELECT id, duration_minutes, start_time, end_time, enable_proctoring FROM exams WHERE id = :eid LIMIT 1")
            ->bind(':eid', $examId)
            ->single();
        if (!$exam) return [];

        // 2. Total questions count
        $totalQuestions = (int)$this->db->query("SELECT COUNT(*) AS total FROM exam_questions WHERE exam_id = :eid")
            ->bind(':eid', $examId)
            ->single()['total'];
        if ($totalQuestions <= 0) $totalQuestions = 1;

        // 3. Query all participants
        $participants = $this->db->query("
            SELECT 
                ep.id AS participant_id,
                ep.exam_id,
                ep.student_id,
                ep.status AS participant_status,
                ep.start_time,
                ep.submit_time,
                ep.risk_score,
                ep.updated_at,
                s.nis,
                s.gender,
                u.full_name AS student_name,
                u.avatar,
                c.name AS class_name
            FROM exam_participants ep
            JOIN students s ON ep.student_id = s.id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE ep.exam_id = :eid
            ORDER BY 
                CASE ep.status 
                    WHEN 'in_progress' THEN 1 
                    WHEN 'not_started' THEN 2 
                    WHEN 'submitted' THEN 3 
                    ELSE 4 
                END,
                ep.risk_score DESC,
                u.full_name ASC
        ")
        ->bind(':eid', $examId)
        ->resultSet();

        if (empty($participants)) {
            return [];
        }

        $now = time();
        $participantIds = array_map('intval', array_column($participants, 'participant_id'));
        $pidList = implode(',', $participantIds);

        // --- BATCH QUERY 1: Answer Stats ---
        $ansStatsRows = $this->db->query("
            SELECT 
                exam_participant_id,
                COUNT(*) AS answered_count,
                SUM(CASE WHEN is_flagged_doubt = 1 THEN 1 ELSE 0 END) AS doubt_count,
                MAX(answered_at) AS last_answered_at
            FROM exam_answers
            WHERE exam_participant_id IN ({$pidList}) 
              AND (selected_option_id IS NOT NULL OR (essay_answer IS NOT NULL AND essay_answer != ''))
            GROUP BY exam_participant_id
        ")->resultSet();

        $ansStatsMap = [];
        foreach ($ansStatsRows as $ar) {
            $ansStatsMap[(int)$ar['exam_participant_id']] = $ar;
        }

        // --- BATCH QUERY 2: Proctoring Event Aggregates (Warning/Critical) ---
        $eventStatsRows = $this->db->query("
            SELECT 
                exam_participant_id,
                COUNT(*) AS total_events,
                SUM(CASE WHEN severity = 'WARNING' THEN 1 ELSE 0 END) AS warning_count,
                SUM(CASE WHEN severity = 'CRITICAL' THEN 1 ELSE 0 END) AS critical_count,
                MAX(event_time) AS last_event_at
            FROM proctoring_events
            WHERE exam_participant_id IN ({$pidList})
            GROUP BY exam_participant_id
        ")->resultSet();

        $eventStatsMap = [];
        foreach ($eventStatsRows as $er) {
            $eventStatsMap[(int)$er['exam_participant_id']] = $er;
        }

        // --- BATCH QUERY 3: Latest Event Per Participant ---
        $latestEventRows = $this->db->query("
            SELECT pe.exam_participant_id, pe.event_type, pe.severity, pe.event_time, pe.camera_source
            FROM proctoring_events pe
            INNER JOIN (
                SELECT exam_participant_id, MAX(id) AS max_id
                FROM proctoring_events
                WHERE exam_participant_id IN ({$pidList})
                GROUP BY exam_participant_id
            ) latest ON pe.id = latest.max_id
        ")->resultSet();

        $latestEventMap = [];
        foreach ($latestEventRows as $lr) {
            $latestEventMap[(int)$lr['exam_participant_id']] = $lr;
        }

        // --- BATCH QUERY 4: Event Counts for Risk Score Calculation ---
        $riskCountRows = $this->db->query("
            SELECT exam_participant_id, event_type, COUNT(*) AS count
            FROM proctoring_events
            WHERE exam_participant_id IN ({$pidList})
            GROUP BY exam_participant_id, event_type
        ")->resultSet();

        $riskCountsMap = [];
        foreach ($riskCountRows as $rc) {
            $pid = (int)$rc['exam_participant_id'];
            if (!isset($riskCountsMap[$pid])) {
                $riskCountsMap[$pid] = [];
            }
            $riskCountsMap[$pid][$rc['event_type']] = (int)$rc['count'];
        }

        // --- BATCH QUERY 5: Camera Disconnect & Reconnect Status ---
        $enableProc = (bool)$exam['enable_proctoring'];
        $camDisconnectMap = [];
        $camReconnectMap = [];

        if ($enableProc) {
            $camDisRows = $this->db->query("
                SELECT exam_participant_id, MAX(event_time) AS last_disconnect_time
                FROM proctoring_events
                WHERE exam_participant_id IN ({$pidList}) 
                  AND event_type = 'CAMERA_DISCONNECTED'
                GROUP BY exam_participant_id
            ")->resultSet();

            foreach ($camDisRows as $dr) {
                $camDisconnectMap[(int)$dr['exam_participant_id']] = $dr['last_disconnect_time'];
            }

            if (!empty($camDisconnectMap)) {
                $disPids = implode(',', array_keys($camDisconnectMap));
                $recRows = $this->db->query("
                    SELECT exam_participant_id, MAX(event_time) AS last_reconnect_time
                    FROM proctoring_events
                    WHERE exam_participant_id IN ({$disPids})
                      AND event_type IN ('FACE_DETECTED', 'NORMAL', 'CAMERA_CONNECTED')
                    GROUP BY exam_participant_id
                ")->resultSet();

                foreach ($recRows as $rr) {
                    $camReconnectMap[(int)$rr['exam_participant_id']] = $rr['last_reconnect_time'];
                }
            }
        }

        // In-memory construction of participant objects
        $riskService = new ProctoringRiskService();
        $result = [];

        foreach ($participants as $p) {
            $pid = (int)$p['participant_id'];

            // Answer stats
            $ansStats = $ansStatsMap[$pid] ?? null;
            $answeredCount = (int)($ansStats['answered_count'] ?? 0);
            $doubtCount = (int)($ansStats['doubt_count'] ?? 0);
            $lastAnsweredAt = $ansStats['last_answered_at'] ?? null;
            $progressPercent = ($totalQuestions > 0) ? min(100, round(($answeredCount / $totalQuestions) * 100)) : 0;

            // Proctoring event stats
            $eventStats = $eventStatsMap[$pid] ?? null;
            $warningCount = (int)($eventStats['warning_count'] ?? 0);
            $criticalCount = (int)($eventStats['critical_count'] ?? 0);
            $lastEventAt = $eventStats['last_event_at'] ?? null;

            // Latest event row
            $latestEventRow = $latestEventMap[$pid] ?? null;

            // Calculate remaining seconds
            $remainingSeconds = 0;
            $status = $p['participant_status'];
            if ($status === 'in_progress' && !empty($p['start_time'])) {
                $durationSec = (int)$exam['duration_minutes'] * 60;
                $elapsed = $now - strtotime($p['start_time']);
                $remainingSeconds = max(0, $durationSec - $elapsed);
                if (!empty($exam['end_time'])) {
                    $untilExamEnd = strtotime($exam['end_time']) - $now;
                    if ($untilExamEnd < $remainingSeconds) {
                        $remainingSeconds = max(0, $untilExamEnd);
                    }
                }
            }

            // Determine last activity timestamp
            $candidates = array_filter([$lastAnsweredAt, $lastEventAt, $p['start_time'], $p['updated_at']]);
            $lastActivityTimestamp = !empty($candidates) ? max(array_map('strtotime', $candidates)) : null;
            $lastActivityStr = $lastActivityTimestamp ? date('H:i:s', $lastActivityTimestamp) : '-';
            $secondsSinceLastActivity = $lastActivityTimestamp ? ($now - $lastActivityTimestamp) : 99999;

            // Determine Online status
            $isOnline = false;
            $onlineStatusText = 'Belum Mulai';
            if ($status === 'in_progress') {
                if ($secondsSinceLastActivity <= 60) {
                    $isOnline = true;
                    $onlineStatusText = 'Online / Aktif';
                } else {
                    $onlineStatusText = 'Idle / Tidak Aktif';
                }
            } elseif ($status === 'submitted') {
                $onlineStatusText = 'Selesai';
            }

            // Determine Camera 1 & Camera 2 status
            $camera1Status = 'Tidak Diaktifkan';
            $camera2Status = 'Tidak Diaktifkan';

            if ($enableProc) {
                if ($status === 'in_progress') {
                    if (isset($camDisconnectMap[$pid])) {
                        $lastDis = $camDisconnectMap[$pid];
                        $lastRec = $camReconnectMap[$pid] ?? null;
                        if (!$lastRec || strtotime($lastRec) <= strtotime($lastDis)) {
                            $camera1Status = 'Terputus';
                        } else {
                            $camera1Status = 'Aktif';
                        }
                    } else {
                        $camera1Status = 'Aktif';
                    }
                    $camera2Status = 'Mode Tunggal';
                } elseif ($status === 'submitted') {
                    $camera1Status = 'Nonaktif (Selesai)';
                    $camera2Status = 'Nonaktif';
                } else {
                    $camera1Status = 'Menunggu Sesi';
                    $camera2Status = 'Menunggu Sesi';
                }
            }

            // In-memory risk calculation from pre-aggregated counts
            $pRiskCounts = $riskCountsMap[$pid] ?? [];
            $riskData = $riskService->calculateRiskFromEventCounts($pRiskCounts, (int)$exam['id'], (int)$p['student_id'], $pid);

            $result[] = [
                'participant_id' => $pid,
                'student_id' => (int)$p['student_id'],
                'student_name' => $p['student_name'],
                'nis' => $p['nis'],
                'class_name' => $p['class_name'] ?? '-',
                'avatar' => $p['avatar'] ?? null,
                'gender' => $p['gender'] ?? 'male',
                'participant_status' => $status,
                'is_online' => $isOnline,
                'online_status_text' => $onlineStatusText,
                'total_questions' => $totalQuestions,
                'answered_count' => $answeredCount,
                'doubt_count' => $doubtCount,
                'progress_percent' => $progressPercent,
                'remaining_seconds' => $remainingSeconds,
                'remaining_formatted' => sprintf('%02d:%02d:%02d', floor($remainingSeconds / 3600), floor(($remainingSeconds % 3600) / 60), $remainingSeconds % 60),
                'camera1_status' => $camera1Status,
                'camera2_status' => $camera2Status,
                'risk_score' => $riskData['risk_score'],
                'risk_level' => $riskData['risk_level'],
                'risk_label' => $riskData['risk_label'],
                'event_summary' => $riskData['event_summary'],
                'needs_review' => $riskData['needs_review'],
                'warning_count' => $warningCount,
                'critical_count' => $criticalCount,
                'last_activity' => $lastActivityStr,
                'seconds_since_last_activity' => $secondsSinceLastActivity,
                'latest_event' => $latestEventRow ? ($latestEventRow['event_type'] . ' (' . $latestEventRow['severity'] . ')') : '-'
            ];
        }

        return $result;
    }

    /**
     * Get aggregated monitoring summary for an exam
     */
    public function getExamMonitoringSummary(int $examId, ?array $participants = null): array
    {
        if ($participants === null) {
            $participants = $this->getExamLiveParticipants($examId);
        }
        $total = count($participants);
        $online = 0;
        $completed = 0;
        $notStarted = 0;
        $normal = 0;
        $warning = 0;
        $highRisk = 0;

        foreach ($participants as $p) {
            if ($p['participant_status'] === 'submitted') {
                $completed++;
            } elseif ($p['participant_status'] === 'not_started') {
                $notStarted++;
            } elseif ($p['is_online']) {
                $online++;
            }

            if ($p['risk_level'] === 'HIGH RISK') {
                $highRisk++;
            } elseif ($p['risk_level'] === 'WARNING') {
                $warning++;
            } else {
                $normal++;
            }
        }

        return [
            'total' => $total,
            'online' => $online,
            'completed' => $completed,
            'not_started' => $notStarted,
            'normal' => $normal,
            'warning' => $warning,
            'high_risk' => $highRisk
        ];
    }

    /**
     * Get participant full chronological timeline events
     */
    public function getParticipantTimeline(int $participantId): array
    {
        $timeline = [];

        // 1. Participant info
        $participant = $this->db->query("
            SELECT ep.start_time, ep.submit_time, ep.status, u.full_name
            FROM exam_participants ep
            JOIN students s ON ep.student_id = s.id
            JOIN users u ON s.user_id = u.id
            WHERE ep.id = :pid LIMIT 1
        ")
        ->bind(':pid', $participantId)
        ->single();

        if ($participant && !empty($participant['start_time'])) {
            $timeline[] = [
                'timestamp' => strtotime($participant['start_time']),
                'time_str' => date('H:i:s', strtotime($participant['start_time'])),
                'event_type' => 'NORMAL',
                'severity' => 'INFO',
                'badge_class' => 'bg-success text-white',
                'title' => 'Memulai Ujian',
                'description' => 'Peserta berhasil masuk ke ruang ujian dan memulai pengerjaan.',
                'snapshot_path' => null
            ];
        }

        // 2. Proctoring events
        $events = $this->db->query("
            SELECT event_type, severity, event_time, metadata_json, snapshot_path, camera_source
            FROM proctoring_events
            WHERE exam_participant_id = :pid
            ORDER BY event_time ASC, id ASC
        ")
        ->bind(':pid', $participantId)
        ->resultSet();

        $readableTitleMap = [
            'FACE_NOT_DETECTED' => 'Wajah Tidak Terdeteksi (Indikasi)',
            'MULTIPLE_FACE' => 'Lebih Dari 1 Wajah Terdeteksi (Needs Review)',
            'CAMERA_DISCONNECTED' => 'Kamera Terputus / Tidak Aktif (Indikasi)',
            'CAMERA_BLOCKED' => 'Kamera Tertutup / Gelap Gulita (Indikasi)',
            'SUSPICIOUS_GAZE' => 'Pandangan Berpaling Keluar Layar (Indikasi)',
            'FACE_CHANGED' => 'Indikasi Perubahan Wajah Peserta (Needs Review)'
        ];

        foreach ($events as $ev) {
            $sev = strtoupper($ev['severity'] ?? 'WARNING');
            $badgeClass = 'bg-warning text-dark';
            if ($sev === 'CRITICAL') $badgeClass = 'bg-danger text-white';
            if ($sev === 'INFO') $badgeClass = 'bg-info text-dark';

            $meta = !empty($ev['metadata_json']) ? json_decode($ev['metadata_json'], true) : [];
            $descParts = [];
            if ($meta) {
                foreach ($meta as $k => $v) {
                    $descParts[] = "$k: " . (is_array($v) ? json_encode($v) : $v);
                }
            }
            $desc = implode(', ', $descParts);

            $timeline[] = [
                'timestamp' => strtotime($ev['event_time']),
                'time_str' => date('H:i:s', strtotime($ev['event_time'])),
                'event_type' => $ev['event_type'],
                'severity' => $sev,
                'badge_class' => $badgeClass,
                'title' => $readableTitleMap[$ev['event_type']] ?? $ev['event_type'],
                'description' => $desc ?: 'Peringatan pengawasan tercatat secara otomatis oleh sistem.',
                'snapshot_path' => $ev['snapshot_path'] ?? null
            ];
        }

        // 3. Submit event if submitted
        if ($participant && !empty($participant['submit_time'])) {
            $timeline[] = [
                'timestamp' => strtotime($participant['submit_time']),
                'time_str' => date('H:i:s', strtotime($participant['submit_time'])),
                'event_type' => 'NORMAL',
                'severity' => 'INFO',
                'badge_class' => 'bg-primary text-white',
                'title' => 'Mengumpulkan Ujian',
                'description' => 'Peserta telah mengumpulkan lembar jawaban ujian.',
                'snapshot_path' => null
            ];
        }

        // Sort chronologically
        usort($timeline, function($a, $b) {
            return $a['timestamp'] <=> $b['timestamp'];
        });

        return $timeline;
    }
}
