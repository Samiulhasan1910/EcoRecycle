<?php

/*
 * Normal user: dashboard and profile
 */
class UserController extends Controller
{
    public function dashboard(): void
    {
        $user_id = $this->requireLogin();

        $this->view('user/dashboard', [
            'name' => $_SESSION['name'] ?? 'User',
            'itemsResult' => $this->model('Item')->recommendedFor($user_id),
        ]);
    }

    public function profile(): void
    {
        $user_id = $this->requireLogin();
        $users = $this->model('User');

        $message = '';
        $error = '';
        $editMode = isset($_GET['edit']) && $_GET['edit'] == '1';

        if (is_post()) {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $location = trim($_POST['location'] ?? '');

            if (empty($name) || empty($email)) {
                $error = 'Name and Email are required.';
                $editMode = true;
            } elseif ($users->emailUsedByOther($email, $user_id)) {
                $error = 'This email is already used by another account.';
                $editMode = true;
            } else {
                try {
                    $users->updateProfile($user_id, $name, $email, $phone, $location);

                    $_SESSION['name'] = $name;
                    $message = 'Profile updated successfully.';

                    // After update, go back to normal profile view
                    $editMode = false;
                } catch (Throwable $e) {
                    $error = 'Failed to update profile.';
                    $editMode = true;
                }
            }
        }

        $user = $users->find($user_id) ?? [];

        $this->view('user/profile', [
            'message' => $message,
            'error' => $error,
            'editMode' => $editMode,
            'user' => $user,
            'name' => $user['name'] ?? '',
            'email' => $user['email'] ?? '',
            'phone' => $user['phone'] ?? '',
            'location' => $user['location'] ?? '',
            'role' => $user['role'] ?? 'User',
        ]);
    }
}
