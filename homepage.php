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

$username = htmlspecialchars($_SESSION['username'] ?? 'Student');

$services = [];
$products = [];
$jobs = [];

$serviceSql = "
    SELECT s.service_title, s.description, s.price, st.username AS owner_username
    FROM services s
    INNER JOIN students st ON s.student_id = st.student_id
    ORDER BY s.created_at DESC
    LIMIT 6
";

$productSql = "
    SELECT p.product_title, p.description, p.price, p.status, st.username AS owner_username
    FROM products p
    INNER JOIN students st ON p.owner_id = st.student_id
    ORDER BY p.created_at DESC
    LIMIT 6
";

$jobSql = "
    SELECT j.job_title, j.description, j.salary, c.name AS company_name
    FROM jobs j
    INNER JOIN companies c ON j.company_id = c.company_id
    ORDER BY j.created_at DESC
    LIMIT 6
";

$serviceResult = mysqli_query($con, $serviceSql);
if ($serviceResult) {
    while ($row = mysqli_fetch_assoc($serviceResult)) {
        $services[] = $row;
    }
}

$productResult = mysqli_query($con, $productSql);
if ($productResult) {
    while ($row = mysqli_fetch_assoc($productResult)) {
        $products[] = $row;
    }
}

$jobResult = mysqli_query($con, $jobSql);
if ($jobResult) {
    while ($row = mysqli_fetch_assoc($jobResult)) {
        $jobs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusLink | Homepage</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header class="topbar">
            <div class="brand">
                <h1>CampusLink</h1>
                <p>Service, product and job opportunities in one place.</p>
            </div>

            <div class="user-menu">
                <button class="user-trigger" type="button"><?php echo $username; ?></button>
                <div class="dropdown">
                    <a href="profile.php">My Profile</a>
                    <form method="post" action="logout.php" class="logout-form">
                        <button type="submit" class="dropdown-action">Log Out</button>
                    </form>
                </div>
            </div>
        </header>

        <section class="hero">
            <h2>Discover what campus offers today</h2>
            <p>Browse services, find quality second-hand products, and apply for opportunities posted by trusted companies.</p>
        </section>

        <nav class="quick-tabs">
            <a class="tab-card" href="#services">
                <h3>Find Services</h3>
                <p>Hire talented students for quick campus tasks.</p>
            </a>
            <a class="tab-card" href="#products">
                <h3>Listed Products</h3>
                <p>Buy and sell useful items within your community.</p>
            </a>
            <a class="tab-card" href="#jobs">
                <h3>Find Jobs</h3>
                <p>Explore internships and part-time openings.</p>
            </a>
        </nav>

        <section id="services" class="section">
            <h3>Find Services</h3>
            <?php if (count($services) > 0): ?>
                <div class="grid">
                    <?php foreach ($services as $service): ?>
                        <article class="item">
                            <h4><?php echo htmlspecialchars($service['service_title']); ?></h4>
                            <p class="meta">By <?php echo htmlspecialchars($service['owner_username']); ?></p>
                            <p><?php echo htmlspecialchars($service['description'] ?? 'No description provided.'); ?></p>
                            <p class="price">$<?php echo number_format((float)$service['price'], 2); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty">No services listed yet.</p>
            <?php endif; ?>
        </section>

        <section id="products" class="section">
            <h3>Listed Products</h3>
            <?php if (count($products) > 0): ?>
                <div class="grid">
                    <?php foreach ($products as $product): ?>
                        <article class="item">
                            <h4><?php echo htmlspecialchars($product['product_title']); ?></h4>
                            <p class="meta">By <?php echo htmlspecialchars($product['owner_username']); ?> | <?php echo htmlspecialchars(ucfirst($product['status'])); ?></p>
                            <p><?php echo htmlspecialchars($product['description'] ?? 'No description provided.'); ?></p>
                            <p class="price">$<?php echo number_format((float)$product['price'], 2); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty">No products listed yet.</p>
            <?php endif; ?>
        </section>

        <section id="jobs" class="section">
            <h3>Find Jobs</h3>
            <?php if (count($jobs) > 0): ?>
                <div class="grid">
                    <?php foreach ($jobs as $job): ?>
                        <article class="item">
                            <h4><?php echo htmlspecialchars($job['job_title']); ?></h4>
                            <p class="meta"><?php echo htmlspecialchars($job['company_name']); ?></p>
                            <p><?php echo htmlspecialchars($job['description'] ?? 'No description provided.'); ?></p>
                            <p class="price">$<?php echo number_format((float)$job['salary'], 2); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty">No jobs posted yet.</p>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>
