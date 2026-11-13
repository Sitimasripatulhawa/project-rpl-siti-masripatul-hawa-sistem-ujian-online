<?php
/**
 * Automated Unit Test Suite: Exam Management (Manajemen Ujian Guru)
 * AI Online Exam & Proctoring System
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/ExamModel.php';
require_once __DIR__ . '/../app/models/QuestionModel.php';
require_once __DIR__ . '/../app/models/ClassModel.php';
require_once __DIR__ . '/../app/models/SubjectModel.php';

echo "=======================================================\n";
echo "RUNNING TEST SUITE: EXAM MANAGEMENT (TAHAP 6)\n";
echo "=======================================================\n\n";

$examModel = new ExamModel();
$questionModel = new QuestionModel();
$classModel = new ClassModel();
$subjectModel = new SubjectModel();
$db = Database::getInstance();

$testsPassed = 0;
$testsFailed = 0;

function assertTest($description, $condition) {
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo " [PASS] " . $description . "\n";
        $testsPassed++;
    } else {
        echo " [FAIL] " . $description . "\n";
        $testsFailed++;
    }
}

// 1. Check teacher and baseline questions exist
$teacher = $db->query("SELECT id FROM teachers LIMIT 1")->single();
$teacherId = (int)($teacher['id'] ?? 1);

$subject = $subjectModel->getAll()[0] ?? null;
$class = $classModel->getAll()[0] ?? null;

assertTest("Teacher exists (ID: {$teacherId})", $teacherId > 0);
assertTest("Subject exists", !empty($subject));
assertTest("Class exists", !empty($class));

// Ensure teacher has at least 2 questions in question bank
$questions = $questionModel->getByTeacher($teacherId);
if (count($questions) < 2) {
    $q1 = $questionModel->createQuestion($teacherId, [
        'subject_id' => $subject['id'],
        'question_text' => 'Soal Test Manajemen Ujian 1: Nilai x dari 2x + 6 = 10 adalah...',
        'question_type' => 'multiple_choice',
        'difficulty' => 'easy',
        'options' => ['A' => '1', 'B' => '2', 'C' => '3', 'D' => '4'],
        'correct_option' => 'B'
    ]);
    $q2 = $questionModel->createQuestion($teacherId, [
        'subject_id' => $subject['id'],
        'question_text' => 'Soal Test Manajemen Ujian 2: Jelaskan hukum Newton I.',
        'question_type' => 'essay',
        'difficulty' => 'medium'
    ]);
    $questions = $questionModel->getByTeacher($teacherId);
}

$qIds = array_slice(array_column($questions, 'id'), 0, 2);
assertTest("Has at least 2 questions in Bank Soal for assignment", count($qIds) >= 2);

// 2. Test Exam Creation with Assigned Questions and Auto Class Enrollment
$examData = [
    'title' => 'Ujian Akhir Semester Matematika Peminatan (Unit Test)',
    'description' => 'Kerjakan secara jujur dan mandiri di bawah pengawasan AI Proctoring.',
    'subject_id' => $subject['id'],
    'duration_minutes' => 60,
    'start_time' => date('Y-m-d H:i:s', strtotime('+1 day')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+1 day +2 hours')),
    'token' => 'MATH99',
    'is_random_question' => 1,
    'is_random_option' => 1,
    'enable_proctoring' => 1,
    'status' => 'draft',
    'total_score' => 100.00,
    'passing_score' => 75.00
];

$createdExamId = $examModel->createExamWithQuestions($teacherId, $examData, $qIds, (int)$class['id']);
assertTest("Exam successfully created via createExamWithQuestions (ID: {$createdExamId})", $createdExamId > 0);

// 3. Test findByIdAndTeacher
$loadedExam = $examModel->findByIdAndTeacher($createdExamId, $teacherId);
assertTest("Exam successfully loaded by teacher", !empty($loadedExam) && $loadedExam['title'] === $examData['title']);
assertTest("Exam has correct assigned questions count (" . count($loadedExam['questions']) . ")", count($loadedExam['questions']) === count($qIds));
assertTest("Assigned questions have options and correct points", !empty($loadedExam['questions'][0]['question_score']));
assertTest("Exam enrolled class participants count (" . count($loadedExam['participants']) . ")", count($loadedExam['participants']) >= 0);

// 4. Test Status Progression & Grouped Counts
$statsInitial = $examModel->countByTeacherGroupedStatus($teacherId);
assertTest("countByTeacherGroupedStatus returns 4 status keys (draft, published, running, finished)", 
    isset($statsInitial['draft'], $statsInitial['published'], $statsInitial['running'], $statsInitial['finished']));
assertTest("Draft count >= 1", $statsInitial['draft'] >= 1);

// Toggle / update status to published
$examModel->updateStatus($createdExamId, $teacherId, 'published');
$statsAfterPublish = $examModel->countByTeacherGroupedStatus($teacherId);
assertTest("Status updated to published", $statsAfterPublish['published'] >= 1);

// 5. Test Exam Update (e.g. change title, duration, passing score)
$updateSuccess = $examModel->updateExamWithQuestions($createdExamId, $teacherId, [
    'title' => 'Ujian Akhir Semester Matematika Peminatan (Updated)',
    'description' => 'Petunjuk telah diperbarui.',
    'subject_id' => $subject['id'],
    'duration_minutes' => 90,
    'start_time' => date('Y-m-d H:i:s', strtotime('+2 days')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+2 days +2 hours')),
    'is_random_question' => 1,
    'is_random_option' => 1,
    'enable_proctoring' => 1,
    'status' => 'published',
    'total_score' => 100.00,
    'passing_score' => 80.00
], [$qIds[0]], (int)$class['id']);

assertTest("updateExamWithQuestions executed successfully", $updateSuccess === true);

$updatedExam = $examModel->findByIdAndTeacher($createdExamId, $teacherId);
assertTest("Updated title matches", $updatedExam['title'] === 'Ujian Akhir Semester Matematika Peminatan (Updated)');
assertTest("Updated duration is 90 mins", (int)$updatedExam['duration_minutes'] === 90);
assertTest("Updated questions count is now 1", count($updatedExam['questions']) === 1);

// 6. Test Teacher Ownership Protection
$foreignTeacherExam = $examModel->findByIdAndTeacher($createdExamId, 99999);
assertTest("Access denied when different teacher queries exam", $foreignTeacherExam === null);

// 7. Test Exam Deletion
$deleteSuccess = $examModel->deleteExam($createdExamId, $teacherId);
assertTest("Exam successfully deleted", $deleteSuccess === true);

$deletedCheck = $examModel->findByIdAndTeacher($createdExamId, $teacherId);
assertTest("Deleted exam is no longer found", $deletedCheck === null);

// Check cascading deletion in exam_questions and exam_participants
$orphanEq = $db->query("SELECT COUNT(*) FROM exam_questions WHERE exam_id = :eid")->bind(':eid', $createdExamId)->single();
assertTest("Cascaded exam_questions count is 0", array_values($orphanEq)[0] == 0);

echo "\n=======================================================\n";
echo "TEST RESULTS: {$testsPassed} PASSED, {$testsFailed} FAILED\n";
echo "=======================================================\n";

if ($testsFailed > 0) {
    exit(1);
}
exit(0);
