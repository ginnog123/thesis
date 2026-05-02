<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'tup_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get total applications and group by course (course_1 primary preference)
$courseQuery = "SELECT course_1 as course, COUNT(*) as count FROM admission_applications WHERE course_1 IS NOT NULL AND course_1 != '' GROUP BY course_1 ORDER BY count DESC LIMIT 10";
$courseResult = $conn->query($courseQuery);
$courses = [];
$courseCounts = [];
if ($courseResult->num_rows > 0) {
    while ($row = $courseResult->fetch_assoc()) {
        $courses[] = $row['course'] ?: 'Not Specified';
        $courseCounts[] = $row['count'];
    }
}

// Get gender distribution
$genderQuery = "SELECT gender, COUNT(*) as count FROM admission_applications WHERE gender IS NOT NULL GROUP BY gender";
$genderResult = $conn->query($genderQuery);
$genders = [];
$genderCounts = [];
if ($genderResult->num_rows > 0) {
    while ($row = $genderResult->fetch_assoc()) {
        $genders[] = ucfirst($row['gender']);
        $genderCounts[] = $row['count'];
    }
}

// Get total applicants
$totalQuery = "SELECT COUNT(*) as total FROM admission_applications";
$totalResult = $conn->query($totalQuery);
$totalApplicants = $totalResult->fetch_assoc()['total'];

// Get recent activity (last 10 applications)
$activityQuery = "SELECT * FROM admission_applications ORDER BY applied_at DESC LIMIT 10";
$activityResult = $conn->query($activityQuery);
$recentActivities = [];
if ($activityResult->num_rows > 0) {
    while ($row = $activityResult->fetch_assoc()) {
        $recentActivities[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics | TUP Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../static/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    

    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo tup .svg" alt="TUP Logo" class="admin-logo">
            <h3>TUP ADMIN</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php" class="nav-link">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>
            <a href="analytics.php" class="nav-link active">
                <i class="fa-solid fa-chart-pie"></i> Analytics
            </a>
            <a href="register_admin.php" class="nav-link">
                <i class="fa-solid fa-user-shield"></i> New Admin
            </a>
            <a href="eligibility_filters.php" class="nav-link" onclick="showFiltersSection()">
                <i class="fa-solid fa-filter"></i> Eligibility Filters
            </a>
            
            <a href="logout.php" class="nav-link logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
            </a>
        </nav>
    </aside>

    <main class="content-area">
        <header class="top-header">
            <div class="page-title">
                <h2>Live Analytics</h2>
                <p>Real-time admission monitoring</p>
            </div>
            <div style="background: white; padding: 10px 20px; border-radius: 20px; display: flex; align-items: center; gap: 10px; box-shadow: var(--shadow);">
                <span style="height: 10px; width: 10px; background: #05CD99; border-radius: 50%; display: inline-block;"></span>
                <span style="font-weight: 600; font-size: 14px; color: black;">Live System Status</span>
            </div>
        </header>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <div class="stat-card">
                <div style="font-size: 32px; font-weight: 700; color: #c94c4c;"><?php echo $totalApplicants; ?></div>
                <div style="font-size: 14px; color: #666; margin-top: 5px;">Total Applicants</div>
            </div>
            <div class="stat-card">
                <div style="font-size: 32px; font-weight: 700; color: #4318FF;"><?php echo count($courses); ?></div>
                <div style="font-size: 14px; color: #666; margin-top: 5px;">Courses Selected</div>
            </div>
            <div class="stat-card">
                <div style="font-size: 32px; font-weight: 700; color: #05CD99;"><?php echo count($genders); ?></div>
                <div style="font-size: 14px; color: #666; margin-top: 5px;">Gender Categories</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            
            <div class="chart-container">
                <h3 style="margin: 0 0 20px 0; font-size: 18px;">Applications by Course</h3>
                <canvas id="admissionChart"></canvas>
            </div>

            <div class="chart-container">
                <h3 style="margin: 0 0 20px 0; font-size: 18px;">Gender Distribution</h3>
                <canvas id="courseChart"></canvas>
            </div>
        </div>

        <div class="table-section">
            <h3 style="margin: 0 0 20px 0; font-size: 18px;">Recent Application Activity</h3>
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Applicant Name</th>
                        <th>Course Applied</th>
                        <th>Gender</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="activityLog">
                    <?php 
                    if (!empty($recentActivities)) {
                        foreach ($recentActivities as $activity) {
                            $timestamp = isset($activity['applied_at']) ? date('h:i A', strtotime($activity['applied_at'])) : 'N/A';
                            $name = $activity['first_name'] . ' ' . $activity['last_name'];
                            $course = $activity['course_1'] ?: 'Not Specified';
                            $gender = $activity['gender'] ? ucfirst($activity['gender']) : 'N/A';
                            $status = $activity['status'] ?: 'Pending';
                            echo "<tr>";
                            echo "<td>" . $timestamp . "</td>";
                            echo "<td>" . htmlspecialchars($name) . "</td>";
                            echo "<td>" . htmlspecialchars($course) . "</td>";
                            echo "<td>" . htmlspecialchars($gender) . "</td>";
                            echo "<td><span class='badge badge-pending'>" . htmlspecialchars($status) . "</span></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align: center; color: #999;'>No recent applications</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </main>

    <script>
        // --- 1. BAR CHART (Applications by Course) ---
        const ctx = document.getElementById('admissionChart').getContext('2d');
        const admissionChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($courses); ?>,
                datasets: [{
                    label: 'Number of Applicants',
                    data: <?php echo json_encode($courseCounts); ?>,
                    backgroundColor: '#c94c4c',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                },
                plugins: {
                    legend: { display: true }
                }
            }
        });

        // --- 2. DOUGHNUT CHART (Gender Distribution) ---
        const ctx2 = document.getElementById('courseChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($genders); ?>,
                datasets: [{
                    data: <?php echo json_encode($genderCounts); ?>,
                    backgroundColor: [
                        '#4318FF',
                        '#6AD2FF',
                        '#EFF4FB',
                        '#c94c4c'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
</body>
</html>

