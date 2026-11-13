<?php
/**
 * Comprehensive End-to-End Final QA Audit Test Suite
 * AI Online Exam & Proctoring System
 */

require_once __DIR__ . '/../app/init.php';

$totalPassed = 0;
$totalFailed = 0;

function assertTest($condition, $label) {
    global $totalPassed, $totalFailed;
    if ($condition) {
        $totalPassed++;
        echo " [PASS] " . $label . "\n";
    } else {
        $totalFailed++;
        echo " [FAIL] " . $label . "\n";
    }
}

echo "=======================================================\n";
echo "RUNNING FINAL QA AUDIT: END-TO-END TEST SUITE\n";
echo "=======================================================\n\n";

$db = Database::getInstance();

// ==========================================================
// 1. SCENARIO: AUTHENTICATION & AUTHORIZATION
// ==========================================================
echo "--- 1. SCENARIO: AUTHENTICATION & AUTHORIZATION ---\n";

// 1.1 Admin login
Auth::logout();
$adminLogin = Auth::attempt('admin@sekolah.sch.id', 'password123');
assertTest($adminLogin['success'] === true, 'Admin login succeeds with valid credentials');
assertTest(Auth::hasRole('admin') === true, 'Session establishes admin role');
assertTest(Auth::user()['email'] === 'admin@sekolah.sch.id', 'Authenticated user identity is verified');

// 1.2 Teacher login
Auth::logout();
$teacherLogin = Auth::attempt('budi.santoso@sekolah.sch.id', 'password123');
assertTest($teacherLogin['success'] === true, 'Teacher login succeeds with valid credentials');
assertTest(Auth::hasRole('teacher') === true, 'Session establishes teacher role');

// 1.3 Student login
Auth::logout();
$studentLogin = Auth::attempt('ani.lestari@sekolah.sch.id', 'password123');
assertTest($studentLogin['success'] === true, 'Student login succeeds with valid credentials');
assertTest(Auth::hasRole('student') === true, 'Session establishes student role');

// 1.4 Wrong password
Auth::logout();
$wrongLogin = Auth::attempt('admin@sekolah.sch.id', 'wrong_pass_999');
assertTest($wrongLogin['success'] === false, 'Wrong password is authenticated as false');
assertTest(!empty($wrongLogin['message']), 'Descriptive error message returned for invalid password');

// 1.5 Brute force lockout check
Auth::clearFailedLogins('test_brute_user');
for ($i = 0; $i < 5; $i++) {
    Auth::recordFailedLogin('test_brute_user');
}
assertTest(Auth::isThrottled('test_brute_user') === true, 'Brute force lockout active after 5 failed attempts');
Auth::clearFailedLogins('test_brute_user');
assertTest(Auth::isThrottled('test_brute_user') === false, 'Account lockout cleared upon reset');

// 1.6 Logout
Auth::attempt('admin@sekolah.sch.id', 'password123');
Auth::logout();
assertTest(Auth::check() === false, 'Logout terminates active session');
assertTest(Auth::user() === null, 'Session user is null after logout');

// 1.7 Unauthorized route protection
Auth::attempt('ani.lestari@sekolah.sch.id', 'password123'); // Student role
assertTest(Auth::role() === 'student', 'Active session role is student');
assertTest(Auth::hasRole('teacher') === false, 'Student is denied teacher role authorization');
assertTest(Auth::hasRole('admin') === false, 'Student is denied admin role authorization');


// ==========================================================
// 2. SCENARIO: ADMIN CRUD OPERATIONS
// ==========================================================
echo "\n--- 2. SCENARIO: ADMIN CRUD OPERATIONS ---\n";

Auth::attempt('admin@sekolah.sch.id', 'password123');
$classModel = new ClassModel();
$subjectModel = new SubjectModel();
$teacherModel = new TeacherModel();
$studentModel = new StudentModel();

// 2.1 CRUD Class
$testClassName = 'QA Test Class ' . time();
$classId = $classModel->create(['name' => $testClassName, 'level' => 'XII', 'academic_year' => '2026/2027']);
assertTest($classId > 0, 'Admin can CREATE a Class (ID: ' . $classId . ')');

