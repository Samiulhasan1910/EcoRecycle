<?php

/*
 * Delivery man panel
 */
class DeliveryController extends Controller
{
    private int $deliveryId;

    public function __construct()
    {
        $this->deliveryId = $this->requireDelivery();
    }

    private function layoutData(array $data = []): array
    {
        return array_merge([
            'name' => $_SESSION['name'] ?? 'Delivery',
            'csrfToken' => csrf_token(),
        ], $data);
    }

    public function dashboard(): void
    {
        $delivery = $this->model('Delivery');

        $this->view('delivery/dashboard', $this->layoutData([
            'summary' => $delivery->summaryFor($this->deliveryId),
            'jobs' => $delivery->currentJobsFor($this->deliveryId),
            'openJobs' => $delivery->openJobsFor($this->deliveryId),
        ]));
    }

    public function jobs(): void
    {
        $this->view('delivery/jobs', $this->layoutData([
            'jobs' => $this->model('Delivery')->jobsFor($this->deliveryId),
        ]));
    }

    public function assigned(): void
    {
        $this->view('delivery/assigned', $this->layoutData([
            'jobs' => $this->model('Delivery')->assignedFor($this->deliveryId),
        ]));
    }

    // Requests (from the "requests" table) assigned to this delivery man, or one request's details
    public function assignedList(): void
    {
        $requestId = (int)($_GET['id'] ?? 0);
        $requests = [];
        $requestDetails = null;
        $pickupLocation = '';
        $mapUrl = '';

        if ($requestId > 0) {
            $requestDetails = $this->model('Request')->detailForDeliveryMan($requestId, $this->deliveryId);

            if (!$requestDetails) {
                redirect('delivery/assigned-list');
            }

            $pickupLocation = trim((string)($requestDetails['pickup_location'] ?? ''));
            $mapUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($pickupLocation);
        } else {
            $requests = $this->model('Request')->assignedToDeliveryMan($this->deliveryId);
        }

        $this->view('delivery/assigned_list', $this->layoutData(
            compact('requests', 'requestDetails', 'pickupLocation', 'mapUrl')
        ));
    }

    public function history(): void
    {
        $status = trim($_GET['status'] ?? '');
        $delivery = $this->model('Delivery');

        $this->view('delivery/history', $this->layoutData([
            'status' => $status,
            'allowed' => Delivery::HISTORY_STATUSES,
            'deliveries' => $delivery->historyFor($this->deliveryId, $status),
        ]));
    }

    public function earnings(): void
    {
        $delivery = $this->model('Delivery');

        $this->view('delivery/earnings', $this->layoutData([
            'totals' => $delivery->earningTotalsFor($this->deliveryId),
            'recent' => $delivery->recentCompletedFor($this->deliveryId),
        ]));
    }

