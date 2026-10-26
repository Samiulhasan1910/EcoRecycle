<?php

/*
 * Donate requests made by users on donated items
 */
class RequestController extends Controller
{
    private int $user_id;

    public function __construct()
    {
        $this->user_id = $this->requireLogin();
    }

    public function mine(): void
    {
        $this->view('requests/mine', [
            'user_id' => $this->user_id,
            'result' => $this->model('Request')->forUser($this->user_id),
        ]);
    }

    // POST from the item page: send a Donate request to the owner
    public function create(): void
    {
        $user_id = $this->user_id;
        $message = '';

        $item_id = 0;

        if (isset($_POST['item_id']) && is_numeric($_POST['item_id'])) {
            $item_id = (int)$_POST['item_id'];
        } elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $item_id = (int)$_GET['id'];
        }

        if ($item_id <= 0) {
            redirect('items/browse');
        }

        if (!is_post()) {
            redirect('items/view', ['id' => $item_id]);
        }

        $item = $this->model('Item')->find($item_id);

        if (!$item) {
            redirect('items/browse');
        }

        $itemType = strtolower(trim($item['type'] ?? ''));

        // Private NGO donations cannot be requested
        if ($itemType === 'donate' && !empty($item['ngo_id'])) {
            redirect('items/browse');
        }

        $owner_id = (int)$item['user_id'];

        if ($owner_id === $user_id || $itemType !== 'donate' || strtolower(trim($item['status'] ?? '')) !== 'available') {
            redirect('items/view', ['id' => $item_id]);
        }

        $requests = $this->model('Request');

        if ($oldRequest = $requests->latestDonateRequest($item_id, $user_id)) {
            $message = match (strtolower(trim($oldRequest['status'] ?? ''))) {
                'pending' => 'You have already sent a request for this item.',
                'accepted' => 'Your request for this item has already been accepted.',
                'rejected' => 'Your previous request for this item was rejected by the owner. You cannot request this item again.',
                default => 'You already have a request for this item.',
            };
        }

        if ($message === '') {
            $request_id = $requests->createDonate($user_id, $item_id);

            $this->model('Notification')->create([
                'user_id' => $owner_id,
                'request_id' => $request_id,
                'notification_type' => 'request',
                'message' => ($_SESSION['name'] ?? 'Someone') . ' has requested your donated item: ' . ($item['title'] ?? 'your donated item') . '.',
                'status' => 'Unread',
            ]);

            $message = 'Request sent successfully!';
        }

        $this->view('requests/create', compact('user_id', 'item_id', 'item', 'owner_id', 'message'));
    }

    // Donor accepts / rejects an NGO's request for their public donation
    public function ngoAction(): void
    {
        $userId = $this->requireUser();

        $requestId = (int)($_GET['request_id'] ?? 0);
        $notificationId = (int)($_GET['notification_id'] ?? 0);
        $action = strtolower(trim($_GET['action'] ?? ''));

        if ($requestId <= 0 || !in_array($action, ['accept', 'reject'], true)) {
            redirect('notifications');
        }

        try {
            $this->model('Request')->answerNgoRequest($requestId, $userId, $action, $notificationId);

            redirect('notifications', ['success' => 'ngo_request_' . $action]);
        } catch (Throwable $e) {
            redirect('notifications', ['error' => $e->getMessage()]);
        }
    }

    // Item owner accepts / rejects a Donate request (link from notifications)
    public function action(): void
    {
        $user_id = $this->user_id;

        if (!isset($_GET['request_id']) || !is_numeric($_GET['request_id'])) {
            die('Invalid request ID.');
        }

        $action = strtolower(trim($_GET['action'] ?? ''));

        if ($action !== 'accept' && $action !== 'reject') {
            die('Invalid action.');
        }

        $requests = $this->model('Request');
        $request = $requests->donateRequestForOwner((int)$_GET['request_id'], $user_id);

        if (!$request) {
            die('Request not found or you are not the owner of this item.');
        }

        if ((int)$request['owner_id'] !== $user_id) {
            die('You are not allowed to perform this action.');
        }

        if (strtolower(trim($request['item_type'])) !== 'donate') {
            die('This is not a Donate item.');
        }

        if (strtolower($request['request_type']) !== 'donate') {
            die('Invalid request type.');
        }

        // Request must be pending
        if (strtolower(trim($request['request_status'])) !== 'pending') {
            redirect('notifications');
        }

        if ($action === 'accept') {
            try {
                $requests->acceptDonate($request, $user_id);
            } catch (Exception $e) {
                die('Accept failed: ' . htmlspecialchars($e->getMessage()));
            }

            redirect('notifications', ['success' => 'Donation request accepted successfully. Item status changed to Donated.']);
        }

        try {
            $requests->rejectDonate($request, $user_id);
        } catch (Exception $e) {
            die('Reject failed: ' . htmlspecialchars($e->getMessage()));
        }

        redirect('notifications', ['success' => 'Donation request rejected successfully.']);
    }
}
