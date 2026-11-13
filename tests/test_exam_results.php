<?php
/**
 * Test Suite: Tahap 8 Exam Results System
 * AI Online Exam & Proctoring System
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/models/ExamModel.php';
require_once __DIR__ . '/../app/models/ExamEngineModel.php';
require_once __DIR__ . '/../app/models/ResultModel.php';
require_once __DIR__ . '/../app/models/QuestionModel.php';

$baseUrl = 'http://localhost/Project2';
$cookieTeacher = sys_get_temp_dir() . '/test_tr_res_' . uniqid() . '.txt';
$cookieStudent = sys_get_temp_dir() . '/test_st_res_' . uniqid() . '.txt';
$cookieAdmin = sys_get_temp_dir() . '/test_ad_res_' . uniqid() . '.txt';

$passed = 0;
$failed = 0;

function assertResultTest($description, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] " . $description . "\n";
        $passed++;
    } else {
        echo " [FAIL] " . $description . "\n";
        $failed++;
    }
}

function httpCall($url, $method = 'GET', $postFields = [], $cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'headers' => $headers,
        'body' => $body
    ];
}

echo "=======================================================\n";
echo "RUNNING TEST SUITE: EXAM RESULTS & ESSAY SCORING (TAHAP 8)\n";
echo "=======================================================\n\n";

$db = Database::getInstance();

// -------------------------------------------------------------------
// 0. Setup Test Data: Create Essay Question and Combined Exam
// -------------------------------------------------------------------
echo "--- 0. Preparing Questions & Exam Data ---\n";

// Check if an essay question exists for teacher 1, if not create one
$essayQ = $db->query("SELECT id FROM questions WHERE question_type = 'essay' AND teacher_id = 1 LIMIT 1")->single();
if (!$essayQ) {
    $db->query("
        INSERT INTO questions (subject_id, teacher_id, question_text, question_type, difficulty, default_score, explanation)
        VALUES (1, 1, 'Jelaskan teorema limit pusat dan penerapannya dalam statistik!', 'essay', 'medium', 20.00, 'Teorema limit pusat menyatakan bahwa rata-rata sampel akan mendekati distribusi normal.')
    ")->execute();
    $essayQuestionId = (int)$db->query("SELECT LAST_INSERT_ID() AS id")->single()['id'];
} else {
    $essayQuestionId = (int)$essayQ['id'];
}
assertResultTest("Essay question available (ID: {$essayQuestionId})", $essayQuestionId > 0);

// Clean previous Tahap 8 test exams
$db->query("DELETE FROM exams WHERE title LIKE '%Tahap 8 Exam%'")->execute();

// Create Exam with 2 MCQ and 1 Essay:
// Q1 (MCQ), Q2 (MCQ), EssayQ (Essay)
$examModel = new ExamModel();
$examData = [
    'title' => 'Tahap 8 Exam Results Testing',
    'description' => 'Ujian uji coba fitur hasil dan penilaian essay.',
    'subject_id' => 1,
    'class_id' => 1,
    'duration_minutes' => 60,
    'start_time' => date('Y-m-d H:i:s', strtotime('-2 hours')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+5 hours')),
    'token' => 'RES123',
    'is_random_question' => 0,
    'is_random_option' => 0,
    'enable_proctoring' => 0,
    'status' => 'published',
    'total_score' => 100.00,
    'passing_score' => 50.00
];

$assignedQuestions = [1, 2, $essayQuestionId];
$examId = $examModel->createExamWithQuestions(1, $examData, $assignedQuestions, 1);
assertResultTest("Created test exam with ID {$examId}", $examId > 0);

// Adjust scores: each question gets 33.33 pts or let's customize eq
$db->query("UPDATE exam_questions SET question_score = 40.00 WHERE exam_id = :eid AND question_id = 1")->bind(':eid', $examId)->execute();
$db->query("UPDATE exam_questions SET question_score = 40.00 WHERE exam_id = :eid AND question_id = 2")->bind(':eid', $examId)->execute();
$db->query("UPDATE exam_questions SET question_score = 20.00 WHERE exam_id = :eid AND question_id = :qid")->bind(':eid', $examId)->bind(':qid', $essayQuestionId)->execute();

// Get participant Ani (student 1)
$participantAni = $db->query("SELECT id FROM exam_participants WHERE exam_id = :eid AND student_id = 1")
    ->bind(':eid', $examId)
    ->single();
$participantIdAni = (int)$participantAni['id'];

// -------------------------------------------------------------------
// 1. Scoring Test (MCQ Auto-Score & Essay Pending)
// -------------------------------------------------------------------
echo "\n--- 1. Testing Automatic Scoring & Essay Pending Status ---\n";
// Student Ani login
httpCall($baseUrl . '/auth/authenticate', 'POST', [
    'username' => 'siswa_ani',
    'password' => 'password123'
], $cookieStudent);

// Student Ani starts exam
httpCall($baseUrl . '/student/startExam/' . $examId, 'POST', ['token' => 'RES123'], $cookieStudent);
httpCall($baseUrl . '/student/enterExam/' . $examId, 'POST', [], $cookieStudent);

// Answer Q1 correctly (Option ID 1 is correct for Q1)
httpCall($baseUrl . '/student/saveAnswer', 'POST', [
    'exam_id' => $examId,
    'question_id' => 1,
    'option_id' => 1
], $cookieStudent);

// Answer Q2 incorrectly (Option ID 6 is wrong for Q2)
httpCall($baseUrl . '/student/saveAnswer', 'POST', [
    'exam_id' => $examId,
    'question_id' => 2,
    'option_id' => 6
], $cookieStudent);

// Answer Q3 (Essay)
httpCall($baseUrl . '/student/saveAnswer', 'POST', [
    'exam_id' => $examId,
    'question_id' => $essayQuestionId,
    'essay_answer' => 'Teorema limit pusat menyatakan bahwa distribusi rata-rata sampel berukuran besar akan mendekati normal.'
], $cookieStudent);

// Submit Exam
$submitRes = httpCall($baseUrl . '/student/submitExam/' . $examId, 'POST', [], $cookieStudent);
assertResultTest("Student submitted exam successfully", $submitRes['code'] === 302);

// Check initial scoring in DB
$initialResult = $db->query("SELECT * FROM exam_results WHERE exam_participant_id = :pid")->bind(':pid', $participantIdAni)->single();
assertResultTest("Auto-scoring: Q1 correct awarded 40 pts, Q2 wrong 0 pts, initial final_score is 40.00", 
    (float)$initialResult['final_score'] == 40.00);
assertResultTest("Auto-scoring: Initial KKM status is 0 (40.00 < 50.00)", (int)$initialResult['is_passed'] === 0);

// Check essay answer status: is_correct should be NULL (Menunggu Penilaian)
$essayAnsRow = $db->query("SELECT * FROM exam_answers WHERE exam_participant_id = :pid AND question_id = :qid")
    ->bind(':pid', $participantIdAni)
    ->bind(':qid', $essayQuestionId)
    ->single();
assertResultTest("Essay answer in DB has is_correct = NULL ('Menunggu Penilaian')", $essayAnsRow['is_correct'] === null);
assertResultTest("Essay answer in DB has initial awarded_score = 0.00", (float)$essayAnsRow['awarded_score'] == 0.00);

// -------------------------------------------------------------------
// 2. Teacher Results & Statistics Test
// -------------------------------------------------------------------
echo "\n--- 2. Testing Teacher Results View & Statistics ---\n";
// Teacher Login
$trLogin = httpCall($baseUrl . '/auth/authenticate', 'POST', [
    'username' => 'guru_budi',
    'password' => 'password123'
], $cookieTeacher);
assertResultTest("Teacher login successful", $trLogin['code'] === 302);

// Access Result List
$trResultsRes = httpCall($baseUrl . '/teacher/results', 'GET', [], $cookieTeacher);
assertResultTest("Teacher results list returned HTTP 200", $trResultsRes['code'] === 200);
assertResultTest("Teacher results list contains test exam title", strpos($trResultsRes['body'], 'Tahap 8 Exam Results Testing') !== false);
assertResultTest("Teacher results list flags pending essay", strpos($trResultsRes['body'], 'Essay Pending') !== false);

// Access Result Detail for this Exam
$detailRes = httpCall($baseUrl . '/teacher/examResultDetail/' . $examId, 'GET', [], $cookieTeacher);
assertResultTest("Teacher exam result detail returned HTTP 200", $detailRes['code'] === 200);

// Verify 4 Simple Statistics displayed:
assertResultTest("Result detail displays 'Nilai Tertinggi'", strpos($detailRes['body'], 'Nilai Tertinggi') !== false);
assertResultTest("Result detail displays 'Nilai Terendah'", strpos($detailRes['body'], 'Nilai Terendah') !== false);
assertResultTest("Result detail displays 'Nilai Rata-rata'", strpos($detailRes['body'], 'Nilai Rata-rata') !== false);
assertResultTest("Result detail displays 'Jumlah Peserta'", strpos($detailRes['body'], 'Jumlah Peserta') !== false);
assertResultTest("Result detail lists student Ani Lestari", strpos($detailRes['body'], 'Ani Lestari') !== false);

// -------------------------------------------------------------------
// 3. Answer Review & Essay Manual Scoring Test
// -------------------------------------------------------------------
echo "\n--- 3. Testing Answer Review & Essay Manual Grading ---\n";
// Open Answer Review
$reviewRes = httpCall($baseUrl . '/teacher/reviewAnswers/' . $participantIdAni, 'GET', [], $cookieTeacher);
assertResultTest("Answer review page returned HTTP 200", $reviewRes['code'] === 200);
assertResultTest("Answer review displays student's essay answer text", 
    strpos($reviewRes['body'], 'Teorema limit pusat menyatakan') !== false);
assertResultTest("Answer review displays 'Menunggu Penilaian'", strpos($reviewRes['body'], 'Menunggu Penilaian') !== false);
assertResultTest("Answer review contains manual grading form", strpos($reviewRes['body'], 'saveEssayGrade') !== false);

// Grade Essay: award 15.00 points (out of 20.00)
$gradeRes = httpCall($baseUrl . '/teacher/saveEssayGrade', 'POST', [
    'participant_id' => $participantIdAni,
    'question_id' => $essayQuestionId,
    'score' => 15.00
], $cookieTeacher);
assertResultTest("Save essay grade redirected back to review with success", $gradeRes['code'] === 302);

// Verify database updates
$gradedAnsRow = $db->query("SELECT * FROM exam_answers WHERE exam_participant_id = :pid AND question_id = :qid")
    ->bind(':pid', $participantIdAni)
    ->bind(':qid', $essayQuestionId)
    ->single();
assertResultTest("Essay answer awarded_score is now 15.00", (float)$gradedAnsRow['awarded_score'] == 15.00);
assertResultTest("Essay answer is_correct is now 1 (graded)", (int)$gradedAnsRow['is_correct'] === 1);

// Verify final_score updated: 40.00 (MCQ) + 15.00 (Essay) = 55.00
$updatedResult = $db->query("SELECT * FROM exam_results WHERE exam_participant_id = :pid")->bind(':pid', $participantIdAni)->single();
assertResultTest("Recalculated final_score is 55.00", (float)$updatedResult['final_score'] == 55.00);
assertResultTest("Recalculated is_passed is 1 (55.00 >= passing_score 50.00)", (int)$updatedResult['is_passed'] === 1);

// Check review page renders updated grade
$reviewAfterRes = httpCall($baseUrl . '/teacher/reviewAnswers/' . $participantIdAni, 'GET', [], $cookieTeacher);
assertResultTest("Review page renders 'Sudah Dinilai: 15.0 / 20.0 Poin'", 
    strpos($reviewAfterRes['body'], 'Sudah Dinilai: 15.0') !== false);

// -------------------------------------------------------------------
// 4. Result Permission (Visibility Toggle) Test
// -------------------------------------------------------------------
echo "\n--- 4. Testing Result Visibility Permission ---\n";
// Set show_result = 0 (Hide from students)
$toggleHide = httpCall($baseUrl . '/teacher/toggleResultVisibility/' . $examId, 'POST', [], $cookieTeacher);
assertResultTest("Teacher toggle visibility executed successfully", $toggleHide['code'] === 302);

$examHidden = $db->query("SELECT show_result FROM exams WHERE id = :eid")->bind(':eid', $examId)->single();
assertResultTest("Exam show_result in DB is 0", (int)$examHidden['show_result'] === 0);

// Student Ani accesses results when show_result = 0
$stHiddenRes = httpCall($baseUrl . '/student/examResult/' . $examId, 'GET', [], $cookieStudent);
assertResultTest("Student result page rendered with HTTP 200", $stHiddenRes['code'] === 200);
assertResultTest("Student sees 'Hasil Ujian Belum Dipublikasikan'", 
    strpos($stHiddenRes['body'], 'Hasil Ujian Belum Dipublikasikan') !== false);
assertResultTest("Student CANNOT see final score banner when show_result = 0", 
    strpos($stHiddenRes['body'], 'SELAMAT, ANDA LULUS') === false);

// Set show_result = 1 (Publish to students)
$toggleShow = httpCall($baseUrl . '/teacher/toggleResultVisibility/' . $examId, 'POST', [], $cookieTeacher);
$examShown = $db->query("SELECT show_result FROM exams WHERE id = :eid")->bind(':eid', $examId)->single();
assertResultTest("Exam show_result in DB toggled back to 1", (int)$examShown['show_result'] === 1);

// Student Ani accesses results when show_result = 1
$stShownRes = httpCall($baseUrl . '/student/examResult/' . $examId, 'GET', [], $cookieStudent);
assertResultTest("Student sees 'Hasil Ujian Anda'", strpos($stShownRes['body'], 'Hasil Ujian Anda') !== false);
assertResultTest("Student sees updated final score 55.0", strpos($stShownRes['body'], '55.0') !== false);
assertResultTest("Student sees 'SELAMAT, ANDA LULUS'", strpos($stShownRes['body'], 'SELAMAT, ANDA LULUS') !== false);

// -------------------------------------------------------------------
// 5. Duplicate Result Prevention Test
// -------------------------------------------------------------------
echo "\n--- 5. Testing Duplicate Result Prevention ---\n";
$resultCount = $db->query("SELECT COUNT(*) AS total FROM exam_results WHERE exam_participant_id = :pid")
    ->bind(':pid', $participantIdAni)
    ->single()['total'];
assertResultTest("Exactly 1 result record exists for participant", (int)$resultCount === 1);

// Teacher updates essay grade to 20.0
httpCall($baseUrl . '/teacher/saveEssayGrade', 'POST', [
    'participant_id' => $participantIdAni,
    'question_id' => $essayQuestionId,
    'score' => 20.00
], $cookieTeacher);

$resultCountAfter = $db->query("SELECT COUNT(*) AS total FROM exam_results WHERE exam_participant_id = :pid")
    ->bind(':pid', $participantIdAni)
    ->single()['total'];
assertResultTest("Regrading essay does not duplicate result record (still 1 record)", (int)$resultCountAfter === 1);

$scoreAfterRegrade = $db->query("SELECT final_score FROM exam_results WHERE exam_participant_id = :pid")
    ->bind(':pid', $participantIdAni)
    ->single()['final_score'];
assertResultTest("Final score updated to 60.00 after regrading", (float)$scoreAfterRegrade == 60.00);

// -------------------------------------------------------------------
// 6. Unauthorized Result Access Test
// -------------------------------------------------------------------
echo "\n--- 6. Testing Unauthorized Result Access ---\n";
// Unauthenticated request to teacher results
$noAuthRes = httpCall($baseUrl . '/teacher/results');
assertResultTest("Unauthenticated access to /teacher/results redirected to login", 
    $noAuthRes['code'] === 302 && strpos($noAuthRes['headers'], 'auth/login') !== false);

// Student trying to access teacher results
$stAccessTeacher = httpCall($baseUrl . '/teacher/results', 'GET', [], $cookieStudent);
assertResultTest("Student accessing /teacher/results blocked and redirected", 
    $stAccessTeacher['code'] === 302 && strpos($stAccessTeacher['headers'], 'student/dashboard') !== false);

// Student trying to grade essay directly
$stGradeEssay = httpCall($baseUrl . '/teacher/saveEssayGrade', 'POST', [
    'participant_id' => $participantIdAni,
    'question_id' => $essayQuestionId,
    'score' => 20.00
], $cookieStudent);
assertResultTest("Student trying to post /teacher/saveEssayGrade blocked", 
    $stGradeEssay['code'] === 302 && strpos($stGradeEssay['headers'], 'student/dashboard') !== false);

// Student trying to toggle visibility
$stToggle = httpCall($baseUrl . '/teacher/toggleResultVisibility/' . $examId, 'POST', [], $cookieStudent);
assertResultTest("Student trying to toggle result visibility blocked", 
    $stToggle['code'] === 302 && strpos($stToggle['headers'], 'student/dashboard') !== false);

// -------------------------------------------------------------------
// 7. Admin School-Wide Results Overview Test
// -------------------------------------------------------------------
echo "\n--- 7. Testing Admin Results Overview ---\n";
$adLogin = httpCall($baseUrl . '/auth/authenticate', 'POST', [
    'username' => 'admin',
    'password' => 'password123'
], $cookieAdmin);
assertResultTest("Admin login successful", $adLogin['code'] === 302);

$adminResultsRes = httpCall($baseUrl . '/admin/results', 'GET', [], $cookieAdmin);
assertResultTest("Admin results overview returned HTTP 200", $adminResultsRes['code'] === 200);
assertResultTest("Admin results overview contains 'Ringkasan Hasil Ujian Tingkat Sekolah'", 
    strpos($adminResultsRes['body'], 'Ringkasan Hasil Ujian Tingkat Sekolah') !== false);
assertResultTest("Admin results displays school average metric", 
    strpos($adminResultsRes['body'], 'Rata-rata Sekolah') !== false);
assertResultTest("Admin results displays school passing rate metric", 
    strpos($adminResultsRes['body'], 'Tingkat Kelulusan') !== false);

// Clean temporary cookies
@unlink($cookieTeacher);
@unlink($cookieStudent);
@unlink($cookieAdmin);

echo "\n=======================================================\n";
echo "TEST RESULTS: {$passed} PASSED, {$failed} FAILED\n";
echo "=======================================================\n";

exit($failed > 0 ? 1 : 0);