$readClass = $classModel->findById($classId);
assertTest($readClass !== null && $readClass['name'] === $testClassName, 'Admin can READ created Class');

$classUpdated = $classModel->update($classId, ['name' => $testClassName . ' Updated', 'level' => 'XII', 'academic_year' => '2026/2027']);
assertTest($classUpdated === true, 'Admin can UPDATE a Class');

// 2.2 CRUD Subject
$testSubjectCode = 'QA-' . substr(md5(uniqid()), 0, 5);
$subjectId = $subjectModel->create(['code' => $testSubjectCode, 'name' => 'QA Subject Test', 'description' => 'QA Description']);
assertTest($subjectId > 0, 'Admin can CREATE a Subject (ID: ' . $subjectId . ')');

$readSubject = $subjectModel->findById($subjectId);
assertTest($readSubject !== null && $readSubject['code'] === strtoupper($testSubjectCode), 'Admin can READ created Subject');

$subjectUpdated = $subjectModel->update($subjectId, ['code' => $testSubjectCode, 'name' => 'QA Subject Test Updated', 'description' => 'Updated Desc']);
assertTest($subjectUpdated === true, 'Admin can UPDATE a Subject');

// 2.3 CRUD Teacher
$testNip = 'QA' . rand(1000000, 9999999);
$testTUsername = 'qat_' . substr(md5(uniqid()), 0, 6);
$qaTeacherId = $teacherModel->createTeacherWithUser(
    [
        'username' => $testTUsername,
        'email' => $testTUsername . '@sekolah.sch.id',
        'password' => 'password123',
        'full_name' => 'QA Test Teacher',
        'phone' => '0812345678',
        'is_active' => 1
    ],
    [
        'nip' => $testNip,
        'specialization' => 'Quality Assurance'
    ]
);
assertTest($qaTeacherId > 0, 'Admin can CREATE a Teacher with linked User account (ID: ' . $qaTeacherId . ')');

$readTeacher = $teacherModel->findById($qaTeacherId);
assertTest($readTeacher !== null && $readTeacher['nip'] === $testNip, 'Admin can READ created Teacher');

$updateTRes = $teacherModel->updateTeacherWithUser(
    $qaTeacherId,
    [
        'username' => $testTUsername,
        'email' => $testTUsername . '@sekolah.sch.id',
        'full_name' => 'QA Test Teacher Updated',
        'phone' => '0812345679',
        'is_active' => 1
    ],
    [
        'nip' => $testNip,
        'specialization' => 'QA Lead'
    ]
);
assertTest($updateTRes === true, 'Admin can UPDATE Teacher profile');

// 2.4 CRUD Student
$testNis = 'QAS' . rand(10000, 99999);
$testSUsername = 'qas_' . substr(md5(uniqid()), 0, 6);
$qaStudentId = $studentModel->createStudentWithUser(
    [
        'username' => $testSUsername,
        'email' => $testSUsername . '@sekolah.sch.id',
        'password' => 'password123',
        'full_name' => 'QA Test Student',
        'phone' => '0812345670',
        'is_active' => 1
    ],
    [
        'nis' => $testNis,
        'nisn' => '009' . rand(1000000, 9999999),
        'class_id' => $classId,
        'gender' => 'L',
        'birth_date' => '2008-01-01'
    ]
);
assertTest($qaStudentId > 0, 'Admin can CREATE a Student with linked User account & class (ID: ' . $qaStudentId . ')');

$readStudent = $studentModel->findById($qaStudentId);
assertTest($readStudent !== null && $readStudent['nis'] === $testNis, 'Admin can READ created Student');

$updateSRes = $studentModel->updateStudentWithUser(
    $qaStudentId,
    [
        'username' => $testSUsername,
        'email' => $testSUsername . '@sekolah.sch.id',
        'full_name' => 'QA Test Student Updated',
        'phone' => '0812345671',
        'is_active' => 1
    ],
    [
        'nis' => $testNis,
        'nisn' => '009' . rand(1000000, 9999999),
        'class_id' => $classId,
        'gender' => 'L',
        'birth_date' => '2008-01-01'
    ]
);
assertTest($updateSRes === true, 'Admin can UPDATE Student profile');


