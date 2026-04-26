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
    
      </nav>
      
      <div class="sidebar-footer">
                <?php if(!$is_logged_in): ?>
                    <a href="login.php" class="login-btn"><i class="fa-solid fa-user"></i> LOGIN</a>
                <?php else: ?>
                    <a href="logout.php" class="login-btn logout"><i class="fa-solid fa-sign-out"></i> LOGOUT</a>
                <?php endif; ?>
            </div>
        </nav>
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

        <section class="programs-header">
            <h1>ACADEMIC PROGRAMS & COURSES</h1>
            <p>Explore the full list of undergraduate and graduate programs offered by the Technological University of the Philippines.</p>
        </section>

        <div class="program-filter-bar">
            <input type="text" id="programSearch" placeholder="Search by program name or keyword..." class="search-input">
            <select id="collegeFilter" class="filter-select" onchange="filterPrograms()">
                <option value="all">All Colleges</option>
                <option value="COE">College of Engineering (COE)</option>             <option value="CIE">College of Industrial Education (CIE)</option>   <option value="CLA">College of Liberal Arts (CLA)</option>         <option value="COS">College of Science (COS)</option>
                <option value="CIT">College of Industrial Technology (CIT)</option>
                <option value="CAFA">College of Architectural and Fine Arts (CAFA)</option>
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
            { title: "Bachelor of Science in Civil Engineering (BSCE)", code: "BSCE", college: "COE", desc: "Focuses on the design, construction, and maintenance of the physical and naturally built environment, including roads, bridges, and buildings.", details: "This program produces licensed civil engineers specializing in structural, transportation, water resources, or construction management.", gradeRequirement: "85 and above GPA on Math, Science, and English (STEM only)" },
            { title: "Bachelor of Science in Electrical Engineering (BSEE)", code: "BSEE", college: "COE", desc: "Deals with the study and application of electricity, electronics, and electromagnetism to design and manage electrical systems and power generation.", details: "This program covers power systems, electrical machines, control systems, and electronics, leading to a professional Electrical Engineering license.", gradeRequirement: "85 and above GPA on Math, Science, and English (STEM only)" },
            { title: "Bachelor of Science in Mechanical Engineering (BSME)", code: "BSME", college: "COE", desc: "Involves the design, analysis, manufacturing, and maintenance of mechanical systems, utilizing the principles of motion, energy, and force.", details: "Focuses on power generation, machine design, thermodynamics, and manufacturing processes. Graduates are qualified for the Mechanical Engineering licensure exam.", gradeRequirement: "85 and above GPA on Math, Science, and English (STEM only)" },
            { title: "Bachelor of Science in Electronics Engineering (BSECE)", code: "BSECE", college: "COE", desc: "Covers the design and application of electronic circuits, communication systems (telecom), and computer hardware engineering.", details: "This program prepares students for the ECE licensure exam, with specialties in communications, microelectronics, and computer systems.", gradeRequirement: "85 and above GPA on Math, Science, and English (STEM only)" },

            // --- Master's and Engineering Programs (COE) - New Data ---
            { title: "Master of Engineering Program", code: "MEP", college: "COE", desc: "A comprehensive graduate program for advanced engineering studies with focus on applied research and professional development.", details: "This program prepares engineers for leadership roles in industry, research, and development with advanced technical and management skills.", gradeRequirement: "Graduate program" },
            { title: "Master of Science in Civil Engineering major in General Civil Engineering", code: "MSCEGen", college: "COE", desc: "Advanced study in civil engineering covering design, construction, infrastructure development, and management principles.", details: "Develops civil engineers with comprehensive expertise in structural design, materials, project management, and infrastructure systems.", gradeRequirement: "Graduate program" },
            { title: "Master of Science in Civil Engineering major in Geotechnical Engineering", code: "MSCEGeo", college: "COE", desc: "Specializes in soil mechanics, foundation design, earth structures, and geotechnical investigation methodologies.", details: "Prepares engineers to handle complex geotechnical challenges in foundation design, slope stability, and underground construction.", gradeRequirement: "Graduate program" },
            { title: "Master of Science in Civil Engineering major in Structural Engineering", code: "MSCEStr", college: "COE", desc: "Focus on structural analysis, design, and assessment of buildings, bridges, and other structures using advanced engineering principles.", details: "Develops expertise in structural mechanics, advanced design methods, and innovative structural solutions for complex projects.", gradeRequirement: "Graduate program" },
            { title: "Master of Science in Electrical Engineering major in Power System Engineering", code: "MSEEPower", college: "COE", desc: "Specializes in power generation, transmission, distribution systems, and power quality management.", details: "Prepares electrical engineers for roles in power utilities, renewable energy systems, and smart grid technologies.", gradeRequirement: "Graduate program" },
            { title: "Master of Science in Electrical Engineering major in Instrumentation and Control Engineering", code: "MSEEControl", college: "COE", desc: "Advanced study in control systems, automation, sensors, and measurement technologies for industrial applications.", details: "Develops engineers to design and optimize instrumentation and control systems for various industrial processes.", gradeRequirement: "Graduate program" },
            { title: "Master of Science in Electrical Engineering major in Electronics Engineering", code: "MSEEElec", college: "COE", desc: "Focus on advanced electronics design, semiconductor technology, and electronic circuit applications.", details: "Prepares engineers for innovation in electronics design, microelectronics, and advanced electronic systems development.", gradeRequirement: "Graduate program" },
            { title: "Master of Science in Electrical Engineering major in Communications Engineering", code: "MSEEComm", college: "COE", desc: "Specializes in telecommunications, signal processing, wireless systems, and communication network technologies.", details: "Develops expertise in modern communication systems, network design, and signal processing technologies.", gradeRequirement: "Graduate program" },
            { title: "Master of Science in Electrical Engineering", code: "MSEE", college: "COE", desc: "A comprehensive graduate program covering all aspects of electrical engineering with flexibility for specialization.", details: "Provides advanced training in electrical power systems, electronics, controls, and communications engineering.", gradeRequirement: "Graduate program" },
            { title: "Master of Science in Electrical Engineering major in Computer Engineering", code: "MSEEComp", college: "COE", desc: "Focus on computer hardware design, embedded systems, FPGA development, and computer architecture.", details: "Prepares engineers for design and development of advanced computer systems and embedded applications.", gradeRequirement: "Graduate program" },
            { title: "Master of Science in Mechanical Engineering major in Energy Engineering", code: "MSMEEnergy", college: "COE", desc: "Specializes in energy systems, thermal engineering, renewable energy technologies, and energy efficiency.", details: "Develops engineers with expertise in sustainable energy solutions, power generation, and energy management.", gradeRequirement: "Graduate program" },
            { title: "Master of Science in Mechanical Engineering major in Production Technology", code: "MSMEProd", college: "COE", desc: "Advanced study in manufacturing processes, production management, quality control, and lean manufacturing.", details: "Prepares engineers for leadership in manufacturing industries with expertise in production systems and optimization.", gradeRequirement: "Graduate program" },
            { title: "Masters of Engineering Program in Civil Engineering major in Structural Engineering Option", code: "MEngCEStr", college: "COE", desc: "Professional master's program in structural engineering with emphasis on practical application and project management.", details: "Combines advanced structural theory with real-world engineering practice for professional development.", gradeRequirement: "Graduate program" },
            { title: "Masters of Engineering Program in Civil Engineering major in Geotechnical Engineering Option", code: "MEngCEGeo", college: "COE", desc: "Professional master's program in geotechnical engineering focusing on foundation design and earth works.", details: "Develops practitioners with advanced geotechnical expertise for foundation engineering and soil mechanics applications.", gradeRequirement: "Graduate program" },
            { title: "Masters of Engineering Program in Civil Engineering major in General Civil Engineering Option", code: "MEngCEGen", college: "COE", desc: "Professional master's program in general civil engineering with comprehensive coverage of all civil engineering disciplines.", details: "Prepares engineers with broad expertise in structural, geotechnical, water resources, and transportation engineering.", gradeRequirement: "Graduate program" },
            { title: "Masters of Engineering Program in Electrical Engineering major in Power Engineering Option", code: "MEngEEPower", college: "COE", desc: "Professional master's program focusing on power generation, distribution, and management systems.", details: "Develops expertise in power systems design, operations, renewable energy integration, and power quality management.", gradeRequirement: "Graduate program" },
            { title: "Masters of Engineering Program in Electrical Engineering major in Instrumentation and Computer Engineering Option", code: "MEngEEInst", college: "COE", desc: "Professional master's program in instrumentation systems and computer engineering with industrial applications.", details: "Prepares engineers for design and implementation of advanced instrumentation and embedded computer systems.", gradeRequirement: "Graduate program" },
            { title: "Masters of Engineering Program in Electrical Engineering major in Electronics and Communications Engineering Option", code: "MEngEEComm", college: "COE", desc: "Professional master's program in electronics and communications technology with focus on telecommunications systems.", details: "Develops expertise in modern communication systems, electronics design, and signal processing technologies.", gradeRequirement: "Graduate program" },
            { title: "Masters of Engineering Program in Mechanical Engineering major in Refrigeration and Airconditioning Option", code: "MEngMERAC", college: "COE", desc: "Professional master's program specializing in refrigeration and air conditioning systems design and management.", details: "Prepares engineers with advanced knowledge in HVAC systems, thermodynamics, and energy-efficient cooling technologies.", gradeRequirement: "Graduate program" },
            { title: "Masters of Engineering Program in Mechanical Engineering major in Heat Power Option", code: "MEngMEHeat", college: "COE", desc: "Professional master's program focusing on thermal systems, heat transfer, and power generation technologies.", details: "Develops expertise in thermal engineering, steam systems, combustion processes, and energy conversion systems.", gradeRequirement: "Graduate program" },
            { title: "Masters of Engineering Program in Mechanical Engineering major in Manufacturing and Production Option", code: "MEngMEMfg", college: "COE", desc: "Professional master's program in advanced manufacturing processes, production systems, and lean management.", details: "Prepares engineers for leadership in manufacturing industries with expertise in modern production technologies.", gradeRequirement: "Graduate program" },

            // --- College of Industrial Education (CIE) - New Data ---
            { title: "Bachelor of Technology and Livelihood Education major in Information and Communication Technology", code: "BTLEd-ICT", college: "CIE", desc: "Prepares teachers to deliver ICT education in senior high and vocational schools, covering computer systems, networking, multimedia, and ICT pedagogy.", details: "A teaching program for ICT educators that combines technical knowledge with classroom management and instructional design.", gradeRequirement: "85 above GPA" },
            { title: "Bachelor of Technology and Livelihood Education major in Home Economics", code: "BTLEd-HE", college: "CIE", desc: "Equips educators with skills in family resource management, food and nutrition, clothing and textiles, and entrepreneurship for technical-vocational teaching.", details: "A teacher education program that integrates Home Economics content with vocational pedagogy, industry practices, and guidance for learners.", gradeRequirement: "85 and above GPA" },
            { title: "Bachelor of Technology and Livelihood Education major in Industrial Arts", code: "BTLEd-IA", college: "CIE", desc: "Develops teacher trainers in woodworking, metalworking, drafting, and industrial design with classroom management and technical teaching methods.", details: "This major prepares future industrial arts teachers with practical shop skills, curriculum planning, and learner-centered teaching strategies.", gradeRequirement: "85 and above GPA" },
            { title: "Bachelor of Technical Vocational Teachers Education major in Animation", code: "BTVTEd-ANIM", college: "CIE", desc: "Prepares students to teach animation and digital media production, covering storyboarding, character design, 2D/3D animation, and multimedia pedagogy.", details: "A vocational teacher education program that develops animation instruction skills alongside technical competencies for the creative industries.", gradeRequirement: "85 and above GPA" },
            { title: "Bachelor of Technical Vocational Teachers Education major in Beauty Care and Wellness", code: "BTVTEd-BCW", college: "CIE", desc: "Combines cosmetology, wellness management, and technical-vocational teaching methodologies for beauty care education.", details: "Prepares educators to teach beauty care, wellness services, and personal grooming with practical industry training and classroom techniques.", gradeRequirement: "85 and above GPA" },
            { title: "Bachelor of Technical Vocational Teachers Education major in Computer Programming", code: "BTVTEd-CP", college: "CIE", desc: "Trains future vocational teachers in software design, programming languages, systems analysis, and teaching methods for computer programming.", details: "This major produces competent computer programming educators ready to teach coding, application development, and ICT fundamentals.", gradeRequirement: "85 and above GPA" },
            { title: "Bachelor of Technical Vocational Teachers Education major in Electrical", code: "BTVTEd-ELEC", college: "CIE", desc: "Focuses on electrical installation, power systems, control circuits, and pedagogy for vocational electrical technology instruction.", details: "A technical teacher education program for electrical technology that balances hands-on skills with instructional design and safety standards.", gradeRequirement: "85 and above GPA" },
            { title: "Bachelor of Technical Vocational Teachers Education major in Electronics", code: "BTVTEd-ELC", college: "CIE", desc: "Covers electronics systems, instrumentation, digital electronics, and teaching strategies for electronics technology.", details: "Prepares vocational educators for electronics programs with a strong foundation in circuits, devices, troubleshooting, and learner-centered teaching.", gradeRequirement: "85 and above GPA" },
            { title: "Bachelor of Technical Vocational Teachers Education major in Food Service Management", code: "BTVTEd-FSM", college: "CIE", desc: "Teaches food production, service operations, hospitality management, and vocational training methods for food service education.", details: "A specialized educator program that blends culinary arts, dining service, and management instruction for technical-vocational schools.", gradeRequirement: "85 and above GPA" },
            { title: "Bachelor of Technical Vocational Teachers Education major in Fashion and Garment", code: "BTVTEd-FG", college: "CIE", desc: "Prepares instructors in fashion design, garment construction, textiles, and merchandising with technical-vocational teaching skills.", details: "This program trains fashion and garment educators in creative design, production techniques, and industry-relevant teaching practices.", gradeRequirement: "85 and above GPA" },
            { title: "Bachelor of Technical Vocational Teachers Education major in Heat Ventilation & Air Conditioning", code: "BTVTEd-HVAC", college: "CIE", desc: "Develops educators in HVAC systems, refrigeration, indoor air quality, and technical teaching for HVAC technology.", details: "Focuses on HVAC installation, maintenance, and system design while preparing teachers to deliver vocational training in this field.", gradeRequirement: "85 and above GPA" },
            { title: "Bachelor of Technical Teacher Education", code: "BTTED", college: "CIE", desc: "A broad program for technical teacher preparation with courses in pedagogy, assessment, and technical specialization across industrial and vocational subjects.", details: "This degree equips future technical teachers with instructional design, assessment strategies, and technical knowledge for diverse vocational courses.", gradeRequirement: "85 and above GPA" },
            { title: "Doctor of Education major in Industrial Education Management", code: "EdD-IEM", college: "CIE", desc: "A professional doctorate preparing leaders in industrial education policy, program management, and educational research.", details: "This program equips experienced educators with advanced leadership, administration, and research competencies for industrial education institutions.", gradeRequirement: "Graduate program" },
            { title: "Doctor of Education major in Career Guidance", code: "EdD-CG", college: "CIE", desc: "Focuses on advanced career guidance theory and leadership in counseling programs for educational institutions.", details: "Prepares doctoral-level practitioners for senior roles in career guidance, counseling services, and educational development.", gradeRequirement: "Graduate program" },
            { title: "Doctor of Technology", code: "DTech", college: "CIE", desc: "An advanced program focused on applied research, innovation, and leadership in technology development and management.", details: "This doctorate develops technology experts equipped to lead applied research, technology policy, and industry-academia collaboration.", gradeRequirement: "Graduate program" },
            { title: "Doctor of Philosophy major in Technology Management", code: "PhD-TM", college: "CIE", desc: "A research doctorate in technology management, innovation strategy, and organizational leadership.", details: "Prepares graduates for academic research, executive management, and consultancy roles in technology-intensive organizations.", gradeRequirement: "Graduate program" },
            { title: "Master of Arts in Industrial Education major in Curriculum and Instruction", code: "MAIE-CI", college: "CIE", desc: "Develops advanced knowledge in curriculum design, assessment, and instructional leadership for industrial education.", details: "This program prepares educators to lead curriculum development, teacher training, and instructional improvement projects.", gradeRequirement: "Graduate program" },
            { title: "Master of Arts in Industrial Education major in Educational Technology", code: "MAIE-ET", college: "CIE", desc: "Focuses on integrating technology into industrial education teaching, learning, and assessment.", details: "Prepares educators to design and implement technology-enhanced instruction and digital learning solutions.", gradeRequirement: "Graduate program" },
            { title: "Master of Arts in Industrial Education major in Administration and Supervision", code: "MAIE-AS", college: "CIE", desc: "Prepares educators for leadership roles in educational administration, supervision, and institutional management.", details: "Emphasizes policy development, organizational leadership, and supervision practices in industrial education settings.", gradeRequirement: "Graduate program" },
            { title: "Master of Arts in Industrial Education major in Guidance and Counseling", code: "MAIE-GC", college: "CIE", desc: "Develops expertise in guidance, counseling, and learner support services within industrial education programs.", details: "This major trains graduate counselors and guidance leaders to support student development and career planning.", gradeRequirement: "Graduate program" },
            { title: "Master of Arts in Teaching major in Technology and Home Economics", code: "MATE-TH", college: "CIE", desc: "Combines advanced teaching methods with technology and home economics content for vocational educators.", details: "Prepares teachers to deliver integrated Technology and Home Economics instruction in technical-vocational settings.", gradeRequirement: "Graduate program" },
            { title: "Master of Technology Education", code: "MTechEd", college: "CIE", desc: "A graduate degree focused on teaching technology and technical subjects in vocational and industrial education.", details: "This program emphasizes instructional leadership, curriculum innovation, and advanced technical education pedagogy.", gradeRequirement: "Graduate program" },

            // --- College of Liberal Arts (CLA) - New Data ---
            { title: "Bachelor of Arts in Management major in Industrial Management (BAIM)", code: "BAIM", college: "CLA", desc: "Develops managers and leaders with a focus on industrial and organizational management practices for efficient operations.", details: "Covers organizational management, human resources, production, and business ethics within a technology-focused environment.", gradeRequirement: "80 and above GPA in English, 80 and above GWA, GAS, HUMSS, ABM preferably" },
            { title: "Bachelor of Science in Entrepreneurial Management (BSEM)", code: "BSEM", college: "CLA", desc: "A program designed to cultivate entrepreneurial skills, turning out competent managers and innovators who create new ventures.", details: "Focuses on business planning, financial management, marketing, and the creation of sustainable enterprises.", gradeRequirement: "80 and above GPA in English, 80 and above GWA, GAS, HUMSS, ABM preferably" },
            { title: "Bachelor of Science in Hospitality Management", code: "BSHM", college: "CLA", desc: "Prepares hospitality professionals with expertise in hotel management, food service operations, event planning, and customer service excellence.", details: "Develops leaders for the hospitality industry with comprehensive training in operations, finance, marketing, and guest relations.", gradeRequirement: "80 and above GPA on English; Female: 5'4\" in height, Male: 5'6\" in height; 2-minute introduction video in corporate attire required. False information on height will lead to disqualification." },
            
            // --- COLLEGE OF SCIENCE (COS) ---
            { title: "Bachelor of Science in Information Technology (BSIT)", code: "BSIT", college: "COS", desc: "Focuses on utilizing computers and software to manage and process information, covering systems analysis, database management, and programming.", details: "A dynamic program that prepares students for careers in software development, network administration, and IT security.", gradeRequirement: "80 and above GPA (STEM only)" },
            { title: "Bachelor of Science in Computer Science (BSCS)", code: "BSCS", college: "COS", desc: "The study of computation and information, focusing on the theoretical foundations of information and computation, and practical techniques.", details: "A more theoretical and algorithms-focused program compared to IT, preparing students for research or complex system architecture roles.", gradeRequirement: "80 and above GPA (STEM only)" },
            { title: "Bachelor of Applied Science in Laboratory Technology", code: "BASLT", college: "COS", desc: "Focuses on laboratory techniques, analysis, and quality control in scientific settings.", details: "Prepares students for careers in medical labs, research facilities, and quality assurance.", gradeRequirement: "80 and above GPA (STEM only)" },
            { title: "Bachelor of Science in Environmental Science", code: "BSENVS", college: "COS", desc: "Studies environmental systems, sustainability, and the impact of human activities on the environment.", details: "Equips students with knowledge in ecology, conservation, and environmental policy for roles in environmental management.", gradeRequirement: "80 and above GPA (STEM only)" },
            { title: "Bachelor of Science in Information System", code: "BSIS", college: "COS", desc: "Integrates technology and business to manage information systems and data.", details: "Focuses on database management, system analysis, and IT project management for organizational efficiency.", gradeRequirement: "80 and above GPA (STEM only)" },

            // --- COLLEGE OF INDUSTRIAL TECHNOLOGY (CIT) ---
            { title: "Bachelor of Science in Industrial Technology (BSITech)", code: "BSITech", college: "CIT", desc: "A broad technology program covering specialized industrial skills and applied management principles.", details: "Includes various specializations like Civil, Electrical, and Mechanical Engineering Technology tracks.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Science in Graphic Arts and Printing Technology (BSGAPT)", code: "BSGAPT", college: "CIT", desc: "A specialized program covering design principles, digital imaging, printing processes, and publishing management.", details: "Combines technical printing skills with modern graphic design and digital media expertise.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Science in Food Technology", code: "BSFT", college: "CIT", desc: "Focuses on food science, product development, processing, and safety management.", details: "Prepares students for careers in food production, quality assurance, and nutrition technology.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Engineering Technology major in Computer Engineering Technology", code: "BET-CET", college: "CIT", desc: "Covers computer hardware, embedded systems, and industrial computing applications.", details: "Prepares graduates for roles in systems integration, automation, and electronic design support.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Engineering Technology major in Civil Technology", code: "BET-CT", college: "CIT", desc: "Focuses on construction technology, surveying, and infrastructure project support.", details: "Prepares students for careers in site supervision, drafting, and construction inspection.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Engineering Technology major in Electrical Technology", code: "BET-ELT", college: "CIT", desc: "Covers electrical systems, power distribution, and industrial electrical applications.", details: "Prepares students for work in electrical installation, maintenance, and industrial power systems.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Engineering Technology major in Electronics Communications Technology", code: "BET-ECT", college: "CIT", desc: "Focuses on electronic communications, signal processing, and telecommunications systems.", details: "Prepares graduates for careers in communications installation, network support, and electronics service.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Engineering Technology major in Electronic Technology", code: "BET-ELX", college: "CIT", desc: "Covers electronic device technology, circuit design, and practical electronics applications.", details: "Prepares students for roles in electronics repair, testing, and systems support.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Engineering Technology major in Instrumentation and Control Technology", code: "BET-ICT", college: "CIT", desc: "Focuses on instrumentation systems, sensors, and industrial control technologies.", details: "Prepares graduates for careers in automation, process control, and instrumentation maintenance.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Engineering Technology major in Mechanical Technology", code: "BET-MT", college: "CIT", desc: "Covers mechanical systems, manufacturing processes, and machine design support.", details: "Prepares students for roles in production, maintenance, and mechanical operations.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Engineering Technology major in Mechatronics Technology", code: "BET-MECH", college: "CIT", desc: "Integrates mechanical, electronic, and control systems for automated machines.", details: "Prepares graduates for work in robotics, automation, and intelligent manufacturing systems.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Engineering Technology major in Railway Technology", code: "BET-RT", college: "CIT", desc: "Focuses on railway systems, track technology, and rail transport engineering support.", details: "Prepares students for careers in railway operations, maintenance, and infrastructure support.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Engineering Technology major in Mechanical Engineering Technology option in Automotive Technology", code: "MET-AUTO", college: "CIT", desc: "Combines mechanical engineering principles with automotive systems and service technology.", details: "Prepares students for careers in automotive design support, maintenance, and repair technology.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Engineering Technology major in Mechanical Engineering Technology option in Foundry Technology", code: "MET-FOUND", college: "CIT", desc: "Focuses on metal casting, foundry processes, and material shaping technologies.", details: "Prepares graduates for careers in foundry operations, mold making, and metal production.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Engineering Technology major in Mechanical Engineering Technology option in Heating Ventilating & Air-Conditioning/Refrigeration Technology", code: "MET-HVACR", college: "CIT", desc: "Covers HVACR systems, refrigeration, and climate control technologies.", details: "Prepares students for careers in HVAC installation, maintenance, and refrigeration systems.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Engineering Technology major in Mechanical Engineering Technology option in Power Plant Technology", code: "MET-PPT", college: "CIT", desc: "Focuses on power plant operations, energy systems, and industrial power generation.", details: "Prepares graduates for roles in plant operations, power distribution, and energy management.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Engineering Technology major in Mechanical Engineering Technology option in Welding Technology", code: "MET-WELD", college: "CIT", desc: "Covers welding processes, fabrication, and metal joining technologies.", details: "Prepares students for careers in welding, fabrication, and structural assembly.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Engineering Technology major in Mechanical Engineering Technology option in Dies and Moulds Technology", code: "MET-DMT", college: "CIT", desc: "Focuses on die design, mould making, and production tooling technologies.", details: "Prepares graduates for roles in tool design, mould fabrication, and manufacturing support.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Technology in Apparel and Fashion", code: "BTAF", college: "CIT", desc: "Covers apparel design, fashion production, and textile technology.", details: "Prepares students for careers in fashion design, garment production, and apparel merchandising.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Technology in Nutrition and Food Technology", code: "BTNFT", college: "CIT", desc: "Integrates nutrition science with food technology and processing.", details: "Prepares graduates for careers in food development, nutrition services, and quality assurance.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },
            { title: "Bachelor of Technology in Print Media Technology", code: "BTPT", college: "CIT", desc: "Covers print production, media technologies, and publishing processes.", details: "Prepares students for careers in print media, publishing operations, and digital printing technologies.", gradeRequirement: "80 above GPA on Math, Science and English (STEM); 85 above GPA on Math, Science and English (Non-STEM)" },

            // --- COLLEGE OF ARCHITECTURAL AND FINE ARTS (CAFA) ---
            { title: "Bachelor of Science in Architecture", code: "BSARCH", college: "CAFA", desc: "Focuses on architectural design, building systems, and urban planning principles.", details: "Prepares students for careers in architectural design, project management, and construction coordination.", gradeRequirement: "85 above GPA in Math, Science, English (STEM only)" },
            { title: "Bachelor of Fine Arts", code: "BFA", college: "CAFA", desc: "Develops artistic skills in various visual arts disciplines including painting, sculpture, and digital art.", details: "Prepares graduates for careers in fine arts, creative direction, and artistic practice.", gradeRequirement: "85 above GPA in Math, Science, English (STEM only)" },
            { title: "Bachelor in Graphics Technology major in Architecture Technology", code: "BGT-ARCH", college: "CAFA", desc: "Integrates graphic design with architectural visualization and technology.", details: "Prepares students for careers in architectural visualization, CAD design, and digital representation.", gradeRequirement: "85 above GPA in Math, Science, English (STEM only)" },
            { title: "Bachelor in Graphics Technology major in Industrial Design", code: "BGT-ID", college: "CAFA", desc: "Covers product design, industrial aesthetics, and design technology.", details: "Prepares graduates for careers in product design, design consulting, and manufacturing support.", gradeRequirement: "85 above GPA in Math, Science, English (STEM only)" },
            { title: "Bachelor in Graphics Technology major in Mechanical Drafting Technology", code: "BGT-MDT", college: "CAFA", desc: "Focuses on mechanical drawing, technical documentation, and CAD design for engineering.", details: "Prepares students for careers in drafting, technical design, and engineering support roles.", gradeRequirement: "85 above GPA in Math, Science, English (STEM only)" }
        ];

       
        function createProgramCard(program) {
            return `
                <div class="program-card ${program.college}" data-program-code="${program.code}">
                    <div class="card-header">
                        <h3 class="program-title">${program.title}</h3>
                        <span class="college-tag ${program.college}">${program.college}</span>
                    </div>
                    <p class="program-desc">${program.desc}</p>
                    ${program.gradeRequirement === 'Graduate program' ? `<p class="program-requirement">GRADUATE PROGRAM</p>` : program.gradeRequirement ? `<p class="program-requirement"><strong>Admission requirement:</strong> ${program.gradeRequirement}</p>` : ''}
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
                    ${program.gradeRequirement === 'Graduate program' ? `<p><strong>GRADUATE PROGRAM</strong></p>` : program.gradeRequirement ? `<p><strong>Grade requirement:</strong> ${program.gradeRequirement}</p>` : ''}
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


