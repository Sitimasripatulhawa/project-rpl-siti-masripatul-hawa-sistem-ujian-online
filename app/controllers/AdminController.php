<?php
/**
 * Super Admin Controller
 * AI Online Exam & Proctoring System
 */

class AdminController extends Controller
{
    protected UserModel $userModel;
    protected TeacherModel $teacherModel;
    protected StudentModel $studentModel;
    protected ClassModel $classModel;
    protected SubjectModel $subjectModel;
    protected SettingModel $settingModel;

    public function __construct()
    {
        // Enforce super admin role authorization
        $this->requireAuth(['admin']);

        // Load models
        $this->userModel = $this->model('UserModel');
        $this->teacherModel = $this->model('TeacherModel');
        $this->studentModel = $this->model('StudentModel');
        $this->classModel = $this->model('ClassModel');
        $this->subjectModel = $this->model('SubjectModel');
        $this->settingModel = $this->model('SettingModel');
    }

    // ==========================================================
    // 1. DASHBOARD
    // ==========================================================
    public function index()
    {
        $this->dashboard();
    }

    public function dashboard()
    {
        $db = Database::getInstance();

        // 5 Primary Admin metrics requested in PRD:
        // Total Guru, Total Peserta, Total Kelas, Total Mata Pelajaran, Total Ujian
        $totalTeachers = $db->query("SELECT COUNT(*) FROM teachers")->single();
        $totalStudents = $db->query("SELECT COUNT(*) FROM students")->single();
        $totalClasses = $db->query("SELECT COUNT(*) FROM classes")->single();
        $totalSubjects = $db->query("SELECT COUNT(*) FROM subjects")->single();
        $totalExams = $db->query("SELECT COUNT(*) FROM exams")->single();
        $totalUsers = $db->query("SELECT COUNT(*) FROM users")->single();

        $data = [
            'page_title' => 'Dashboard Super Admin',
            'current_role' => 'admin',
            'user' => $this->currentUser(),
            'stats' => [
                'total_teachers' => array_values($totalTeachers)[0] ?? 0,
                'total_students' => array_values($totalStudents)[0] ?? 0,
                'total_classes' => array_values($totalClasses)[0] ?? 0,
                'total_subjects' => array_values($totalSubjects)[0] ?? 0,
                'total_exams' => array_values($totalExams)[0] ?? 0,
                'total_users' => array_values($totalUsers)[0] ?? 0,
            ]
        ];

        $this->view('admin/dashboard', $data);
    }

    // ==========================================================
    // 2. USERS MANAGEMENT (CRUD)
    // ==========================================================
    public function users()
    {
        $search = trim($_GET['q'] ?? '');
        $role = trim($_GET['role'] ?? '');

        $users = $this->userModel->getAll($search, $role);
        $totalUsers = $this->userModel->countAll($search, $role);

        $data = [
            'page_title' => 'Manajemen Pengguna - Admin Panel',
            'users' => $users,
            'total_users' => $totalUsers,
            'search' => $search,
            'role_filter' => $role
        ];

        $this->view('admin/users/index', $data);
    }

    public function userShow($id = null)
    {
        if (!$id) $this->redirect('admin/users');
        $user = $this->userModel->findById((int)$id);
        if (!$user) {
            Auth::setFlash('danger', 'Pengguna tidak ditemukan.');
            $this->redirect('admin/users');
        }

        $data = [
            'page_title' => 'Detail Pengguna: ' . htmlspecialchars($user['full_name']),
            'user_detail' => $user
        ];
        $this->view('admin/users/show', $data);
    }

