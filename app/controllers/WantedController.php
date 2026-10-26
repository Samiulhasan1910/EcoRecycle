<?php

/*
 * Wanted posts: a user asks for an item, others make offers
 */
class WantedController extends Controller
{
    private int $user_id;

    public function __construct()
    {
        $this->user_id = $this->requireLogin();
    }

    public function mine(): void
    {
        $this->view('wanted/mine', [
            'name' => $_SESSION['name'] ?? 'User',
            'result' => $this->model('WantedPost')->byOwner($this->user_id),
        ]);
    }

    // Public list of active wanted posts and available donations
    public function posts(): void
    {
        $category = trim($_GET['category'] ?? '');
        $posts = $this->model('WantedPost');

        $this->view('wanted/posts', [
            'category' => $category,
            'categories' => $posts->publicCategories(),
            'result' => $posts->publicPosts($category),
        ]);
    }

    public function create(): void
    {
        $user_id = $this->user_id;
        $name = $_SESSION['name'] ?? 'User';
        $message = '';
        $messageType = '';

        if (is_post()) {
            $title = trim($_POST['title'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $imageName = '';

            if ($title == '' || $category == '' || $location == '') {
                $message = 'Please fill all required fields.';
            }

            // Image is optional
            if ($message == '' && isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
                if ($_FILES['image']['error'] == UPLOAD_ERR_OK) {
                    try {
                        $imageName = $this->uploadImage($_FILES['image'], false);
                    } catch (RuntimeException $e) {
                        $message = $e->getMessage();
                    }
                } else {
                    $message = 'There was an error uploading the image.';
                }
            }

            if ($message == '') {
                try {
                    $this->model('WantedPost')->create($user_id, $title, $category, $location, $description, $imageName);

                    $message = 'Wanted item posted successfully!';
                    $messageType = 'success';
                } catch (mysqli_sql_exception $e) {
                    $message = 'Database Error: ' . $e->getMessage();
                }
            }

            if ($messageType == '' && $message != '') {
                $messageType = 'error';
            }
        }

        $this->view('wanted/create', compact('user_id', 'name', 'message', 'messageType'));
    }

    public function show(): void
    {
        $user_id = $this->user_id;

        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            redirect('wanted/mine');
        }

        $wanted_id = (int)$_GET['id'];
        $wanted = $this->model('WantedPost')->find($wanted_id);

        if (!$wanted) {
            redirect('wanted/mine', ['msg' => 'wanted_not_found']);
        }

        $wanted_owner_id = (int)($wanted['user_id'] ?? 0);
        $is_owner = $wanted_owner_id > 0 && $wanted_owner_id === $user_id;

        // Back page: explicit ?from=..., else came from browse/search
        $from = $_GET['from'] ?? '';
        $backRoute = 'wanted/mine';

        if ($from === 'browse') {
            $backRoute = 'items/browse';
        } elseif ($from !== 'wanted') {
            parse_str((string)parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_QUERY), $refQuery);

            if (in_array($refQuery['url'] ?? '', ['items/browse', 'items/search'], true)) {
                $backRoute = 'items/browse';
            }
        }

        $backPage = url($backRoute);
        $msg = $_GET['msg'] ?? '';

        // Has this user already made an offer?
        $existing_offer_id = $is_owner ? 0 : $this->model('WantedOffer')->idForOfferer($wanted_id, $user_id);

        $this->view('wanted/view', compact(
            'user_id', 'wanted_id', 'wanted', 'wanted_owner_id', 'is_owner', 'backPage', 'from', 'msg', 'existing_offer_id'
        ));
    }

    public function edit(): void
    {
        $user_id = $this->user_id;
        $name = $_SESSION['name'] ?? 'User';
        $message = '';
        $messageType = '';
        $posts = $this->model('WantedPost');

        $wanted_id = (int)($_GET['id'] ?? 0);

        if (!is_numeric($_GET['id'] ?? '') || $wanted_id <= 0) {
            redirect('wanted/mine');
        }

        // Only the owner can edit
        $wanted = $posts->findOwned($wanted_id, $user_id);

        if (!$wanted || $this->isLocked($wanted['status'] ?? '')) {
            redirect('wanted/mine');
        }

        if (is_post()) {
            // Check again before update
            $latest = $posts->findOwned($wanted_id, $user_id);

            if ($latest && $this->isLocked($latest['status'] ?? '')) {
                redirect('wanted/mine');
            }

            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'category' => trim($_POST['category'] ?? ''),
                'location' => trim($_POST['location'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'status' => trim($_POST['status'] ?? ''),
            ];

            if ($data['title'] == '' || $data['category'] == '' || $data['location'] == '' || $data['status'] == '') {
                $message = 'Please fill all required fields.';
            } elseif (!in_array($data['status'], ['Active', 'Closed'])) {
                $message = 'Invalid status selected.';
            } else {
                $imageName = null;

                try {
                    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                        $imageName = $this->uploadImage($_FILES['image'], true);
                    }

                    try {
                        $posts->updateOwned($wanted_id, $user_id, $data, $imageName);
                    } catch (mysqli_sql_exception $e) {
                        if ($imageName && file_exists('uploads/' . $imageName)) {
                            unlink('uploads/' . $imageName);
                        }

                        throw new RuntimeException('Failed to update wanted post: ' . $e->getMessage());
                    }

                    // Delete the old image when a new one was saved
                    if ($imageName && !empty($wanted['image']) && file_exists('uploads/' . $wanted['image'])) {
                        unlink('uploads/' . $wanted['image']);
                    }

                    redirect('wanted/view', ['id' => $wanted_id]);
                } catch (RuntimeException $e) {
                    $message = $e->getMessage();
                }
            }

            if ($message != '') {
                $messageType = 'error';
            }
        }

        $this->view('wanted/edit', compact('user_id', 'name', 'wanted_id', 'wanted', 'message', 'messageType'));
    }

    public function delete(): void
    {
        $wanted_id = (int)($_GET['id'] ?? 0);

        if ($wanted_id <= 0) {
            redirect('wanted/mine', ['deleted' => 0]);
        }

        try {
            $this->model('WantedPost')->deleteOwned($wanted_id, $this->user_id);
            redirect('wanted/mine', ['deleted' => 1]);
        } catch (mysqli_sql_exception $e) {
            redirect('wanted/mine', ['deleted' => 0]);
        }
    }

    private function isLocked(string $status): bool
    {
        return in_array(strtolower(trim($status)), ['accepted', 'paid'], true);
    }

    // Wanted posts also accept GIF; only the edit form limits the size
    private function uploadImage(array $file, bool $checkSize): string
    {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            throw new RuntimeException($checkSize
                ? 'Only JPG, JPEG, PNG, GIF or WEBP images are allowed.'
                : 'Only JPG, JPEG, PNG, GIF and WEBP images are allowed.');
        }

        if ($checkSize && $file['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('Image size must be less than 5 MB.');
        }

        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        $imageName = time() . '_' . uniqid() . '.' . $extension;

        if (!move_uploaded_file($file['tmp_name'], 'uploads/' . $imageName)) {
            throw new RuntimeException('Image upload failed.');
        }

        return $imageName;
    }
}
