<?php
session_start();

$host = "localhost"; $dbname = "tup_system"; $dbuser = "root"; $dbpass = "";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$is_logged_in = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? 'guest';
$application = null;
$success_msg = "";
$error_msg = "";
// DEFINING COURSES
$course_offerings = [
    "College of Science" => [
        "BAS Laboratory Technology",
        "BS Computer Science",
        "BS Environmental Science",
        "BS Information System",
        "BS Information Technology"
    ],
    "College of Engineering" => [
        "BS Civil Engineering",
        "BS Electrical Engineering",
        "BS Electronics Engineering",
        "BS Mechanical Engineering"
    ],
    "Engineering Technology (BET)" => [
        "BET Chemical Technology",
        "BET Electrical Technology",
        "BET Electronics Technology",
        "BET Automotive Technology",
        "BET Electromechanical Technology",
        "BET Civil Technology",
        "BET Instrumentation & Control Technology",
        "BET Mechatronics Technology",
        "BET Non-Destructive Testing Technology"
    ],
    "Teacher Education (BTVTEd)" => [
        "BTVTEd Electrical Technology",
        "BTVTEd Electronics Technology",
        "BTVTEd ICT – Computer Programming",
        "BTVTEd ICT – Computer Hardware Servicing"
    ]
];

// --- HANDLE PHOTO UPLOAD ---
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];
    if ($file['error'] === 0) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        if (in_array($ext, $allowed)) {
            $new_name = $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            $destination = '../uploads/' . $new_name;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $stmt = $pdo->prepare("UPDATE admission_applications SET photo_path = ? WHERE user_id = ?");
                $stmt->execute([$destination, $_SESSION['user_id']]);
                header("Location: admission.php");
                exit;
            } else { $error_msg = "Failed to upload file."; }
        } else { $error_msg = "Only JPG and PNG files are allowed."; }
    }
}

// --- HANDLE REGISTRATION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    $app_id = $_POST['application_id']; 
    $password = $_POST['password'];    
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $full_name = $first_name . ' ' . $last_name;
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();
        $stmtUser = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, 'student')");
        $stmtUser->execute([$app_id, $hashed_password, $full_name]);

        $sql = "INSERT INTO admission_applications 
                (application_id, user_id, first_name, last_name, date_of_birth, gender, email, phone_number, address, course_1, course_2, course_3, previous_school, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
        
        $stmtApp = $pdo->prepare($sql);
        $stmtApp->execute([$app_id, $app_id, $first_name, $last_name, $_POST['date_of_birth'], $_POST['gender'], $_POST['email'], $_POST['phone_number'], $_POST['address'], $_POST['course_1'], $_POST['course_2'], $_POST['course_3'], $_POST['previous_school']]);

        $pdo->commit();
        $success_msg = "Application Successful! Your Applicant ID is <strong>$app_id</strong>. Please <a href='login.php'>Login here</a>.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Error: " . $e->getMessage();
    }
}

