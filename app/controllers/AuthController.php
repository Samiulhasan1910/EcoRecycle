<?php

/*
 * Login, registration and logout for every account type
 */
class AuthController extends Controller
{
    public function login(): void
    {
        // Redirect if already logged in
        $home = [
            'admin' => 'admin/dashboard',
            'user'  => 'dashboard',
            'ngo'   => 'ngo/dashboard',
        ];

        $role = $_SESSION['role'] ?? '';

        if (isset($home[$role])) {
            redirect($home[$role]);
        }

        $this->view('auth/login', [
            'email' => $_COOKIE['remember_email'] ?? '',
        ]);
    }

    public function register(): void
    {
        $this->view('auth/register');
    }

    public function forgot(): void
    {
        $this->view('auth/forgot');
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();

        redirect('auth/login');
    }

    /* =========================
       LOGIN (AJAX, JSON)
       Checks admins, users, delivery men, then NGOs
       ========================= */

    public function authenticate(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = $_POST['remember'] ?? '0';

        if ($email === '' || $password === '') {
            $this->json(['success' => false, 'message' => 'Please enter email and password.']);
        }

        try {
            // 1. ADMIN
            if ($admin = $this->model('Admin')->findByEmail($email)) {
                $this->checkPasswordOrFail($password, $admin['password']);
                $this->upgradePassword($password, $admin['password'], fn($hash) => $this->model('Admin')->updatePassword((int)$admin['admin_id'], $hash));

                $_SESSION['admin_id'] = $admin['admin_id'];
                $this->startSession($admin, 'admin', $email, $remember);

                $this->json(['success' => true, 'message' => 'Admin login successful!', 'redirect' => url('admin/dashboard')]);
            }

            // 2. NORMAL USER
            if ($user = $this->model('User')->findByEmail($email)) {
                $this->checkPasswordOrFail($password, $user['password']);
                $this->upgradePassword($password, $user['password'], fn($hash) => $this->model('User')->updatePassword((int)$user['user_id'], $hash));

                $_SESSION['user_id'] = $user['user_id'];
                $this->startSession($user, 'user', $email, $remember);

                $this->json(['success' => true, 'message' => 'Login successful.', 'redirect' => url('dashboard')]);
            }

            // 3. DELIVERY MAN (must be approved)
            if ($delivery = $this->model('DeliveryMan')->findByEmail($email)) {
                $this->checkPasswordOrFail($password, $delivery['password']);
                $this->requireApproved($delivery['verification_status'] ?? 'Pending', 'Delivery Man');
                $this->upgradePassword($password, $delivery['password'], fn($hash) => $this->model('DeliveryMan')->updatePassword((int)$delivery['delivery_id'], $hash));

                $_SESSION['user_id'] = $delivery['delivery_id'];
                $_SESSION['delivery_id'] = $delivery['delivery_id'];
                $_SESSION['delivery_name'] = $delivery['name'];
                $this->startSession($delivery, 'delivery_man', $email, $remember);

                $this->json(['success' => true, 'message' => 'Delivery Man login successful!', 'redirect' => url('delivery/dashboard')]);
            }

            // 4. NGO (must be approved)
            if ($ngo = $this->model('Ngo')->findByEmail($email)) {
                $this->checkPasswordOrFail($password, $ngo['password']);
                $this->requireApproved($ngo['verification_status'] ?? '', 'NGO');
                $this->upgradePassword($password, $ngo['password'], fn($hash) => $this->model('Ngo')->updatePassword((int)$ngo['ngo_id'], $hash));

                $_SESSION['ngo_id'] = $ngo['ngo_id'];
                $this->startSession($ngo, 'ngo', $email, $remember);

                $this->json(['success' => true, 'message' => 'NGO login successful!', 'redirect' => url('ngo/dashboard')]);
            }
        } catch (mysqli_sql_exception $e) {
            $this->json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }

        // NO ACCOUNT FOUND
        $this->json(['success' => false, 'message' => 'Invalid email or password.']);
    }

