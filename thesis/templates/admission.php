<?php
session_start();

// 1. Database Connection
$host = "localhost"; $dbname = "tup_system"; $dbuser = "root"; $dbpass = "";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// 2. Initialize Variables
$is_logged_in = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? 'guest';
$application = null;
$success_msg = "";
$error_msg = "";

// 3. HANDLE FORM SUBMISSION (For Guests/New Applicants)
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

// 4. DETERMINE STEP FOR STUDENT VIEW
$current_step = 1; // Default to Profile
if ($is_logged_in && $role === 'student') {
    $stmt = $pdo->prepare("SELECT * FROM admission_applications WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($application) {
        $status = $application['status'];
        if ($status === 'Exam Status') $current_step = 2;
        elseif ($status === 'Exam Schedule') $current_step = 3;
        elseif ($status === 'Document Checking') $current_step = 4;
        elseif ($status === 'Enrolled') $current_step = 5;
        elseif ($status === 'Rejected') $current_step = 0;
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
</head>
<body>

    <header id="header" class="header">
        <div class="logo-container"><img src="../logo tup .svg" alt="TUP Logo" class="logo2" /></div>
        
        <nav class="nav-menu">
            <a href="home.php" class="nav-item">HOME</a>
            
            <?php if($is_logged_in && $role === 'student'): ?>
                <a href="admission.php" class="nav-item active">MY ADMISSION</a>
            <?php elseif($is_logged_in && $role === 'admin'): ?>
                <a href="admin_dashboard.php" class="nav-item">ADMIN PANEL</a>
            <?php else: ?>
                <a href="admission.php" class="nav-item active">ADMISSIONS</a>
            <?php endif; ?>

            <a href="registrar.php" class="nav-item">REGISTRAR</a>
            <a href="program.php" class="nav-item">PROGRAMS</a>
          
            
            <div class="sidebar-footer">
                <?php if(!$is_logged_in): ?>
                    <a href="login.php" class="login-btn"><i class="fa-solid fa-user"></i> LOGIN</a>
                <?php else: ?>
                    <div class="user-info" style="text-align:center; padding-bottom:5px; color:#8b0000; font-size:12px;">
                        User: <strong><?= htmlspecialchars($_SESSION['user_id']) ?></strong>
                    </div>
                    <a href="logout.php" class="login-btn logout"><i class="fa-solid fa-sign-out"></i> LOGOUT</a>
                <?php endif; ?>
            </div>
        </nav>
        <div class="nav-footer" onclick="toggleMenu()"><i class="fa-solid fa-arrow-left"></i></div>
    </header>

    <main class="main-content">
        <div class="top-bar"><p id="date-time">Loading...</p></div>

        <?php if($success_msg): ?><div class="alert-box alert-success"><?= $success_msg ?></div><?php endif; ?>
        <?php if($error_msg): ?><div class="alert-box alert-error"><?= $error_msg ?></div><?php endif; ?>

        <?php if ($is_logged_in && $role === 'student' && $application): ?>
            
            <div class="stepper-container">
                <div class="stepper-line"></div>
                <div class="step-box <?= $current_step >= 1 ? 'unlocked' : '' ?>" onclick="switchStep(1)">
                    <div class="circle">1</div>
                    <span>Profile</span>
                </div>
                <div class="step-box <?= $current_step >= 2 ? 'unlocked' : '' ?>" onclick="switchStep(2)">
                    <div class="circle">2</div>
                    <span>Exam Status</span>
                </div>
                <div class="step-box <?= $current_step >= 3 ? 'unlocked' : '' ?>" onclick="switchStep(3)">
                    <div class="circle">3</div>
                    <span>Exam Schedule</span>
                </div>
                <div class="step-box <?= $current_step >= 4 ? 'unlocked' : '' ?>" onclick="switchStep(4)">
                    <div class="circle">4</div>
                    <span>Documents</span>
                </div>
                <div class="step-box <?= $current_step >= 5 ? 'unlocked' : '' ?>" onclick="switchStep(5)">
                    <div class="circle">5</div>
                    <span>Enrolled</span>
                </div>
            </div>

            <div class="step-content-wrapper">
                
                <div id="step-1" class="step-pane active">
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="avatar"><?= strtoupper($application['first_name'][0]) ?></div>
                            <div class="header-text">
                                <h2><?= htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) ?></h2>
                                <p>Application ID: <strong><?= $application['application_id'] ?></strong></p>
                            </div>
                            <div class="current-status">
                                Current Status: <span class="badge"><?= $application['status'] ?></span>
                            </div>
                        </div>
                        <div class="profile-body">
                            <div class="detail-grid">
                                <div class="detail-item"><label>Email</label><p><?= $application['email'] ?></p></div>
                                <div class="detail-item"><label>Phone</label><p><?= $application['phone_number'] ?></p></div>
                                <div class="detail-item"><label>Birthdate</label><p><?= $application['date_of_birth'] ?></p></div>
                                <div class="detail-item"><label>Gender</label><p><?= ucfirst($application['gender']) ?></p></div>
                                <div class="detail-item full"><label>Address</label><p><?= $application['address'] ?></p></div>
                                <div class="detail-item"><label>Previous School</label><p><?= $application['previous_school'] ?></p></div>
                                <div class="detail-item"><label>Priority Course</label><p><?= $application['course_1'] ?></p></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="step-2" class="step-pane">
                    <div class="info-box">
                        <i class="fa-solid fa-file-signature big-icon"></i>
                        <h3>Entrance Examination Status</h3>
                        <p>Your application is currently under review for the Entrance Exam.</p>
                        <?php if($current_step >= 2): ?>
                            <div class="alert-success" style="display:inline-block; padding:10px 20px; border-radius:5px; margin-top:10px;">
                                <i class="fa-solid fa-check"></i> Qualified for Exam
                            </div>
                        <?php else: ?>
                            <p style="color:#856404;">Please wait for Admin verification.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="step-3" class="step-pane">
                    <div class="info-box">
                        <i class="fa-solid fa-calendar-check big-icon"></i>
                        <h3>Examination Schedule</h3>
                        <?php if($current_step >= 3 && !empty($application['exam_date'])): ?>
                            <div class="schedule-ticket">
                                <div class="ticket-row"><span>Date:</span> <strong><?= htmlspecialchars($application['exam_date']) ?></strong></div>
                                <div class="ticket-row"><span>Time:</span> <strong><?= htmlspecialchars($application['exam_time']) ?></strong></div>
                                <div class="ticket-row"><span>Venue:</span> <strong><?= htmlspecialchars($application['exam_venue']) ?></strong></div>
                                <button class="btn-print">Print Permit</button>
                            </div>
                        <?php else: ?>
                            <p>Your exam schedule is being processed. Please check back later.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="step-4" class="step-pane">
                    <div class="info-box">
                        <i class="fa-solid fa-folder-open big-icon"></i>
                        <h3>Document Submission</h3>
                        <p>Please submit the following requirements to the Registrar:</p>
                        <ul class="req-list">
                            <li><i class="fa-regular fa-square-check"></i> Form 138 (Original Report Card)</li>
                            <li><i class="fa-regular fa-square-check"></i> Certificate of Good Moral</li>
                            <li><i class="fa-regular fa-square-check"></i> PSA Birth Certificate (Photocopy)</li>
                            <li><i class="fa-regular fa-square-check"></i> 2 pcs 2x2 ID Picture</li>
                        </ul>
                    </div>
                </div>

                <div id="step-5" class="step-pane">
                    <div class="info-box success">
                        <i class="fa-solid fa-graduation-cap big-icon" style="color:#27ae60;"></i>
                        <h2 style="color:#27ae60;">Congratulations!</h2>
                        <p>You are officially enrolled in <strong><?= $application['course_1'] ?></strong>.</p>
                        <p>Welcome to the Technological University of the Philippines!</p>
                        <button class="btn-print" style="background:#27ae60;">Download Registration Form</button>
                    </div>
                </div>

            </div>

        <?php elseif (!$is_logged_in): ?>
            
            <div class="form-container">
                <form method="POST">
                    <h2>TUP Admission Application Form</h2>
                    <p>Complete the form below to register and submit your application.</p>

                    <div class="app-id-display" style="background:#f8f9fa; padding:15px; border-left:4px solid #8b0000; margin-bottom:20px;">
                        <strong>Application ID:</strong> <span id="gen-id" style="color:#8b0000; font-size:18px;">Generating...</span>
                        <input type="hidden" name="application_id" id="input-app-id">
                        <small style="display:block; margin-top:5px; color:#666;">(This will be your Username)</small>
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
                                <select name="gender" required><option value="male">Male</option><option value="female">Female</option></select>
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
                        <div class="form-group"><label>Previous School</label><input type="text" name="previous_school" required></div>
                        <div class="form-group"><label>1st Choice</label><select name="course_1" required><option>BS Civil Engineering</option><option>BS Electrical Engineering</option><option>BS Mechanical Engineering</option><option>BS Info Tech</option></select></div>
                        <div class="form-group"><label>2nd Choice</label><select name="course_2"><option>BS Civil Engineering</option><option>BS Electrical Engineering</option><option>BS Info Tech</option></select></div>
                        <div class="form-group"><label>3rd Choice</label><select name="course_3"><option>BTVTEd</option><option>BS Industrial Tech</option></select></div>
                    </fieldset>

                    <button type="submit" name="submit_application" class="submit-btn">Submit Application</button>
                </form>
            </div>
        <?php endif; ?>

    </main>

    <script src="../static/admission.js"></script>
    <script src="../static/header.js"></script>

    <script>
        function switchStep(stepNum) {
            const steps = document.querySelectorAll('.step-box');
            const clickedStep = steps[stepNum - 1];

            if (clickedStep.classList.contains('unlocked')) {
                document.querySelectorAll('.step-pane').forEach(el => el.classList.remove('active'));
                document.getElementById('step-' + stepNum).classList.add('active');
            }
        }
    </script>
</body>
</html>