<?php

class NotificationController extends Controller
{
    // Main notification page (requests, wanted offers, ...)
    public function index(): void
    {
        $user_id = $this->requireLogin();
        $notifications = $this->model('Notification');

        if (isset($_GET['clear_id']) && is_numeric($_GET['clear_id'])) {
            $notifications->deleteForUser((int)$_GET['clear_id'], $user_id);
            redirect('notifications');
        }

        if (isset($_GET['read_id']) && is_numeric($_GET['read_id'])) {
            $notifications->markReadForUser((int)$_GET['read_id'], $user_id);
            redirect('notifications');
        }

        $result = $notifications->allForUser($user_id);
        $requests = $this->model('Request');

        // Attach request details for request-type notifications
        foreach ($result as &$notification) {
            $type = strtolower(trim($notification['notification_type'] ?? ''));
            $isWantedOffer = in_array($type, ['wanted_offer', 'offer', 'wanted offer'], true);
            $requestId = (int)$notification['request_id'];

            $notification['request'] = (!$isWantedOffer && $requestId > 0)
                ? $requests->notificationContext($requestId)
                : null;
        }
        unset($notification);

        $this->view('user/notifications', compact('user_id', 'result'));
    }

    // Simple list (mark read / clear by POST)
    public function user(): void
    {
        $userId = $this->requireUser();
        $notifications = $this->model('Notification');

        if (is_post()) {
            $action = $_POST['action'] ?? '';
            $notificationId = (int)($_POST['notification_id'] ?? 0);

            if ($action === 'clear' && $notificationId > 0) {
                $notifications->deleteForUser($notificationId, $userId);
            } elseif ($notificationId > 0) {
                $notifications->markReadForUser($notificationId, $userId);
            }

            redirect('notifications/user');
        }

        $this->view('user/user_notifications', [
            'notifications' => $notifications->latestForUser($userId),
        ]);
    }
}
