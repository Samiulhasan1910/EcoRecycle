<?php

/*
 * NGO panel
 */
class NgoController extends Controller
{
    private function panel(): NgoPanel
    {
        return $this->model('NgoPanel');
    }

    // First two initials of a name: "Helping Hands NGO" -> "HH"
    private function initials(string $name): string
    {
        $initials = '';

        foreach (preg_split('/\s+/', trim($name)) as $word) {
            if ($word !== '') {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }

        return substr($initials, 0, 2);
    }

    public function dashboard(): void
    {
        if (($_SESSION['role'] ?? '') !== 'ngo' || !isset($_SESSION['ngo_id'])) {
            redirect('auth/login');
        }

        $ngo_id = (int)$_SESSION['ngo_id'];
        $panel = $this->panel();

        if (isset($_GET['accept_wanted_id'])) {
            $wantedId = (int)$_GET['accept_wanted_id'];

            if ($wantedId > 0) {
                $panel->acceptWantedPost($wantedId, $ngo_id);
            }

            redirect('ngo/dashboard');
        }

        if (isset($_GET['accept_item_id'])) {
            $itemId = (int)$_GET['accept_item_id'];

            if ($itemId > 0) {
                $panel->acceptItemFromDashboard($itemId, $ngo_id);
            }

            redirect('ngo/dashboard');
        }

        $panel->ensureNotificationColumns();
        $panel->syncPickupNotifications($ngo_id, false);

        $name = $_SESSION['name'] ?? 'Helping Hands NGO';

        $this->view('ngo/dashboard', [
            'ngo_id' => $ngo_id,
            'name' => $name,
            'initials' => $this->initials($name),
            'total' => $panel->donationCount($ngo_id),
            'pending' => $panel->donationCount($ngo_id, 'Pending'),
            'accepted' => $panel->donationCount($ngo_id, 'Accepted'),
            'completed' => $panel->donationCount($ngo_id, 'Completed'),
            'recent_notifications_result' => $panel->notifications($ngo_id, 4),
            'unread_notifications' => $panel->unreadCount($ngo_id),
        ]);
    }

    public function profile(): void
    {
        $ngo_id = $this->requireNgo();
        $panel = $this->panel();

        $message = '';
        $message_type = '';

        if (is_post()) {
            if (($_POST['profile_action'] ?? 'update') === 'delete') {
                if ($panel->deleteAccount($ngo_id)) {
                    session_unset();
                    session_destroy();
                    redirect('auth/login', ['profile_deleted' => 1]);
                }

                $message = 'Profile could not be deleted. Please try again.';
                $message_type = 'error';
            } else {
                [$message, $message_type] = $this->updateProfile($ngo_id);
            }
        }

        $profile = $panel->profile($ngo_id);

        if (!$profile) {
            die('NGO profile not found.');
        }

        $this->view('ngo/profile', compact('ngo_id', 'message', 'message_type', 'profile'));
    }

    // Returns [message, message_type]
    private function updateProfile(int $ngo_id): array
    {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'location' => trim($_POST['location'] ?? ''),
            'organization_type' => trim($_POST['organization_type'] ?? ''),
            'established_year' => (int)($_POST['established_year'] ?? 0),
        ];

        if ($data['name'] === '' || $data['email'] === '' || $data['phone'] === '' || $data['location'] === '') {
            return ['Please fill in all required fields.', 'error'];
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['Please enter a valid email address.', 'error'];
        }

        $documentPath = null;
        $file = $_FILES['verification_document'] ?? null;

        if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if ($file['error'] !== UPLOAD_ERR_OK || !in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                return ['Only PDF, JPG, JPEG and PNG files are allowed.', 'error'];
            }

            $folder = 'uploads/ngo_documents/';

            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }

            $documentPath = $folder . 'ngo_' . time() . '_' . uniqid() . '.' . $extension;

