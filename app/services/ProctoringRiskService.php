<?php
/**
 * Proctoring Risk Service
 * AI Online Exam & Proctoring System (Tahap 12)
 * 
 * Centralized service to calculate and evaluate student proctoring risk score,
 * risk level (NORMAL, WARNING, HIGH RISK), and event summary based on configurable
 * event weights and thresholds.
 */

class ProctoringRiskService
{
    private Database $db;
    private array $weights;
    private array $thresholds;

    /**
     * Default event weights
     */
    public const DEFAULT_WEIGHTS = [
        'FACE_NOT_DETECTED' => 10,
        'MULTIPLE_FACE' => 40,
        'CAMERA_DISCONNECTED' => 20,
        'FACE_CHANGED' => 50,
        'SUSPICIOUS_GAZE' => 15,
        'CAMERA_BLOCKED' => 15,
        'TAB_SWITCH' => 10
    ];

    /**
     * Default risk level thresholds:
     * 0–29   = NORMAL
     * 30–59  = WARNING
     * 60–100 = HIGH RISK
     */
    public const DEFAULT_THRESHOLDS = [
        'normal_max' => 29,
        'warning_max' => 59,
        'high_risk_min' => 60
    ];

    /**
     * Constructor with optional custom weights and thresholds
     *
     * @param array|null $customWeights
     * @param array|null $customThresholds
     */
    public function __construct(?array $customWeights = null, ?array $customThresholds = null)
    {
        $this->db = Database::getInstance();
        $baseWeights = class_exists('SettingModel') ? SettingModel::getProctoringWeights() : self::DEFAULT_WEIGHTS;
        $baseThresholds = class_exists('SettingModel') ? SettingModel::getProctoringThresholds() : self::DEFAULT_THRESHOLDS;
        $this->weights = $customWeights !== null ? array_merge($baseWeights, $customWeights) : $baseWeights;
        $this->thresholds = $customThresholds !== null ? array_merge($baseThresholds, $customThresholds) : $baseThresholds;
    }