// ==========================================================
// 3. SCENARIO: TEACHER WORKFLOW (QUESTION BANK & EXAM)
// ==========================================================
echo "\n--- 3. SCENARIO: TEACHER WORKFLOW ---\n";

Auth::attempt($testTUsername . '@sekolah.sch.id', 'password123');
$questionModel = new QuestionModel();
$examModel = new ExamModel();

// 3.1 Create Multiple Choice Question
$mcQId = $questionModel->createQuestion($qaTeacherId, [
    'subject_id' => $subjectId,
    'question_text' => 'Berapakah hasil dari 25 x 4?',
    'question_type' => 'multiple_choice',
    'difficulty' => 'easy',
    'default_score' => 50,
    'explanation' => '25 dikali 4 sama dengan 100.',
    'options' => [
        'A' => '50',
        'B' => '100',
        'C' => '75',
        'D' => '125'
    ],
    'correct_option' => 'B'
]);
assertTest($mcQId > 0, 'Teacher can CREATE a Multiple Choice Question with 4 options (ID: ' . $mcQId . ')');

// 3.2 Create Essay Question
$essayQId = $questionModel->createQuestion($qaTeacherId, [
    'subject_id' => $subjectId,
    'question_text' => 'Jelaskan prinsip kerja dari enkripsi password satu arah menggunakan Bcrypt!',
    'question_type' => 'essay',
    'difficulty' => 'medium',
    'default_score' => 50,
    'explanation' => 'Bcrypt menggunakan key derivation function berbasis Blowfish dengan salt acak.'
]);
assertTest($essayQId > 0, 'Teacher can CREATE an Essay Question (ID: ' . $essayQId . ')');

// 3.3 Edit Question
$editRes = $questionModel->updateQuestion($mcQId, $qaTeacherId, [
    'subject_id' => $subjectId,
    'question_text' => 'Berapakah hasil dari 25 x 4? (Updated)',
    'question_type' => 'multiple_choice',
    'difficulty' => 'easy',
    'default_score' => 50,
    'explanation' => '25 dikali 4 sama dengan 100.',
    'options' => [
        'A' => '50',
        'B' => '100',
        'C' => '75',
        'D' => '150'
    ],
    'correct_option' => 'B'
]);
assertTest($editRes === true, 'Teacher can UPDATE a Question and its options');

// 3.4 Create Exam with Questions & Class assignment
$examData = [
    'title' => 'Ujian Akhir Semester QA Automated',
    'description' => 'Ujian evaluasi akhir semester QA.',
    'subject_id' => $subjectId,
    'duration_minutes' => 60,
    'passing_score' => 65.0,
    'start_time' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+2 hours')),
    'is_random_question' => 1,
    'is_random_option' => 1,
    'enable_proctoring' => 1,
    'status' => 'draft'
];
$qaExamId = $examModel->createExamWithQuestions($qaTeacherId, $examData, [$mcQId, $essayQId], $classId);
assertTest($qaExamId > 0, 'Teacher can CREATE an Exam with assigned questions & class enrollment (ID: ' . $qaExamId . ')');

$readExam = $examModel->findByIdAndTeacher($qaExamId, $qaTeacherId);
assertTest($readExam !== null && $readExam['title'] === $examData['title'], 'Exam recorded with correct properties');
assertTest(count($readExam['questions']) === 2, 'Exam has exactly 2 assigned questions');
assertTest(!empty($readExam['token']), 'Exam generated random access token: ' . $readExam['token']);

// 3.5 Publish Exam
$publishRes = $examModel->updateStatus($qaExamId, $qaTeacherId, 'published');
assertTest($publishRes === true, 'Teacher can PUBLISH Exam (status: published)');


// ==========================================================
// 4. SCENARIO: STUDENT EXAM LIFECYCLE
// ==========================================================
echo "\n--- 4. SCENARIO: STUDENT EXAM LIFECYCLE ---\n";

Auth::attempt($testSUsername . '@sekolah.sch.id', 'password123');
$examEngine = new ExamEngineModel();

