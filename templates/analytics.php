<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

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
                <span style="font-weight: 600; font-size: 14px; color: var(--text-main);">Live System Status</span>
            </div>
        </header>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            
            <div class="chart-container">
                <h3 style="margin: 0 0 20px 0; font-size: 18px;">Application Influx (Real-time)</h3>
                <canvas id="admissionChart"></canvas>
            </div>

            <div class="chart-container">
                <h3 style="margin: 0 0 20px 0; font-size: 18px;">Course Preferences</h3>
                <canvas id="courseChart"></canvas>
            </div>
        </div>

        <div class="table-section">
            <h3 style="margin: 0 0 20px 0; font-size: 18px;">Recent System Activity</h3>
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Activity</th>
                        <th>User</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="activityLog">
                    <tr>
                        <td>10:42 AM</td>
                        <td>New Application Received</td>
                        <td>Student ID 2024-001</td>
                        <td><span class="badge badge-pending">New</span></td>
                    </tr>
                    <tr>
                        <td>10:40 AM</td>
                        <td>Exam Scheduled</td>
                        <td>Admin Staff</td>
                        <td><span class="badge badge-exam-status">Updated</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

    </main>

    <script>
        // --- 1. LINE CHART (Real-time Updates) ---
        const ctx = document.getElementById('admissionChart').getContext('2d');
        const admissionChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['10:00', '10:05', '10:10', '10:15', '10:20', '10:25'],
                datasets: [{
                    label: 'New Applicants',
                    data: [12, 19, 3, 5, 2, 3],
                    borderColor: '#c94c4c',
                    backgroundColor: 'rgba(201, 76, 76, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Simulate Real-time Data
        setInterval(() => {
            const now = new Date();
            const timeLabel = now.getHours() + ':' + now.getMinutes() + ':' + now.getSeconds();
            const newData = Math.floor(Math.random() * 10) + 1;

            // Remove oldest
            if(admissionChart.data.labels.length > 10) {
                admissionChart.data.labels.shift();
                admissionChart.data.datasets[0].data.shift();
            }

            // Add newest
            admissionChart.data.labels.push(timeLabel);
            admissionChart.data.datasets[0].data.push(newData);
            admissionChart.update();
        }, 2000); // Update every 2 seconds

        // --- 2. DOUGHNUT CHART (Courses) ---
        const ctx2 = document.getElementById('courseChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Civil Eng', 'Electrical Eng', 'Mech Eng', 'Info Tech'],
                datasets: [{
                    data: [30, 20, 15, 35],
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