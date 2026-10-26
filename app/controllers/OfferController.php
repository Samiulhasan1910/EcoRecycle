<?php

/*
 * Offers made on wanted posts
 */
class OfferController extends Controller
{
    private int $user_id;

    public function __construct()
    {
        $this->user_id = $this->requireLogin();
    }

    /* =========================
       MAKE AN OFFER
       ========================= */

    public function create(): void
    {
        $user_id = $this->user_id;
        $offers = $this->model('WantedOffer');

        $wanted_id = (int)($_GET['wanted_id'] ?? 0);

        if ($wanted_id <= 0) {
            redirect('wanted/mine');
        }

        $wanted = $this->model('WantedPost')->find($wanted_id);

        if (!$wanted) {
            redirect('wanted/mine', ['msg' => 'wanted_not_found']);
        }

        if ((int)$wanted['user_id'] === $user_id) {
            redirect('wanted/view', ['id' => $wanted_id, 'msg' => 'own_wanted']);
        }

        if ($offers->idForOfferer($wanted_id, $user_id)) {
            redirect('wanted/view', ['id' => $wanted_id, 'msg' => 'duplicate_offer']);
        }

        $message = '';
        $message_type = '';
        $old_price = '';
        $old_is_free = 0;
        $old_bkash = '';
        $old_nagad = '';
        $old_bkash_checked = 0;
        $old_nagad_checked = 0;
        $old_location = '';

        if (is_post()) {
            $is_free = isset($_POST['is_free']) ? 1 : 0;
            $price = trim($_POST['price'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $bkash_number = trim($_POST['bkash_number'] ?? '');
            $nagad_number = trim($_POST['nagad_number'] ?? '');
            $bkash_selected = isset($_POST['bkash_selected']) ? 1 : 0;
            $nagad_selected = isset($_POST['nagad_selected']) ? 1 : 0;

            // Keep old values for the form
            $old_price = $price;
            $old_is_free = $is_free;
            $old_location = $location;
            $old_bkash = $bkash_number;
            $old_nagad = $nagad_number;
            $old_bkash_checked = $bkash_selected;
            $old_nagad_checked = $nagad_selected;

            $message = $this->validateOffer($wanted_id, $location, $is_free, $price, $bkash_selected, $bkash_number, $nagad_selected, $nagad_number);

            // Duplicate check again for this POST
            if ($message === '' && $offers->idForOfferer($wanted_id, $user_id)) {
                redirect('wanted/view', ['id' => $wanted_id, 'msg' => 'duplicate_offer']);
            }

            $image_path = '';

            if ($message === '') {
                try {
                    $image_name = $this->uploadOfferImage();
                    $image_path = 'uploads/' . $image_name;

                    $methods = [];
                    $numbers = [];

                    if (!$is_free && $bkash_selected) {
                        $methods[] = 'Bkash';
                        $numbers[] = 'Bkash: ' . $bkash_number;
                    }

                    if (!$is_free && $nagad_selected) {
                        $methods[] = 'Nagad';
                        $numbers[] = 'Nagad: ' . $nagad_number;
                    }

                    $offer_id = $offers->create([
                        'wanted_id' => $wanted_id,
                        'offerer_user_id' => $user_id,
                        'image' => $image_name,
                        'location' => $location,
                        'price' => $is_free ? null : $price,
                        'is_free' => $is_free,
                        'payment_method' => $is_free ? null : implode(', ', $methods),
                        'payment_number' => $is_free ? null : implode(', ', $numbers),
                    ]);

                    // The offer is saved; a failed notification must not undo it
                    try {
                        $offerer = $this->model('User')->find($user_id);
                        $offerer_name = trim($offerer['name'] ?? '') !== '' ? $offerer['name'] : 'Someone';

                        $this->model('Notification')->create([
                            'user_id' => (int)$wanted['user_id'],
                            'request_id' => $offer_id,
                            'notification_type' => 'wanted_offer',
                            'message' => $offerer_name . ' offered an item for your wanted post: ' . $wanted['title'],
                            'status' => 'Unread',
                        ]);
                    } catch (mysqli_sql_exception $e) {
                    }

                    redirect('wanted/view', ['id' => $wanted_id, 'msg' => 'offer_sent']);
                } catch (RuntimeException $e) {
                    $message = $e->getMessage();
                } catch (mysqli_sql_exception $e) {
                    if ($image_path !== '' && file_exists($image_path)) {
                        unlink($image_path);
                    }

                    $message = 'Offer failed: ' . $e->getMessage();
                }
            }

            if ($message !== '') {
                $message_type = 'error';
            }
        }

        $this->view('offers/create', compact(
            'user_id', 'wanted_id', 'wanted', 'message', 'message_type', 'old_price', 'old_is_free', 'old_bkash',
            'old_nagad', 'old_bkash_checked', 'old_nagad_checked', 'old_location'
        ));
    }

    private function validateOffer(int $wanted_id, string $location, int $is_free, string $price, int $bkashSelected, string $bkash, int $nagadSelected, string $nagad): string
    {
        if ((int)($_POST['wanted_id'] ?? 0) !== $wanted_id) {
            return 'Invalid wanted post.';
        }

        if ($location === '') {
            return 'Please enter your item location.';
        }

        if (strlen($location) > 255) {
            return 'Location must be less than 255 characters.';
        }

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            return 'Please upload your item picture.';
        }

        $image = $_FILES['image'];

        if ($image['size'] > 5 * 1024 * 1024) {
            return 'Image must be less than 5MB.';
        }

        $extension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return 'Only JPG, PNG and WEBP images are allowed.';
        }

        // Check the real MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realType = finfo_file($finfo, $image['tmp_name']);
        finfo_close($finfo);

        if (!in_array($realType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return 'Invalid image file.';
        }

        if ($is_free == 1) {
            return '';
        }

        if ($price === '' || !is_numeric($price) || (float)$price <= 0) {
            return 'Please enter a valid price.';
        }

        if (!$bkashSelected && !$nagadSelected) {
            return 'Please select Bkash or Nagad.';
        }

        if ($bkashSelected && !preg_match('/^01[0-9]{9}$/', $bkash)) {
            return 'Please enter a valid Bkash number.';
        }

        if ($nagadSelected && !preg_match('/^01[0-9]{9}$/', $nagad)) {
            return 'Please enter a valid Nagad number.';
        }

        return '';
    }

    private function uploadOfferImage(): string
    {
        if (!is_dir('uploads/') && !mkdir('uploads/', 0777, true)) {
            throw new RuntimeException('Unable to create upload directory.');
        }

        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $imageName = 'offer_' . time() . '_' . uniqid() . '.' . $extension;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $imageName)) {
            throw new RuntimeException('Image upload failed.');
        }

