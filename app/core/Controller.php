<?php

/*
 * Base controller: rendering, redirects and access guards
 */
abstract class Controller
{
    private array $models = [];

    /*
     * Load (and cache) a model: $this->model('Item')
     */
    protected function model(string $name): Model
    {
        if (!isset($this->models[$name])) {
            require_once APP_PATH . '/models/' . $name . '.php';
            $this->models[$name] = new $name();
        }

        return $this->models[$name];
    }

    /*
     * Render app/views/<view>.php with $data as variables
     */
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        require APP_PATH . '/views/' . $view . '.php';
    }

    protected function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /* =========================
       ACCESS GUARDS
       ========================= */

    // Any logged-in account that has a user_id (user or delivery man)
    protected function requireLogin(): int
    {
        if (!isset($_SESSION['user_id'])) {
            redirect('auth/login');
        }

        return (int)$_SESSION['user_id'];
    }

    protected function requireUser(): int
    {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
            redirect('auth/login');
        }

        return (int)$_SESSION['user_id'];
    }

    protected function requireAdmin(): int
    {
        if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
            redirect('auth/login');
        }

        return (int)$_SESSION['admin_id'];
    }

    protected function requireNgo(): int
    {
        if (!isset($_SESSION['ngo_id'])) {
            redirect('auth/login');
        }

        return (int)$_SESSION['ngo_id'];
    }

    protected function requireDelivery(): int
    {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'delivery_man') {
            redirect('auth/login');
        }

        return (int)($_SESSION['delivery_id'] ?? $_SESSION['user_id']);
    }
}
