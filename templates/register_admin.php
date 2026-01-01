<?php
session_start();
$host = "localhost"; $dbname = "tup_system"; $dbuser = "root"; $dbpass = "";
$success = ""; $error = "";
$is_first_run = false;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. SMART SECURITY CHECK
    // Count how many admins exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $admin_count = $stmt->fetchColumn();

    if ($admin_count == 0) {
        $is_first_run = true; // Allow access without login
    } else {
        // If admins exist, strict security applies
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: login.php");
            exit;
        }
    }
    
    

    // 2. Handle Registration
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'];
        $full_name = $_POST['full_name'];
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([$username, $hashed_password, $full_name]);
        
        if ($is_first_run) {
            $success = "System Setup Complete! <a href='login.php' style='color:#8b0000; font-weight:bold;'>Go to Login</a>";
        } else {
            $success = "New admin account created successfully!";
        }
    }

} catch (PDOException $e) {
    $error = "System Error: " . $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Admin | TUP Admission</title>
    <link rel="stylesheet" href="../static/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php if (!$is_first_run): ?>
        <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo tup .svg" alt="TUP Logo" class="admin-logo">
            <h3>TUP ADMIN</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php" class="nav-link">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>
            <a href="analytics.php" class="nav-link">
                <i class="fa-solid fa-chart-pie"></i> Analytics
            </a>
            <a href="register_admin.php" class="nav-link active">
                <i class="fa-solid fa-user-shield"></i> New Admin
            </a>
            <a href="logout.php" class="nav-link logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
            </a>
        </nav>
    </aside>
        <?php endif; ?>

        <main class="content-area" style="<?= $is_first_run ? 'margin:0 auto; max-width:600px;' : '' ?>">
            <header class="top-header">
                <h2><?= $is_first_run ? 'System Initialization' : 'Administrative Management' ?></h2>
            </header>

            <section class="form-section">
                <div class="form-card">
                    <div class="form-header">
                        <h3><?= $is_first_run ? 'Setup First Admin' : 'Create New Admin Account' ?></h3>
                        <p><?= $is_first_run ? 'Welcome! Please create the main administrator account to begin.' : 'Assign administrative privileges to a new user.' ?></p>
                    </div>

                    <?php if($success): ?>
                        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= $success ?></div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
                    <?php endif; ?>

                    <?php if (!($is_first_run && $success)): ?>
                    <form method="POST" class="styled-form" id="adminForm">
                        <div class="input-group">
                            <label><i class="fa-solid fa-user-tag"></i> Username</label>
                            <input type="text" name="username" placeholder="Enter admin username" required>
                        </div>
                        <div class="input-group">
                            <label><i class="fa-solid fa-id-card"></i> Full Name</label>
                            <input type="text" name="full_name" placeholder="Enter complete name" required>
                        </div>
                        <div class="input-group">
                            <label><i class="fa-solid fa-key"></i> Password</label>
                            <input type="password" name="password" id="passInput" placeholder="Minimum 8 characters" required>
                            <small id="passStrength"></small>
                        </div>
                        <button type="submit" class="btn-submit"><?= $is_first_run ? 'Complete Setup' : 'Register Administrator' ?></button>
                    </form>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
    <script src="../static/admin.js"></script>
</body>
</html>