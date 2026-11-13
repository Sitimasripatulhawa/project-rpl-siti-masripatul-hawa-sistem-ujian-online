<?php
/**
 * Notification Controller
 * Manages in-website notifications for authenticated users (Admin, Guru, Peserta Didik).
 */

class NotificationController extends Controller
{
    protected NotificationModel $notificationModel;

    public function __construct()
    {
        // Enforce authentication for all notification actions
        $this->requireAuth();
        $this->notificationModel = new NotificationModel();
    }

    /**
     * Display all notifications page
     */
    public function index()
    {
        $user = $this->currentUser();
        $userId = (int)$user['id'];

        $filter = trim($_GET['filter'] ?? 'all');
        $unreadOnly = ($filter === 'unread');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $notifications = $this->notificationModel->getByUser($userId, $limit, $offset, $unreadOnly);
        $unreadCount = $this->notificationModel->countUnread($userId);

        $data = [
            'page_title' => 'Pusat Notifikasi - ' . APP_SHORT_NAME,
            'current_role' => $user['role'],
            'user' => $user,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'current_filter' => $filter,
            'page' => $page
        ];

        $this->view('notification/index', $data);
    }

    /**
     * AJAX Endpoint: Get unread notifications count for navbar bell badge
     */
    public function unreadCount()
    {
        $user = $this->currentUser();
        $userId = (int)($user['id'] ?? 0);

        $count = $this->notificationModel->countUnread($userId);

        $this->json([
            'success' => true,
            'unread_count' => $count
        ]);
    }

    /**
     * AJAX Endpoint: Get recent notifications for navbar dropdown
     */
    public function recent()
    {
        $user = $this->currentUser();
        $userId = (int)($user['id'] ?? 0);

        $recent = $this->notificationModel->getRecent($userId, 5);
        $count = $this->notificationModel->countUnread($userId);

        $this->json([
            'success' => true,
            'unread_count' => $count,
            'notifications' => $recent
        ]);
    }

    /**
     * Mark a single notification as read
     *
     * @param int|string $id
     */
    public function markAsRead($id = null)
    {
        $this->verifyCsrfToken(false);
        $user = $this->currentUser();
        $userId = (int)$user['id'];
        $notifId = (int)$id;

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                  || (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

        if ($notifId > 0) {
            $this->notificationModel->markAsRead($notifId, $userId);
        }

        if ($isAjax) {
            $this->json([
                'success' => true,
                'unread_count' => $this->notificationModel->countUnread($userId),
                'message' => 'Notifikasi ditandai sudah dibaca.'
            ]);
        }

        $this->redirect('notification');
    }

    /**
     * Mark all notifications as read for current user
     */
    public function markAllAsRead()
    {
        $this->verifyCsrfToken(false);
        $user = $this->currentUser();
        $userId = (int)$user['id'];

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                  || (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

        $this->notificationModel->markAllAsRead($userId);

        if ($isAjax) {
            $this->json([
                'success' => true,
                'unread_count' => 0,
                'message' => 'Semua notifikasi berhasil ditandai sudah dibaca.'
            ]);
        }

        Auth::setFlash('success', 'Semua notifikasi telah ditandai sebagai sudah dibaca.');
        $this->redirect('notification');
    }

    /**
     * Delete notification
     *
     * @param int|string $id
     */
    public function delete($id = null)
    {
        $this->verifyCsrfToken(true);
        $user = $this->currentUser();
        $userId = (int)$user['id'];
        $notifId = (int)$id;

        $this->notificationModel->delete($notifId, $userId);
        Auth::setFlash('success', 'Notifikasi berhasil dihapus.');
        $this->redirect('notification');
    }
}
