<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$host = "localhost"; $dbname = "tup_system"; $dbuser = "root"; $dbpass = "";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch Stats
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM admission_applications")->fetchColumn(),
        'pending' => $pdo->query("SELECT COUNT(*) FROM admission_applications WHERE status='Pending'")->fetchColumn(),
        'enrolled' => $pdo->query("SELECT COUNT(*) FROM admission_applications WHERE status='Enrolled'")->fetchColumn()
    ];

    $stmt = $pdo->query("SELECT * FROM admission_applications ORDER BY applied_at DESC");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel | TUP Admission</title>
    <link rel="stylesheet" href="../static/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../logo tup .svg" alt="TUP Logo" class="admin-logo">
                <h3>TUP ADMIN</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-link active"><i class="fa-solid fa-gauge"></i> Dashboard</a>
                <a href="register_admin.php" class="nav-link"><i class="fa-solid fa-user-plus"></i> New Admin</a>
                <div class="nav-divider"></div>
                <a href="logout.php" class="nav-link logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </nav>
        </aside>

        <main class="content-area">
            <header class="top-header">
                <h2>Admission Control</h2>
                <div class="search-wrapper">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="adminSearch" placeholder="Search applicants...">
                </div>
            </header>

            <section class="stats-container">
                <div class="stat-card">
                    <span class="stat-label">Total Applications</span>
                    <span class="stat-value"><?= $stats['total'] ?></span>
                </div>
                <div class="stat-card pending">
                    <span class="stat-label">Pending Review</span>
                    <span class="stat-value"><?= $stats['pending'] ?></span>
                </div>
                <div class="stat-card enrolled">
                    <span class="stat-label">Enrolled Students</span>
                    <span class="stat-value"><?= $stats['enrolled'] ?></span>
                </div>
            </section>

            <section class="table-section">
                <div class="sub-nav">
                    <button class="tab-btn active" onclick="filterTable('all')">All</button>
                    <button class="tab-btn" onclick="filterTable('Pending')">Pending</button>
                    <button class="tab-btn" onclick="filterTable('Exam Status')">Exam Status</button>
                    <button class="tab-btn" onclick="filterTable('Exam Schedule')">Ongoing Exam</button>
                    <button class="tab-btn" onclick="filterTable('Document Checking')">Documents</button>
                    <button class="tab-btn" onclick="filterTable('Enrolled')">Enrolled</button>
                </div>

                <div class="table-card">
                    <table>
                        <thead>
                            <tr>
                                <th>App ID</th>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th>Current Status</th>
                                <th style="min-width: 250px;">Action Required</th>
                            </tr>
                        </thead>
                        <tbody id="appTableBody">
                            <?php foreach ($applications as $app): ?>
                            <tr data-status="<?= $app['status'] ?>">
                                <td><strong><?= htmlspecialchars($app['application_id']) ?></strong></td>
                                <td><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></td>
                                <td><?= htmlspecialchars($app['course_1']) ?></td>
                                <td>
                                    <span class="badge badge-<?= strtolower(str_replace(' ', '-', $app['status'])) ?>">
                                        <?= htmlspecialchars($app['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($app['status'] === 'Pending'): ?>
                                        <form action="update_status.php" method="POST" style="display:flex; gap:5px;">
                                            <input type="hidden" name="app_id" value="<?= $app['application_id'] ?>">
                                            <button type="submit" name="action" value="accept_student" class="btn-action accept">Accept</button>
                                            <button type="submit" name="action" value="reject" class="btn-action reject">Reject</button>
                                        </form>

                                    <?php elseif ($app['status'] === 'Exam Status'): ?>
                                        <form action="update_status.php" method="POST" class="exam-form">
                                            <input type="hidden" name="app_id" value="<?= $app['application_id'] ?>">
                                            <input type="hidden" name="action" value="set_exam">
                                            <div style="display:flex; gap:5px; margin-bottom:5px;">
                                                <input type="date" name="exam_date" required style="width:110px;">
                                                <input type="time" name="exam_time" required>
                                            </div>
                                            <div style="display:flex; gap:5px;">
                                                <input type="text" name="exam_venue" placeholder="Room/Venue" required>
                                                <button type="submit" class="btn-action schedule">Set Schedule</button>
                                            </div>
                                        </form>

                                    <?php elseif ($app['status'] === 'Exam Schedule'): ?>
                                        <div style="font-size:0.8rem; color:#666; margin-bottom:5px;">
                                            Status: <strong>Ongoing Exam</strong><br>
                                            Scheduled: <?= $app['exam_date'] ?>
                                        </div>
                                        <form action="update_status.php" method="POST" style="display:flex; gap:5px;">
                                            <input type="hidden" name="app_id" value="<?= $app['application_id'] ?>">
                                            <button type="submit" name="action" value="exam_passed" class="btn-action accept">Passed</button>
                                            <button type="submit" name="action" value="exam_failed" class="btn-action reject">Failed</button>
                                        </form>

                                    <?php elseif ($app['status'] === 'Document Checking'): ?>
                                        <form action="update_status.php" method="POST">
                                            <input type="hidden" name="app_id" value="<?= $app['application_id'] ?>">
                                            <button type="submit" name="action" value="enroll" class="btn-action enroll">Enroll Student</button>
                                        </form>

                                    <?php else: ?>
                                        <span style="color:#aaa;">No actions available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
    
    <script src="../static/admin.js"></script>
    <script>
        function filterTable(status) {
            const tabs = document.querySelectorAll('.tab-btn');
            tabs.forEach(tab => {
                tab.classList.remove('active');
                if (tab.innerText.includes(status) || (status === 'all' && tab.innerText.includes('All'))) {
                    tab.classList.add('active');
                }
            });

            const rows = document.querySelectorAll('#appTableBody tr');
            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                if (status === 'all' || rowStatus === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>