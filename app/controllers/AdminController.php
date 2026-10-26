<?php

/*
 * Admin panel: users, NGOs, delivery staff, items and requests
 */
class AdminController extends Controller
{
    private function panel(): AdminPanel
    {
        return $this->model('AdminPanel');
    }

    private function admin(): void
    {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            redirect('auth/login');
        }
    }

    // [page, offset] from ?page=
    private function paging(int $perPage): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));

        return [$page, ($page - 1) * $perPage];
    }

    public function dashboard(): void
    {
        $this->admin();
        $panel = $this->panel();

        $this->view('admin/dashboard', [
            'totalUsers' => $panel->count('SELECT COUNT(*) FROM users'),
            'totalNGOs' => $panel->count('SELECT COUNT(*) FROM ngos'),
            'totalDelivery' => $panel->count('SELECT COUNT(*) FROM delivery'),
            'totalItems' => $panel->count('SELECT COUNT(*) FROM items'),
            'totalWantedPosts' => $panel->count('SELECT COUNT(*) FROM wanted_posts'),
            'totalRequests' => $panel->count('SELECT COUNT(*) FROM requests'),
            'pendingRequests' => $panel->count("SELECT COUNT(*) FROM requests WHERE LOWER(status) = 'pending'"),
            'approvedRequests' => $panel->count("SELECT COUNT(*) FROM requests WHERE LOWER(status) = 'approved'"),
            'totalPickups' => $panel->count('SELECT COUNT(*) FROM pickups'),
            'pendingItems' => $panel->count("SELECT COUNT(*) FROM items WHERE LOWER(status) = 'pending'"),
            'recentRequests' => $panel->recentRequests(),
            'recentItems' => $panel->recentItems(),
        ]);
    }

    /* =========================
       USERS
       ========================= */

    public function users(): void
    {
        $this->admin();

        $search = trim($_GET['search'] ?? '');
        $limit = 10;
        [$page, $offset] = $this->paging($limit);

        [$result, $totalCount] = $this->panel()->users($search, $offset, $limit);

        $this->view('admin/users', [
            'totalUsers' => $this->panel()->count('SELECT COUNT(*) FROM users'),
            'search' => $search,
            'page' => $page,
            'result' => $result,
            'totalPages' => ceil($totalCount / $limit),
        ]);
    }

    public function usersAction(): void
    {
        $this->admin();

        $action = $_GET['action'] ?? '';
        $userId = (int)($_GET['id'] ?? 0);

        if ($action == 'delete' && $userId > 0) {
            try {
                $this->panel()->deleteUser($userId);
                redirect('admin/users', ['message' => 'User deleted successfully']);
            } catch (mysqli_sql_exception $e) {
                redirect('admin/users', ['error' => 'Failed to delete user']);
            }
        }

        if ($action == 'edit' && $userId > 0) {
            $users = $this->model('User');
            $user = $users->find($userId);

            if (!$user) {
                redirect('admin/users');
            }

            if (is_post()) {
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $location = trim($_POST['location'] ?? '');

                if ($name && $email && $phone && $location) {
                    $users->updateProfile($userId, $name, $email, $phone, $location);
                    redirect('admin/users', ['message' => 'User updated successfully']);
                }
            }

            $this->view('admin/user_edit', compact('user', 'userId'));
            return;
        }

        redirect('admin/users');
    }

    /* =========================
       ITEMS
       ========================= */

    public function items(): void
    {
        $this->admin();
        $panel = $this->panel();

        $search = trim($_GET['search'] ?? '');
        $categoryFilter = trim($_GET['category'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $perPage = 10;
        [$page, $offset] = $this->paging($perPage);

        [$items, $totalRecords] = $panel->items($search, $categoryFilter, $statusFilter, $offset, $perPage);

        $countStatus = fn(string $status) => $panel->count('SELECT COUNT(*) FROM items WHERE status = ?', [$status]);

        $this->view('admin/items', [
            'search' => $search,
            'categoryFilter' => $categoryFilter,
            'statusFilter' => $statusFilter,
            'page' => $page,
            'items' => $items,
            'totalPages' => max(1, (int)ceil($totalRecords / $perPage)),
            'totalItems' => $panel->count('SELECT COUNT(*) FROM items'),
            'pendingItems' => $panel->count("SELECT COUNT(*) FROM items WHERE status = 'Pending' OR status = ''"),
            'approvedItems' => $countStatus('Approved'),
            'availableItems' => $countStatus('Available'),
            'requestedItems' => $countStatus('Requested'),
            'collectedItems' => $countStatus('Collected'),
            'completedItems' => $countStatus('Completed'),
            'rejectedItems' => $countStatus('Rejected'),
        ]);
    }

    public function itemsAction(): void
    {
        $this->admin();
        $panel = $this->panel();

        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        $itemId = (int)($_GET['id'] ?? 0);

        if ($itemId <= 0) {
            redirect('admin/items');
        }

        switch ($action) {
            case 'view':
                $item = $panel->itemWithOwner($itemId);

                if (!$item) {
                    redirect('admin/items');
                }

                $statusText = status_or_pending($item['status'] ?? '');

                $this->view('admin/item_view', compact('item', 'itemId', 'statusText'));
                return;

            case 'approve':
                $panel->setItemStatus($itemId, 'Approved');
                redirect('admin/items', ['success' => 'approved']);

            case 'reject':
                $panel->setItemStatus($itemId, 'Rejected');
                redirect('admin/items', ['success' => 'rejected']);

            case 'set_status':
                $value = $_GET['value'] ?? 'Available';
                $allowed = ['Pending', 'Approved', 'Available', 'Requested', 'Collected', 'Completed', 'Rejected'];

                $panel->setItemStatus($itemId, in_array($value, $allowed, true) ? $value : 'Available');
                redirect('admin/items', ['success' => 'status_updated']);

            case 'delete':
                $panel->deleteItem($itemId);
                redirect('admin/items', ['success' => 'deleted']);
        }

        redirect('admin/items');
    }

    /* =========================
       NGOS
       ========================= */

    public function ngos(): void
    {
        $this->admin();
        $panel = $this->panel();

        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';

        $itemsPerPage = 10;
        [$page, $offset] = $this->paging($itemsPerPage);

        [$ngos, $totalRecords] = $panel->ngos($search, $status, $offset, $itemsPerPage);

        $this->view('admin/ngos', [
            // the view prints $search directly
            'search' => htmlspecialchars($search),
            'page' => $page,
            'ngos' => $ngos,
            'totalPages' => ceil($totalRecords / $itemsPerPage),
            'totalNGOs' => $panel->count('SELECT COUNT(*) FROM ngos'),
            'pendingNGOs' => $panel->count("SELECT COUNT(*) FROM ngos WHERE verification_status = 'Pending' OR verification_status = ''"),
            'approvedNGOs' => $panel->count("SELECT COUNT(*) FROM ngos WHERE verification_status = 'Approved'"),
            'rejectedNGOs' => $panel->count("SELECT COUNT(*) FROM ngos WHERE verification_status = 'Rejected'"),
        ]);
    }

    public function ngosAction(): void
    {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $this->json(['success' => false, 'message' => 'Unauthorized']);
        }

        $panel = $this->panel();
        $action = $_GET['action'] ?? '';
        $ngoId = (int)($_GET['id'] ?? 0);

        if (!$ngoId) {
            redirect('admin/ngos');
        }

        switch ($action) {
            case 'view':
                $ngo = $panel->ngo($ngoId);

                if (!$ngo) {
                    redirect('admin/ngos');
                }

                $statusText = status_or_pending($ngo['verification_status'] ?? '');

                $this->view('admin/ngo_view', compact('ngo', 'ngoId', 'statusText'));
                return;

            case 'approve':
            case 'reject':
                $status = $action === 'approve' ? 'Approved' : 'Rejected';

                try {
                    $panel->setNgoStatus($ngoId, $status);
                    error_log('Admin ' . strtolower($status) . " NGO ID: $ngoId");
                    redirect('admin/ngos', [strtolower($status) => 1]);
                } catch (mysqli_sql_exception $e) {
                    redirect('admin/ngos', ['error' => 1]);
                }

            case 'download':
                $ngo = $panel->ngo($ngoId);

                if (!$ngo) {
                    redirect('admin/ngos', ['error' => 'not_found']);
                }

                $this->download('uploads/ngo_documents/' . basename((string)$ngo['verification_document']), 'admin/ngos', 'file_not_found');
        }

        redirect('admin/ngos');
    }

    // Force-download a stored document or go back with an error
    private function download(string $filePath, string $backRoute, string $error): never
    {
        if ($filePath !== '' && is_file($filePath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($filePath));
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }

        redirect($backRoute, ['error' => $error]);
    }

    /* =========================
       DELIVERY STAFF
       ========================= */

    public function deliveryMen(): void
    {
        $this->admin();
        $panel = $this->panel();
        $panel->ensureDeliveryColumns();

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $perPage = 10;
        [$page, $offset] = $this->paging($perPage);

        [$staffList, $totalRecords] = $panel->deliveryStaff($search, $statusFilter, $offset, $perPage);

        foreach ($staffList as &$row) {
            $row['status_text'] = status_or_pending($row['verification_status']);
        }
        unset($row);

        $this->view('admin/delivery_men', [
            'search' => $search,
            'statusFilter' => $statusFilter,
            'page' => $page,
            'staffList' => $staffList,
            'totalPages' => max(1, (int)ceil($totalRecords / $perPage)),
            'totalStaff' => $panel->count('SELECT COUNT(*) FROM delivery'),
            'pendingStaff' => $panel->count("SELECT COUNT(*) FROM delivery WHERE verification_status = 'Pending' OR verification_status = ''"),
            'approvedStaff' => $panel->count("SELECT COUNT(*) FROM delivery WHERE verification_status = 'Approved'"),
            'blockedStaff' => $panel->count("SELECT COUNT(*) FROM delivery WHERE verification_status = 'Blocked' OR is_blocked = 1"),
            'availableStaff' => $panel->count("SELECT COUNT(*) FROM delivery WHERE availability = 'Available'"),
            'busyStaff' => $panel->count("SELECT COUNT(*) FROM delivery WHERE availability = 'Busy'"),
            'offlineStaff' => $panel->count("SELECT COUNT(*) FROM delivery WHERE availability = 'Offline'"),
        ]);
    }

    public function deliveryMenAction(): void
    {
        $this->admin();
        $panel = $this->panel();

        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        $deliveryId = (int)($_GET['id'] ?? 0);

        if ($action === 'add') {
            $this->addDeliveryMan();
        }

        if ($deliveryId <= 0) {
            redirect('admin/delivery-men');
        }

        switch ($action) {
            case 'view':
                $staff = $panel->deliveryMan($deliveryId);

                if (!$staff) {
                    redirect('admin/delivery-men');
                }

                $statusText = status_or_pending($staff['verification_status'] ?? '');
                $documentPath = trim((string)($staff['verification_document'] ?? ''));
                $documentFile = $documentPath === '' ? '' : basename($documentPath);

                $this->view('admin/delivery_man_view', compact('staff', 'deliveryId', 'statusText', 'documentPath', 'documentFile'));
                return;

            case 'download':
                $staff = $panel->deliveryMan($deliveryId);
                $this->download(trim((string)($staff['verification_document'] ?? '')), 'admin/delivery-men', 'document_not_found');

            case 'approve':
            case 'unblock':
                $panel->setDeliveryVerification($deliveryId, 'Approved', 0);
                redirect('admin/delivery-men', ['success' => $action === 'approve' ? 'approved' : 'unblocked']);

            case 'block':
                $panel->setDeliveryVerification($deliveryId, 'Blocked', 1);
                redirect('admin/delivery-men', ['success' => 'blocked']);

            case 'delete':
                $panel->deleteDeliveryMan($deliveryId);
                redirect('admin/delivery-men', ['success' => 'deleted']);

            case 'set_availability':
                $value = $_GET['value'] ?? 'Offline';

                $panel->setDeliveryAvailability($deliveryId, in_array($value, ['Available', 'Busy', 'Offline']) ? $value : 'Offline');
                redirect('admin/delivery-men', ['success' => 'availability_updated']);
        }

        redirect('admin/delivery-men');
    }

    private function addDeliveryMan(): never
    {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'phone' => trim($_POST['phone'] ?? ''),
            'location' => trim($_POST['location'] ?? ''),
        ];

        if (in_array('', $data, true)) {
            redirect('admin/delivery-men', ['error' => 'missing_fields']);
        }

        $file = $_FILES['verification_document'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            redirect('admin/delivery-men', ['error' => 'license_required']);
        }

        if ($this->panel()->deliveryEmailExists($data['email'])) {
            redirect('admin/delivery-men', ['error' => 'email_exists']);
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            redirect('admin/delivery-men', ['error' => 'invalid_license_type']);
        }

        if ((int)$file['size'] > 5 * 1024 * 1024) {
            redirect('admin/delivery-men', ['error' => 'license_too_large']);
        }

        $folder = 'uploads/delivery_documents/';

        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $filePath = $folder . 'delivery_' . time() . '_' . uniqid() . '.' . $extension;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            redirect('admin/delivery-men', ['error' => 'license_upload_failed']);
        }

        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['verification_document'] = $filePath;

        try {
            $this->panel()->addDeliveryMan($data);
            redirect('admin/delivery-men', ['success' => 'staff_added']);
        } catch (mysqli_sql_exception $e) {
            redirect('admin/delivery-men', ['error' => 'insert_failed']);
        }
    }

    /* =========================
       REQUESTS
       ========================= */

    public function requests(): void
    {
        $this->admin();

        $this->view('admin/requests', [
            'requests' => $this->panel()->allRequests(),
        ]);
    }

    /*
     * Used by the admin (approve / reject / assign / cancel) and by the
     * assigned delivery man (accept / reject / arrived / picked up / complete).
     */
    public function requestsAction(): void
    {
        $panel = $this->panel();

        $action = $_GET['action'] ?? '';
        $requestId = (int)($_GET['id'] ?? 0);
        $returnRoute = ($_GET['return'] ?? '') === 'my_requests' ? 'requests/mine' : 'admin/requests';

        if ($requestId <= 0) {
            redirect($returnRoute);
        }

        $role = $_SESSION['role'] ?? '';
        $isAdmin = $role === 'admin';
        $isDelivery = $role === 'delivery_man';
        $deliveryActions = ['accept', 'reject_delivery', 'arrived_at_pickup', 'picked_up_delivery', 'complete_delivery'];

        if ((!$isAdmin && !$isDelivery) || ($isDelivery && !in_array($action, $deliveryActions, true))) {
            redirect('auth/login');
        }

        if ($isDelivery) {
            if (!is_post() || !csrf_valid($_POST['csrf_token'] ?? '')) {
                redirect('delivery/dashboard');
            }

            $this->deliveryRequestAction($action, $requestId);
        }

        switch ($action) {
            case 'cancel':
                $request = $panel->request($requestId);
                $isOwner = $request && isset($_SESSION['user_id']) && (int)$request['user_id'] === (int)$_SESSION['user_id'];

                if (!$isAdmin && !$isOwner) {
                    redirect('auth/login');
                }

                $panel->cancelRequest($requestId, !empty($request['item_id']) ? (int)$request['item_id'] : null);
                redirect($returnRoute);

            case 'approve':
                $panel->setRequestAndItemStatus($requestId, 'Approved', 'Requested');
                break;

            case 'reject':
                $panel->setRequestAndItemStatus($requestId, 'Rejected', 'Available');
                break;

            case 'assign_form':
                $this->view('admin/request_assign', [
                    'requestId' => $requestId,
                    'deliveryList' => $panel->assignableDeliveryMen(),
                ]);
                return;

            case 'assign':
                $deliveryId = (int)($_POST['delivery_id'] ?? 0);

                if ($deliveryId > 0) {
                    $panel->assignRequest($requestId, $deliveryId);
                }
                break;
        }

        redirect('admin/requests');
    }

    private function deliveryRequestAction(string $action, int $requestId): never
    {
        $panel = $this->panel();
        $deliveryId = (int)($_SESSION['delivery_id'] ?? $_SESSION['user_id'] ?? 0);
        $detail = ['id' => $requestId];

        switch ($action) {
            case 'accept':
                $panel->deliveryAccept($requestId, $deliveryId);
                redirect('delivery/dashboard');

            case 'reject_delivery':
                $reason = trim($_POST['reason'] ?? '');

                if ($reason === '') {
                    redirect('delivery/assigned-list', $detail + ['error' => 'reason_required']);
                }

                $panel->deliveryReject($requestId, $deliveryId, $reason);
                redirect('delivery/assigned-list');

            case 'arrived_at_pickup':
                $panel->deliveryArrived($requestId, $deliveryId);
                break;

            case 'picked_up_delivery':
                $itemReceived = $_POST['item_received'] ?? '';
                $pickupNote = trim($_POST['pickup_note'] ?? '');

                if (!in_array($itemReceived, ['Yes', 'No'], true)) {
                    redirect('delivery/assigned-list', $detail + ['error' => 'item_received_required']);
                }

                if ($pickupNote === '') {
                    $pickupNote = $itemReceived === 'No' ? 'Item was not received.' : 'Collected successfully.';
                }

                $panel->deliveryPickedUp($requestId, $deliveryId, $itemReceived === 'Yes', $pickupNote);
                break;

            case 'complete_delivery':
                $completionNote = trim($_POST['completion_note'] ?? '');

                if ($completionNote === '') {
                    redirect('delivery/assigned-list', $detail + ['error' => 'completion_required']);
                }

                $panel->deliveryComplete($requestId, $deliveryId, $completionNote);
                break;
        }

        redirect('delivery/assigned-list', $detail);
    }
}
