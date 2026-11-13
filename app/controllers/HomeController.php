<?php
/**
 * Home Controller
 * AI Online Exam & Proctoring System
 */

class HomeController extends Controller
{
    /**
     * Display Homepage (Beranda) with System Overview & AI Chatbot
     */
    public function index()
    {
        $stats = [
            'total_users' => 0,
            'active_exams' => 0,
            'question_banks' => 0,
            'proctoring_alerts' => 0,
        ];

        try {
            $db = Database::getInstance();
            if ($db && $db->isConnected()) {
                $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
                $stats['total_users'] = (int)$userCount;

                $examCount = $db->query("SELECT COUNT(*) FROM exams WHERE status IN ('published', 'ongoing')")->fetchColumn();
                $stats['active_exams'] = (int)$examCount;

                $qCount = $db->query("SELECT COUNT(*) FROM questions")->fetchColumn();
                $stats['question_banks'] = (int)$qCount;

                $pCount = $db->query("SELECT COUNT(*) FROM proctoring_events WHERE severity IN ('WARNING', 'CRITICAL')")->fetchColumn();
                $stats['proctoring_alerts'] = (int)$pCount;
            } else {
                // Realistic mock stats for landing demonstration if DB offline
                $stats = [
                    'total_users' => 128,
                    'active_exams' => 6,
                    'question_banks' => 450,
                    'proctoring_alerts' => 14,
                ];
            }
        } catch (Throwable $e) {
            $stats = [
                'total_users' => 128,
                'active_exams' => 6,
                'question_banks' => 450,
                'proctoring_alerts' => 14,
            ];
        }

        $this->view('home/index', [
            'page_title' => 'Beranda - Sistem Ujian Online & AI Proctoring',
            'stats' => $stats,
            'auth_user' => Auth::user(),
            'default_suggestions' => ChatbotService::getDefaultSuggestions()
        ]);
    }

    /**
     * Chatbot API endpoint for receiving questions and returning intelligent responses
     */
    public function chat()
    {
        // Set JSON header
        header('Content-Type: application/json; charset=utf-8');

        // Check request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'status' => 'error',
                'message' => 'Method Not Allowed. Gunakan POST request.'
            ]);
            exit;
        }

        // Parse input from JSON or POST form body
        $rawInput = file_get_contents('php://input');
        $jsonInput = json_decode($rawInput, true);

        $message = '';
        if (is_array($jsonInput) && isset($jsonInput['message'])) {
            $message = trim((string)$jsonInput['message']);
        } elseif (isset($_POST['message'])) {
            $message = trim((string)$_POST['message']);
        }

        $currentUser = Auth::user();
        $response = ChatbotService::reply($message, $currentUser);

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