            if (!move_uploaded_file($file['tmp_name'], $documentPath)) {
                return ['Document upload failed.', 'error'];
            }
        }

        try {
            $this->panel()->updateProfile($ngo_id, $data, $documentPath);
        } catch (mysqli_sql_exception $e) {
            return ['Database error: ' . $e->getMessage(), 'error'];
        }

        $_SESSION['name'] = $data['name'];
        $_SESSION['email'] = $data['email'];

        return ['Profile updated successfully.', 'success'];
    }

    public function donationRequests(): void
    {
        $ngo_id = $this->requireNgo();

        if (isset($_GET['accept_id'])) {
            $itemId = (int)$_GET['accept_id'];

            if ($itemId > 0) {
                $this->panel()->acceptPrivateDonation($itemId, $ngo_id);
            }

            redirect('ngo/accepted-donations');
        }

        $this->view('ngo/donation_requests', [
            'ngo_id' => $ngo_id,
            'result' => $this->panel()->pendingDonations($ngo_id),
        ]);
    }

    public function acceptedDonations(): void
    {
        $ngo_id = $this->requireNgo();
        csrf_token();

        $this->view('ngo/accepted_donations', [
            'ngo_id' => $ngo_id,
            'result' => $this->panel()->acceptedDonations($ngo_id),
            'deliveryMen' => $this->panel()->availableDeliveryMen(),
        ]);
    }

    public function assignDelivery(): void
    {
        $ngoId = $this->requireNgo();

        $requestId = (int)($_POST['request_id'] ?? 0);
        $deliveryManId = (int)($_POST['delivery_man_id'] ?? 0);

        if (!is_post() || $requestId <= 0 || $deliveryManId <= 0) {
            redirect('ngo/accepted-donations', ['error' => 'invalid_assignment']);
        }

        try {
            $this->model('Delivery')->assignFromNgo($requestId, $ngoId, $deliveryManId);
            redirect('ngo/accepted-donations', ['assigned' => 1]);
        } catch (Throwable $e) {
            redirect('ngo/accepted-donations', ['error' => $e->getMessage()]);
        }
    }

    public function pickups(): void
    {
        $ngo_id = $this->requireNgo();

        $this->view('ngo/pickups', [
            'ngo_id' => $ngo_id,
            'result' => $this->panel()->pickups($ngo_id),
        ]);
    }

    public function arrangePickup(): void
    {
        $ngo_id = $this->requireNgo();

        $this->view('ngo/arrange_pickup', [
            'ngo_id' => $ngo_id,
            'result' => $this->panel()->pickups($ngo_id),
        ]);
    }

    public function notifications(): void
    {
        $ngo_id = $this->requireNgo();
        $panel = $this->panel();

        $panel->ensureNotificationColumns();
        $panel->syncPickupNotifications($ngo_id);
        $panel->syncDonationNotifications($ngo_id);

        $this->view('ngo/notifications', [
            'ngo_id' => $ngo_id,
            'result' => $panel->notifications($ngo_id),
        ]);
    }

    public function notificationView(): void
    {
        $ngo_id = $this->requireNgo();
        $csrfToken = csrf_token();
        $panel = $this->panel();

        $panel->ensureNotificationColumns();

        if (!isset($_GET['id'])) {
            redirect('ngo/notifications');
        }

        $notification_id = (int)$_GET['id'];
        $notification = $panel->notification($notification_id, $ngo_id);

        if (!$notification) {
            echo 'Notification not found.';
            exit;
        }

        if (is_post()) {
            if (!csrf_valid($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                exit('Invalid request token.');
            }

            $action = $_POST['action'] ?? '';
            $item_id = (int)($notification['item_id'] ?? 0);

            if ($item_id > 0 && in_array($action, ['request_donor', 'accept', 'reject'], true)) {
                try {
                    if ($action === 'request_donor') {
                        $panel->requestFromDonor($item_id, $ngo_id, $notification_id);
                        $result = 'requested';
                    } else {
                        $panel->answerDonation($item_id, $ngo_id, $notification_id, $action);
                        $result = $action;
                    }

                    redirect('ngo/notification-view', ['id' => $notification_id, 'result' => $result]);
                } catch (Throwable $e) {
                    redirect('ngo/notification-view', ['id' => $notification_id, 'error' => $e->getMessage()]);
                }
            }
        }

        $panel->setNotificationStatus($notification_id, $ngo_id, 'Read');

        $wanted = !empty($notification['wanted_id']) ? $panel->wantedSummary((int)$notification['wanted_id']) : null;
        $item = !empty($notification['item_id']) ? $panel->itemForNgo((int)$notification['item_id'], $ngo_id) : null;

        $request = null;
        $chargeDelivery = null;

        if (!empty($notification['request_id'])) {
            $request = $panel->requestForNgo((int)$notification['request_id'], $ngo_id);

            if ($request) {
                $chargeDelivery = $panel->proposedCharge((int)$notification['request_id']);
            }
        }

        $pickup = !empty($notification['pickup_id']) ? $panel->pickupForNgo((int)$notification['pickup_id'], $ngo_id) : null;

        $this->view('ngo/notification_view', compact(
            'ngo_id', 'csrfToken', 'notification_id', 'notification', 'wanted', 'item', 'request', 'chargeDelivery', 'pickup'
        ));
    }

    public function createPost(): void
    {
        $ngo_id = $this->requireNgo();
        $panel = $this->panel();

        $panel->ensureItemDescriptionColumn();

        $message = '';
        $message_type = '';

        $editPost = isset($_GET['edit_id']) ? $panel->post((int)$_GET['edit_id'], $ngo_id) : null;

        if (is_post()) {
            $postAction = $_POST['post_action'] ?? 'create';
            $postId = (int)($_POST['item_id'] ?? 0);

            if ($postAction === 'delete' && $postId > 0) {
                $panel->deletePost($postId, $ngo_id);
                redirect('ngo/create-post', ['deleted' => 1]);
            }

            [$message, $message_type] = $this->savePost($ngo_id, $postAction, $postId);
        }

        if (isset($_GET['deleted'])) {
            $message = 'Donation post deleted successfully.';
            $message_type = 'success';
        }

        // Stored title is "<quantity> - <name>"
        $editTitle = $editPost ? $editPost['title'] : '';
        $editQuantity = '';
        $editName = $editTitle;

        if ($editPost && strpos($editTitle, ' - ') !== false) {
            [$editQuantity, $editName] = explode(' - ', $editTitle, 2);
        }

        $this->view('ngo/create_post', [
            'ngo_id' => $ngo_id,
            'message' => $message,
            'message_type' => $message_type,
            'editPost' => $editPost,
            'myPostsResult' => $panel->posts($ngo_id),
            'editTitle' => $editTitle,
            'editQuantity' => $editQuantity,
            'editName' => $editName,
        ]);
    }

    // Returns [message, message_type]
    private function savePost(int $ngo_id, string $postAction, int $postId): array
    {
        $title = trim($_POST['title'] ?? '');
        $quantity = trim($_POST['quantity'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($title === '' || $quantity === '' || $category === '' || $location === '' || ($postAction !== 'edit' && $description === '')) {
            return ['Please fill in all required fields.', 'error'];
        }

        $imageName = '';
        $file = $_FILES['image'] ?? null;

        if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE && $file['error'] !== UPLOAD_ERR_OK) {
            return ['Image upload failed. Please choose an image smaller than 5MB.', 'error'];
        }

        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            if ((int)$file['size'] > 5 * 1024 * 1024) {
                return ['Image must be smaller than 5MB.', 'error'];
            }

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                return ['Invalid image format. Please use JPG, JPEG, PNG, GIF or WEBP.', 'error'];
            }

            if (!is_dir(UPLOAD_PATH)) {
                mkdir(UPLOAD_PATH, 0777, true);
            }

            $imageName = uniqid('ngo_', true) . '.' . $extension;

            if (!move_uploaded_file($file['tmp_name'], UPLOAD_PATH . '/' . $imageName)) {
                return ['The image could not be uploaded. Please check the uploads folder permissions.', 'error'];
            }
        }

        $publicTitle = $quantity . ' - ' . $title;

        try {
            if ($postAction === 'edit' && $postId > 0) {
                $this->panel()->updatePost($postId, $ngo_id, $publicTitle, $category, $description, $location, $imageName);

                return ['Donation post updated successfully!', 'success'];
            }

            $this->panel()->createPost($ngo_id, $publicTitle, $category, $description, $location, $imageName);

            return ['Donation post created successfully!', 'success'];
        } catch (mysqli_sql_exception $e) {
            return ['Database Error: ' . $e->getMessage(), 'error'];
        }
    }

    // Shared by NGOs, users and delivery men
    public function browseWanted(): void
    {
        $role = $_SESSION['role'] ?? '';

        if (!in_array($role, ['user', 'delivery_man', 'ngo'], true)) {
            redirect('auth/login');
        }

        $ngo_name = $_SESSION['name'] ?? ($role === 'delivery_man' ? 'Delivery' : 'User');
        $backLink = url(match ($role) {
            'ngo' => 'ngo/dashboard',
            'delivery_man' => 'delivery/dashboard',
            default => 'dashboard',
        });

        $search = trim($_GET['search'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $location = trim($_GET['location'] ?? '');

        $panel = $this->panel();

        $this->view('ngo/browse_wanted', [
            'role' => $role,
            'ngo_id' => (int)($_SESSION['ngo_id'] ?? 0),
            'ngo_name' => $ngo_name,
            'backLink' => $backLink,
            'search' => $search,
            'category' => $category,
            'location' => $location,
            'result' => $panel->browse($search, $category, $location),
            'category_result' => $this->model('WantedPost')->publicCategories(),
            'location_result' => $panel->wantedLocations(),
            'initials' => $this->initials($ngo_name),
        ]);
    }
}