        return $imageName;
    }

    /* =========================
       ACCEPT / REJECT (wanted owner)
       ========================= */

    public function action(): void
    {
        $offer_id = (int)($_POST['offer_id'] ?? $_GET['offer_id'] ?? 0);
        $action = $_POST['action'] ?? ($_GET['action'] ?? '');

        if ($offer_id <= 0) {
            die('Invalid Offer ID.');
        }

        if ($action !== 'accept' && $action !== 'reject') {
            die('Invalid Action.');
        }

        $offers = $this->model('WantedOffer');
        $offer = $offers->find($offer_id);

        if (!$offer) {
            die('Offer not found.<br>Received offer_id = ' . htmlspecialchars((string)$offer_id));
        }

        $wanted_id = (int)$offer['wanted_id'];
        $wanted = $this->model('WantedPost')->find($wanted_id);

        if (!$wanted) {
            die('Wanted post not found.<br>wanted_id = ' . htmlspecialchars((string)$wanted_id));
        }

        if ((int)$wanted['user_id'] !== $this->user_id) {
            die('You are not authorized to perform this action.');
        }

        // Only pending offers can be accepted / rejected
        if ($offer['status'] !== 'Pending') {
            if ($offer['status'] === 'Accepted') {
                redirect('payment', ['offer_id' => $offer_id]);
            }

            redirect('offers/list', ['wanted_id' => $wanted_id, 'msg' => 'already_processed']);
        }

        if ($action === 'accept') {
            try {
                $offers->accept($offer, $wanted);
            } catch (Exception $e) {
                die('Failed to accept offer: ' . $e->getMessage());
            }

            // Go straight to payment for this offer
            redirect('payment', ['offer_id' => $offer_id]);
        }

        try {
            $offers->reject($offer, $wanted);
        } catch (Exception $e) {
            die('Failed to reject offer: ' . $e->getMessage());
        }

        redirect('offers/list', ['wanted_id' => $wanted_id, 'msg' => 'rejected']);
    }

    /* =========================
       VIEW ONE OFFER (owner or offerer)
       ========================= */

    public function show(): void
    {
        $user_id = $this->user_id;

        if (!isset($_GET['offer_id']) || !is_numeric($_GET['offer_id']) || (int)$_GET['offer_id'] <= 0) {
            redirect('notifications');
        }

        $offer_id = (int)$_GET['offer_id'];

        // Mark only this notification as read
        if (isset($_GET['notification_id']) && is_numeric($_GET['notification_id']) && (int)$_GET['notification_id'] > 0) {
            $this->model('Notification')->markOneReadForUser((int)$_GET['notification_id'], $user_id);
        }

        $offer = $this->model('WantedOffer')->find($offer_id);

        if (!$offer) {
            redirect('notifications');
        }

        $wanted_id = (int)($offer['wanted_id'] ?? 0);
        $offerer_user_id = (int)($offer['offerer_user_id'] ?? 0);

        $wanted = $wanted_id > 0 ? $this->model('WantedPost')->find($wanted_id) : null;
        $offerer = $offerer_user_id > 0 ? $this->model('User')->find($offerer_user_id) : null;

        $wanted_owner_id = (int)($wanted['user_id'] ?? 0);
        $isOwner = ($user_id == $wanted_owner_id);
        $isOfferer = ($user_id == $offerer_user_id);

        if (!$isOwner && !$isOfferer) {
            redirect('notifications');
        }

        $offer_status = trim($offer['status'] ?? 'Pending');
        $is_free = (int)($offer['is_free'] ?? 0);
        $offer_price = $offer['price'] ?? '';
        $payment_method = $offer['payment_method'] ?? '';
        $payment_number = $offer['payment_number'] ?? '';

        // Display values are already HTML-escaped (the view prints them directly)
        $offer_location = $this->escapedOr($offer['location'] ?? '', '');
        $wanted_title = $this->escapedOr($wanted['title'] ?? '', 'Wanted Item');
        $wanted_category = $this->escapedOr($wanted['category'] ?? '', '');
        $wanted_location = $this->escapedOr($wanted['location'] ?? '', '');
        $offerer_name = $this->escapedOr($offerer['name'] ?? '', 'User');
        $offer_image = $this->escapedOr($offer['image'] ?? '', '');

        $display_price = '';

        if ($is_free == 1) {
            $display_price = 'Free / Donate';
        } elseif ($offer_price !== '' && $offer_price !== null) {
            $display_price = '৳ ' . htmlspecialchars($offer_price);
        }

        $display_payment_method = $this->escapedOr($payment_method, '');
        $display_payment_number = $this->escapedOr($payment_number, '');

        $this->view('offers/view', compact(
            'user_id', 'offer_id', 'offer', 'wanted_id', 'offerer_user_id', 'offer_status', 'is_free', 'offer_price',
            'payment_method', 'payment_number', 'offer_location', 'wanted', 'offerer', 'wanted_owner_id', 'isOwner',
            'isOfferer', 'wanted_title', 'wanted_category', 'wanted_location', 'offerer_name', 'offer_image',
            'display_price', 'display_payment_method', 'display_payment_number'
        ));
    }

    private function escapedOr(?string $value, string $default): string
    {
        return trim((string)$value) !== '' ? htmlspecialchars($value) : $default;
    }

    /* =========================
       ALL OFFERS ON ONE WANTED POST
       ========================= */

    public function list(): void
    {
        if (!isset($_GET['wanted_id']) || !is_numeric($_GET['wanted_id'])) {
            redirect('requests/mine');
        }

        $wanted_id = (int)$_GET['wanted_id'];
        $wanted = $this->model('WantedPost')->find($wanted_id);

        if (!$wanted) {
            die('Wanted post not found.');
        }

        if ($wanted['user_id'] != $this->user_id) {
            die('Not authorized.');
        }

        $this->view('offers/list', [
            'user_id' => $this->user_id,
            'wanted_id' => $wanted_id,
            'wanted' => $wanted,
            'res' => $this->model('WantedOffer')->forWanted($wanted_id),
        ]);
    }
}
