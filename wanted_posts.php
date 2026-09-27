<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include("db.php");

$category = trim($_GET["category"] ?? "");
$categorySafe = mysqli_real_escape_string($conn, $category);

$sql = "SELECT post_id, title, category, location, description, status, ngo_name
     FROM (
         SELECT wanted_posts.wanted_id AS post_id,
             wanted_posts.title,
             wanted_posts.category,
                   wanted_posts.location,
             wanted_posts.description,
                 wanted_posts.status,
                 NULL AS ngo_name
         FROM wanted_posts
         WHERE LOWER(wanted_posts.status) = 'active'

         UNION ALL

         SELECT items.item_id AS post_id,
             items.title,
             items.category,
                   items.location,
             items.description,
             'Active' AS status,
             ngos.name AS ngo_name
         FROM items
         LEFT JOIN ngos ON ngos.ngo_id = items.ngo_id
         WHERE items.type = 'Donate'
        AND LOWER(items.status) = 'available'
    ) AS all_posts
    WHERE 1";

if ($category !== "") {
    $sql .= " AND LOWER(TRIM(category)) = LOWER(TRIM('$categorySafe'))";
}

$sql .= "
     ORDER BY post_id DESC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wanted Posts</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f7f6; color: #222; }
        .container { max-width: 1100px; margin: 35px auto; padding: 0 20px; }
        .back { color: #0f766e; text-decoration: none; font-weight: bold; }
        h1 { margin: 22px 0; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .post { background: #fff; border: 1px solid #ddd; padding: 20px; }
        .post h2 { margin-top: 0; font-size: 20px; }
        .meta { margin: 8px 0; color: #555; }
        .description { line-height: 1.5; }
        .empty { background: #fff; padding: 25px; text-align: center; color: #666; }
    </style>
</head>
<body>
    <main class="container">
        <a class="back" href="dashboard.php">&larr; Back to Dashboard</a>
        <h1>Wanted Donation Posts</h1>
        <form method="get" style="margin-bottom:20px;display:flex;gap:10px;align-items:center;">
            <label for="category">Category:</label>
            <select id="category" name="category">
                <option value="">All Categories</option>
                <?php
                $categories = mysqli_query($conn, "SELECT category FROM wanted_posts WHERE category IS NOT NULL AND category != '' UNION SELECT category FROM items WHERE type = 'Donate' AND status = 'Available' AND category IS NOT NULL AND category != '' ORDER BY category ASC");
                if ($categories) while ($categoryRow = mysqli_fetch_assoc($categories)):
                ?>
                    <option value="<?php echo htmlspecialchars($categoryRow["category"]); ?>" <?php echo strcasecmp(trim($category), trim($categoryRow["category"])) === 0 && $category !== "" ? "selected" : ""; ?>><?php echo htmlspecialchars($categoryRow["category"]); ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit">Search</button>
            <?php if ($category !== ""): ?><a href="wanted_posts.php">Clear</a><?php endif; ?>
        </form>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <section class="grid">
                <?php while ($post = mysqli_fetch_assoc($result)): ?>
                    <article class="post">
                        <h2><?php echo htmlspecialchars($post["title"] ?? ""); ?></h2>
                        <p class="meta"><strong>Category:</strong> <?php echo htmlspecialchars($post["category"] ?? ""); ?></p>
                        <p class="meta"><strong>Location:</strong> <?php echo htmlspecialchars($post["location"] ?? ""); ?></p>
                        <?php if (!empty($post["ngo_name"])): ?>
                            <p class="meta"><strong>NGO Profile:</strong> <?php echo htmlspecialchars($post["ngo_name"]); ?></p>
                        <?php else: ?>
                            <p class="meta"><strong>Requested by:</strong> User</p>
                        <?php endif; ?>
                        <p class="description"><?php echo nl2br(htmlspecialchars($post["description"] ?? "")); ?></p>
                    </article>
                <?php endwhile; ?>
            </section>
        <?php else: ?>
            <div class="empty">No active wanted posts found.</div>
        <?php endif; ?>
    </main>
</body>
</html>