    public function userCreate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $role = trim($_POST['role'] ?? 'student');
            $fullName = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            // Validation
            if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
                Auth::setFlash('danger', 'Semua kolom bertanda bintang (*) wajib diisi.');
                $this->redirect('admin/userCreate');
            }

            if ($this->userModel->isUsernameTaken($username)) {
                Auth::setFlash('danger', "Username '{$username}' sudah digunakan.");
                $this->redirect('admin/userCreate');
            }

            if ($this->userModel->isEmailTaken($email)) {
                Auth::setFlash('danger', "Email '{$email}' sudah terdaftar.");
                $this->redirect('admin/userCreate');
            }

            $userId = $this->userModel->create([
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'full_name' => $fullName,
                'phone' => $phone,
                'is_active' => $isActive
            ]);

            Auth::setFlash('success', "Pengguna '{$fullName}' berhasil ditambahkan.");
            $this->redirect('admin/users');
        }

        $data = [
            'page_title' => 'Tambah Pengguna Baru - Admin Panel',
            'is_edit' => false
        ];
        $this->view('admin/users/form', $data);
    }

    public function userEdit($id = null)
    {
        if (!$id) $this->redirect('admin/users');
        $user = $this->userModel->findById((int)$id);
        if (!$user) {
            Auth::setFlash('danger', 'Pengguna tidak ditemukan.');
            $this->redirect('admin/users');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $role = trim($_POST['role'] ?? 'student');
            $fullName = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (empty($username) || empty($email) || empty($fullName)) {
                Auth::setFlash('danger', 'Username, email, dan nama lengkap wajib diisi.');
                $this->redirect('admin/userEdit/' . $id);
            }

            if ($this->userModel->isUsernameTaken($username, (int)$id)) {
                Auth::setFlash('danger', "Username '{$username}' sudah digunakan akun lain.");
                $this->redirect('admin/userEdit/' . $id);
            }

            if ($this->userModel->isEmailTaken($email, (int)$id)) {
                Auth::setFlash('danger', "Email '{$email}' sudah terdaftar pada akun lain.");
                $this->redirect('admin/userEdit/' . $id);
            }

            $updateData = [
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'full_name' => $fullName,
                'phone' => $phone,
                'is_active' => $isActive
            ];

            if (!empty($password)) {
                $updateData['password'] = $password;
            }

            $this->userModel->update((int)$id, $updateData);
            Auth::setFlash('success', "Data pengguna '{$fullName}' berhasil diperbarui.");
            $this->redirect('admin/users');
        }

        $data = [
            'page_title' => 'Edit Pengguna: ' . htmlspecialchars($user['full_name']),
            'is_edit' => true,
            'user' => $user
        ];
        $this->view('admin/users/form', $data);
    }

    public function userDelete($id = null)
    {
        if (!$id) $this->redirect('admin/users');
        $id = (int)$id;

        // Prevent admin from deleting their own active account
        if ($id === Auth::id()) {
            Auth::setFlash('danger', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
            $this->redirect('admin/users');
        }

        $user = $this->userModel->findById($id);
        if ($user) {
            $this->userModel->delete($id);
            Auth::setFlash('success', "Pengguna '{$user['full_name']}' berhasil dihapus.");
        } else {
            Auth::setFlash('danger', 'Pengguna tidak ditemukan.');
        }

        $this->redirect('admin/users');
    }

    // ==========================================================
    // 3. TEACHERS MANAGEMENT (CRUD)
    // ==========================================================
    public function teachers()
    {
        $search = trim($_GET['q'] ?? '');
        $teachers = $this->teacherModel->getAll($search);

        $data = [
            'page_title' => 'Data Guru & Penguji - Admin Panel',
            'teachers' => $teachers,
            'search' => $search
        ];

        $this->view('admin/teachers/index', $data);
    }

    public function teacherShow($id = null)
    {
        if (!$id) $this->redirect('admin/teachers');
        $teacher = $this->teacherModel->findById((int)$id);
        if (!$teacher) {
            Auth::setFlash('danger', 'Data guru tidak ditemukan.');
            $this->redirect('admin/teachers');
        }

        $data = [
            'page_title' => 'Detail Guru: ' . htmlspecialchars($teacher['full_name']),
            'teacher' => $teacher
        ];
        $this->view('admin/teachers/show', $data);
    }

    public function teacherCreate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nip = trim($_POST['nip'] ?? '');
            $fullName = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $specialization = trim($_POST['specialization'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (empty($nip) || empty($fullName) || empty($username) || empty($email) || empty($password)) {
                Auth::setFlash('danger', 'NIP, Nama, Username, Email, dan Kata Sandi wajib diisi.');
                $this->redirect('admin/teacherCreate');
            }

            if ($this->teacherModel->isNipTaken($nip)) {
                Auth::setFlash('danger', "NIP '{$nip}' sudah terdaftar.");
                $this->redirect('admin/teacherCreate');
            }

            if ($this->userModel->isUsernameTaken($username)) {
                Auth::setFlash('danger', "Username '{$username}' sudah digunakan.");
                $this->redirect('admin/teacherCreate');
            }

            if ($this->userModel->isEmailTaken($email)) {
                Auth::setFlash('danger', "Email '{$email}' sudah terdaftar.");
                $this->redirect('admin/teacherCreate');
            }

            try {
                $this->teacherModel->createTeacherWithUser(
                    [
                        'username' => $username,
                        'email' => $email,
                        'password' => $password,
                        'full_name' => $fullName,
                        'phone' => $phone,
                        'is_active' => $isActive
                    ],
                    [
                        'nip' => $nip,
                        'specialization' => $specialization
                    ]
                );

                Auth::setFlash('success', "Data Guru '{$fullName}' berhasil ditambahkan.");
                $this->redirect('admin/teachers');
            } catch (Exception $e) {
                Auth::setFlash('danger', 'Gagal menambahkan data guru: ' . $e->getMessage());
                $this->redirect('admin/teacherCreate');
            }
        }

        $data = [
            'page_title' => 'Tambah Guru Baru - Admin Panel',
            'is_edit' => false
        ];
        $this->view('admin/teachers/form', $data);
    }

    public function teacherEdit($id = null)
    {
        if (!$id) $this->redirect('admin/teachers');
        $teacher = $this->teacherModel->findById((int)$id);
        if (!$teacher) {
            Auth::setFlash('danger', 'Data guru tidak ditemukan.');
            $this->redirect('admin/teachers');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nip = trim($_POST['nip'] ?? '');
            $fullName = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $specialization = trim($_POST['specialization'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (empty($nip) || empty($fullName) || empty($username) || empty($email)) {
                Auth::setFlash('danger', 'NIP, Nama, Username, dan Email wajib diisi.');
                $this->redirect('admin/teacherEdit/' . $id);
            }

            if ($this->teacherModel->isNipTaken($nip, (int)$id)) {
                Auth::setFlash('danger', "NIP '{$nip}' sudah digunakan guru lain.");
                $this->redirect('admin/teacherEdit/' . $id);
            }

            if ($this->userModel->isUsernameTaken($username, (int)$teacher['user_id'])) {
                Auth::setFlash('danger', "Username '{$username}' sudah digunakan akun lain.");
                $this->redirect('admin/teacherEdit/' . $id);
            }

            if ($this->userModel->isEmailTaken($email, (int)$teacher['user_id'])) {
                Auth::setFlash('danger', "Email '{$email}' sudah digunakan akun lain.");
                $this->redirect('admin/teacherEdit/' . $id);
            }

            try {
                $userData = [
                    'username' => $username,
                    'email' => $email,
                    'full_name' => $fullName,
                    'phone' => $phone,
                    'is_active' => $isActive
                ];
                if (!empty($password)) {
                    $userData['password'] = $password;
                }

                $this->teacherModel->updateTeacherWithUser(
                    (int)$id,
                    $userData,
                    [
                        'nip' => $nip,
                        'specialization' => $specialization
                    ]
                );

                Auth::setFlash('success', "Data Guru '{$fullName}' berhasil diperbarui.");
                $this->redirect('admin/teachers');
            } catch (Exception $e) {
                Auth::setFlash('danger', 'Gagal memperbarui data guru: ' . $e->getMessage());
                $this->redirect('admin/teacherEdit/' . $id);
            }
        }

        $data = [
            'page_title' => 'Edit Data Guru: ' . htmlspecialchars($teacher['full_name']),
            'is_edit' => true,
            'teacher' => $teacher
        ];
        $this->view('admin/teachers/form', $data);
    }

    public function teacherDelete($id = null)
    {
        if (!$id) $this->redirect('admin/teachers');
        $teacher = $this->teacherModel->findById((int)$id);
        if ($teacher) {
            $this->teacherModel->deleteTeacher((int)$id);
            Auth::setFlash('success', "Data Guru '{$teacher['full_name']}' berhasil dihapus.");
        } else {
            Auth::setFlash('danger', 'Data guru tidak ditemukan.');
        }
        $this->redirect('admin/teachers');
    }

    // ==========================================================
    // 4. STUDENTS MANAGEMENT (CRUD)
    // ==========================================================
    public function students()
    {
        $search = trim($_GET['q'] ?? '');
        $classId = !empty($_GET['class_id']) ? (int)$_GET['class_id'] : null;

        $students = $this->studentModel->getAll($search, $classId);
        $classes = $this->classModel->getAll();

        $data = [
            'page_title' => 'Data Peserta Didik - Admin Panel',
            'students' => $students,
            'classes' => $classes,
            'search' => $search,
            'class_filter' => $classId
        ];

        $this->view('admin/students/index', $data);
    }

    public function studentShow($id = null)
    {
        if (!$id) $this->redirect('admin/students');
        $student = $this->studentModel->findById((int)$id);
        if (!$student) {
            Auth::setFlash('danger', 'Data peserta didik tidak ditemukan.');
            $this->redirect('admin/students');
        }

        $data = [
            'page_title' => 'Detail Peserta: ' . htmlspecialchars($student['full_name']),
            'student' => $student
        ];
        $this->view('admin/students/show', $data);
    }

    public function studentCreate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nis = trim($_POST['nis'] ?? '');
            $nisn = trim($_POST['nisn'] ?? '');
            $fullName = trim($_POST['full_name'] ?? '');
            $classId = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : null;
            $gender = trim($_POST['gender'] ?? 'L');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (empty($nis) || empty($fullName) || empty($username) || empty($email) || empty($password)) {
                Auth::setFlash('danger', 'NIS, Nama Lengkap, Username, Email, dan Kata Sandi wajib diisi.');
                $this->redirect('admin/studentCreate');
            }

            if ($this->studentModel->isNisTaken($nis)) {
                Auth::setFlash('danger', "NIS '{$nis}' sudah terdaftar.");
                $this->redirect('admin/studentCreate');
            }

            if (!empty($nisn) && $this->studentModel->isNisnTaken($nisn)) {
                Auth::setFlash('danger', "NISN '{$nisn}' sudah terdaftar.");
                $this->redirect('admin/studentCreate');
            }

            if ($this->userModel->isUsernameTaken($username)) {
                Auth::setFlash('danger', "Username '{$username}' sudah digunakan.");
                $this->redirect('admin/studentCreate');
            }

            if ($this->userModel->isEmailTaken($email)) {
                Auth::setFlash('danger', "Email '{$email}' sudah terdaftar.");
                $this->redirect('admin/studentCreate');
            }

            try {
                $this->studentModel->createStudentWithUser(
                    [
                        'username' => $username,
                        'email' => $email,
                        'password' => $password,
                        'full_name' => $fullName,
                        'phone' => $phone,
                        'is_active' => $isActive
                    ],
                    [
                        'nis' => $nis,
                        'nisn' => $nisn,
                        'class_id' => $classId,
                        'gender' => $gender
                    ]
                );

                Auth::setFlash('success', "Peserta Didik '{$fullName}' berhasil ditambahkan.");
                $this->redirect('admin/students');
            } catch (Exception $e) {
                Auth::setFlash('danger', 'Gagal menambahkan peserta didik: ' . $e->getMessage());
                $this->redirect('admin/studentCreate');
            }
        }

        $classes = $this->classModel->getAll();
        $data = [
            'page_title' => 'Tambah Peserta Didik Baru - Admin Panel',
            'is_edit' => false,
            'classes' => $classes
        ];
        $this->view('admin/students/form', $data);
    }

    public function studentEdit($id = null)
    {
        if (!$id) $this->redirect('admin/students');
        $student = $this->studentModel->findById((int)$id);
        if (!$student) {
            Auth::setFlash('danger', 'Data peserta didik tidak ditemukan.');
            $this->redirect('admin/students');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nis = trim($_POST['nis'] ?? '');
            $nisn = trim($_POST['nisn'] ?? '');
            $fullName = trim($_POST['full_name'] ?? '');
            $classId = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : null;
            $gender = trim($_POST['gender'] ?? 'L');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (empty($nis) || empty($fullName) || empty($username) || empty($email)) {
                Auth::setFlash('danger', 'NIS, Nama Lengkap, Username, dan Email wajib diisi.');
                $this->redirect('admin/studentEdit/' . $id);
            }

            if ($this->studentModel->isNisTaken($nis, (int)$id)) {
                Auth::setFlash('danger', "NIS '{$nis}' sudah digunakan peserta lain.");
                $this->redirect('admin/studentEdit/' . $id);
            }

            if (!empty($nisn) && $this->studentModel->isNisnTaken($nisn, (int)$id)) {
                Auth::setFlash('danger', "NISN '{$nisn}' sudah digunakan peserta lain.");
                $this->redirect('admin/studentEdit/' . $id);
            }

            if ($this->userModel->isUsernameTaken($username, (int)$student['user_id'])) {
                Auth::setFlash('danger', "Username '{$username}' sudah digunakan akun lain.");
                $this->redirect('admin/studentEdit/' . $id);
            }

            if ($this->userModel->isEmailTaken($email, (int)$student['user_id'])) {
                Auth::setFlash('danger', "Email '{$email}' sudah digunakan akun lain.");
                $this->redirect('admin/studentEdit/' . $id);
            }

            try {
                $userData = [
                    'username' => $username,
                    'email' => $email,
                    'full_name' => $fullName,
                    'phone' => $phone,
                    'is_active' => $isActive
                ];
                if (!empty($password)) {
                    $userData['password'] = $password;
                }

                $this->studentModel->updateStudentWithUser(
                    (int)$id,
                    $userData,
                    [
                        'nis' => $nis,
                        'nisn' => $nisn,
                        'class_id' => $classId,
                        'gender' => $gender
                    ]
                );

                Auth::setFlash('success', "Data Peserta Didik '{$fullName}' berhasil diperbarui.");
                $this->redirect('admin/students');
            } catch (Exception $e) {
                Auth::setFlash('danger', 'Gagal memperbarui data peserta: ' . $e->getMessage());
                $this->redirect('admin/studentEdit/' . $id);
            }
        }

        $classes = $this->classModel->getAll();
        $data = [
            'page_title' => 'Edit Peserta: ' . htmlspecialchars($student['full_name']),
            'is_edit' => true,
            'student' => $student,
            'classes' => $classes
        ];
        $this->view('admin/students/form', $data);
    }

    public function studentDelete($id = null)
    {
        if (!$id) $this->redirect('admin/students');
        $student = $this->studentModel->findById((int)$id);
        if ($student) {
            $this->studentModel->deleteStudent((int)$id);
            Auth::setFlash('success', "Peserta Didik '{$student['full_name']}' berhasil dihapus.");
        } else {
            Auth::setFlash('danger', 'Data peserta didik tidak ditemukan.');
        }
        $this->redirect('admin/students');
    }

    // ==========================================================
    // 5. CLASSES MANAGEMENT (CRUD)
    // ==========================================================
    public function classes()
    {
        $search = trim($_GET['q'] ?? '');
        $classes = $this->classModel->getAll($search);

        $data = [
            'page_title' => 'Manajemen Kelas / Rombel - Admin Panel',
            'classes' => $classes,
            'search' => $search
        ];

        $this->view('admin/classes/index', $data);
    }

    public function classCreate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $level = trim($_POST['level'] ?? '');
            $academicYear = trim($_POST['academic_year'] ?? '');

            if (empty($name) || empty($level) || empty($academicYear)) {
                Auth::setFlash('danger', 'Nama kelas, tingkat, dan tahun ajaran wajib diisi.');
                $this->redirect('admin/classCreate');
            }

            $this->classModel->create([
                'name' => $name,
                'level' => $level,
                'academic_year' => $academicYear
            ]);

            Auth::setFlash('success', "Kelas '{$name}' berhasil ditambahkan.");
            $this->redirect('admin/classes');
        }

        $data = [
            'page_title' => 'Tambah Kelas Baru - Admin Panel',
            'is_edit' => false
        ];
        $this->view('admin/classes/form', $data);
    }

    public function classEdit($id = null)
    {
        if (!$id) $this->redirect('admin/classes');
        $class = $this->classModel->findById((int)$id);
        if (!$class) {
            Auth::setFlash('danger', 'Data kelas tidak ditemukan.');
            $this->redirect('admin/classes');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $level = trim($_POST['level'] ?? '');
            $academicYear = trim($_POST['academic_year'] ?? '');

            if (empty($name) || empty($level) || empty($academicYear)) {
                Auth::setFlash('danger', 'Semua kolom wajib diisi.');
                $this->redirect('admin/classEdit/' . $id);
            }

            $this->classModel->update((int)$id, [
                'name' => $name,
                'level' => $level,
                'academic_year' => $academicYear
            ]);

            Auth::setFlash('success', "Data kelas '{$name}' berhasil diperbarui.");
            $this->redirect('admin/classes');
        }

        $data = [
            'page_title' => 'Edit Kelas: ' . htmlspecialchars($class['name']),
            'is_edit' => true,
            'class' => $class
        ];
        $this->view('admin/classes/form', $data);
    }

    public function classDelete($id = null)
    {
        if (!$id) $this->redirect('admin/classes');
        $class = $this->classModel->findById((int)$id);
        if ($class) {
            $this->classModel->delete((int)$id);
            Auth::setFlash('success', "Kelas '{$class['name']}' berhasil dihapus.");
        } else {
            Auth::setFlash('danger', 'Data kelas tidak ditemukan.');
        }
        $this->redirect('admin/classes');
    }

    // ==========================================================
    // 6. SUBJECTS MANAGEMENT (CRUD)
    // ==========================================================
    public function subjects()
    {
        $search = trim($_GET['q'] ?? '');
        $subjects = $this->subjectModel->getAll($search);

        $data = [
            'page_title' => 'Mata Pelajaran - Admin Panel',
            'subjects' => $subjects,
            'search' => $search
        ];

        $this->view('admin/subjects/index', $data);
    }

    public function subjectCreate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = trim($_POST['code'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($code) || empty($name)) {
                Auth::setFlash('danger', 'Kode dan nama mata pelajaran wajib diisi.');
                $this->redirect('admin/subjectCreate');
            }

            if ($this->subjectModel->isCodeTaken($code)) {
                Auth::setFlash('danger', "Kode mata pelajaran '{$code}' sudah digunakan.");
                $this->redirect('admin/subjectCreate');
            }

            $this->subjectModel->create([
                'code' => $code,
                'name' => $name,
                'description' => $description
            ]);

            Auth::setFlash('success', "Mata pelajaran '{$name}' ({$code}) berhasil ditambahkan.");
            $this->redirect('admin/subjects');
        }

        $data = [
            'page_title' => 'Tambah Mata Pelajaran Baru - Admin Panel',
            'is_edit' => false
        ];
        $this->view('admin/subjects/form', $data);
    }

    public function subjectEdit($id = null)
    {
        if (!$id) $this->redirect('admin/subjects');
        $subject = $this->subjectModel->findById((int)$id);
        if (!$subject) {
            Auth::setFlash('danger', 'Mata pelajaran tidak ditemukan.');
            $this->redirect('admin/subjects');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = trim($_POST['code'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($code) || empty($name)) {
                Auth::setFlash('danger', 'Kode dan nama mata pelajaran wajib diisi.');
                $this->redirect('admin/subjectEdit/' . $id);
            }

            if ($this->subjectModel->isCodeTaken($code, (int)$id)) {
                Auth::setFlash('danger', "Kode mata pelajaran '{$code}' sudah digunakan pada mapel lain.");
                $this->redirect('admin/subjectEdit/' . $id);
            }

            $this->subjectModel->update((int)$id, [
                'code' => $code,
                'name' => $name,
                'description' => $description
            ]);

            Auth::setFlash('success', "Mata pelajaran '{$name}' berhasil diperbarui.");
            $this->redirect('admin/subjects');
        }

        $data = [
            'page_title' => 'Edit Mata Pelajaran: ' . htmlspecialchars($subject['name']),
            'is_edit' => true,
            'subject' => $subject
        ];
        $this->view('admin/subjects/form', $data);
    }

    public function subjectDelete($id = null)
    {
        if (!$id) $this->redirect('admin/subjects');
        $subject = $this->subjectModel->findById((int)$id);
        if ($subject) {
            $this->subjectModel->delete((int)$id);
            Auth::setFlash('success', "Mata pelajaran '{$subject['name']}' berhasil dihapus.");
        } else {
            Auth::setFlash('danger', 'Mata pelajaran tidak ditemukan.');
        }
        $this->redirect('admin/subjects');
    }

    // ==========================================================
    // 7. HASIL UJIAN SE-SEKOLAH (SUPER ADMIN)
    // ==========================================================
    public function results()
    {
        $resultModel = $this->model('ResultModel');
        $search = trim((string)($_GET['search'] ?? ''));

        $overview = $resultModel->getAdminResultsOverview($search);

        $data = [
            'page_title' => 'Ringkasan Hasil Ujian Se-Sekolah',
            'current_role' => 'admin',
            'user' => $this->currentUser(),
            'stats' => $overview['stats'],
            'exams' => $overview['exams'],
            'search' => $search
        ];

        $this->view('admin/results/index', $data);
    }

    // ==========================================================
    // 8. EMAIL & SMTP CONFIGURATION & LOGS (SUPER ADMIN)
    // ==========================================================
    public function mailSettings()
    {
        $emailService = new EmailService();
        $statusFilter = trim((string)($_GET['status'] ?? ''));
        $scopeFilter = trim((string)($_GET['scope'] ?? 'all'));
        $logs = $emailService->getLogs(50, 0, $statusFilter, $scopeFilter);

        $stats = $emailService->getStats();
        $configDetails = EmailService::getConfigurationDetails();

        $data = [
            'page_title' => 'Pengaturan Email & Log Pengiriman SMTP',
            'current_role' => 'admin',
            'user' => $this->currentUser(),
            'is_configured' => $configDetails['is_configured'],
            'config_details' => $configDetails,
            'mail_host' => $configDetails['host'],
            'mail_port' => $configDetails['port'],
            'mail_encryption' => $configDetails['encryption'],
            'mail_username_masked' => $configDetails['username_masked'],
            'mail_from_address' => $configDetails['from_address'],
            'mail_from_name' => $configDetails['from_name'],
            'logs' => $logs,
            'stats' => $stats,
            'current_filter' => $statusFilter,
            'current_scope' => $scopeFilter
        ];

        $this->view('admin/mail/index', $data);
    }

    public function sendTestEmail()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/mailSettings');
        }

        $this->verifyCsrfToken(true);

        $testEmail = trim((string)($_POST['test_email'] ?? ''));
        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            Auth::setFlash('danger', 'Alamat email pengujian tidak valid. Mohon masukkan format email yang benar.');
            $this->redirect('admin/mailSettings');
        }

        $emailService = new EmailService();
        $result = $emailService->testSmtpConnection($testEmail);

        if ($result['success']) {
            Auth::setFlash('success', 'Email uji coba BERHASIL diserahkan ke server SMTP untuk penerima ' . htmlspecialchars($testEmail) . '. Silakan periksa folder Inbox (atau Spam). Catatan: Keberhasilan penyerahan ke SMTP memastikan koneksi dan kredensial valid.');
        } else {
            Auth::setFlash('danger', 'Pengujian email GAGAL: ' . htmlspecialchars($result['message']));
        }

        $this->redirect('admin/mailSettings');
    }

    public function retryEmail($id = null)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/mailSettings');
        }

        $this->verifyCsrfToken(true);
        $logId = (int)$id;

        $emailService = new EmailService();
        $res = $emailService->retryFailedEmail($logId);

        if ($res['success']) {
            Auth::setFlash('success', 'Email berhasil diserahkan ke server SMTP.');
        } else {
            Auth::setFlash('danger', 'Gagal mengirim ulang email: ' . htmlspecialchars($res['message'] ?? 'Kesalahan SMTP'));
        }

        $this->redirect('admin/mailSettings');
    }

    // ==========================================================
    // 9. SYSTEM CONFIGURATION (SUPER ADMIN)
    // ==========================================================
    public function settings()
    {
        $activeTab = trim((string)($_GET['tab'] ?? 'general'));
        if (!in_array($activeTab, ['general', 'exam', 'proctoring', 'ai', 'mail'])) {
            $activeTab = 'general';
        }

        $groupedSettings = $this->settingModel->getAllGrouped();
        $smtpConfig = SettingModel::getSmtpConfig();

        // Get prioritized Asian and Indonesian timezones plus popular world timezones
        $popularTimezones = [
            'Asia/Jakarta' => 'Asia/Jakarta (WIB, UTC+7)',
            'Asia/Makassar' => 'Asia/Makassar (WITA, UTC+8)',
            'Asia/Jayapura' => 'Asia/Jayapura (WIT, UTC+9)',
            'Asia/Singapore' => 'Asia/Singapore (UTC+8)',
            'Asia/Kuala_Lumpur' => 'Asia/Kuala Lumpur (UTC+8)',
            'Asia/Bangkok' => 'Asia/Bangkok (UTC+7)',
            'UTC' => 'UTC (Coordinated Universal Time)'
        ];

        $data = [
            'page_title' => 'Konfigurasi Sistem & Pengaturan - Super Admin',
            'current_role' => 'admin',
            'user' => $this->currentUser(),
            'active_tab' => $activeTab,
            'settings' => $groupedSettings,
            'smtp' => $smtpConfig,
            'popular_timezones' => $popularTimezones,
            'available_ai_models' => [
                'gemini-3.8-flash' => 'Google Gemini 3.8 Flash (Direkomendasikan - Cepat, Cerdas & Hemat Kuota)',
                'gemma-4-26b-a4b-it' => 'Google Gemma 4 26B A4B IT (Presisi Tinggi untuk Analisis Edukasi)',
                'gemini-3.1-flash-lite' => 'Google Gemini 3.1 Flash-Lite (Super Ringan & Respon Instan)'
            ]
        ];

        $this->view('admin/settings/index', $data);
    }

    public function configuration()
    {
        $this->settings();
    }

    public function updateSettings()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/settings');
        }

        $this->verifyCsrfToken(true);
        $group = trim((string)($_POST['group'] ?? 'general'));

        try {
            switch ($group) {
                case 'general':
                    $appName = trim((string)($_POST['app_name'] ?? ''));
                    $appShortName = trim((string)($_POST['app_short_name'] ?? ''));
                    $appInstitution = trim((string)($_POST['app_institution'] ?? ''));
                    $appTimezone = trim((string)($_POST['app_timezone'] ?? 'Asia/Jakarta'));
                    $appHeaderTagline = trim((string)($_POST['app_header_tagline'] ?? ''));

                    if (strlen($appName) < 2) {
                        throw new Exception('Nama Aplikasi minimal 2 karakter.');
                    }
                    if (strlen($appShortName) < 2) {
                        throw new Exception('Nama Singkat Aplikasi minimal 2 karakter.');
                    }
                    if (!in_array($appTimezone, DateTimeZone::listIdentifiers(), true)) {
                        throw new Exception('Zona waktu tidak valid.');
                    }

                    $this->settingModel->set('app_name', $appName, 'general', 'Nama Aplikasi');
                    $this->settingModel->set('app_short_name', $appShortName, 'general', 'Nama Singkat (Brand)');
                    $this->settingModel->set('app_institution', $appInstitution, 'general', 'Nama Institusi / Sekolah');
                    $this->settingModel->set('app_timezone', $appTimezone, 'general', 'Zona Waktu Default');
                    $this->settingModel->set('app_header_tagline', $appHeaderTagline, 'general', 'Tagline / Slogan Sistem');

                    // Synchronize with .env for application constants
                    SettingModel::updateEnvFile([
                        'APP_NAME' => $appName,
                        'APP_SHORT_NAME' => $appShortName,
                        'APP_TIMEZONE' => $appTimezone
                    ]);

                    Auth::setFlash('success', 'Konfigurasi Identitas & Umum berhasil disimpan dan disinkronkan.');
                    break;

                case 'exam':
                    $duration = (int)($_POST['exam_default_duration'] ?? 90);
                    $passingScore = (float)($_POST['exam_default_passing_score'] ?? 75.00);
                    $randomQuestion = isset($_POST['exam_default_random_question']) ? '1' : '0';
                    $randomOption = isset($_POST['exam_default_random_option']) ? '1' : '0';
                    $enableProctoring = isset($_POST['exam_default_enable_proctoring']) ? '1' : '0';
                    $showResult = isset($_POST['exam_default_show_result']) ? '1' : '0';

                    if ($duration < 1 || $duration > 600) {
                        throw new Exception('Durasi default ujian harus antara 1 sampai 600 menit.');
                    }
                    if ($passingScore < 0 || $passingScore > 100) {
                        throw new Exception('Nilai kelulusan default (KKM) harus antara 0 sampai 100.');
                    }

                    $this->settingModel->set('exam_default_duration', (string)$duration, 'exam', 'Durasi Default Ujian (Menit)');
                    $this->settingModel->set('exam_default_passing_score', number_format($passingScore, 2, '.', ''), 'exam', 'Nilai Kelulusan Default (KKM)');
                    $this->settingModel->set('exam_default_random_question', $randomQuestion, 'exam', 'Acak Urutan Soal');
                    $this->settingModel->set('exam_default_random_option', $randomOption, 'exam', 'Acak Opsi Pilihan Ganda');
                    $this->settingModel->set('exam_default_enable_proctoring', $enableProctoring, 'exam', 'Aktifkan AI Proctoring');
                    $this->settingModel->set('exam_default_show_result', $showResult, 'exam', 'Tampilkan Nilai ke Siswa');

                    Auth::setFlash('success', 'Pengaturan Default Ujian berhasil disimpan.');
                    break;

                case 'proctoring':
                    $cooldown = (int)($_POST['proctoring_cooldown_seconds'] ?? 10);
                    $wFaceNotDetected = (int)($_POST['proctoring_weight_face_not_detected'] ?? 10);
                    $wMultiFace = (int)($_POST['proctoring_weight_multiple_face'] ?? 40);
                    $wCameraDisc = (int)($_POST['proctoring_weight_camera_disconnected'] ?? 20);
                    $wCameraBlocked = (int)($_POST['proctoring_weight_camera_blocked'] ?? 15);
                    $wSuspiciousGaze = (int)($_POST['proctoring_weight_suspicious_gaze'] ?? 15);
                    $wTabSwitch = (int)($_POST['proctoring_weight_tab_switch'] ?? 10);
                    $wFaceChanged = (int)($_POST['proctoring_weight_face_changed'] ?? 50);

                    $thNormalMax = (int)($_POST['proctoring_threshold_normal_max'] ?? 29);
                    $thWarningMax = (int)($_POST['proctoring_threshold_warning_max'] ?? 59);
                    $thHighRiskMin = (int)($_POST['proctoring_threshold_high_risk_min'] ?? 60);

                    if ($cooldown < 1 || $cooldown > 120) {
                        throw new Exception('Debounce cooldown proctoring harus antara 1 sampai 120 detik.');
                    }
                    if ($thNormalMax >= $thWarningMax || $thWarningMax >= $thHighRiskMin) {
                        throw new Exception('Ambang batas risiko tidak logis. Syarat: Normal Max (' . $thNormalMax . ') < Warning Max (' . $thWarningMax . ') < High Risk Min (' . $thHighRiskMin . ').');
                    }

                    $this->settingModel->set('proctoring_cooldown_seconds', (string)$cooldown, 'proctoring', 'Debounce Cooldown (Detik)');
                    $this->settingModel->set('proctoring_weight_face_not_detected', (string)$wFaceNotDetected, 'proctoring', 'Bobot: Wajah Tidak Terdeteksi');
                    $this->settingModel->set('proctoring_weight_multiple_face', (string)$wMultiFace, 'proctoring', 'Bobot: Lebih dari 1 Wajah');
                    $this->settingModel->set('proctoring_weight_camera_disconnected', (string)$wCameraDisc, 'proctoring', 'Bobot: Kamera Terputus');
                    $this->settingModel->set('proctoring_weight_camera_blocked', (string)$wCameraBlocked, 'proctoring', 'Bobot: Kamera Tertutup / Gelap');
                    $this->settingModel->set('proctoring_weight_suspicious_gaze', (string)$wSuspiciousGaze, 'proctoring', 'Bobot: Pandangan Mencurigakan');
                    $this->settingModel->set('proctoring_weight_tab_switch', (string)$wTabSwitch, 'proctoring', 'Bobot: Pindah Tab Browser');
                    $this->settingModel->set('proctoring_weight_face_changed', (string)$wFaceChanged, 'proctoring', 'Bobot: Pergantian Wajah (Joki)');

                    $this->settingModel->set('proctoring_threshold_normal_max', (string)$thNormalMax, 'proctoring', 'Batas Normal Max');
                    $this->settingModel->set('proctoring_threshold_warning_max', (string)$thWarningMax, 'proctoring', 'Batas Warning Max');
                    $this->settingModel->set('proctoring_threshold_high_risk_min', (string)$thHighRiskMin, 'proctoring', 'Batas High Risk Min');

                    Auth::setFlash('success', 'Konfigurasi Sensitivitas & Ambang Batas Proctoring AI berhasil diperbarui.');
                    break;

                case 'ai':
                    $model = trim((string)($_POST['gemini_model'] ?? 'gemini-3.8-flash'));
                    $apiKey = trim((string)($_POST['gemini_api_key'] ?? ''));

                    $allowedModels = ['gemini-3.8-flash', 'gemma-4-26b-a4b-it', 'gemini-3.1-flash-lite'];
                    if (!in_array($model, $allowedModels, true)) {
                        throw new Exception('Model AI Gemini yang dipilih tidak valid.');
                    }

                    $this->settingModel->set('gemini_model', $model, 'ai', 'Model Google Gemini AI');

                    $envUpdates = ['GEMINI_MODEL' => $model];
                    if (!empty($apiKey)) {
                        $envUpdates['GEMINI_API_KEY'] = $apiKey;
                    }

                    SettingModel::updateEnvFile($envUpdates);
                    Auth::setFlash('success', 'Konfigurasi AI Chatbot (Model & Kredensial) berhasil disimpan.');
                    break;

                case 'mail':
                    $host = trim((string)($_POST['mail_host'] ?? 'smtp.gmail.com'));
                    $port = (int)($_POST['mail_port'] ?? 587);
                    $encryption = strtolower(trim((string)($_POST['mail_encryption'] ?? 'tls')));
                    $username = trim((string)($_POST['mail_username'] ?? ''));
                    $password = trim((string)($_POST['mail_password'] ?? ''));
                    $fromAddress = trim((string)($_POST['mail_from_address'] ?? ''));
                    $fromName = trim((string)($_POST['mail_from_name'] ?? ''));

                    if (empty($host)) {
                        throw new Exception('SMTP Host tidak boleh kosong.');
                    }
                    if ($port < 1 || $port > 65535) {
                        throw new Exception('Port SMTP harus antara 1 sampai 65535.');
                    }
                    if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
                        throw new Exception('Tipe enkripsi SMTP harus TLS, SSL, atau none.');
                    }
                    if (!empty($fromAddress) && !filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception('Format alamat email pengirim (From Address) tidak valid.');
                    }

                    $envUpdates = [
                        'MAIL_HOST' => $host,
                        'MAIL_PORT' => (string)$port,
                        'MAIL_ENCRYPTION' => $encryption,
                        'MAIL_USERNAME' => $username,
                        'MAIL_FROM_ADDRESS' => $fromAddress ?: $username,
                        'MAIL_FROM_NAME' => $fromName ?: APP_NAME
                    ];

                    // SAFE PASSWORD HANDLING:
                    // Only update password if admin entered a new password. If blank, preserve existing password!
                    if (!empty($password)) {
                        $envUpdates['MAIL_PASSWORD'] = $password;
                    } else {
                        $envUpdates['MAIL_PASSWORD'] = null; // SettingModel::updateEnvFile will safely preserve it!
                    }

                    SettingModel::updateEnvFile($envUpdates);
                    Auth::setFlash('success', 'Pengaturan server SMTP berhasil disimpan dengan aman tanpa merusak kredensial rahasia.');
                    break;

                default:
                    throw new Exception('Grup konfigurasi tidak dikenali.');
            }
        } catch (Exception $e) {
            Auth::setFlash('danger', 'Gagal menyimpan pengaturan: ' . $e->getMessage());
        }

        $this->redirect('admin/settings?tab=' . urlencode($group));
    }
}

