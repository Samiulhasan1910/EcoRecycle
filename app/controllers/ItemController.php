<?php

/*
 * Items posted by users (Sell / Donate)
 */
class ItemController extends Controller
{
    private int $user_id;

    public function __construct()
    {
        $this->user_id = $this->requireLogin();
    }

    /* =========================
       LISTS
       ========================= */

    public function mine(): void
    {
        $this->view('items/mine', ['name' => $_SESSION['name'] ?? 'User']);
    }

    public function sell(): void
    {
        $this->view('items/sell', [
            'name' => $_SESSION['name'] ?? 'User',
            'result' => $this->model('Item')->byOwnerAndType($this->user_id, 'Sell'),
        ]);
    }

    public function donate(): void
    {
        $this->view('items/donate', [
            'name' => $_SESSION['name'] ?? 'User',
            'result' => $this->model('Item')->byOwnerAndType($this->user_id, 'Donate'),
        ]);
    }

    public function browse(): void
    {
        $this->view('items/browse', ['name' => $_SESSION['name'] ?? 'User']);
    }

    // AJAX fragment used by the browse page
    public function search(): void
    {
        $search = trim($_GET['search'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $location = trim($_GET['location'] ?? '');

        $this->view('items/search_results', [
            'itemResult' => $this->model('Item')->search($this->user_id, $search, $category, $location),
            'wantedResult' => $this->model('WantedPost')->search($this->user_id, $search, $category, $location),
        ]);
    }

    /* =========================
       VIEW ONE ITEM
       ========================= */

    public function show(): void
    {
        $user_id = $this->user_id;

        if (!isset($_GET['id']) || !is_numeric($_GET['id']) || (int)$_GET['id'] <= 0) {
            redirect('items/browse');
        }

        $item_id = (int)$_GET['id'];
        $back = trim($_GET['back'] ?? '');

        $item = $this->model('Item')->find($item_id);

        if (!$item) {
            redirect('items/browse');
        }

        $owner_id = (int)$item['user_id'];
        $isOwner = ($owner_id === $user_id);
        $itemType = strtolower(trim($item['type'] ?? ''));
        $itemStatus = strtolower(trim($item['status'] ?? ''));

        // A donation addressed to an NGO is private to that NGO and its donor.
        if (!$isOwner && $itemType === 'donate' && !empty($item['ngo_id'])) {
            redirect('items/browse');
        }

        // User's latest donate request
        $requestStatus = '';
        $request_id = 0;

        if (!$isOwner && $itemType === 'donate') {
            if ($requestRow = $this->model('Request')->latestDonateRequest($item_id, $user_id)) {
                $request_id = (int)$requestRow['request_id'];
                $requestStatus = strtolower(trim($requestRow['status'] ?? ''));
            }
        }

        // Donated items are not public, except for the accepted requester
        if ($itemType === 'donate' && $itemStatus === 'donated' && !$isOwner && $requestStatus !== 'accepted') {
            redirect('items/browse');
        }

        // Back link
        $backText = '← Back';

        if ($back === 'my_requests') {
            $backLink = url('requests/mine');
            $backText = '← Back to My Requests';
        } elseif ($isOwner) {
            $backLink = url(match ($itemType) {
                'sell' => 'items/sell',
                'donate' => 'items/donate',
                default => 'items/mine',
            });
        } else {
            $backLink = url('items/browse');
        }

        $this->view('items/view', compact(
            'user_id', 'item_id', 'back', 'item', 'owner_id', 'isOwner', 'itemType', 'itemStatus',
            'requestStatus', 'request_id', 'backLink', 'backText'
        ));
    }

    // Validates a Sell item and sends the buyer to payment
    public function buy(): void
    {
        $item_id = (int)($_GET['id'] ?? 0);

        if ($item_id <= 0) {
            redirect('items/browse');
        }

        $item = $this->model('Item')->find($item_id);

        if (!$item) {
            redirect('items/browse');
        }

        $canBuy = strtolower(trim($item['type'] ?? '')) === 'sell'
            && (int)$item['user_id'] !== $this->user_id
            && strtolower(trim($item['status'] ?? '')) === 'available'
            && floatval($item['price'] ?? 0) > 0;

        if (!$canBuy) {
            redirect('items/view', ['id' => $item_id]);
        }

        redirect('payment', ['item_id' => $item_id]);
    }

    /* =========================
       CREATE
       ========================= */

    public function create(): void
    {
        $user_id = $this->user_id;
        $name = $_SESSION['name'] ?? 'User';

        $items = $this->model('Item');
        $items->ensureNgoColumn();

        $ngoOptions = $this->model('Ngo')->approvedOptions();

        $message = '';
        $messageType = '';

        if (is_post()) {
            $title = trim($_POST['title'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $type = trim($_POST['type'] ?? '');
            $price = trim($_POST['price'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $bkash_number = trim($_POST['bkash_number'] ?? '');
            $nagad_number = trim($_POST['nagad_number'] ?? '');
            $payment_method = '';
            $payment_number = '';
            $ngoId = null;

            if ($title == '' || $category == '' || $type == '' || $location == '') {
                $message = 'Please fill all required fields.';
            } elseif ($type == 'Sell') {
                [$payment_method, $payment_number, $message] = $this->paymentInfo($price, $bkash_number, $nagad_number, false);
            } elseif ($type == 'Donate') {
                $price = '0';
                $ngoInput = trim($_POST['ngo_id'] ?? '');

                if ($ngoInput != '' && !ctype_digit($ngoInput)) {
                    $message = 'Selected NGO is invalid.';
                } elseif (!empty($ngoInput)) {
                    $ngoId = (int)$ngoInput;
                }
            }

            if ($message == '' && !has_upload('image')) {
                $message = 'Please upload an item image.';
            }

            if ($message == '') {
                $imageName = null;

                try {
                    $imageName = upload_image($_FILES['image']);

                    $items->begin();

                    $postedItemId = $items->create([
                        'user_id' => (int)$user_id,
                        'ngo_id' => $ngoId,
                        'title' => $title,
                        'category' => $category,
                        'type' => $type,
                        'price' => $price,
                        'location' => $location,
                        'image' => $imageName,
                        'status' => ($type == 'Donate' && $ngoId !== null) ? 'Pending' : 'Available',
                        'payment_method' => $payment_method,
                        'payment_number' => $payment_number,
                    ]);

                    $notifications = $this->model('Notification');

                    if ($type == 'Donate' && $ngoId !== null) {
                        $notifications->create([
                            'user_id' => null,
                            'ngo_id' => $ngoId,
                            'item_id' => $postedItemId,
                            'notification_type' => 'ngo_donation',
                            'message' => $name . " sent a private donation request for '" . $title . "'.",
                            'status' => 'Unread',
                        ]);
                    } elseif ($type == 'Donate') {
                        $notifications->notifyApprovedNgos(
                            $postedItemId,
                            'ngo_donation',
                            $name . " posted a donation item: '" . $title . "'."
                        );
                    }

                    $items->commit();

                    $message = 'Item posted successfully!';
                    $messageType = 'success';
                } catch (RuntimeException $e) {
                    // upload validation / move failure
                    $message = $e->getMessage();
                } catch (Throwable $e) {
                    $items->rollback();

                    if ($imageName && file_exists('uploads/' . $imageName)) {
                        unlink('uploads/' . $imageName);
                    }

                    $message = 'Failed to post item: ' . $e->getMessage();
                }
            }

            if ($message != '' && $messageType == '') {
                $messageType = 'error';
            }
        }

        $this->view('items/create', compact('name', 'ngoOptions', 'message', 'messageType'));
    }

    /*
     * Validates price + Bkash / Nagad numbers.
     * Returns [payment_method, payment_number, error message]
     */
    private function paymentInfo(string $price, string $bkash, string $nagad, bool $editForm): array
    {
        if ($price == '' || !is_numeric($price) || $price <= 0) {
            return ['', '', 'Please enter a valid price.'];
        }

        if ($editForm && $bkash == '' && $nagad == '') {
            return ['', '', 'Please enter at least one payment number.'];
        }

        if ($bkash != '' && !preg_match('/^01[0-9]{9}$/', $bkash)) {
            return ['', '', $editForm ? 'Enter a valid Bkash number.' : 'Enter a valid 11-digit Bkash number.'];
        }

        if ($nagad != '' && !preg_match('/^01[0-9]{9}$/', $nagad)) {
            return ['', '', $editForm ? 'Enter a valid Nagad number.' : 'Enter a valid 11-digit Nagad number.'];
        }

        $methods = [];
        $numbers = [];

        if ($bkash != '') {
            $methods[] = 'Bkash';
            $numbers[] = 'Bkash: ' . $bkash;
        }

        if ($nagad != '') {
            $methods[] = 'Nagad';
            $numbers[] = 'Nagad: ' . $nagad;
        }

        if (!$methods) {
            return ['', '', 'Please provide at least one payment number.'];
        }

        return [implode(', ', $methods), implode(', ', $numbers), ''];
    }

    /* =========================
       EDIT
       ========================= */

    public function edit(): void
    {
        $user_id = $this->user_id;
        $name = $_SESSION['name'] ?? 'User';
        $items = $this->model('Item');

        $item_id = (int)($_GET['id'] ?? 0);

        if ($item_id <= 0) {
            redirect('items/mine');
        }

        $item = $items->findOwned($item_id, $user_id);

        if (!$item) {
            redirect('items/mine');
        }

        $item_type = strtolower(trim($item['type'] ?? ''));
        $backRoute = $item_type == 'donate' ? 'items/donate' : 'items/sell';

        // Completed items cannot be edited
        if ($this->isLocked($item_type, $item['status'] ?? '')) {
            redirect($backRoute);
        }

        $message = '';
        $success = false;

        $items->ensureNgoColumn();
        $ngoOptions = $this->model('Ngo')->allOptions();

        // Existing payment numbers ("Bkash: 01..., Nagad: 01..." or old single-number format)
        $old_payment_method = $item['payment_method'] ?? '';
        $old_payment_number = $item['payment_number'] ?? '';
        $old_bkash = $this->paymentNumberFor('Bkash', $old_payment_method, $old_payment_number);
        $old_nagad = $this->paymentNumberFor('Nagad', $old_payment_method, $old_payment_number);

        if (is_post()) {
            // Check again in case it was completed meanwhile
            if ($this->isLocked($item_type, $items->statusOwned($item_id, $user_id) ?? '')) {
                redirect($backRoute);
            }

            $title = trim($_POST['title'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $location = trim($_POST['location'] ?? '');

            // Type cannot change
            $type = $item['type'];
            $price = $item['price'] ?? '0';
            $payment_method = $item['payment_method'] ?? '';
            $payment_number = $item['payment_number'] ?? '';
            $ngoIdValue = null;

            if ($title == '' || $category == '' || $location == '') {
                $message = 'Please fill all required fields.';
            } elseif (strtolower(trim($type)) == 'sell') {
                $price = trim($_POST['price'] ?? '');

                [$payment_method, $payment_number, $message] = $this->paymentInfo(
                    $price,
                    trim($_POST['bkash_number'] ?? ''),
                    trim($_POST['nagad_number'] ?? ''),
                    true
                );
            } elseif (strtolower(trim($type)) == 'donate') {
                $ngoId = trim($_POST['ngo_id'] ?? '');
                $price = '0';
                $payment_method = '';
                $payment_number = '';

                if ($ngoId == '' || !ctype_digit($ngoId) || (int)$ngoId <= 0) {
                    $message = 'Please select a valid NGO.';
                } else {
                    $ngoIdValue = (int)$ngoId;
                }
            }

            $newImage = $item['image'] ?? '';

            if ($message == '' && isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                try {
                    $newImage = upload_image($_FILES['image']);
                } catch (RuntimeException $e) {
                    $message = $e->getMessage();
                }
            }

            if ($message == '') {
                try {
                    $items->updateOwned($item_id, $user_id, [
                        'title' => $title,
                        'category' => $category,
                        'price' => $price,
                        'location' => $location,
                        'image' => $newImage,
                        'ngo_id' => $ngoIdValue,
                        'payment_method' => $payment_method,
                        'payment_number' => $payment_number,
                    ]);

                    // Delete the old image after a new one was saved
                    if ($newImage != ($item['image'] ?? '') && !empty($item['image']) && file_exists('uploads/' . $item['image'])) {
                        unlink('uploads/' . $item['image']);
                    }

                    $item = $items->findOwned($item_id, $user_id) ?? $item;
                    $item_type = strtolower(trim($item['type'] ?? ''));
                    $backRoute = $item_type == 'donate' ? 'items/donate' : 'items/sell';

                    $success = true;
                    $message = 'Item updated successfully!';
                } catch (Throwable $e) {
                    // Remove the new image if the update failed
                    if ($newImage != ($item['image'] ?? '') && !empty($newImage) && file_exists('uploads/' . $newImage)) {
                        unlink('uploads/' . $newImage);
                    }

                    $message = 'Failed to update item. ' . $e->getMessage();
                }
            }
        }

        $backPage = url($backRoute);

        $this->view('items/edit', compact(
            'user_id', 'name', 'item_id', 'item', 'item_type', 'backPage', 'message', 'success',
            'ngoOptions', 'old_bkash', 'old_nagad', 'old_payment_method', 'old_payment_number'
        ));
    }

    private function isLocked(string $itemType, string $status): bool
    {
        $status = strtolower(trim($status));

        return $status === 'sold' || ($itemType === 'donate' && in_array($status, ['accepted', 'donated'], true));
    }

    private function paymentNumberFor(string $method, string $paymentMethod, string $paymentNumber): string
    {
        if (stripos($paymentMethod, $method) === false) {
            return '';
        }

        foreach (explode(',', $paymentNumber) as $part) {
            $part = trim($part);

            if (stripos($part, $method . ':') === 0) {
                return trim(substr($part, strlen($method . ':')));
            }
        }

        // Old format: method "Bkash" with just the number
        if (strtolower(trim($paymentMethod)) == strtolower($method)) {
            return $paymentNumber;
        }

        return '';
    }

    /* =========================
       DELETE
       ========================= */

    public function delete(): void
    {
        $item_id = (int)($_GET['id'] ?? 0);

        if ($item_id <= 0) {
            redirect('items/mine');
        }

        try {
            $this->model('Item')->deleteOwned($item_id, $this->user_id);
            redirect('items/mine', ['deleted' => 1]);
        } catch (mysqli_sql_exception $e) {
            redirect('items/mine', ['deleted' => 0]);
        }
    }

    public function deleteDonate(): void
    {
        $item_id = (int)($_GET['id'] ?? 0);

        if ($item_id <= 0) {
            redirect('items/mine');
        }

        try {
            $this->model('Item')->deleteOwned($item_id, $this->user_id, 'Donate');
            redirect('items/mine', ['deleted' => 1]);
        } catch (mysqli_sql_exception $e) {
            redirect('items/mine', ['deleted' => 0]);
        }
    }
}
