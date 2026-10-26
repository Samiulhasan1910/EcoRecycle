<?php

/*
 * Transaction history and the payment page (Sell item or accepted wanted offer)
 */
class TransactionController extends Controller
{
    public function index(): void
    {
        $user_id = $this->requireLogin();

        $this->view('requests/transactions', [
            'user_id' => $user_id,
            'result' => $this->model('Transaction')->forUser($user_id),
        ]);
    }

    public function payment(): void
    {
        $user_id = $this->requireLogin();

        $item_id = (int)($_GET['item_id'] ?? 0);
        $offer_id = (int)($_GET['offer_id'] ?? 0);
        $isOfferPayment = ($offer_id > 0);

        $paymentError = '';
        $successMessage = '';
        $item = null;
        $offer = null;
        $sellerId = 0;
        $sellerName = 'User';
        $deliveryJobId = 0;

        if ($isOfferPayment) {
            // Wanted offer payment
            $offer = $this->model('WantedOffer')->findWithPost($offer_id);

            if (!$offer) {
                redirect('notifications');
            }

            $sellerId = (int)($offer['offerer_user_id'] ?? 0);
            $itemTitle = $offer['wanted_title'] ?? 'Wanted Item';
            $itemPrice = floatval($offer['price'] ?? 0);
            $sellerNumberText = $offer['payment_number'] ?? '';

            // Only the wanted-post owner pays, and only for an accepted / paid offer
            $offerStatus = strtolower(trim($offer['status'] ?? ''));

            if ((int)($offer['wanted_owner'] ?? 0) !== $user_id || !in_array($offerStatus, ['accepted', 'paid'], true)) {
                redirect('offers/view', ['offer_id' => $offer_id]);
            }

            $seller = $this->model('User')->find($sellerId);

            if ($seller && !empty($seller['name'])) {
                $sellerName = $seller['name'];
            }
        } elseif ($item_id > 0) {
            // Normal Sell item payment
            $item = $this->model('Item')->find($item_id);

            if (!$item) {
                redirect('items/browse');
            }

            if (
                strtolower(trim($item['type'] ?? '')) !== 'sell'
                || (int)$item['user_id'] === $user_id
                || strtolower(trim($item['status'] ?? '')) !== 'available'
            ) {
                redirect('items/view', ['id' => $item_id]);
            }

            $seller = $this->model('User')->find((int)$item['user_id']);
            $item['seller_name'] = $seller['name'] ?? null;

            $sellerId = (int)$item['user_id'];
            $itemTitle = $item['title'] ?? '';
            $itemPrice = floatval($item['price'] ?? 0);
            $sellerNumberText = $item['payment_number'] ?? '';
        } else {
            redirect('items/browse');
        }

        $paymentNumbers = $this->parsePaymentNumbers($sellerNumberText);

        if (is_post()) {
            $payment_method = trim($_POST['payment_method'] ?? '');
            $amount = trim($_POST['amount'] ?? '');
            $sender_number = trim($_POST['sender_number'] ?? '');
            $receiver_number = trim($_POST['receiver_number'] ?? '');
            $pin = trim($_POST['pin'] ?? '');

            if (!in_array($payment_method, ['Bkash', 'Nagad'], true)) {
                $paymentError = 'Please select Bkash or Nagad.';
            } elseif ($amount === '' || !is_numeric($amount) || floatval($amount) <= 0) {
                $paymentError = 'Please enter a valid payment amount.';
            } elseif (abs(floatval($amount) - $itemPrice) > 0.001) {
                $paymentError = 'Amount must match the item price: ' . number_format($itemPrice, 2, '.', '') . ' BDT.';
            } elseif (!isset($paymentNumbers[$payment_method])) {
                $paymentError = 'Seller has not added a valid ' . $payment_method . ' number.';
            } elseif ($receiver_number !== $paymentNumbers[$payment_method]) {
                $paymentError = 'Receiver number does not match the selected payment method.';
            } elseif (!preg_match('/^01[0-9]{9}$/', $sender_number)) {
                $paymentError = 'Enter a valid 11-digit sender number.';
            } elseif (!preg_match('/^[0-9]{4}$/', $pin)) {
                $paymentError = 'PIN must be 4 digits.';
            }

            if ($paymentError === '') {
                $transactions = $this->model('Transaction');

                try {
                    if ($isOfferPayment) {
                        $deliveryJobId = $transactions->payForOffer($user_id, $offer_id, $itemTitle, $payment_method, $amount, $receiver_number);

                        $successMessage = 'Payment successful! Your payment has been completed.';
                        $offer['status'] = 'Paid';
                    } else {
                        $deliveryJobId = $transactions->payForItem($user_id, $sellerId, $item_id, $itemTitle, $payment_method, $amount, $receiver_number);

                        $successMessage = 'Payment successful! The item has been marked as sold. '
                            . 'Your delivery request is now open for available delivery men.';
                    }
                } catch (Exception $e) {
                    $paymentError = $e->getMessage();
                }
            }
        }

        $this->view('requests/payment', compact(
            'user_id', 'item_id', 'offer_id', 'isOfferPayment', 'paymentError', 'successMessage', 'item', 'offer',
            'sellerId', 'itemTitle', 'itemPrice', 'sellerNumberText', 'paymentNumbers', 'sellerName', 'deliveryJobId'
        ));
    }

    /*
     * "Bkash: 01..., Nagad: 01..." -> ['Bkash' => '01...', 'Nagad' => '01...']
     * Old data with just one number -> ['Payment' => '01...']
     */
    private function parsePaymentNumbers(string $text): array
    {
        $numbers = [];

        if (trim($text) === '') {
            return $numbers;
        }

        foreach (explode(',', $text) as $part) {
            $part = trim($part);

            foreach (['Bkash', 'Nagad'] as $method) {
                if ($part !== '' && stripos($part, $method . ':') !== false) {
                    $value = trim(str_ireplace($method . ':', '', $part));

                    if ($value !== '') {
                        $numbers[$method] = $value;
                    }
                }
            }
        }

        if (empty($numbers) && preg_match('/^01[0-9]{9}$/', trim($text))) {
            $numbers['Payment'] = trim($text);
        }

        return $numbers;
    }
}
