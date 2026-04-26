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
    <title>Admin Dashboard | TUP</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../static/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo tup .svg" alt="TUP Logo" class="admin-logo">
            <h3>TUP ADMIN</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php" class="nav-link active">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>
            <a href="analytics.php" class="nav-link">
                <i class="fa-solid fa-chart-pie"></i> Analytics
            </a>
            <a href="register_admin.php" class="nav-link">
                <i class="fa-solid fa-user-shield"></i> New Admin
            </a>
            
            <a href="logout.php" class="nav-link logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
            </a>
        </nav>
    </aside>
    

    <main class="content-area">
        <header class="top-header">
            <div class="page-title">
                <h2>Dashboard</h2>
                <p>Welcome back, Admin</p>
            </div>
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="adminSearch" placeholder="Search by name or ID...">
            </div>
        </header>

        <section class="stats-container">
            <div class="stat-card">
                <div class="stat-icon total"><i class="fa-solid fa-users"></i></div>
                <div class="stat-info">
                    <span class="label">Total Applicants</span>
                    <span class="value"><?= $stats['total'] ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pending"><i class="fa-solid fa-clock"></i></div>
                <div class="stat-info">
                    <span class="label">Pending Review</span>
                    <span class="value"><?= $stats['pending'] ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon enrolled"><i class="fa-solid fa-graduation-cap"></i></div>
                <div class="stat-info">
                    <span class="label">Enrolled Students</span>
                    <span class="value"><?= $stats['enrolled'] ?></span>
                </div>
            </div>
        </section>

        <section class="table-section">
            <div class="sub-nav">
                <button class="tab-btn active" onclick="filterTable('all')">All Applicants</button>
                <button class="tab-btn" onclick="filterTable('Pending')">Pending</button>
                <button class="tab-btn" onclick="filterTable('Exam Status')">For Exam</button>
                <button class="tab-btn" onclick="filterTable('Exam Schedule')">Ongoing Exam</button>
                <button class="tab-btn" onclick="filterTable('Document Checking')">Documents</button>
                <button class="tab-btn" onclick="filterTable('Enrolled')">Enrolled</button>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>App ID</th>
                            <th>Student Name</th>
                            <th>Priority Course</th>
                            <th>Status</th>
                            <th>Actions</th>
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
                                    <button type="button" class="btn-action view" title="View info" onclick="openPendingInfo(this)"
                                        data-app-id="<?= htmlspecialchars($app['application_id'] ?: '-') ?>"
                                        data-first-name="<?= htmlspecialchars($app['first_name'] ?: '-') ?>"
                                        data-last-name="<?= htmlspecialchars($app['last_name'] ?: '-') ?>"
                                        data-date-of-birth="<?= htmlspecialchars($app['date_of_birth'] ?: '-') ?>"
                                        data-gender="<?= htmlspecialchars($app['gender'] ?: '-') ?>"
                                        data-gwa="<?= htmlspecialchars($app['final_gwa'] ?: '-') ?>"
                                        data-strand="<?= htmlspecialchars($app['strand'] ?: '-') ?>"
                                        data-email="<?= htmlspecialchars($app['email'] ?: '-') ?>"
                                        data-phone-number="<?= htmlspecialchars($app['phone_number'] ?: '-') ?>"
                                        data-address="<?= htmlspecialchars($app['address'] ?: '-') ?>"
                                        data-course1="<?= htmlspecialchars($app['course_1'] ?: '-') ?>"
                                        data-course2="<?= htmlspecialchars($app['course_2'] ?: '-') ?>"
                                        data-course3="<?= htmlspecialchars($app['course_3'] ?: '-') ?>"
                                        data-previous-school="<?= htmlspecialchars($app['previous_school'] ?: '-') ?>"
                                        data-status="<?= htmlspecialchars($app['status'] ?: '-') ?>"
                                        data-applied-at="<?= htmlspecialchars($app['applied_at'] ?: '-') ?>">
                                        <i class="fa-solid fa-eye"></i> View Info
                                    </button>

                                <?php elseif ($app['status'] === 'Exam Status'): ?>
                                    <form action="update_status.php" method="POST" class="exam-form" style="display:flex; gap:5px; align-items:center;">
                                        <input type="hidden" name="app_id" value="<?= $app['application_id'] ?>">
                                        <input type="hidden" name="action" value="set_exam">
                                        <input type="date" name="exam_date" required>
                                        <input type="time" name="exam_time" required>
                                        <input type="text" name="exam_venue" placeholder="Venue" size="8" required>
                                        <button type="submit" class="btn-action schedule">Set</button>
                                    </form>

                                <?php elseif ($app['status'] === 'Exam Schedule'): ?>
                                    <form action="update_status.php" method="POST" style="display:flex; gap:5px;">
                                        <input type="hidden" name="app_id" value="<?= $app['application_id'] ?>">
                                        <button type="submit" name="action" value="exam_passed" class="btn-action accept">Passed</button>
                                        <button type="submit" name="action" value="exam_failed" class="btn-action reject">Failed</button>
                                    </form>

                                <?php elseif ($app['status'] === 'Document Checking'): ?>
                                    <button onclick="openChecklist('<?= $app['application_id'] ?>', '<?= htmlspecialchars($app['first_name'].' '.$app['last_name']) ?>')" class="btn-action verify">
                                        <i class="fa-solid fa-magnifying-glass"></i> Check Docs
                                    </button>

                                <?php elseif ($app['status'] === 'Enrolled'): ?>
                                    <span style="color:var(--success-green); font-size:12px; font-weight:bold;">Completed</span>
                                <?php else: ?>
                                    <span style="color:#aaa;">--</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div id="checklistModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin:0; color:white;">Verify Documents</h3>
                <span class="close-modal" onclick="closeChecklist()" style="color:white;">&times;</span>
            </div>
            <div class="modal-body">
                <p style="margin-bottom:15px; color:var(--text-muted);">
                    Applicant: <strong id="modalStudentName" style="color:var(--text-main);"></strong><br>
                    <small>ID: <span id="modalAppId" style="color:var(--primary-accent);"></span></small>
                </p>

                <form action="update_status.php" method="POST" id="checklistForm">
                    <input type="hidden" name="app_id" id="formAppId">
                    <input type="hidden" name="action" value="verify_docs">
                    <input type="hidden" name="missing_list" id="missingListInput">
                    
                    <div class="checklist-box">
                        <label class="check-item" style="display:flex; gap:10px;">
                            <input type="checkbox" name="docs[]" value="Form 138" class="doc-check" onchange="updateModalBtn()">
                            <span>Form 138 (Report Card)</span>
                        </label>
                        <label class="check-item" style="display:flex; gap:10px;">
                            <input type="checkbox" name="docs[]" value="Good Moral" class="doc-check" onchange="updateModalBtn()">
                            <span>Certificate of Good Moral</span>
                        </label>
                        <label class="check-item" style="display:flex; gap:10px;">
                            <input type="checkbox" name="docs[]" value="Birth Certificate" class="doc-check" onchange="updateModalBtn()">
                            <span>PSA Birth Certificate</span>
                        </label>
                        <label class="check-item" style="display:flex; gap:10px;">
                            <input type="checkbox" name="docs[]" value="ID Picture" class="doc-check" onchange="updateModalBtn()">
                            <span>2x2 ID Picture</span>
                        </label>
                    </div>

                    <button type="submit" id="enrollBtn" class="btn-submit full-enroll" style="width:100%; border:none; color:white; font-weight:bold; cursor:pointer;">Enroll Student</button>
                </form>
            </div>
        </div>
    </div>

    <div id="pendingInfoModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin:0; color:white;">Applicant Info</h3>
                <span class="close-modal" onclick="closePendingInfo()" style="color:white;">&times;</span>
            </div>
            <div class="modal-body">
                <p style="margin-bottom:12px; color:var(--text-muted);">
                    Applicant: <strong id="infoStudentName" style="color:var(--text-main);"></strong><br>
                    ID: <span id="infoAppId" style="color:var(--primary-accent);"></span><br>
                    Status: <span id="infoStatus"></span>
                </p>

                <div class="info-grid">
                    <div><strong>First Name:</strong> <span id="infoFirstName"></span></div>
                    <div><strong>Last Name:</strong> <span id="infoLastName"></span></div>
                    <div><strong>Date of Birth:</strong> <span id="infoDateOfBirth"></span></div>
                    <div><strong>Gender:</strong> <span id="infoGender"></span></div>
                    <div><strong>GWA:</strong> <span id="infoGwa"></span></div>
                    <div><strong>Strand:</strong> <span id="infoStrand"></span></div>
                    <div><strong>Course 1:</strong> <span id="infoCourse1"></span></div>
                    <div><strong>Course 2:</strong> <span id="infoCourse2"></span></div>
                    <div><strong>Course 3:</strong> <span id="infoCourse3"></span></div>
                    <div><strong>Email:</strong> <span id="infoEmail"></span></div>
                    <div><strong>Phone:</strong> <span id="infoPhoneNumber"></span></div>
                    <div><strong>Address:</strong> <span id="infoAddress"></span></div>
                    <div><strong>Previous School:</strong> <span id="infoPreviousSchool"></span></div>
                    <div><strong>Applied At:</strong> <span id="infoAppliedAt"></span></div>
                </div>

                <form action="update_status.php" method="POST" id="pendingActionForm">
                    <input type="hidden" name="app_id" id="pendingModalAppId">
                    <button type="submit" name="action" value="accept_student" class="btn-action accept">Accept</button>
                    <button type="submit" name="action" value="reject" class="btn-action reject">Reject</button>
                </form>
            </div>
        </div>
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

        function openPendingInfo(button) {
            const modal = document.getElementById('pendingInfoModal');
            const data = button.dataset;
            document.getElementById('infoStudentName').innerText = data.firstName + ' ' + data.lastName;
            document.getElementById('infoAppId').innerText = data.appId;
            document.getElementById('infoStatus').innerText = data.status;
            document.getElementById('infoFirstName').innerText = data.firstName;
            document.getElementById('infoLastName').innerText = data.lastName;
            document.getElementById('infoDateOfBirth').innerText = data.dateOfBirth;
            document.getElementById('infoGender').innerText = data.gender;
            document.getElementById('infoGwa').innerText = data.gwa;
            document.getElementById('infoStrand').innerText = data.strand;
            document.getElementById('infoCourse1').innerText = data.course1;
            document.getElementById('infoCourse2').innerText = data.course2;
            document.getElementById('infoCourse3').innerText = data.course3;
            console.log('infoCourse1 innerText:', document.getElementById('infoCourse1').innerText);
            document.getElementById('infoEmail').innerText = data.email;
            document.getElementById('infoPhoneNumber').innerText = data.phoneNumber;
            document.getElementById('infoAddress').innerText = data.address;
            document.getElementById('infoPreviousSchool').innerText = data.previousSchool;
            document.getElementById('infoAppliedAt').innerText = data.appliedAt;
            document.getElementById('pendingModalAppId').value = data.appId;
            modal.style.display = 'flex';
        }

        function closePendingInfo() {
            document.getElementById('pendingInfoModal').style.display = 'none';
        }
    </script>
</body>
</html>

