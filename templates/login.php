<?php
session_start();


$is_logged_in = isset($_SESSION['user_id']); 
$role = $_SESSION['role'] ?? 'guest'; 
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

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | TUP Portal</title>
    <link rel="stylesheet" href="../static/style.css">
    <link rel="stylesheet" href="../static/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    
    <header id="header" class="header">
      <div class="logo-container">
          <img src="logo tup .svg" alt="TUP Logo" class="logo2" />
      </div>

      <nav class="nav-menu">
        <div class="nav-section-label">Main Menu</div>
        <a href="home.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'home.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i> <span>HOME</span>
        </a>
        <a href="admission.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'admission.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-graduation-cap"></i> <span>ADMISSIONS</span>
        </a>
        <a href="registrar.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'registrar.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-file-signature"></i> <span>REGISTRAR</span>
        </a>
        <a href="program.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'program.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-book-open"></i> <span>PROGRAMS</span>
        </a>
        <div class="nav-section-label">University</div>
        <a href="#" class="nav-item"><i class="fa-solid fa-building-columns"></i> <span>DEPARTMENTS</span></a>
        <a href="#" class="nav-item"><i class="fa-solid fa-circle-info"></i> <span>ABOUT TUP</span></a>
      </nav>
      
      <div class="sidebar-footer">
            <?php if(!$is_logged_in): ?>
                <a href="login.php" class="login-btn"><i class="fa-solid fa-right-to-bracket"></i> LOGIN</a>
            <?php else: ?>
                <div class="user-info" style="text-align: center; color: #dcdcdc; font-size: 12px; margin-bottom: 10px;">
                    Logged in as: <strong style="color: white; display:block;"><?= htmlspecialchars($_SESSION['user_id']) ?></strong>
                </div>
                <a href="logout.php" class="login-btn logout"><i class="fa-solid fa-right-from-bracket"></i> LOGOUT</a>
            <?php endif; ?>
      </div>
      <div class="nav-footer" onclick="toggleMenu()"><span>Close Menu</span></div>
    </header>

    <main class="main-content">
        <button class="menu-toggle" onclick="toggleMenu()"><i class="fa-solid fa-bars"></i></button>
        <div class="top-bar"><p id="date-time">Loading...</p></div>

        <div class="login-box">
            <h2>Welcome Back</h2>
            <p class="sub-text">Sign in to access your portal</p>
            
            <?php if($error): ?>
                <div class="error-msg">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <label>Username / App ID</label>
                    <input type="text" name="username" placeholder="Enter your ID" required>
                    <i class="fa-solid fa-user"></i>
                </div>
                
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                    <i class="fa-solid fa-lock"></i>
                </div>

                <button type="submit" class="submit-login">
                    Secure Login <i class="fa-solid fa-arrow-right" style="margin-left:8px;"></i>
                </button>
            </form>
        </div>
    </main>

    <script src="../static/header.js"></script>
</body>
</html>