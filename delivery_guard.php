<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'delivery_man') {
    header('Location: login.php');
    exit;
}
include 'db.php';
$deliveryId = (int)($_SESSION['delivery_id'] ?? $_SESSION['user_id']);
$name = $_SESSION['name'] ?? 'Delivery';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
