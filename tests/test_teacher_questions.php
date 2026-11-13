<?php
/**
 * Test Suite for Teacher Module & Bank Soal (Question Bank CRUD & Ownership)
 * AI Online Exam & Proctoring System
 */

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/Project2/';
$_SERVER['SCRIPT_NAME'] = '/Project2/index.php';

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/models/UserModel.php';
require_once __DIR__ . '/../app/models/TeacherModel.php';
require_once __DIR__ . '/../app/models/SubjectModel.php';
require_once __DIR__ . '/../app/models/QuestionModel.php';

Auth::init();

$passed = 0;
$failed = 0;

function runTest($name, $callable) {
    global $passed, $failed;
    echo "Testing: {$name}... ";
    try {
        $result = $callable();
        if ($result === true) {
            echo " [PASSED]\n";
            $passed++;
        } else {
            echo " [FAILED] ({$result})\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo " [EXCEPTION] ({$e->getMessage()})\n";
        $failed++;
    }
}

echo "========================================================\n";
echo " AI Online Exam - Teacher Module & Question Bank Tests\n";
echo "========================================================\n\n";

$db = Database::getInstance();
$questionModel = new QuestionModel();
$teacherModel = new TeacherModel();
$subjectModel = new SubjectModel();

// Setup: Ensure Teacher 1 exists (Guru Budi, ID 1)
$teacher1 = $teacherModel->findById(1);
if (!$teacher1) {
    die("Seed teacher ID 1 not found. Please run migrate.php first.\n");
}
$teacher1Id = (int)$teacher1['id'];

// Setup: Create Teacher 2 for ownership testing
$db->query("DELETE FROM users WHERE username = 'guru_siti_test'")->execute();
$teacher2Id = $teacherModel->createTeacherWithUser(
    [
        'username' => 'guru_siti_test',
        'email' => 'siti.nurhaliza@sekolah.sch.id',
        'password' => 'password123',
        'full_name' => 'Siti Nurhaliza, S.Pd',
        'is_active' => 1
    ],
    [
        'nip' => '199205102022012001',
        'specialization' => 'Fisika Terapan'
    ]
);

// 1. Test Create Multiple Choice Question
$mcQId = null;
runTest('1.1 Create Multiple Choice Question with 4 Options', function() use ($questionModel, $teacher1Id, &$mcQId) {
    $mcQId = $questionModel->createQuestion($teacher1Id, [
        'subject_id' => 1,
        'question_text' => 'Berapakah hasil dari 2^5 dikali 2^3?',
        'question_type' => 'multiple_choice',
        'difficulty' => 'easy',
        'default_score' => 10.00,
        'explanation' => '2^(5+3) = 2^8 = 256.',
        'options' => [
            'A' => '128',
            'B' => '256',
            'C' => '512',
            'D' => '64'
        ],
        'correct_option' => 'B'
    ]);
    return ($mcQId > 0);
});

runTest('1.2 Verify MC Question Details & Options in Database', function() use ($questionModel, $teacher1Id, &$mcQId) {
    $q = $questionModel->findByIdAndTeacher($mcQId, $teacher1Id);
    if (!$q || count($q['options']) !== 4) return false;
    
    $optB = null;
    foreach ($q['options'] as $opt) {
        if ($opt['option_label'] === 'B') $optB = $opt;
    }
    return ($optB && (int)$optB['is_correct'] === 1 && $optB['option_text'] === '256');
});

// 2. Test Create Essay Question
$essayQId = null;
runTest('2.1 Create Essay Question', function() use ($questionModel, $teacher1Id, &$essayQId) {
    $essayQId = $questionModel->createQuestion($teacher1Id, [
        'subject_id' => 1,
        'question_text' => 'Jelaskan perbedaan antara integral tentu dan integral tak tentu!',
        'question_type' => 'essay',
        'difficulty' => 'medium',
        'default_score' => 25.00,
        'explanation' => 'Integral tentu memiliki batas atas dan bawah dan menghasilkan nilai skalar numerik, sedangkan integral tak tentu menghasilkan fungsi umum dengan konstanta C.'
    ]);
    return ($essayQId > 0);
});

runTest('2.2 Verify Essay Question has zero options', function() use ($questionModel, $teacher1Id, &$essayQId) {
    $q = $questionModel->findByIdAndTeacher($essayQId, $teacher1Id);
    return ($q && $q['question_type'] === 'essay' && empty($q['options']));
});

// 3. Test Search and Filters
runTest('3.1 Search Question by Keyword', function() use ($questionModel, $teacher1Id) {
    $results = $questionModel->getByTeacher($teacher1Id, 'integral');
    return (count($results) >= 1);
});

runTest('3.2 Filter Question by Subject and Type', function() use ($questionModel, $teacher1Id) {
    $mcList = $questionModel->getByTeacher($teacher1Id, '', 1, 'multiple_choice');
    $essayList = $questionModel->getByTeacher($teacher1Id, '', 1, 'essay');
    return (count($mcList) >= 1 && count($essayList) >= 1);
});

// 4. Test Update Question
runTest('4.1 Update Question Text, Difficulty and Options', function() use ($questionModel, $teacher1Id, &$mcQId) {
    $ok = $questionModel->updateQuestion($mcQId, $teacher1Id, [
        'subject_id' => 1,
        'question_text' => 'Berapakah hasil dari 2^6 dikali 2^2?',
        'question_type' => 'multiple_choice',
        'difficulty' => 'medium',
        'default_score' => 15.00,
        'explanation' => '2^(6+2) = 2^8 = 256.',
        'options' => [
            'A' => '64',
            'B' => '128',
            'C' => '256',
            'D' => '512'
        ],
        'correct_option' => 'C'
    ]);

    $q = $questionModel->findByIdAndTeacher($mcQId, $teacher1Id);
    $optC = null;
    foreach ($q['options'] as $opt) {
        if ($opt['option_label'] === 'C') $optC = $opt;
    }
    return ($ok && $q['difficulty'] === 'medium' && $optC && (int)$optC['is_correct'] === 1);
});

// 5. Test Ownership Enforcement
runTest('5.1 Teacher 2 Cannot Read Teacher 1 Question', function() use ($questionModel, $teacher2Id, &$mcQId) {
    $q = $questionModel->findByIdAndTeacher($mcQId, $teacher2Id);
    return ($q === null);
});

runTest('5.2 Teacher 2 Cannot Update Teacher 1 Question', function() use ($questionModel, $teacher2Id, &$mcQId) {
    try {
        $questionModel->updateQuestion($mcQId, $teacher2Id, [
            'subject_id' => 1,
            'question_text' => 'Hacked question'
        ]);
        return false; // Should not reach here
    } catch (Exception $e) {
        return true; // Expected ownership exception
    }
});

runTest('5.3 Teacher 2 Cannot Delete Teacher 1 Question', function() use ($questionModel, $teacher2Id, &$mcQId) {
    $deleted = $questionModel->deleteQuestion($mcQId, $teacher2Id);
    // Should return false and record remains
    $remains = $questionModel->findByIdAndTeacher($mcQId, 1);
    return ($remains !== null);
});

// 6. Test Delete Question
runTest('6.1 Teacher 1 Deletes Own MC Question', function() use ($questionModel, $teacher1Id, &$mcQId) {
    $ok = $questionModel->deleteQuestion($mcQId, $teacher1Id);
    $q = $questionModel->findByIdAndTeacher($mcQId, $teacher1Id);
    return ($ok && $q === null);
});

runTest('6.2 Teacher 1 Deletes Own Essay Question', function() use ($questionModel, $teacher1Id, &$essayQId) {
    $ok = $questionModel->deleteQuestion($essayQId, $teacher1Id);
    $q = $questionModel->findByIdAndTeacher($essayQId, $teacher1Id);
    return ($ok && $q === null);
});

// Cleanup Teacher 2
$teacherModel->deleteTeacher($teacher2Id);

echo "\n========================================================\n";
echo " Total Tests: " . ($passed + $failed) . " | Passed: {$passed} | Failed: {$failed}\n";
echo "========================================================\n";

exit($failed > 0 ? 1 : 0);