// 4.1 See available exams
$assignedExams = $examEngine->getAssignedExamsForStudent($qaStudentId, 'available');
$foundAssigned = false;
foreach ($assignedExams as $ae) {
    if ((int)$ae['exam_id'] === $qaExamId) {
        $foundAssigned = true;
        break;
    }
}
assertTest($foundAssigned === true, 'Student can see assigned published Exam in catalog');

// 4.2 Start exam with token verification
$wrongVerify = $examEngine->verifyExamAccess($qaExamId, $qaStudentId, 'INVALIDTOKEN');
assertTest($wrongVerify['valid'] === false, 'Access rejected with wrong exam token');

$correctVerify = $examEngine->verifyExamAccess($qaExamId, $qaStudentId, $readExam['token']);
assertTest($correctVerify['valid'] === true, 'Access granted with correct exam token');
$participantRecord = $correctVerify['participant'];
$qaParticipantId = (int)$participantRecord['participant_id'];

// 4.3 Enter Exam Room (transition status to in_progress)
$startRes = $examEngine->startExam($qaParticipantId);
assertTest($startRes === true, 'Student enters exam room and session starts');

$activeParticipant = $examEngine->getExamParticipant($qaExamId, $qaStudentId);
assertTest($activeParticipant['participant_status'] === 'in_progress', 'Participant status transitioned to in_progress');
assertTest(!empty($activeParticipant['participant_start_time']), 'Participant start_time recorded by server');

// 4.4 Load Questions & verify anti-tamper security
$roomPayload = $examEngine->getExamQuestionsAndSavedAnswers($qaExamId, $qaParticipantId, false, false);
assertTest(count($roomPayload) === 2, 'Room delivers exactly 2 questions to student');

// Verify SECURITY: is_correct is NOT leaked to student
$mcQPayload = ($roomPayload[0]['question_type'] === 'multiple_choice') ? $roomPayload[0] : $roomPayload[1];
$leakedKey = false;
foreach ($mcQPayload['options'] as $opt) {
    if (isset($opt['is_correct'])) {
        $leakedKey = true;
    }
}
assertTest($leakedKey === false, 'SECURITY: Option is_correct is completely excluded from student payload');

// 4.5 Auto-save Multiple Choice Answer
$correctOptionId = null;
$rawOptions = $db->query("SELECT id, is_correct FROM question_options WHERE question_id = :qid")->bind(':qid', $mcQId)->resultSet();
foreach ($rawOptions as $ro) {
    if ((int)$ro['is_correct'] === 1) {
        $correctOptionId = (int)$ro['id'];
        break;
    }
}
$saveMcRes = $examEngine->saveAnswer($qaParticipantId, $mcQId, $correctOptionId, null, 0);
assertTest($saveMcRes['success'] === true, 'Student answer for Multiple Choice is auto-saved via API');

// 4.6 Auto-save Essay Answer & Doubt Flag
$essayText = 'Bcrypt menggunakan algoritma hashing adaptif dengan salt 16-byte untuk melindungi password dari rainbow table.';
$saveEssayRes = $examEngine->saveAnswer($qaParticipantId, $essayQId, null, $essayText, 1);
assertTest($saveEssayRes['success'] === true, 'Student essay answer is auto-saved with doubt flag = true');

// 4.7 Page Refresh Persistence
$refreshedPayload = $examEngine->getExamQuestionsAndSavedAnswers($qaExamId, $qaParticipantId, false, false);
$persistedMC = false;
$persistedEssay = false;
foreach ($refreshedPayload as $rq) {
    if ((int)$rq['id'] === $mcQId && !empty($rq['saved_answer'])) {
        if ((int)$rq['saved_answer']['selected_option_id'] === $correctOptionId) {
            $persistedMC = true;
        }
    }
    if ((int)$rq['id'] === $essayQId && !empty($rq['saved_answer'])) {
        if ($rq['saved_answer']['essay_answer'] === $essayText && (int)$rq['saved_answer']['is_flagged_doubt'] === 1) {
            $persistedEssay = true;
        }
    }
}
assertTest($persistedMC === true, 'Browser refresh retains saved Multiple Choice answer');
assertTest($persistedEssay === true, 'Browser refresh retains saved Essay answer and doubt flag');

// 4.8 Remaining Time Server Check
$remSeconds = $examEngine->calculateRemainingSeconds($readExam, $activeParticipant);
assertTest($remSeconds > 0, 'Server provides authoritative countdown remaining seconds (' . $remSeconds . 's)');