    /**
     * Calculate risk score, risk level, and event summary for a specific exam and student
     *
     * @param int $examId
     * @param int $studentId
     * @param bool $syncToDb Whether to update exam_participants.risk_score in DB
     * @return array
     */
    public function calculateRisk(int $examId, int $studentId, bool $syncToDb = true): array
    {
        // 1. Find participant ID
        $participant = $this->db->query("
            SELECT id, status, risk_score 
            FROM exam_participants 
            WHERE exam_id = :eid AND student_id = :sid 
            LIMIT 1
        ")
        ->bind(':eid', $examId)
        ->bind(':sid', $studentId)
        ->single();

        if (!$participant) {
            return $this->buildEmptyRiskResponse($examId, $studentId, 0);
        }

        $participantId = (int)$participant['id'];
        return $this->calculateRiskByParticipantId($participantId, $syncToDb);
    }

    /**
     * Calculate risk score by participant ID directly
     *
     * @param int $participantId
     * @param bool $syncToDb
     * @return array
     */
    public function calculateRiskByParticipantId(int $participantId, bool $syncToDb = true): array
    {
        // 1. Get participant and exam info
        $participant = $this->db->query("
            SELECT ep.id, ep.exam_id, ep.student_id, ep.status, ep.risk_score
            FROM exam_participants ep
            WHERE ep.id = :pid
            LIMIT 1
        ")
        ->bind(':pid', $participantId)
        ->single();

        if (!$participant) {
            return $this->buildEmptyRiskResponse(0, 0, $participantId);
        }

        $examId = (int)$participant['exam_id'];
        $studentId = (int)$participant['student_id'];

        // 2. Query event counts grouped by event_type
        $eventRows = $this->db->query("
            SELECT event_type, COUNT(*) AS count
            FROM proctoring_events
            WHERE exam_participant_id = :pid
            GROUP BY event_type
        ")
        ->bind(':pid', $participantId)
        ->resultSet();

        // 3. Map event counts
        $eventCounts = [];
        foreach ($eventRows as $row) {
            $type = strtoupper(trim((string)$row['event_type']));
            $eventCounts[$type] = (int)$row['count'];
        }

        $result = $this->calculateRiskFromEventCounts($eventCounts, $examId, $studentId, $participantId);

        // Sync to DB if requested
        if ($syncToDb) {
            $this->syncRiskScoreToParticipant($participantId, (float)$result['risk_score']);
        }

        return $result;
    }

    /**
     * Calculate risk score directly from event counts in memory (zero DB queries)
     *
     * @param array $rawEventCounts e.g. ['MULTIPLE_FACE' => 1, 'FACE_NOT_DETECTED' => 2]
     * @param int $examId
     * @param int $studentId
     * @param int $participantId
     * @return array
     */
    public function calculateRiskFromEventCounts(array $rawEventCounts, int $examId = 0, int $studentId = 0, int $participantId = 0): array
    {
        $rawScore = 0;
        $eventCounts = [
            'FACE_NOT_DETECTED' => 0,
            'MULTIPLE_FACE' => 0,
            'CAMERA_DISCONNECTED' => 0,
            'FACE_CHANGED' => 0,
            'SUSPICIOUS_GAZE' => 0,
            'CAMERA_BLOCKED' => 0,
            'TAB_SWITCH' => 0
        ];

        foreach ($rawEventCounts as $type => $count) {
            $normalizedType = strtoupper(trim((string)$type));
            $count = (int)$count;
            $eventCounts[$normalizedType] = $count;

            $weight = $this->weights[$normalizedType] ?? 10;
            $rawScore += ($count * $weight);
        }

        // Cap score at maximum 100
        $riskScore = min(100, max(0, $rawScore));

        // Determine risk level based on configured thresholds
        $riskLevel = $this->determineRiskLevel($riskScore);

        // Build human-readable event summary
        $eventSummary = [
            'Multiple Face' => $eventCounts['MULTIPLE_FACE'] ?? 0,
            'Face Missing' => $eventCounts['FACE_NOT_DETECTED'] ?? 0,
            'Camera Disconnect' => $eventCounts['CAMERA_DISCONNECTED'] ?? 0,
            'Suspicious Gaze' => $eventCounts['SUSPICIOUS_GAZE'] ?? 0,
            'Face Changed' => $eventCounts['FACE_CHANGED'] ?? 0,
            'Camera Blocked' => $eventCounts['CAMERA_BLOCKED'] ?? 0
        ];

        // Descriptive risk label
        $riskLabelMap = [
            'NORMAL' => 'Normal',
            'WARNING' => 'Warning (Indikasi)',
            'HIGH RISK' => 'High Risk (Needs Review)'
        ];
        $riskLabel = $riskLabelMap[$riskLevel] ?? 'Normal';

        return [
            'exam_id' => $examId,
            'student_id' => $studentId,
            'participant_id' => $participantId,
            'risk_score' => (int)$riskScore,
            'risk_level' => $riskLevel,
            'risk_label' => $riskLabel,
            'event_summary' => $eventSummary,
            'event_counts' => $eventCounts,
            'needs_review' => ($riskLevel === 'HIGH RISK'),
            'weights_used' => $this->weights,
            'thresholds_used' => $this->thresholds
        ];
    }

    /**
     * Determine risk level from score using configured thresholds:
     * 0–29   = NORMAL
     * 30–59  = WARNING
     * 60–100 = HIGH RISK
     *
     * @param int|float $score
     * @return string NORMAL | WARNING | HIGH RISK
     */
    public function determineRiskLevel($score): string
    {
        $score = (float)$score;
        if ($score <= $this->thresholds['normal_max']) {
            return 'NORMAL';
        } elseif ($score <= $this->thresholds['warning_max']) {
            return 'WARNING';
        } else {
            return 'HIGH RISK';
        }
    }

    /**
     * Update participant's risk_score in database
     *
     * @param int $participantId
     * @param float $score
     * @return void
     */
    public function syncRiskScoreToParticipant(int $participantId, float $score): void
    {
        $this->db->query("
            UPDATE exam_participants 
            SET risk_score = :score 
            WHERE id = :pid
        ")
        ->bind(':score', $score)
        ->bind(':pid', $participantId)
        ->execute();
    }

    /**
     * Get currently active event weights
     */
    public function getWeights(): array
    {
        return $this->weights;
    }

    /**
     * Set custom event weights
     */
    public function setWeights(array $weights): void
    {
        $this->weights = array_merge($this->weights, $weights);
    }

    /**
     * Get currently active score thresholds
     */
    public function getThresholds(): array
    {
        return $this->thresholds;
    }

    /**
     * Set custom score thresholds
     */
    public function setThresholds(array $thresholds): void
    {
        $this->thresholds = array_merge($this->thresholds, $thresholds);
    }

    /**
     * Build default response when no participant exists
     */
    private function buildEmptyRiskResponse(int $examId, int $studentId, int $participantId): array
    {
        return [
            'exam_id' => $examId,
            'student_id' => $studentId,
            'participant_id' => $participantId,
            'risk_score' => 0,
            'risk_level' => 'NORMAL',
            'risk_label' => 'Normal',
            'event_summary' => [
                'Multiple Face' => 0,
                'Face Missing' => 0,
                'Camera Disconnect' => 0,
                'Suspicious Gaze' => 0,
                'Face Changed' => 0,
                'Camera Blocked' => 0
            ],
            'event_counts' => [],
            'needs_review' => false,
            'weights_used' => $this->weights,
            'thresholds_used' => $this->thresholds
        ];
    }
}
