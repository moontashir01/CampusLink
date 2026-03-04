<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['username'])) {
    // Fallback when session is not yet wired from login.
    $_SESSION['username'] = 'Guest User';
}

$username = htmlspecialchars($_SESSION['username']);

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
    <style>
        :root {
            --bg: #f5f6f8;
            --surface: rgba(255, 255, 255, 0.76);
            --surface-solid: #ffffff;
            --text: #111827;
            --muted: #6b7280;
            --line: #e5e7eb;
            --accent: #0f766e;
            --accent-soft: #ccfbf1;
            --shadow: 0 20px 45px rgba(17, 24, 39, 0.08);
            --radius-lg: 20px;
            --radius-md: 14px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            color: var(--text);
            background: radial-gradient(circle at 5% 10%, #e0f2fe 0%, transparent 35%),
                        radial-gradient(circle at 95% 5%, #ccfbf1 0%, transparent 40%),
                        var(--bg);
            min-height: 100vh;
            line-height: 1.5;
        }

        .container {
            width: min(1120px, 92%);
            margin: 28px auto 50px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding: 16px 18px;
            border-radius: var(--radius-lg);
            background: var(--surface);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: var(--shadow);
        }

        .brand h1 {
            font-size: 1.45rem;
            letter-spacing: 0.4px;
        }

        .brand p {
            color: var(--muted);
            font-size: 0.92rem;
        }

        .user-menu {
            position: relative;
            display: inline-block;
        }

        .user-trigger {
            border: 1px solid var(--line);
            background: var(--surface-solid);
            color: var(--text);
            border-radius: 999px;
            padding: 10px 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .user-trigger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(17, 24, 39, 0.08);
        }

        .dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 165px;
            background: var(--surface-solid);
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 16px 30px rgba(17, 24, 39, 0.12);
            opacity: 0;
            visibility: hidden;
            transform: translateY(8px);
            transition: all 0.2s ease;
            z-index: 30;
        }

        .user-menu:hover .dropdown,
        .user-menu:focus-within .dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown a {
            display: block;
            padding: 10px 12px;
            text-decoration: none;
            color: #1f2937;
            font-size: 0.92rem;
            transition: background 0.2s ease;
        }

        .dropdown a:hover {
            background: #f3f4f6;
        }

        .hero {
            margin-bottom: 26px;
        }

        .hero h2 {
            font-size: clamp(1.5rem, 3vw, 2.3rem);
            margin-bottom: 6px;
        }

        .hero p {
            color: var(--muted);
            max-width: 640px;
        }

        .quick-tabs {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 30px;
        }

        .tab-card {
            text-decoration: none;
            color: var(--text);
            background: var(--surface-solid);
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            padding: 18px;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(17, 24, 39, 0.05);
        }

        .tab-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(110deg, transparent 35%, rgba(15, 118, 110, 0.12), transparent 65%);
            transform: translateX(-130%);
            transition: transform 0.5s ease;
        }

        .tab-card:hover::before {
            transform: translateX(130%);
        }

        .tab-card:hover {
            transform: translateY(-6px);
            border-color: #a7f3d0;
            box-shadow: 0 16px 28px rgba(15, 118, 110, 0.18);
        }

        .tab-card h3 {
            margin-bottom: 4px;
            font-size: 1.08rem;
        }

        .tab-card p {
            color: var(--muted);
            font-size: 0.9rem;
        }

        .section {
            margin-bottom: 28px;
        }

        .section h3 {
            font-size: 1.2rem;
            margin-bottom: 12px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .item {
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            padding: 14px;
            background: var(--surface-solid);
            box-shadow: 0 4px 14px rgba(17, 24, 39, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(17, 24, 39, 0.09);
        }

        .item h4 {
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .meta {
            color: var(--muted);
            font-size: 0.86rem;
            margin-bottom: 8px;
        }

        .price {
            font-weight: 600;
            color: var(--accent);
            font-size: 0.95rem;
        }

        .empty {
            color: var(--muted);
            font-size: 0.94rem;
            padding: 10px 0;
        }

        @media (max-width: 980px) {
            .quick-tabs,
            .grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .quick-tabs,
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                    <a href="logout.php">Log Out</a>
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
