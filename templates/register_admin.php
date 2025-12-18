<?php
session_start();
// Security: Only allow existing Admins to create new Admins
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$host = "localhost"; $dbname = "tup_system"; $dbuser = "root"; $dbpass = "";
$success = ""; $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $username = $_POST['username'];
        $full_name = $_POST['full_name'];
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([$username, $hashed_password, $full_name]);
        $success = "Admin account created successfully!";
    } catch (PDOException $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
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
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="logo tup .svg" alt="TUP Logo" class="admin-logo">
                <h3>TUP ADMIN</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-link"><i class="fa-solid fa-gauge"></i> Dashboard</a>
                <a href="register_admin.php" class="nav-link active"><i class="fa-solid fa-user-plus"></i> New Admin</a>
                <div class="nav-divider"></div>
                <a href="logout.php" class="nav-link logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </nav>
        </aside>

        <main class="content-area">
            <header class="top-header">
                <h2>Administrative Management</h2>
            </header>

            <section class="form-section">
                <div class="form-card">
                    <div class="form-header">
                        <h3>Create New Admin Account</h3>
                        <p>Assign administrative privileges to a new user.</p>
                    </div>

                    <?php if($success): ?>
                        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= $success ?></div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
                    <?php endif; ?>

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
                            <label><i class="fa-solid fa-key"></i> Temporary Password</label>
                            <input type="password" name="password" id="passInput" placeholder="Minimum 8 characters" required>
                            <small id="passStrength"></small>
                        </div>
                        <button type="submit" class="btn-submit">Register Administrator</button>
                    </form>
                </div>
            </section>
        </main>
    </div>

    <script src="../static/admin.js"></script>
</body>
</html>