$current_step = 1;
if ($is_logged_in && $role === 'student') {
    $stmt = $pdo->prepare("SELECT * FROM admission_applications WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($application) {
        $status = $application['status'];
        // LOGIC UPDATE: If Rejected, stay on Step 3 to show the result
        if ($status === 'Exam Status') $current_step = 2;
        elseif ($status === 'Exam Schedule') $current_step = 3;
        elseif ($status === 'Document Checking') $current_step = 4;
        elseif ($status === 'Enrolled') $current_step = 5;
        elseif ($status === 'Rejected') $current_step = 3; // CHANGED FROM 0 TO 3
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TUP Admission</title>
    <link rel="stylesheet" href="../static/style.css">
    <link rel="stylesheet" href="../static/admission.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
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

        <?php if($success_msg): ?><div class="alert-box alert-success"><?= $success_msg ?></div><?php endif; ?>
        <?php if($error_msg): ?><div class="alert-box alert-error"><?= $error_msg ?></div><?php endif; ?>

        <?php if ($is_logged_in && $role === 'student' && $application): ?>
            
            <input type="hidden" id="student-name" value="<?= htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) ?>">
            <input type="hidden" id="student-app-id" value="<?= $application['application_id'] ?>">
            <input type="hidden" id="student-course" value="<?= $application['course_1'] ?>">
            <input type="hidden" id="exam-date" value="<?= !empty($application['exam_date']) ? date('F j, Y', strtotime($application['exam_date'])) : 'TBA' ?>">
            <input type="hidden" id="exam-time" value="<?= $application['exam_time'] ?? 'TBA' ?>">
            <input type="hidden" id="exam-venue" value="<?= $application['exam_venue'] ?? 'TBA' ?>">

            <div class="dashboard-container">
                <div class="dashboard-header">
                    <div><h1>Admission Dashboard</h1><p class="subtitle">Track your application progress</p></div>
                    <div class="app-id-badge"><span class="label">Application ID</span><span class="code"><?= $application['application_id'] ?></span></div>
                </div>

                <div class="modern-tabs-container">
                    <div class="modern-tab <?= $current_step >= 1 ? 'active' : '' ?>" onclick="switchStep(1)"><span class="tab-icon"><i class="fa-solid fa-user"></i></span> <span class="tab-text">Profile</span></div>
                    <div class="modern-tab <?= $current_step >= 2 ? 'active' : '' ?> <?= $current_step < 2 ? 'locked' : '' ?>" onclick="switchStep(2)"><span class="tab-icon"><i class="fa-solid fa-file-contract"></i></span> <span class="tab-text">Status</span></div>
                    <div class="modern-tab <?= $current_step >= 3 ? 'active' : '' ?> <?= $current_step < 3 ? 'locked' : '' ?>" onclick="switchStep(3)">
                        <span class="tab-icon"><i class="fa-solid fa-calendar-days"></i></span> 
                        <span class="tab-text"><?= $application['status'] === 'Rejected' ? 'Result' : 'Schedule' ?></span>
                    </div>
                    
                    <div class="modern-tab <?= $current_step >= 4 ? 'active' : '' ?> <?= $current_step < 4 ? 'locked' : '' ?>" onclick="switchStep(4)"><span class="tab-icon"><i class="fa-solid fa-folder-open"></i></span> <span class="tab-text">Documents</span></div>
                    <div class="modern-tab <?= $current_step >= 5 ? 'active' : '' ?> <?= $current_step < 5 ? 'locked' : '' ?>" onclick="switchStep(5)"><span class="tab-icon"><i class="fa-solid fa-graduation-cap"></i></span> <span class="tab-text">Enrolled</span></div>
                </div>

                <div class="dashboard-content">
                    <div id="step-1" class="step-pane active">
                        <div class="content-card">
                            <div class="card-header"><h3>Applicant Profile</h3><span class="status-tag"><?= $application['status'] ?></span></div>
                            <div class="profile-layout">
                                <div class="avatar-section">
                                    <?php if (!empty($application['photo_path'])): ?>
                                        <div class="large-avatar" style="background-image: url('<?= $application['photo_path'] ?>'); background-size: cover; background-position: center; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1);"></div>
                                    <?php else: ?>
                                        <div class="large-avatar"><?= strtoupper(substr($application['first_name'], 0, 1)) ?></div>
                                    <?php endif; ?>
                                    <h3><?= htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) ?></h3>
                                    <form method="POST" enctype="multipart/form-data" style="margin-top:10px;">
                                        <label for="photo-upload" class="upload-link"><i class="fa-solid fa-camera"></i> Change Photo</label>
                                        <input type="file" name="profile_photo" id="photo-upload" style="display:none;" onchange="this.form.submit()">
                                    </form>
                                </div>
                                <div class="info-grid">
                                    <div class="info-group"><label>Email</label><p><?= htmlspecialchars($application['email']) ?></p></div>
                                    <div class="info-group"><label>Phone</label><p><?= htmlspecialchars($application['phone_number']) ?></p></div>
                                    <div class="info-group"><label>Birth Date</label><p><?= htmlspecialchars($application['date_of_birth']) ?></p></div>
                                    <div class="info-group"><label>Gender</label><p><?= ucfirst($application['gender']) ?></p></div>
                                    <div class="info-group full-width"><label>Address</label><p><?= htmlspecialchars($application['address']) ?></p></div>
                                    <div class="info-group full-width highlight-bg"><label>Priority Program</label><p><strong><?= htmlspecialchars($application['course_1']) ?></strong></p></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="step-2" class="step-pane">
                        <div class="content-card center-aligned">
                            <div class="icon-circle"><i class="fa-solid fa-magnifying-glass"></i></div>
                            <h2>Application Review</h2>
                            <p class="lead-text">Your application is being evaluated for the TUP Entrance Exam.</p>
                            <?php if($current_step >= 2): ?>
                                <div class="status-box success">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <div><h4>Qualified for Examination</h4><p>Your profile is approved.</p></div>
                                </div>
                            <?php else: ?>
                                <div class="status-box pending">
                                    <i class="fa-solid fa-clock"></i>
                                    <div><h4>Pending Verification</h4><p>Please wait for the Admission Office.</p></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="step-3" class="step-pane">
                        <div class="content-card">
                            
                            <?php if ($application['status'] === 'Rejected'): ?>
                                <div class="failed-state">
                                    <div class="failed-icon"><i class="fa-solid fa-circle-xmark"></i></div>
                                    <h2 class="failed-title">Admission Application Update</h2>
                                    <p class="failed-message">
                                        We regret to inform you that you did not reach the required passing score for the TUP Entrance Examination. 
                                        Your application for <strong><?= htmlspecialchars($application['course_1']) ?></strong> has been declined.
                                    </p>
                                    <div class="status-badge-fail">Status: Failed</div>
                                    
                                    <div class="retry-info">
                                        <p>Thank you for your interest in the Technological University of the Philippines.<br>
                                        You may re-apply for the next academic year.</p>
                                    </div>
                                </div>
                            
                            <?php elseif($current_step >= 3 && !empty($application['exam_date'])): ?>
                                <div class="card-header"><h3>Examination Permit</h3></div>
                                <div class="official-ticket">
                                    <div class="ticket-header">
                                        <div class="tup-logo-small"><img src="logo tup .svg" alt="TUP"></div>
                                        <div class="ticket-title"><h4>TECHNOLOGICAL UNIVERSITY OF THE PHILIPPINES</h4><span>OFFICE OF ADMISSIONS</span><h5>ENTRANCE EXAM PERMIT</h5></div>
                                    </div>
                                    <div class="ticket-body">
                                        <div class="ticket-info">
                                            <div class="info-row"><span class="lbl">NAME</span><span class="val"><?= strtoupper($application['first_name'] . ' ' . $application['last_name']) ?></span></div>
                                            <div class="info-row"><span class="lbl">COURSE</span><span class="val"><?= $application['course_1'] ?></span></div>
                                            <div class="ticket-schedule-grid">
                                                <div class="sched-box"><span class="lbl">DATE</span><span class="val-large"><?= date('M d, Y', strtotime($application['exam_date'])) ?></span></div>
                                                <div class="sched-box"><span class="lbl">TIME</span><span class="val-large"><?= htmlspecialchars($application['exam_time']) ?></span></div>
                                                <div class="sched-box venue"><span class="lbl">VENUE</span><span class="val-large"><?= htmlspecialchars($application['exam_venue']) ?></span></div>
                                            </div>
                                        </div>
                                        <div class="ticket-qr-section">
                                            <div id="qr-display-web"></div><span class="qr-label">SCAN</span><span class="permit-id">ID: <?= $application['application_id'] ?></span>
                                        </div>
                                    </div>
                                    <div class="ticket-footer"><p>* Present this permit with valid ID.</p></div>
                                </div>
                                <button class="btn-download" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Official Permit</button>
                            
                            <?php else: ?>
                                <div class="card-header"><h3>Examination Schedule</h3></div>
                                <div class="empty-state" style="text-align:center; padding:20px;">
                                    <i class="fa-solid fa-calendar-xmark" style="font-size:50px; color:#ccc; margin-bottom:20px;"></i>
                                    <p>Schedule processing...</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="step-4" class="step-pane">
                        <div class="content-card">
                            <div class="card-header"><h3>Requirements Submission</h3></div>
                            <div class="checklist-container">
                                <p>Submit the following original documents:</p>
                                <div class="checklist-item"><i class="fa-solid fa-square-check" style="color:#8b0000; margin-right:10px;"></i> Form 138 (High School Report Card)</div>
                                <div class="checklist-item"><i class="fa-solid fa-square-check" style="color:#8b0000; margin-right:10px;"></i> Certificate of Good Moral Character</div>
                                <div class="checklist-item"><i class="fa-solid fa-square-check" style="color:#8b0000; margin-right:10px;"></i> PSA Birth Certificate (Original & Photocopy)</div>
                                <div class="checklist-item"><i class="fa-solid fa-square-check" style="color:#8b0000; margin-right:10px;"></i> 2 pcs. 2x2 Recent ID Picture</div>
                            </div>
                        </div>
                    </div>

                    <div id="step-5" class="step-pane">
                        <div class="content-card center-aligned success-bg">
                            <i class="fa-solid fa-award big-success-icon" style="font-size:60px; color:#27ae60; margin-bottom:20px;"></i>
                            <h1>Welcome to TUP!</h1>
                            <p class="lead-text">You are officially enrolled in <strong><?= htmlspecialchars($application['course_1']) ?></strong>.</p>
                            <?php if(!empty($application['remarks'])): ?>
                                <div class="status-box pending">
                                    <strong><i class="fa-solid fa-triangle-exclamation"></i> TO BE FOLLOWED:</strong>
                                    <ul style="margin:10px 0 0 20px;">
                                        <?php $missing_docs = explode(',', $application['remarks']); foreach($missing_docs as $doc) { echo "<li>".htmlspecialchars(trim($doc))."</li>"; } ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="print-area">
                <div class="print-border">
                    <div class="print-header">
                        <img src="logo tup .svg" alt="TUP Logo" style="width:70px; float:left;">
                        <div class="print-titles">
                            <h3>TECHNOLOGICAL UNIVERSITY OF THE PHILIPPINES</h3>
                            <p>Ayala Blvd., Ermita, Manila</p>
                            <h1>OFFICE OF ADMISSIONS</h1>
                            <h2>EXAMINATION PERMIT</h2>
                        </div>
                        <div style="clear:both;"></div>
                    </div>
                    <div class="print-body">
                        <div class="print-info">
                            <table class="info-table">
                                <tr><td><strong>Application ID:</strong></td><td><?= $application['application_id'] ?></td></tr>
                                <tr><td><strong>Name:</strong></td><td><?= strtoupper($application['first_name'] . ' ' . $application['last_name']) ?></td></tr>
                                <tr><td><strong>Course:</strong></td><td><?= $application['course_1'] ?></td></tr>
                            </table>
                        </div>
                        <div class="print-qr"><div id="qr-display-print"></div><small>Scan for Verification</small></div>
                    </div>
                    <div class="print-sched-box">
                        <h3>EXAMINATION DETAILS</h3>
                        <table class="sched-table">
                            <tr><th>DATE</th><th>TIME</th><th>VENUE</th></tr>
                            <tr>
                                <td><?= !empty($application['exam_date']) ? date('F j, Y', strtotime($application['exam_date'])) : 'TBA' ?></td>
                                <td><?= $application['exam_time'] ?? 'TBA' ?></td>
                                <td><?= $application['exam_venue'] ?? 'TBA' ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="print-footer"><p><strong>IMPORTANT:</strong> Present this permit with your School ID. Bring pencils and eraser.</p><div class="print-sig"><div class="line"></div><p>Signature over Printed Name</p></div></div>
                </div>
            </div>

        <?php elseif (!$is_logged_in): ?>
            <div class="form-container">
                <form method="POST">
                    <h2>TUP Admission Application</h2>
                    <p>Complete the form below to register.</p>
                    
                    <div class="app-id-display">
                        <strong>Application ID:</strong> <span id="gen-id" style="color:#fff; font-size:18px;">Generating...</span>
                        <input type="hidden" name="application_id" id="input-app-id">
                        <small style="display:block; margin-top:5px;">(This will be your Username)</small>
                    </div>

                    <fieldset>
                        <legend>Account Security</legend>
                        <div class="form-group"><label>Create Password</label><input type="password" name="password" required></div>
                    </fieldset>

                    <fieldset>
                        <legend>Personal Information</legend>
                        <div class="form-row" style="display:flex; gap:15px;">
                            <div class="form-group" style="flex:1;"><label>First Name</label><input type="text" name="first_name" required></div>
                            <div class="form-group" style="flex:1;"><label>Last Name</label><input type="text" name="last_name" required></div>
                        </div>
                        <div class="form-row" style="display:flex; gap:15px; margin-top:10px;">
                            <div class="form-group" style="flex:1;"><label>Date of Birth</label><input type="date" name="date_of_birth" required></div>
                            <div class="form-group" style="flex:1;">
                                <label>Gender</label>
                                <select name="gender" required>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Contact Details</legend>
                        <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                        <div class="form-group"><label>Phone</label><input type="tel" name="phone_number" required></div>
                        <div class="form-group"><label>Address</label><textarea name="address" rows="2"></textarea></div>
                    </fieldset>

                    <fieldset>
                        <legend>Academic Preferences</legend>
                        <div class="form-group">
                            <label>Previous School</label>
                            <input type="text" name="previous_school" required>
                        </div>
                        
                        <div class="form-group">
                            <label>1st Choice (Priority)</label>
                            <select name="course_1" id="course_1" required onchange="updateCourseOptions()">
                                <option value="" selected disabled>-- Select Priority Course --</option>
                                <?php foreach ($course_offerings as $college => $programs): ?>
                                    <optgroup label="<?= $college ?>">
                                        <?php foreach ($programs as $program): ?>
                                            <option value="<?= $program ?>"><?= $program ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>2nd Choice</label>
                            <select name="course_2" id="course_2" required onchange="updateCourseOptions()">
                                <option value="" selected disabled>-- Select 2nd Option --</option>
                                <?php foreach ($course_offerings as $college => $programs): ?>
                                    <optgroup label="<?= $college ?>">
                                        <?php foreach ($programs as $program): ?>
                                            <option value="<?= $program ?>"><?= $program ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>3rd Choice</label>
                            <select name="course_3" id="course_3" required onchange="updateCourseOptions()">
                                <option value="" selected disabled>-- Select 3rd Option --</option>
                                <?php foreach ($course_offerings as $college => $programs): ?>
                                    <optgroup label="<?= $college ?>">
                                        <?php foreach ($programs as $program): ?>
                                            <option value="<?= $program ?>"><?= $program ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </fieldset>

                    <button type="submit" name="submit_application" class="submit-btn">Submit Application</button>
                </form>
            </div>
        <?php endif; ?>
    </main>
    

    <script src="../static/admission.js"></script>
    <script src="../static/header.js"></script>
</body>
</html>
