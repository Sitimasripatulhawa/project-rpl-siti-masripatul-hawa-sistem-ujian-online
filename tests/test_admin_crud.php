<?php
/**
 * CRUD Integration Test Suite for Admin Module (Idempotent)
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
require_once __DIR__ . '/../app/models/StudentModel.php';
require_once __DIR__ . '/../app/models/ClassModel.php';
require_once __DIR__ . '/../app/models/SubjectModel.php';

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
echo " AI Online Exam - Admin Module CRUD Test Suite\n";
echo "========================================================\n\n";

$db = Database::getInstance();
// Cleanup leftover test artifacts if any
$db->query("DELETE FROM subjects WHERE code LIKE 'BIO-TEST-%'")->execute();
$db->query("DELETE FROM users WHERE username IN ('guru_test_crud', 'siswa_test_crud', 'user_test_crud')")->execute();
$db->query("DELETE FROM classes WHERE name LIKE '%Test%' OR name LIKE '%Updated%'")->execute();

$userModel = new UserModel();
$teacherModel = new TeacherModel();
$studentModel = new StudentModel();
$classModel = new ClassModel();
$subjectModel = new SubjectModel();

// 1. Test Class CRUD
$testClassId = null;
runTest('1.1 Create Class', function() use ($classModel, &$testClassId) {
    $testClassId = $classModel->create([
        'name' => 'XI RPL 1 Test',
        'level' => 'XI',
        'academic_year' => '2025/2026'
    ]);
    return ($testClassId > 0);
});

runTest('1.2 Read & Search Class', function() use ($classModel, &$testClassId) {
    $classes = $classModel->getAll('XI RPL 1 Test');
    return (count($classes) === 1 && $classes[0]['id'] == $testClassId);
});

runTest('1.3 Update Class', function() use ($classModel, &$testClassId) {
    $ok = $classModel->update($testClassId, [
        'name' => 'XI RPL 1 Updated',
        'level' => 'XI',
        'academic_year' => '2025/2026'
    ]);
    $c = $classModel->findById($testClassId);
    return ($ok && $c['name'] === 'XI RPL 1 Updated');
});

// 2. Test Subject CRUD
$testSubId = null;
runTest('2.1 Create Subject', function() use ($subjectModel, &$testSubId) {
    $testSubId = $subjectModel->create([
        'code' => 'BIO-TEST-01',
        'name' => 'Biologi Sel Test',
        'description' => 'Mata pelajaran uji coba'
    ]);
    return ($testSubId > 0);
});

runTest('2.2 Check Unique Subject Code', function() use ($subjectModel) {
    $taken = $subjectModel->isCodeTaken('BIO-TEST-01');
    $notTaken = $subjectModel->isCodeTaken('CODE-NOT-EXIST');
    return ($taken === true && $notTaken === false);
});

runTest('2.3 Update Subject', function() use ($subjectModel, &$testSubId) {
    $ok = $subjectModel->update($testSubId, [
        'code' => 'BIO-TEST-01',
        'name' => 'Biologi Sel Molekuler',
        'description' => 'Deskripsi diperbarui'
    ]);
    $s = $subjectModel->findById($testSubId);
    return ($ok && $s['name'] === 'Biologi Sel Molekuler');
});

// 3. Test Teacher CRUD (Atomic Transaction)
$testTeacherId = null;
runTest('3.1 Create Teacher with User Account', function() use ($teacherModel, &$testTeacherId) {
    $testTeacherId = $teacherModel->createTeacherWithUser(
        [
            'username' => 'guru_test_crud',
            'email' => 'guru.test@sekolah.sch.id',
            'password' => 'password123',
            'full_name' => 'Guru Test CRUD, S.Kom',
            'phone' => '0899999991',
            'is_active' => 1
        ],
        [
            'nip' => '199001012020011099',
            'specialization' => 'Informatika & Robotik'
        ]
    );
    return ($testTeacherId > 0);
});

runTest('3.2 Check Duplicate NIP & Search Teacher', function() use ($teacherModel) {
    $taken = $teacherModel->isNipTaken('199001012020011099');
    $teachers = $teacherModel->getAll('Robotik');
    return ($taken === true && count($teachers) >= 1);
});

runTest('3.3 Update Teacher with User Account', function() use ($teacherModel, &$testTeacherId) {
    $ok = $teacherModel->updateTeacherWithUser(
        $testTeacherId,
        [
            'username' => 'guru_test_crud',
            'email' => 'guru.test.updated@sekolah.sch.id',
            'full_name' => 'Guru Test Updated, M.Kom',
            'phone' => '0899999992',
            'is_active' => 1
        ],
        [
            'nip' => '199001012020011099',
            'specialization' => 'Kecerdasan Buatan'
        ]
    );
    $t = $teacherModel->findById($testTeacherId);
    return ($ok && $t['specialization'] === 'Kecerdasan Buatan' && $t['email'] === 'guru.test.updated@sekolah.sch.id');
});

// 4. Test Student CRUD (Atomic Transaction)
$testStudentId = null;
runTest('4.1 Create Student with User Account & Class Association', function() use ($studentModel, $testClassId, &$testStudentId) {
    $testStudentId = $studentModel->createStudentWithUser(
        [
            'username' => 'siswa_test_crud',
            'email' => 'siswa.test@sekolah.sch.id',
            'password' => 'password123',
            'full_name' => 'Siswa Test CRUD',
            'phone' => '0899999993',
            'is_active' => 1
        ],
        [
            'nis' => '20269999',
            'nisn' => '0069999999',
            'class_id' => $testClassId,
            'gender' => 'L'
        ]
    );
    return ($testStudentId > 0);
});

runTest('4.2 Check Duplicate NIS & Search Student', function() use ($studentModel) {
    $taken = $studentModel->isNisTaken('20269999');
    $students = $studentModel->getAll('20269999');
    return ($taken === true && count($students) === 1);
});

runTest('4.3 Update Student with User Account', function() use ($studentModel, $testClassId, &$testStudentId) {
    $ok = $studentModel->updateStudentWithUser(
        $testStudentId,
        [
            'username' => 'siswa_test_crud',
            'email' => 'siswa.test.updated@sekolah.sch.id',
            'full_name' => 'Siswa Test Updated',
            'phone' => '0899999994',
            'is_active' => 1
        ],
        [
            'nis' => '20269999',
            'nisn' => '0069999999',
            'class_id' => $testClassId,
            'gender' => 'P'
        ]
    );
    $s = $studentModel->findById($testStudentId);
    return ($ok && $s['gender'] === 'P' && $s['email'] === 'siswa.test.updated@sekolah.sch.id');
});

// 5. Test User CRUD
$testUserId = null;
runTest('5.1 Create Generic User', function() use ($userModel, &$testUserId) {
    $testUserId = $userModel->create([
        'username' => 'user_test_crud',
        'email' => 'user.test@sekolah.sch.id',
        'password' => 'password123',
        'role' => 'student',
        'full_name' => 'User Standalone Test',
        'phone' => '0899999995',
        'is_active' => 1
    ]);
    return ($testUserId > 0);
});

runTest('5.2 Check Duplicate Username & Email', function() use ($userModel, &$testUserId) {
    $uTaken = $userModel->isUsernameTaken('user_test_crud');
    $eTaken = $userModel->isEmailTaken('user.test@sekolah.sch.id');
    return ($uTaken && $eTaken);
});

runTest('5.3 Update User', function() use ($userModel, &$testUserId) {
    $ok = $userModel->update($testUserId, [
        'username' => 'user_test_crud',
        'email' => 'user.test.updated@sekolah.sch.id',
        'role' => 'admin',
        'full_name' => 'User Standalone Updated',
        'phone' => '0899999996',
        'is_active' => 1
    ]);
    $u = $userModel->findById($testUserId);
    return ($ok && $u['role'] === 'admin' && $u['email'] === 'user.test.updated@sekolah.sch.id');
});

// 6. Test Delete operations
runTest('6.1 Delete User', function() use ($userModel, &$testUserId) {
    $ok = $userModel->delete($testUserId);
    $u = $userModel->findById($testUserId);
    return ($ok && $u === null);
});

runTest('6.2 Delete Student & User', function() use ($studentModel, &$testStudentId) {
    $ok = $studentModel->deleteStudent($testStudentId);
    $s = $studentModel->findById($testStudentId);
    return ($ok && $s === null);
});

runTest('6.3 Delete Teacher & User', function() use ($teacherModel, &$testTeacherId) {
    $ok = $teacherModel->deleteTeacher($testTeacherId);
    $t = $teacherModel->findById($testTeacherId);
    return ($ok && $t === null);
});

runTest('6.4 Delete Subject', function() use ($subjectModel, &$testSubId) {
    $ok = $subjectModel->delete($testSubId);
    $s = $subjectModel->findById($testSubId);
    return ($ok && $s === null);
});

runTest('6.5 Delete Class', function() use ($classModel, &$testClassId) {
    $ok = $classModel->delete($testClassId);
    $c = $classModel->findById($testClassId);
    return ($ok && $c === null);
});

echo "\n========================================================\n";
echo " Total Tests: " . ($passed + $failed) . " | Passed: {$passed} | Failed: {$failed}\n";
echo "========================================================\n";

exit($failed > 0 ? 1 : 0);