    public function availability(): void
    {
        $allowed = ['Available', 'Busy', 'Offline'];
        $message = '';
        $staff = $this->model('DeliveryMan');

        if (is_post()) {
            if (!csrf_valid($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                exit('Invalid request token.');
            }

            $availability = $_POST['availability'] ?? 'Offline';

            if (in_array($availability, $allowed, true)) {
                if ($availability === 'Available' && $this->model('Delivery')->hasActiveDelivery($this->deliveryId)) {
                    $message = 'You have an active delivery, so availability must remain Busy.';
                    $availability = 'Busy';
                } else {
                    $message = 'Availability updated successfully.';
                }

                $staff->setAvailability($this->deliveryId, $availability);
            }
        }

        $this->view('delivery/availability', $this->layoutData([
            'message' => $message,
            'current' => $staff->availability($this->deliveryId),
        ]));
    }

    public function profile(): void
    {
        $message = '';
        $error = '';
        $staff = $this->model('DeliveryMan');

        if (is_post()) {
            if (!csrf_valid($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                exit('Invalid request token.');
            }

            $nameInput = trim($_POST['name'] ?? '');
            $phoneInput = trim($_POST['phone'] ?? '');
            $locationInput = trim($_POST['location'] ?? '');
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($nameInput === '' || $phoneInput === '' || $locationInput === '') {
                $error = 'Name, phone, and location are required.';
            } elseif (!preg_match('/^01[0-9]{9}$/', $phoneInput)) {
                $error = 'Enter a valid 11-digit Bangladeshi phone number.';
            } elseif ($newPassword !== '' && strlen($newPassword) < 6) {
                $error = 'New password must contain at least 6 characters.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New password and confirmation do not match.';
            } else {
                try {
                    $hash = $newPassword !== '' ? password_hash($newPassword, PASSWORD_DEFAULT) : null;
                    $staff->updateProfile($this->deliveryId, $nameInput, $phoneInput, $locationInput, $hash);

                    $_SESSION['name'] = $nameInput;
                    $_SESSION['delivery_name'] = $nameInput;
                    $message = 'Profile updated successfully.';
                } catch (Throwable $e) {
                    $error = 'Profile could not be updated. Please try again.';
                }
            }
        }

        $profile = $staff->find($this->deliveryId);

        if (!$profile) {
            redirect('auth/logout');
        }

        $this->view('delivery/profile', $this->layoutData(compact('message', 'error', 'profile')));
    }

    public function notifications(): void
    {
        $notifications = $this->model('Notification');

        if (is_post()) {
            $notificationId = (int)($_POST['notification_id'] ?? 0);

            if ($notificationId > 0) {
                $notifications->markReadForDelivery($notificationId, $this->deliveryId);
            }

            redirect('delivery/notifications');
        }

        $this->view('delivery/notifications', $this->layoutData([
            'notifications' => $notifications->latestForDelivery($this->deliveryId),
        ]));
    }

    /* =========================
       ACTIONS (POST)
       ========================= */

    public function jobResponse(): void
    {
        if (!is_post() || !csrf_valid($_POST['csrf_token'] ?? '')) {
            redirect('delivery/dashboard');
        }

        $jobId = (int)($_POST['delivery_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($jobId <= 0 || !in_array($action, ['accept', 'reject'], true)) {
            redirect('delivery/dashboard');
        }

        try {
            $this->model('Delivery')->respondToJob($jobId, $this->deliveryId, $action);

            redirect('delivery/dashboard', [
                'message' => $action === 'accept' ? 'Delivery accepted successfully.' : 'Delivery job rejected.',
            ]);
        } catch (Exception $e) {
            redirect('delivery/dashboard', ['error' => $e->getMessage()]);
        }
    }

    public function charge(): void
    {
        if (!is_post() || !csrf_valid($_POST['csrf_token'] ?? '')) {
            redirect('delivery/jobs');
        }

        $jobId = (int)($_POST['delivery_id'] ?? 0);
        $charge = (float)($_POST['charge'] ?? 0);

        if ($jobId <= 0 || $charge <= 0) {
            redirect('delivery/jobs', ['error' => 'invalid_charge']);
        }

        try {
            $this->model('Delivery')->proposeCharge($jobId, $this->deliveryId, $charge);

            redirect('delivery/jobs', ['message' => 'charge_sent']);
        } catch (Exception $e) {
            redirect('delivery/jobs', ['error' => $e->getMessage()]);
        }
    }

    public function progress(): void
    {
        if (!is_post() || !csrf_valid($_POST['csrf_token'] ?? '')) {
            redirect('delivery/jobs');
        }

        $jobId = (int)($_POST['delivery_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($jobId <= 0 || !in_array($action, ['arrived', 'picked_up', 'complete'], true)) {
            redirect('delivery/jobs');
        }

        try {
            if ($this->model('Delivery')->advance($jobId, $this->deliveryId, $action)) {
                redirect('delivery/assigned', ['message' => 'status_updated']);
            }

            redirect('delivery/jobs', ['error' => 'job_not_updated']);
        } catch (Throwable $e) {
            redirect('delivery/jobs', ['error' => $e->getMessage()]);
        }
    }
}
