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
        <a class="back" href="index.php?url=dashboard">&larr; Back to Dashboard</a>
        <h1>Wanted Donation Posts</h1>
        <form method="get" style="margin-bottom:20px;display:flex;gap:10px;align-items:center;"><input type="hidden" name="url" value="wanted/posts">
            <label for="category">Category:</label>
            <select id="category" name="category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $categoryRow): ?>
                    <option value="<?php echo htmlspecialchars($categoryRow["category"]); ?>" <?php echo strcasecmp(trim($category), trim($categoryRow["category"])) === 0 && $category !== "" ? "selected" : ""; ?>><?php echo htmlspecialchars($categoryRow["category"]); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Search</button>
            <?php if ($category !== ""): ?><a href="index.php?url=wanted/posts">Clear</a><?php endif; ?>
        </form>

        <?php if ($result && count($result) > 0): ?>
            <section class="grid">
                <?php foreach ($result as $post): ?>
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
                <?php endforeach; ?>
            </section>
        <?php else: ?>
            <div class="empty">No active wanted posts found.</div>
        <?php endif; ?>
    </main>
</body>
</html>
