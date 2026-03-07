<?php
session_start();
require_once 'connection.php';

if (($_SESSION['role'] ?? '') !== 'student' || !isset($_SESSION['student_id'])) {
    if (($_SESSION['role'] ?? '') === 'company' && isset($_SESSION['company_id'])) {
        header('Location: compHomepage.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

$studentId = (int) $_SESSION['student_id'];
$username = htmlspecialchars($_SESSION['username'] ?? 'Student');

function buildRedirectUrl(string $search, int $productId = 0): string
{
    $params = [];
    if ($search !== '') {
        $params['q'] = $search;
    }
    if ($productId > 0) {
        $params['product'] = $productId;
    }

    return 'products.php' . (count($params) > 0 ? '?' . http_build_query($params) : '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $searchRedirect = trim($_POST['q'] ?? '');
    $postProductId = (int) ($_POST['product_id'] ?? 0);

    if ($action === 'buy_product') {
        if ($postProductId <= 0) {
            $_SESSION['product_flash'] = ['type' => 'error', 'message' => 'Invalid product selection.'];
            header('Location: ' . buildRedirectUrl($searchRedirect));
            exit();
        }

        $checkStmt = mysqli_prepare(
            $con,
            'SELECT owner_id, status FROM products WHERE product_id = ? LIMIT 1'
        );

        if (!$checkStmt) {
            $_SESSION['product_flash'] = ['type' => 'error', 'message' => 'Could not process buy request.'];
            header('Location: ' . buildRedirectUrl($searchRedirect, $postProductId));
            exit();
        }

        mysqli_stmt_bind_param($checkStmt, 'i', $postProductId);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $product = $checkResult ? mysqli_fetch_assoc($checkResult) : null;
        mysqli_stmt_close($checkStmt);

        if (!$product) {
            $_SESSION['product_flash'] = ['type' => 'error', 'message' => 'Product not found.'];
            header('Location: ' . buildRedirectUrl($searchRedirect));
            exit();
        }

        if ((int) $product['owner_id'] === $studentId) {
            $_SESSION['product_flash'] = ['type' => 'error', 'message' => 'You cannot buy your own product.'];
            header('Location: ' . buildRedirectUrl($searchRedirect, $postProductId));
            exit();
        }

        if (($product['status'] ?? '') !== 'available') {
            $_SESSION['product_flash'] = ['type' => 'error', 'message' => 'This product is no longer available.'];
            header('Location: ' . buildRedirectUrl($searchRedirect, $postProductId));
            exit();
        }

        mysqli_begin_transaction($con);
        $ok = true;

        $buyStmt = mysqli_prepare(
            $con,
            'INSERT INTO buy_product (product_id, buyer_id) VALUES (?, ?)'
        );

        if ($buyStmt) {
            mysqli_stmt_bind_param($buyStmt, 'ii', $postProductId, $studentId);
            $ok = mysqli_stmt_execute($buyStmt);
            mysqli_stmt_close($buyStmt);
        } else {
            $ok = false;
        }

        if ($ok) {
            $updateStmt = mysqli_prepare(
                $con,
                "UPDATE products SET status = 'sold' WHERE product_id = ? AND status = 'available'"
            );

            if ($updateStmt) {
                mysqli_stmt_bind_param($updateStmt, 'i', $postProductId);
                $ok = mysqli_stmt_execute($updateStmt) && (mysqli_stmt_affected_rows($updateStmt) > 0);
                mysqli_stmt_close($updateStmt);
            } else {
                $ok = false;
            }
        }

        if ($ok) {
            mysqli_commit($con);
            $_SESSION['product_flash'] = ['type' => 'success', 'message' => 'Product bought successfully.'];
        } else {
            mysqli_rollback($con);
            $_SESSION['product_flash'] = ['type' => 'error', 'message' => 'Could not complete purchase.'];
        }

        header('Location: ' . buildRedirectUrl($searchRedirect, $postProductId));
        exit();
    }

    if ($action === 'submit_review') {
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($postProductId <= 0) {
            $_SESSION['product_flash'] = ['type' => 'error', 'message' => 'Invalid product for review.'];
            header('Location: ' . buildRedirectUrl($searchRedirect));
            exit();
        }

        if ($rating < 1 || $rating > 5) {
            $_SESSION['product_flash'] = ['type' => 'error', 'message' => 'Rating must be between 1 and 5.'];
            header('Location: ' . buildRedirectUrl($searchRedirect, $postProductId));
            exit();
        }

        if ($comment === '') {
            $_SESSION['product_flash'] = ['type' => 'error', 'message' => 'Please write a review comment.'];
            header('Location: ' . buildRedirectUrl($searchRedirect, $postProductId));
            exit();
        }

        $checkStmt = mysqli_prepare(
            $con,
            'SELECT product_id FROM products WHERE product_id = ? LIMIT 1'
        );

        if (!$checkStmt) {
            $_SESSION['product_flash'] = ['type' => 'error', 'message' => 'Could not verify product.'];
            header('Location: ' . buildRedirectUrl($searchRedirect, $postProductId));
            exit();
        }

        mysqli_stmt_bind_param($checkStmt, 'i', $postProductId);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $exists = $checkResult ? mysqli_fetch_assoc($checkResult) : null;
        mysqli_stmt_close($checkStmt);

        if (!$exists) {
            $_SESSION['product_flash'] = ['type' => 'error', 'message' => 'Product not found for review.'];
            header('Location: ' . buildRedirectUrl($searchRedirect));
            exit();
        }

        $reviewStmt = mysqli_prepare(
            $con,
            'INSERT INTO reviews (reviewer_id, product_id, rating, comment) VALUES (?, ?, ?, ?)'
        );

        if ($reviewStmt) {
            mysqli_stmt_bind_param($reviewStmt, 'iiis', $studentId, $postProductId, $rating, $comment);
            $inserted = mysqli_stmt_execute($reviewStmt);
            mysqli_stmt_close($reviewStmt);

            if ($inserted) {
                $_SESSION['product_flash'] = ['type' => 'success', 'message' => 'Your review was submitted.'];
            } else {
                $_SESSION['product_flash'] = ['type' => 'error', 'message' => 'Could not submit review.'];
            }
        } else {
            $_SESSION['product_flash'] = ['type' => 'error', 'message' => 'Could not submit review.'];
        }

        header('Location: ' . buildRedirectUrl($searchRedirect, $postProductId));
        exit();
    }
}

$flash = $_SESSION['product_flash'] ?? null;
unset($_SESSION['product_flash']);

$searchQuery = trim($_GET['q'] ?? '');
$selectedProductId = (int) ($_GET['product'] ?? 0);
$products = [];
$reviewsByProduct = [];
$selectedProduct = null;

$productSql = "
    SELECT
        p.product_id,
        p.owner_id,
        p.product_title,
        p.description,
        p.price,
        p.status,
        p.created_at,
        st.username AS owner_username,
        COALESCE(AVG(r.rating), 0) AS avg_rating,
        COUNT(r.review_id) AS review_count
    FROM products p
    INNER JOIN students st ON p.owner_id = st.student_id
    LEFT JOIN reviews r ON r.product_id = p.product_id
";

if ($searchQuery !== '') {
    $productSql .= " WHERE p.product_title LIKE ? ";
}

$productSql .= "
    GROUP BY p.product_id, p.owner_id, p.product_title, p.description, p.price, p.status, p.created_at, st.username
    ORDER BY p.created_at DESC
";

$productStmt = mysqli_prepare($con, $productSql);

if ($productStmt) {
    if ($searchQuery !== '') {
        $searchLike = '%' . $searchQuery . '%';
        mysqli_stmt_bind_param($productStmt, 's', $searchLike);
    }

    mysqli_stmt_execute($productStmt);
    $productResult = mysqli_stmt_get_result($productStmt);

    if ($productResult) {
        while ($row = mysqli_fetch_assoc($productResult)) {
            $products[] = $row;
        }
    }

    mysqli_stmt_close($productStmt);
}

if (count($products) > 0) {
    $productIds = array_map(static function ($productRow) {
        return (int) $productRow['product_id'];
    }, $products);

    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $reviewSql = "
        SELECT
            r.product_id,
            r.rating,
            r.comment,
            r.created_at,
            st.username AS reviewer_username
        FROM reviews r
        INNER JOIN students st ON r.reviewer_id = st.student_id
        WHERE r.product_id IN ($placeholders)
        ORDER BY r.created_at DESC
    ";

    $reviewStmt = mysqli_prepare($con, $reviewSql);

    if ($reviewStmt) {
        $types = str_repeat('i', count($productIds));
        mysqli_stmt_bind_param($reviewStmt, $types, ...$productIds);
        mysqli_stmt_execute($reviewStmt);
        $reviewResult = mysqli_stmt_get_result($reviewStmt);

        if ($reviewResult) {
            while ($review = mysqli_fetch_assoc($reviewResult)) {
                $key = (int) $review['product_id'];
                if (!isset($reviewsByProduct[$key])) {
                    $reviewsByProduct[$key] = [];
                }
                $reviewsByProduct[$key][] = $review;
            }
        }

        mysqli_stmt_close($reviewStmt);
    }
}

foreach ($products as $productRow) {
    if ((int) $productRow['product_id'] === $selectedProductId) {
        $selectedProduct = $productRow;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusLink | Products</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="shortcut icon" href="img/logo.png" type="image/x-icon">
    <style>
        .products-shell {
            display: grid;
            gap: 16px;
        }

        .toolbar {
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: var(--surface-solid);
            padding: 12px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            flex: 1 1 420px;
        }

        .search-form input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 12px;
            font: inherit;
        }

        .search-form button,
        .link-button {
            border: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, #0f766e, #0ea5e9);
            color: #fff;
            padding: 10px 14px;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .search-form button:hover,
        .link-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(14, 116, 144, 0.22);
        }

        .product-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .status-pill {
            display: inline-block;
            font-size: 0.78rem;
            font-weight: 600;
            border-radius: 999px;
            padding: 3px 8px;
            background: #f3f4f6;
            color: #374151;
            margin-left: 8px;
            text-transform: capitalize;
        }

        .status-available {
            background: #dcfce7;
            color: #166534;
        }

        .status-reserved {
            background: #fef3c7;
            color: #92400e;
        }

        .status-sold {
            background: #fee2e2;
            color: #991b1b;
        }

        .rating-text {
            color: var(--muted);
            font-size: 0.86rem;
            margin-top: 6px;
        }

        .modal {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 100;
        }

        .modal.open {
            display: flex;
        }

        .modal-card {
            width: min(760px, 100%);
            max-height: 88vh;
            overflow-y: auto;
            border-radius: 16px;
            border: 1px solid var(--line);
            background: #fff;
            box-shadow: 0 24px 45px rgba(17, 24, 39, 0.28);
            padding: 16px;
            display: grid;
            gap: 14px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: flex-start;
        }

        .close-link {
            text-decoration: none;
            color: #374151;
            font-weight: 600;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 4px 10px;
            background: #fff;
        }

        .buy-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .buy-actions button[disabled] {
            background: #e5e7eb;
            color: #6b7280;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        .reviews-list {
            display: grid;
            gap: 10px;
        }

        .review-item {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px;
            background: #f9fafb;
        }

        .review-head {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            font-size: 0.86rem;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .review-form {
            display: grid;
            gap: 8px;
            border-top: 1px solid var(--line);
            padding-top: 12px;
        }

        .review-form textarea,
        .review-form select {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 12px;
            font: inherit;
            background: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="topbar">
            <div class="brand">
                <h1>CampusLink Products</h1>
                <p>Browse all listed products and check item reviews before buying.</p>
            </div>
            <div class="user-menu">
                <button class="user-trigger" type="button"><?php echo $username; ?></button>
                <div class="dropdown">
                    <a href="homepage.php">Back to Homepage</a>
                    <form method="post" action="logout.php" class="logout-form">
                        <button type="submit" class="dropdown-action">Log Out</button>
                    </form>
                </div>
            </div>
        </header>

        <div class="products-shell">
            <?php if (is_array($flash) && isset($flash['message'])): ?>
                <div class="auth-message <?php echo ($flash['type'] ?? '') === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <section class="toolbar">
                <form class="search-form" method="get" action="products.php">
                    <input
                        type="search"
                        name="q"
                        placeholder="Search products by title..."
                        value="<?php echo htmlspecialchars($searchQuery); ?>"
                    >
                    <button type="submit">Search</button>
                </form>
                <a class="link-button" href="products.php">Show All</a>
            </section>

            <section class="section">
                <h3>Listed Products</h3>
                <?php if ($searchQuery !== ''): ?>
                    <p class="meta">Search results for "<?php echo htmlspecialchars($searchQuery); ?>"</p>
                <?php endif; ?>

                <?php if (count($products) > 0): ?>
                    <div class="grid">
                        <?php foreach ($products as $product): ?>
                            <?php
                                $pid = (int) $product['product_id'];
                                $status = strtolower((string) ($product['status'] ?? 'available'));
                                $statusClass = in_array($status, ['available', 'reserved', 'sold'], true) ? $status : 'available';
                                $queryParams = [];
                                if ($searchQuery !== '') {
                                    $queryParams['q'] = $searchQuery;
                                }
                                $queryParams['product'] = $pid;
                                $cardHref = 'products.php?' . http_build_query($queryParams);
                            ?>
                            <a class="product-link" href="<?php echo htmlspecialchars($cardHref); ?>">
                                <article class="item">
                                    <h4>
                                        <?php echo htmlspecialchars($product['product_title']); ?>
                                        <span class="status-pill status-<?php echo htmlspecialchars($statusClass); ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </h4>
                                    <p class="meta">By <?php echo htmlspecialchars($product['owner_username']); ?></p>
                                    <p><?php echo htmlspecialchars($product['description'] ?? 'No description provided.'); ?></p>
                                    <p class="price">$<?php echo number_format((float) $product['price'], 2); ?></p>
                                    <p class="rating-text">
                                        Rating: <?php echo number_format((float) $product['avg_rating'], 1); ?>/5
                                        (<?php echo (int) $product['review_count']; ?> review<?php echo ((int) $product['review_count']) === 1 ? '' : 's'; ?>)
                                    </p>
                                </article>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty">No products found for your search.</p>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <?php if ($selectedProduct): ?>
        <?php
            $pid = (int) $selectedProduct['product_id'];
            $status = strtolower((string) ($selectedProduct['status'] ?? 'available'));
            $productReviews = $reviewsByProduct[$pid] ?? [];
            $isOwner = ((int) $selectedProduct['owner_id']) === $studentId;
            $canBuy = !$isOwner && $status === 'available';
            $closeParams = [];
            if ($searchQuery !== '') {
                $closeParams['q'] = $searchQuery;
            }
            $closeHref = 'products.php' . (count($closeParams) > 0 ? '?' . http_build_query($closeParams) : '');
        ?>
        <div id="productModal" class="modal open" role="dialog" aria-modal="true" aria-label="Product details">
            <div class="modal-card">
                <div class="modal-header">
                    <div>
                        <h3><?php echo htmlspecialchars($selectedProduct['product_title']); ?></h3>
                        <p class="meta">
                            By <?php echo htmlspecialchars($selectedProduct['owner_username']); ?> |
                            <span class="status-pill status-<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></span>
                        </p>
                    </div>
                    <a href="<?php echo htmlspecialchars($closeHref); ?>" class="close-link">Close</a>
                </div>

                <p><?php echo htmlspecialchars($selectedProduct['description'] ?? 'No description provided.'); ?></p>
                <p class="price">$<?php echo number_format((float) $selectedProduct['price'], 2); ?></p>

                <div class="buy-actions">
                    <form method="post" action="products.php">
                        <input type="hidden" name="action" value="buy_product">
                        <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <button type="submit" <?php echo $canBuy ? '' : 'disabled'; ?>>
                            <?php
                                if ($isOwner) {
                                    echo 'You own this item';
                                } elseif ($status !== 'available') {
                                    echo 'Not available to buy';
                                } else {
                                    echo 'Buy Product';
                                }
                            ?>
                        </button>
                    </form>
                </div>

                <section>
                    <h4>Previous Reviews</h4>
                    <?php if (count($productReviews) > 0): ?>
                        <div class="reviews-list">
                            <?php foreach ($productReviews as $review): ?>
                                <article class="review-item">
                                    <div class="review-head">
                                        <span><?php echo htmlspecialchars($review['reviewer_username']); ?> | <?php echo (int) $review['rating']; ?>/5</span>
                                        <span><?php echo htmlspecialchars(date('M j, Y', strtotime((string) $review['created_at']))); ?></span>
                                    </div>
                                    <p><?php echo nl2br(htmlspecialchars($review['comment'] ?? '')); ?></p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty">No reviews yet for this product.</p>
                    <?php endif; ?>
                </section>

                <form method="post" action="products.php" class="review-form">
                    <h4>Write a Review</h4>
                    <input type="hidden" name="action" value="submit_review">
                    <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>">

                    <label for="rating">Rating</label>
                    <select id="rating" name="rating" required>
                        <option value="">Select rating</option>
                        <option value="5">5 - Excellent</option>
                        <option value="4">4 - Very Good</option>
                        <option value="3">3 - Good</option>
                        <option value="2">2 - Fair</option>
                        <option value="1">1 - Poor</option>
                    </select>

                    <label for="comment">Your review</label>
                    <textarea id="comment" name="comment" rows="4" maxlength="1000" placeholder="Share your experience..." required></textarea>

                    <button type="submit">Submit Review</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