    // Supports hashed passwords and old plain-text ones
    private function checkPasswordOrFail(string $plain, string $stored): void
    {
        if (password_verify($plain, $stored) || $plain === $stored) {
            return;
        }

        $this->json(['success' => false, 'message' => 'Invalid email or password.']);
    }

    // Convert an old plain-text password to a hash
    private function upgradePassword(string $plain, string $stored, callable $save): void
    {
        if ($plain === $stored && !password_verify($plain, $stored)) {
            $save(password_hash($plain, PASSWORD_DEFAULT));
        }
    }

    private function requireApproved(?string $status, string $label): void
    {
        $status = strtolower(trim((string)$status));

        if ($status === '') {
            $status = 'pending';
        }

        if ($status === 'approved') {
            return;
        }

        $messages = [
            'pending'  => "Your $label account is pending verification. Please wait for admin approval.",
            'rejected' => "Your $label account has been rejected. Please contact support.",
        ];

        $this->json([
            'success' => false,
            'message' => $messages[$status] ?? "Your $label account is not verified yet.",
        ]);
    }

    private function startSession(array $account, string $role, string $email, string $remember): void
    {
        $_SESSION['name'] = $account['name'];
        $_SESSION['email'] = $account['email'];
        $_SESSION['role'] = $role;

        if ($remember == '1') {
            setcookie('remember_email', $email, time() + (86400 * 30), '/');
        } else {
            setcookie('remember_email', '', time() - 3600, '/');
        }
    }

    /* =========================
       REGISTER (AJAX, JSON)
       ========================= */

    public function store(): void
    {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || $email === '' || $phone === '' || $location === '' || $role === '' || $password === '') {
            $this->json(['success' => false, 'message' => 'Please fill in all fields.']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Invalid email address.']);
        }

        if (strlen($password) < 6) {
            $this->json(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        }

        if (!in_array($role, ['user', 'ngo', 'delivery_man'], true)) {
            $this->json(['success' => false, 'message' => 'Invalid registration role.']);
        }

        try {
            if ($this->model('User')->emailTaken($email)) {
                $this->json(['success' => false, 'message' => 'This email is already registered.']);
            }

            $account = [
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'phone' => $phone,
                'location' => $location,
            ];

            if ($role === 'user') {
                $this->model('User')->create($name, $email, $account['password'], $phone, $location);
            } elseif ($role === 'delivery_man') {
                $account['verification_document'] = $this->uploadDocument(
                    'uploads/delivery_documents/', 'delivery_',
                    'Please upload your Driving License.', 'Failed to upload Driving License.'
                );

                $this->model('DeliveryMan')->create($account);
            } else {
                $account['organization_type'] = trim($_POST['organization_type'] ?? '');
                $account['established_year'] = trim($_POST['established_year'] ?? '');

                if ($account['organization_type'] === '' || $account['established_year'] === '') {
                    $this->json(['success' => false, 'message' => 'Please provide all NGO information.']);
                }

                $account['verification_document'] = $this->uploadDocument(
                    'uploads/ngo_documents/', 'ngo_',
                    'Please upload NGO verification document.', 'Failed to upload NGO document.'
                );

                $this->model('Ngo')->create($account);
            }
        } catch (mysqli_sql_exception $e) {
            $this->json(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
        }

        $this->json(['success' => true, 'message' => 'Registration successful.']);
    }

    // Validates and stores the verification document, returns its relative path
    private function uploadDocument(string $folder, string $prefix, string $missingMessage, string $failMessage): string
    {
        $file = $_FILES['verification_document'] ?? null;

        if (!$file || $file['error'] != UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => $missingMessage]);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            $this->json(['success' => false, 'message' => 'Only PDF, JPG, JPEG and PNG files are allowed.']);
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            $this->json(['success' => false, 'message' => 'File size must be less than 5MB.']);
        }

        if (!is_dir($folder) && !mkdir($folder, 0777, true)) {
            $this->json(['success' => false, 'message' => 'Failed to create upload folder.']);
        }

        $path = $folder . $prefix . time() . '_' . uniqid() . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            $this->json(['success' => false, 'message' => $failMessage]);
        }

        return $path;
    }
}
