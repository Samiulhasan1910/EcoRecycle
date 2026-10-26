<?php

/*
 * Receiver side of a delivery: track it and answer the proposed charge
 */
class TrackingController extends Controller
{
    public function index(): void
    {
        $userId = $this->requireLogin();
        csrf_token();

        $loadError = '';

        try {
            $deliveries = $this->model('Delivery')->forReceiver($userId);
        } catch (Throwable $e) {
            $deliveries = [];
            $loadError = $e->getMessage();
        }

        $this->view('delivery/track', compact('deliveries', 'loadError'));
    }

    public function chargeResponse(): void
    {
        if (!isset($_SESSION['user_id']) && !isset($_SESSION['ngo_id'])) {
            redirect('auth/login');
        }

        if (!is_post() || !csrf_valid($_POST['csrf_token'] ?? '')) {
            redirect('delivery/track');
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $ngoId = (int)($_SESSION['ngo_id'] ?? 0);
        $returnRoute = $ngoId > 0 ? 'ngo/accepted-donations' : 'delivery/track';
        $jobId = (int)($_POST['delivery_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($jobId <= 0 || !in_array($action, ['accept', 'reject'], true)) {
            redirect($returnRoute);
        }

        try {
            $this->model('Delivery')->respondToCharge($jobId, $action, $userId, $ngoId);

            redirect($returnRoute, ['message' => 'charge_' . $action . 'ed']);
        } catch (Exception $e) {
            redirect($returnRoute, ['error' => $e->getMessage()]);
        }
    }
}
