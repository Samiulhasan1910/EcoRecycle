<?php

/*
 * Global view / controller helpers
 */

// Escape output for HTML
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Build a link to a route: url('items/view', ['id' => 5]) -> index.php?url=items/view&id=5
function url(string $route, array $params = []): string
{
    $query = http_build_query(array_merge(['url' => $route], $params));

    return 'index.php?' . str_replace('%2F', '/', $query);
}

function asset(string $path): string
{
    return 'assets/' . ltrim($path, '/');
}

function redirect(string $route, array $params = []): never
{
    header('Location: ' . url($route, $params));
    exit;
}

function current_route(): string
{
    return $GLOBALS['currentRoute'] ?? '';
}

// Returns 'active' when the current page matches one of the given routes
function active(string ...$routes): string
{
    return in_array(current_route(), $routes, true) ? 'active' : '';
}

// Empty status values are shown as "Pending"
function status_or_pending($status): string
{
    $status = trim((string)$status);

    return $status !== '' ? $status : 'Pending';
}

// "Arrived at Pickup" -> "status-arrived-at-pickup"
function status_class($status): string
{
    return 'status-' . strtolower(str_replace(' ', '-', trim((string)$status)));
}

/*
 * Save an uploaded image into uploads/ and return the new file name.
 * Throws RuntimeException with a user-facing message.
 */
function upload_image(array $file, string $prefix = '', string $folder = 'uploads'): string
{
    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new RuntimeException('Only JPG, JPEG, PNG or WEBP images are allowed.');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Image size must be less than 5 MB.');
    }

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $imageName = $prefix . time() . '_' . uniqid() . '.' . $extension;

    if (!move_uploaded_file($file['tmp_name'], $folder . '/' . $imageName)) {
        throw new RuntimeException('Image upload failed.');
    }

    return $imageName;
}

function has_upload(string $field): bool
{
    return isset($_FILES[$field]) && $_FILES[$field]['error'] == UPLOAD_ERR_OK;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_valid(?string $token): bool
{
    return hash_equals(csrf_token(), (string)$token);
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}