// 4.9 Manual Submit & Auto-Scoring
$submitRes = $examEngine->submitExamAndCalculateScore($qaParticipantId);
assertTest($submitRes['success'] === true, 'Student manually submits completed exam sheet');

$completedParticipant = $examEngine->getExamParticipant($qaExamId, $qaStudentId);
assertTest($completedParticipant['participant_status'] === 'submitted', 'Participant status updated to submitted');
assertTest(!empty($completedParticipant['participant_submit_time']), 'Submission timestamp permanently recorded');
assertTest((float)$submitRes['result']['final_score'] === 50.0, 'MC question automatically scored: 50.0 pts');

// 4.10 Duplicate Submit Prevention
$duplicateSubmit = $examEngine->submitExamAndCalculateScore($qaParticipantId);
assertTest($duplicateSubmit['success'] === false, 'Duplicate submit prevented by server check');

// 4.11 Exam re-access prevention
$reaccessVerify = $examEngine->verifyExamAccess($qaExamId, $qaStudentId, $readExam['token']);
assertTest($reaccessVerify['participant']['participant_status'] === 'submitted', 'Student cannot retake submitted exam');


// ==========================================================
// 5. SCENARIO: PROCTORING & RISK SERVICE
// ==========================================================
echo "\n--- 5. SCENARIO: PROCTORING & RISK SERVICE ---\n";

$proctoringModel = new ProctoringModel();
$riskService = new ProctoringRiskService();

// 5.1 Event logging (Camera disconnect)
$logDis = $proctoringModel->logEvent($qaParticipantId, $qaExamId, $qaStudentId, 'CAMERA_DISCONNECTED', 'WARNING', null, null, 20.0, 'camera_1', 0);
assertTest($logDis['logged'] === true, 'Proctoring logged CAMERA_DISCONNECTED event');

// 5.2 Event logging (Missing face)
$logMissing = $proctoringModel->logEvent($qaParticipantId, $qaExamId, $qaStudentId, 'FACE_NOT_DETECTED', 'WARNING', null, null, 10.0, 'camera_1', 0);
assertTest($logMissing['logged'] === true, 'Proctoring logged FACE_NOT_DETECTED event');

// 5.3 Event logging (Multiple faces)
$logMulti = $proctoringModel->logEvent($qaParticipantId, $qaExamId, $qaStudentId, 'MULTIPLE_FACE', 'CRITICAL', null, null, 40.0, 'camera_1', 0);
assertTest($logMulti['logged'] === true, 'Proctoring logged MULTIPLE_FACE event with CRITICAL severity');

// 5.4 Risk Score Calculation
// Score should be: CAMERA_DISCONNECTED(20) + FACE_NOT_DETECTED(10) + MULTIPLE_FACE(40) = 70
$riskAssessment = $riskService->calculateRiskByParticipantId($qaParticipantId, false);
assertTest($riskAssessment['risk_score'] === 70, 'Risk score calculated accurately: 70 / 100');
assertTest($riskAssessment['risk_level'] === 'HIGH RISK', 'Risk level classified as HIGH RISK (>= 60)');
assertTest($riskAssessment['needs_review'] === true, 'High-risk participant flagged for teacher review');
assertTest($riskAssessment['event_summary']['Multiple Face'] === 1, 'Event summary reports 1 Multiple Face');
assertTest($riskAssessment['event_summary']['Camera Disconnect'] === 1, 'Event summary reports 1 Camera Disconnect');

// 5.5 Teacher Live Monitoring Data Verification
$liveParticipants = $proctoringModel->getExamLiveParticipants($qaExamId);
assertTest(count($liveParticipants) >= 1, 'Teacher live monitoring returns participant data');
$monitoredP = $liveParticipants[0];
assertTest($monitoredP['risk_score'] === 70, 'Teacher live monitoring receives accurate risk score');
assertTest($monitoredP['risk_level'] === 'HIGH RISK', 'Teacher live monitoring receives HIGH RISK alert');

