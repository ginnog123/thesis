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
    <title>TUP KIOSK HOME</title>
    <link rel="stylesheet" href="../static/style.css" />
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

      <div class="nav-footer" onclick="toggleMenu()">
          <span>Close Menu</span>
      </div>
    </header>

    <main class="main-content">
        <button class="menu-toggle" onclick="toggleMenu()">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="top-bar">
            <p id="date-time">Loading...</p>
        </div>

       <section class="welcome-section">
            <h1>WELCOME TO TUP KIOSK!</h1>
            <div class="slider-container">
                <div class="slider-wrapper">
                    <img src="../images/slide1.png" class="slide active" alt="Event 1">
                    <img src="../images/image.png" class="slide" alt="Event 2">
                    <img src="../images/tup.jpg" class="slide" alt="Event 3">
                </div>
                <button class="prev-btn" onclick="changeSlide(-1)">&#10094;</button>
                <button class="next-btn" onclick="changeSlide(1)">&#10095;</button>
                
                <div class="dots-container">
                    <span class="dot active" onclick="currentSlide(0)"></span>
                    <span class="dot" onclick="currentSlide(1)"></span>
                    <span class="dot" onclick="currentSlide(2)"></span>
                </div>
            </div>
        </section>

     <section class="campuses-section">
    <h2>CAMPUSES</h2>
    <div class="grid-box-container">

        <div class="campus-box" style="--bg:url('../images/manila.jpeg')">
            <span>TUP MANILA</span>
        </div>

        <div class="campus-box" style="--bg:url('../images/taguig.png')">
            <span>TUP TAGUIG</span>
        </div>

        <div class="campus-box" style="--bg:url('../images/cavite.jpg')">
            <span>TUP CAVITE</span>
        </div>

        <div class="campus-box" style="--bg:url('../images/visayas.jpg')">
            <span>TUP VISAYAS</span>
        </div>


    </div>
</section>
        <section class="info-section">
            <div class="info-grid">
                <div class="white-box info-box">
                    <h3>VISION</h3>
                    <div class="red-underline"></div>
                    <p class="info-text">
                        A premier state university with recognized excellence in engineering and technology education at par with leading universities in the ASEAN region.
                    </p>
                </div>
                <div class="white-box info-box">
                    <h3>MISSION</h3>
                    <div class="red-underline"></div>
                    <p class="info-text">
                        The University shall provide higher and advanced vocational, technical, industrial, technological and professional education and training in industries and technology, and in practical arts leading to certificates, diplomas and degrees. It shall provide progressive leadership in applied research, developmental studies in technical, industrial, and technological fields and production using indigenous materials; effect technology transfer in the countryside; and assist in the development of small-and-medium scale industries in identified growth centers.
                    </p>
                </div>
                <div class="white-box info-box">
                    <h3>CORE VALUES</h3>
                    <div class="red-underline"></div>
                    <ul class="info-list">
                        <li><strong>T</strong> - Transparent and participatory governance</li>
                        <li><strong>U</strong> - Unity in the pursuit of TUP mission, goals, and objectives</li>
                        <li><strong>P</strong> - Professionalism in the discharge of quality service</li>
                        <li><strong>I</strong> - Integrity and commitment to maintain the good name of the University</li>
                        <li><strong>A</strong> - Accountability for individual and organizational quality performance</li>
                        <li><strong>N</strong> - Nationalism through tangible contribution to the rapid economic growth of the country</li>
                        <li><strong>S</strong> - Shared responsibility, hard work, and resourcefulness in compliance to the mandates of the university</li>
                    </ul>
                </div>
            </div>
        </section>
    </main>

    <div class="overlay" onclick="toggleMenu()"></div>

    <script src="../static/home.js"></script>
    <script src="../static/header.js"></script>
  </body>
</html>


