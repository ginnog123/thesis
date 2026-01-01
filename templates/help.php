<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']); 
$role = $_SESSION['role'] ?? 'guest';
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Help Center | TUP Kiosk</title>
    <link rel="stylesheet" href="../static/style.css" />
    <link rel="stylesheet" href="../static/help.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  </head>
  <body>
    
    <header id="header" class="header">
      <div class="logo-container">
          <img src="../images/logo tup .svg" alt="TUP Logo" class="logo2" />
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
        <a href="help.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'help.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-headset"></i> <span>HELP / CHAT</span>
        </a>
        
        <div class="nav-section-label">University</div>
        
        <a href="#" class="nav-item">
            <i class="fa-solid fa-building-columns"></i> <span>DEPARTMENTS</span>
        </a>
        <a href="#" class="nav-item">
            <i class="fa-solid fa-circle-info"></i> <span>ABOUT TUP</span>
        </a>
      </nav>
      
      <div class="sidebar-footer">
            <?php if(!$is_logged_in): ?>
                <a href="login.php" class="login-btn">
                    <i class="fa-solid fa-right-to-bracket"></i> LOGIN
                </a>
            <?php else: ?>
                <div class="user-info" style="text-align: center; color: #dcdcdc; font-size: 12px; margin-bottom: 10px;">
                    Logged in as: <strong style="color: white; display:block;"><?= htmlspecialchars($_SESSION['user_id']) ?></strong>
                </div>
                <a href="logout.php" class="login-btn logout">
                    <i class="fa-solid fa-right-from-bracket"></i> LOGOUT
                </a>
            <?php endif; ?>
      </div>
      <div class="nav-footer" onclick="toggleMenu()"><span>Close Menu</span></div>
    </header>

    <main class="main-content">
        <button class="menu-toggle" onclick="toggleMenu()">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="top-bar">
            <p id="date-time">Loading...</p>
        </div>

        <section style="padding: 20px;">
            <h1>Admissions Assistant</h1>
            <p>Have questions about your application? Ask our virtual assistant below.</p>
            
            <div class="chat-container">
                <div class="chat-header">
                    <i class="fa-solid fa-robot"></i>
                    <div>
                        <h2>TUP Bot</h2>
                        <small style="color: #eee;">Online • Automated Support</small>
                    </div>
                </div>

                <div class="suggestions">
                    <div class="chip" onclick="sendSuggestion('Why did I fail?')">Why did I fail?</div>
                    <div class="chip" onclick="sendSuggestion('What courses are offered?')">Offered Courses</div>
                    <div class="chip" onclick="sendSuggestion('How much is the tuition?')">Tuition Fee</div>
                    <div class="chip" onclick="sendSuggestion('What are the requirements?')">Requirements</div>
                </div>

                <div class="chat-box" id="chatBox">
                    <div class="message bot-message">
                        Hello! I am the TUP Admissions Bot. I can answer questions about the admission process, courses, and requirements. How can I help you today?
                    </div>
                </div>

                <div class="chat-input-area">
                    <input type="text" id="userInput" placeholder="Type your question here..." onkeypress="handleEnter(event)">
                    <button class="send-btn"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
            </div>
        </section>

    </main>

    <div class="overlay" onclick="toggleMenu()"></div>

    <script src="../static/header.js"></script>
    <script src="../static/help.js"></script>
  </body>
</html>