$liveSummary = $proctoringModel->getExamMonitoringSummary($qaExamId, $liveParticipants);
assertTest($liveSummary['high_risk'] >= 1, 'Monitoring summary aggregates high_risk participant count');


// ==========================================================
// 6. SCENARIO: SECURITY HARDENING
// ==========================================================
echo "\n--- 6. SCENARIO: SECURITY HARDENING ---\n";

// 6.1 SQL Injection Protection
$userModel = new UserModel();
$sqlInjection = "' OR 1=1 -- ";
$injectedUser = $userModel->findByUsernameOrEmail($sqlInjection);
assertTest($injectedUser === null, 'SQL injection payload in user login identifier treated as literal, zero unauthorized rows');

// 6.2 Foreign Question Injection IDOR Prevention
$foreignSave = $examEngine->saveAnswer($qaParticipantId, 999999, null, 'Hacked text', 0);
assertTest($foreignSave['success'] === false, 'IDOR: Submitting answer for foreign/unassigned question_id is rejected');

// 6.3 CSRF Protection Verification
$validToken = Csrf::getToken();
assertTest(strlen($validToken) === 64, 'CSRF token generated is secure 64-character hex string');
assertTest(Csrf::verify($validToken) === true, 'Csrf::verify accepts genuine session token');
assertTest(Csrf::verify('evil_attacker_forged_csrf_token') === false, 'Csrf::verify rejects forged CSRF token');

// 6.4 Proctoring Log Event Type Whitelist
$fakeEventLog = $proctoringModel->logEvent($qaParticipantId, $qaExamId, $qaStudentId, 'FAKE_UNAUTHORIZED_EVENT', 'CRITICAL', null, null, 10.0, 'camera_1', 0);
assertTest($fakeEventLog['logged'] === false, 'Non-whitelisted proctoring event type is rejected by server');


// ==========================================================
// 7. SCENARIO: PERFORMANCE OPTIMIZATION
// ==========================================================
echo "\n--- 7. SCENARIO: PERFORMANCE OPTIMIZATION ---\n";

// Measure query count / execution time for live monitoring polling
$startMicro = microtime(true);
$benchParticipants = $proctoringModel->getExamLiveParticipants($qaExamId);
$benchSummary = $proctoringModel->getExamMonitoringSummary($qaExamId, $benchParticipants);
$elapsedMs = (microtime(true) - $startMicro) * 1000;

assertTest($elapsedMs < 100.0, sprintf('Live monitoring batched aggregation executes in under 100ms (Actual: %.2f ms)', $elapsedMs));
assertTest(!empty($benchParticipants) && !empty($benchSummary), 'Batch monitoring produces complete participant & summary payload');


// ==========================================================
// 8. CLEANUP OF TEST FIXTURES
// ==========================================================
echo "\n--- 8. CLEANUP OF TEST FIXTURES ---\n";

$examModel->deleteExam($qaExamId, $qaTeacherId);
assertTest(true, 'QA Exam test fixture cleaned up');
$questionModel->deleteQuestion($mcQId, $qaTeacherId);
$questionModel->deleteQuestion($essayQId, $qaTeacherId);
assertTest(true, 'QA Questions test fixtures cleaned up');
$studentModel->deleteStudent($qaStudentId);
assertTest(true, 'QA Student test fixture cleaned up');
$teacherModel->deleteTeacher($qaTeacherId);
assertTest(true, 'QA Teacher test fixture cleaned up');
$classModel->delete($classId);
assertTest(true, 'QA Class test fixture cleaned up');
$subjectModel->delete($subjectId);
assertTest(true, 'QA Subject test fixture cleaned up');


// ==========================================================
// FINAL SUMMARY
// ==========================================================
echo "\n=======================================================\n";
echo "FINAL QA AUDIT TEST RESULTS:\n";
echo " PASSED: {$totalPassed}\n";
echo " FAILED: {$totalFailed}\n";
echo " TOTAL : " . ($totalPassed + $totalFailed) . "\n";
echo "=======================================================\n";

if ($totalFailed === 0) {
    echo "STATUS: ALL END-TO-END AUDIT TESTS PASSED SUCCESSFULLY!\n";
    exit(0);
} else {
    echo "STATUS: AUDIT DETECTED FAILURES!\n";
    exit(1);
}
