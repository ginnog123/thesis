<?php
session_start();

// --- FIX STARTS HERE ---
// 1. Initialize these variables at the very top so the HTML can use them without warnings
$is_logged_in = isset($_SESSION['user_id']); 
$role = $_SESSION['role'] ?? 'guest'; 
// --- FIX ENDS HERE ---

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $pdo = new PDO("mysql:host=localhost;dbname=tup_system", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Check password and role
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect Logic
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php"); 
            } else {
                header("Location: admission.php");
            }
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | TUP Portal</title>
    <link rel="stylesheet" href="../static/style.css">
    <link rel="stylesheet" href="../static/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-page">
    
    <header id="header" class="header">
        <div class="logo-container">
            <img src="../logo tup .svg" alt="TUP Logo" class="logo2" />
        </div>

        <nav class="nav-menu">
            <a href="home.php" class="nav-item">HOME</a>

            <?php if($is_logged_in && $role === 'student'): ?>
                <a href="admission.php" class="nav-item">MY ADMISSION</a>
            <?php elseif($is_logged_in && $role === 'admin'): ?>
                <a href="admin_dashboard.php" class="nav-item">ADMIN PANEL</a>
            <?php else: ?>
                <a href="admission.php" class="nav-item">ADMISSIONS</a>
            <?php endif; ?>

            <a href="registrar.php" class="nav-item">REGISTRAR</a>
            <a href="program.php" class="nav-item">PROGRAMS</a>

            <div class="sidebar-footer">
                <?php if(!$is_logged_in): ?>
                    <a href="login.php" class="login-btn">
                        <i class="fa-solid fa-user"></i> LOGIN
                    </a>
                <?php else: ?>
                    <div class="user-info" style="text-align:center; padding-bottom:10px; font-size:12px; color:#8b0000;">
                        User: <strong><?= htmlspecialchars($_SESSION['user_id']) ?></strong>
                    </div>
                    <a href="logout.php" class="login-btn logout">
                        <i class="fa-solid fa-sign-out"></i> LOGOUT
                    </a>
                <?php endif; ?>
            </div>
        </nav>

        <div class="nav-footer" onclick="toggleMenu()">
            <i class="fa-solid fa-arrow-left"></i>
        </div>
    </header>

    <main class="main-content">
        <div class="login-box">
            <h2>Admission Login</h2>
            
            <?php if($error): ?>
                <div class="error-msg" style="color: #721c24; background: #f8d7da; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="username" placeholder="Username / Application ID" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="submit-login">Login</button>
            </form>
        </div>
    </main>

    <script src="../static/header.js"></script>
</body>
</html>