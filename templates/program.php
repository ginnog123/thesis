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
    <title>TUP Academic Programs</title>
    <link rel="stylesheet" href="../static/style.css" />
    <link rel="stylesheet" href="../static/program.css" />
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
        <a href="#" class="nav-item">DEPARTMENTS</a>
        <a href="#" class="nav-item">ABOUT TUP</a>
      </nav>
      
      <div class="sidebar-footer">
                <?php if(!$is_logged_in): ?>
                    <a href="login.php" class="login-btn"><i class="fa-solid fa-user"></i> LOGIN</a>
                <?php else: ?>
                    <a href="logout.php" class="login-btn logout"><i class="fa-solid fa-sign-out"></i> LOGOUT</a>
                <?php endif; ?>
            </div>
        </nav>
        <div class="nav-footer" onclick="toggleMenu()"><i class="fa-solid fa-arrow-left"></i></div>
    </header>

    <main class="main-content">
        <button class="menu-toggle" onclick="toggleMenu()">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="top-bar">
            <p id="date-time">Loading...</p>
        </div>

        <section class="programs-header">
            <h1>ACADEMIC PROGRAMS & COURSES</h1>
            <p>Explore the full list of undergraduate and graduate programs offered by the Technological University of the Philippines.</p>
        </section>

        <div class="program-filter-bar">
            <input type="text" id="programSearch" placeholder="Search by program name or keyword..." class="search-input">
            <select id="collegeFilter" class="filter-select" onchange="filterPrograms()">
                <option value="all">All Colleges</option>
                <option value="COE">College of Engineering (COE)</option>             <option value="CIE">College of Industrial Education (CIE)</option>   <option value="CLA">College of Liberal Arts (CLA)</option>         <option value="CICT">College of Industrial Computing and Technology (CICT)</option>
                <option value="CIT">College of Industrial Technology (CIT)</option>
                <option value="CAS">College of Arts and Sciences (CAS)</option>
                </select>
        </div>
        
        <div class="program-grid" id="programGrid">
            </div>
        
        <div id="programDetailsPopup" class="popup-overlay" style="display:none;">
            <div class="popup-content">
                <span class="close-btn" onclick="closeDetailsPopup()">&times;</span>
                <h2 id="popupTitle"></h2>
                <div id="popupContent">Loading curriculum details...</div>
                <button class="submit-btn" onclick="closeDetailsPopup()">Close</button>
            </div>
        </div>

    </main>

    <div class="overlay" onclick="toggleMenu()"></div>

    
    
    <script>
        const programs = [
            // --- College of Engineering (COE) - New Data ---
            { title: "Bachelor of Science in Civil Engineering (BSCE)", code: "BSCE", college: "COE", desc: "Focuses on the design, construction, and maintenance of the physical and naturally built environment, including roads, bridges, and buildings.", details: "This program produces licensed civil engineers specializing in structural, transportation, water resources, or construction management." },
            { title: "Bachelor of Science in Electrical Engineering (BSEE)", code: "BSEE", college: "COE", desc: "Deals with the study and application of electricity, electronics, and electromagnetism to design and manage electrical systems and power generation.", details: "This program covers power systems, electrical machines, control systems, and electronics, leading to a professional Electrical Engineering license." },
            { title: "Bachelor of Science in Mechanical Engineering (BSME)", code: "BSME", college: "COE", desc: "Involves the design, analysis, manufacturing, and maintenance of mechanical systems, utilizing the principles of motion, energy, and force.", details: "Focuses on power generation, machine design, thermodynamics, and manufacturing processes. Graduates are qualified for the Mechanical Engineering licensure exam." },
            { title: "Bachelor of Science in Electronics Engineering (BSECE)", code: "BSECE", college: "COE", desc: "Covers the design and application of electronic circuits, communication systems (telecom), and computer hardware engineering.", details: "This program prepares students for the ECE licensure exam, with specialties in communications, microelectronics, and computer systems." },

            // --- College of Industrial Education (CIE) - New Data ---
            { title: "Bachelor of Science in Industrial Education (BSIEd)", code: "BSIEd", college: "CIE", desc: "The foundation of teacher education, developing highly competent teachers/trainors, leaders, and innovators in industrial and technology education.", details: "Specializations may include Home Economics, Industrial Arts, and ICT. Focuses on pedagogical and technical skills." },
            { title: "Bachelor of Technical Vocational Teacher Education (BTVTEd)", code: "BTVTEd", college: "CIE", desc: "Prepares educators for vocational and technical training institutions, with focus on practical skills and teaching methodologies.", details: "Common majors include Computer Programming and Electrical Technology." },

            // --- College of Liberal Arts (CLA) - New Data ---
            { title: "Bachelor of Arts in Management major in Industrial Management (BAIM)", code: "BAIM", college: "CLA", desc: "Develops managers and leaders with a focus on industrial and organizational management practices for efficient operations.", details: "Covers organizational management, human resources, production, and business ethics within a technology-focused environment." },
            { title: "Bachelor of Science in Entrepreneurial Management (BSEM)", code: "BSEM", college: "CLA", desc: "A program designed to cultivate entrepreneurial skills, turning out competent managers and innovators who create new ventures.", details: "Focuses on business planning, financial management, marketing, and the creation of sustainable enterprises." },
            
            // --- Other Colleges (Refined Placeholders) ---
            { title: "Bachelor of Science in Information Technology (BSIT)", code: "BSIT", college: "CICT", desc: "Focuses on utilizing computers and software to manage and process information, covering systems analysis, database management, and programming.", details: "A dynamic program that prepares students for careers in software development, network administration, and IT security." },
            { title: "Bachelor of Science in Computer Science (BSCS)", code: "BSCS", college: "CICT", desc: "The study of computation and information, focusing on the theoretical foundations of information and computation, and practical techniques.", details: "A more theoretical and algorithms-focused program compared to IT, preparing students for research or complex system architecture roles." },
            { title: "Bachelor of Science in Industrial Technology (BSITech)", code: "BSITech", college: "CIT", desc: "A broad technology program covering specialized industrial skills and applied management principles.", details: "Includes various specializations like Civil, Electrical, and Mechanical Engineering Technology tracks." },
            { title: "Bachelor of Science in Graphic Arts and Printing Technology (BSGAPT)", code: "BSGAPT", college: "CIT", desc: "A specialized program covering design principles, digital imaging, printing processes, and publishing management.", details: "Combines technical printing skills with modern graphic design and digital media expertise." },
            { title: "Bachelor of Science in Applied Mathematics (BSAM)", code: "BSAM", college: "CAS", desc: "A core science program focusing on mathematical modeling, scientific computing, and data analysis for industrial applications.", details: "Prepares students for roles in quantitative finance, research, and data science." }
        ];

       
        function createProgramCard(program) {
            return `
                <div class="program-card ${program.college}" data-program-code="${program.code}">
                    <div class="card-header">
                        <h3 class="program-title">${program.title}</h3>
                        <span class="college-tag ${program.college}">${program.college}</span>
                    </div>
                    <p class="program-desc">${program.desc}</p>
                    <button class="details-btn" onclick="showDetails('${program.code}')">View Curriculum</button>
                </div>
            `;
        }

     
        function renderProgramCards(programsToRender) {
            const grid = document.getElementById('programGrid');
            grid.innerHTML = ''; // Clear existing cards
            programsToRender.forEach(program => {
                grid.innerHTML += createProgramCard(program);
            });
        }
        
       
        function filterPrograms() {
            const search = document.getElementById('programSearch').value.toLowerCase();
            const college = document.getElementById('collegeFilter').value;
            
            const filteredPrograms = programs.filter(program => {
                const titleMatch = program.title.toLowerCase().includes(search) || program.code.toLowerCase().includes(search);
                const collegeMatch = program.college === college || college === 'all';
                return titleMatch && collegeMatch;
            });
            
            renderProgramCards(filteredPrograms);
        }

        document.getElementById('programSearch').addEventListener('input', filterPrograms);

      
        window.showDetails = (programCode) => {
            const program = programs.find(p => p.code === programCode);
            if (program) {
                document.getElementById('popupTitle').textContent = program.title + ' Details';
                document.getElementById('popupContent').innerHTML = `
                    <p>${program.desc}</p>
                    <p style="margin-top: 15px; font-weight: bold;">Program Overview:</p>
                    <textarea style="width: 100%; height: 200px; padding: 10px; border: 1px solid #ccc; font-size: 14px;">${program.details}</textarea>
                    <p style="margin-top: 10px;">For official and complete curriculum, please check the TUP website or visit the Registrar's Office.</p>
                `;
                document.getElementById('programDetailsPopup').style.display = 'flex';
            }
        }

        window.closeDetailsPopup = () => {
            document.getElementById('programDetailsPopup').style.display = 'none';
        }

       
        document.addEventListener('DOMContentLoaded', () => {
            renderProgramCards(programs);
            
        });

    </script>

    <script src="../static/header.js"></script>
    <script src="../static/home.js"></script> 
  </body>
</html>


