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

$stmtFilters = $pdo->query("SELECT * FROM college_filters");
$eligibilityFilters = $stmtFilters->fetchAll(PDO::FETCH_ASSOC);
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
    "College of Architecture and Fine Arts" => [
        "Bachelor of Science in Architecture",
        "Bachelor of Fine Arts",
        "Bachelor in Graphics Technology - Architecture Technology",
        "Bachelor in Graphics Technology - Industrial Design",
        "Bachelor in Graphics Technology - Mechanical Drafting Technology"
    ],
    "College of Industrial Education" => [
        "BTLEd major in Information and Communication Technology",
        "BTLEd major in Home Economics",
        "BTLEd major in Industrial Arts",
        "BTVTEd major in Animation",
        "BTVTEd major in Beauty Care and Wellness",
        "BTVTEd major in Computer Programming",
        "BTVTEd major in Electrical",
        "BTVTEd major in Electronics",
        "BTVTEd major in Food Service Management",
        "BTVTEd major in Fashion and Garment",
        "BTVTEd major in Heat Ventilation & Air Conditioning",
        "Bachelor of Technical Teacher Education"
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

function getOCRText($filePath) {
    $text = '';

    if (!file_exists($filePath)) {
        return $text;
    }

    $tesseractPath = null;
    if (stripos(PHP_OS, 'WIN') === 0) {
        $where = trim(shell_exec('where tesseract 2>NUL'));
        if ($where) {
            $tesseractPath = $where;
        }
    } else {
        $which = trim(shell_exec('command -v tesseract 2>/dev/null'));
        if ($which) {
            $tesseractPath = $which;
        }
    }

    if ($tesseractPath) {
        $escaped = escapeshellarg($filePath);
        $output = [];
        $returnVar = 1;
        exec("$tesseractPath $escaped stdout 2>&1", $output, $returnVar);
        if ($returnVar === 0) {
            $text = implode("\n", $output);
        }
    }

    // If OCR extraction failed or Tesseract unavailable, use filename and basic image analysis as fallback
    if (empty($text)) {
        $filename = basename($filePath);
        $text = $filename;
        
        $info = @getimagesize($filePath);
        if ($info) {
            $text .= " " . $info[0] . "x" . $info[1] . "px";
        }
    }

    return trim(strtolower($text));
}


function detectDocumentMatch($field, $ocrText, $imagePath, $application = []) {
    $actualText = strtolower(trim($ocrText));
    $filename = strtolower(basename($imagePath));
    $actualText = trim(str_replace($filename, '', $actualText));
    $actualText = preg_replace('/[^a-z0-9\s\.\-\/]/', ' ', $actualText);

    $firstName = strtolower(trim($application['first_name'] ?? ''));
    $lastName = strtolower(trim($application['last_name'] ?? ''));
    $fullName = trim("$firstName $lastName");
    $reversedName = trim("$lastName $firstName");
    $appId = strtolower(trim($application['application_id'] ?? ''));
    $dob = $application['date_of_birth'] ?? '';
    $dobVariants = [];
    if ($dob) {
        $date = date_create($dob);
        if ($date) {
            $dobVariants = [
                strtolower($date->format('Y-m-d')),
                strtolower($date->format('m/d/Y')),
                strtolower($date->format('d/m/Y')),
                strtolower($date->format('m.d.Y')),
                strtolower($date->format('d.m.Y')),
                strtolower($date->format('F j, Y')),
                strtolower($date->format('j F Y'))
            ];
        }
    }

    $nameMatched = false;
    if ($fullName) {
        $nameMatched = strpos($actualText, $fullName) !== false || strpos($actualText, $reversedName) !== false || strpos($filename, $fullName) !== false || strpos($filename, $reversedName) !== false;
        if (!$nameMatched && $firstName && $lastName) {
            $nameMatched = strpos($actualText, $firstName) !== false && strpos($actualText, $lastName) !== false;
        }
    }

    $dobMatched = false;
    foreach ($dobVariants as $variant) {
        if ($variant && strpos($actualText, $variant) !== false) {
            $dobMatched = true;
            break;
        }
    }

    $appIdMatched = $appId && (strpos($actualText, $appId) !== false || strpos($filename, $appId) !== false);

    $keywords = [];
    $fieldKeywords = [];
    switch ($field) {
        case 'doc_form138':
            $keywords = ['form 138', 'form138', 'report card', 'general average', 'gwa', 'grades', 'secondary', 'high school', 'deped', 'school'];
            $fieldKeywords = ['form138', 'form_138', 'doc_form138', 'reportcard', 'report_card'];
            break;
        case 'doc_moral':
            $keywords = ['good moral', 'moral character', 'certificate of good moral', 'certificate', 'disciplinary', 'standing', 'conduct', 'honorable'];
            $fieldKeywords = ['goodmoral', 'doc_moral', 'moral'];
            break;
        case 'doc_birthcert':
            $keywords = ['birth certificate', 'psa', 'republic of the philippines', 'civil registry', 'registry', 'born', 'certificate of live birth', 'birthdate'];
            $fieldKeywords = ['birthcert', 'doc_birthcert', 'birth'];
            break;
        case 'doc_idpic':
            $keywords = ['student id', 'id picture', 'passport size', '2x2', 'identification', 'idpic'];
            $fieldKeywords = ['idpic', 'doc_idpic', 'passport', 'student_id'];
            break;
    }

    $textKeywordsFound = [];
    foreach ($keywords as $keyword) {
        if ($keyword && strpos($actualText, $keyword) !== false) {
            $textKeywordsFound[] = $keyword;
        }
    }
    $filenameKeywordsFound = [];
    foreach ($fieldKeywords as $keyword) {
        if ($keyword && strpos($filename, $keyword) !== false) {
            $filenameKeywordsFound[] = $keyword;
        }
    }

    if ($field === 'doc_idpic') {
        $dimensions = @getimagesize($imagePath);
        if ($dimensions) {
            $width = $dimensions[0];
            $height = $dimensions[1];
            if ($width >= 200 && $height >= 200 && $height >= $width) {
                return ['match' => true];
            }
        }
        return ['match' => false, 'reason' => 'Invalid ID format. Please upload a clear 2x2 or passport-style portrait photo.'];
    }

    $documentTypeDetected = !empty($textKeywordsFound) || !empty($filenameKeywordsFound);
    $wordCount = str_word_count($actualText);

    if ($field === 'doc_form138') {
        if ($documentTypeDetected && ($nameMatched || $wordCount >= 20)) {
            return ['match' => true];
        }
    }

    if ($field === 'doc_moral') {
        if ($documentTypeDetected && ($nameMatched || $wordCount >= 20)) {
            return ['match' => true];
        }
    }

    if ($field === 'doc_birthcert') {
        if ($documentTypeDetected && ($nameMatched || $dobMatched || $appIdMatched)) {
            return ['match' => true];
        }
        if (($nameMatched || $dobMatched) && strpos($actualText, 'birth') !== false) {
            return ['match' => true];
        }
    }

    $reason = 'Document type could not be automatically verified. ';
    if (empty(trim($actualText)) || strlen(trim($actualText)) < 15) {
        $reason .= 'No readable text was detected. ';
    }
    if (!$documentTypeDetected && !$appIdMatched) {
        $reason .= 'Document content does not match the expected type. ';
    }
    if ($field === 'doc_birthcert' && !$dobMatched) {
        $reason .= 'Birth date did not match the provided applicant information. ';
    }
    $reason .= 'Please upload a clear, legible copy of the correct document.';

    return ['match' => false, 'reason' => $reason];
}



function ensureDocumentVerificationColumns($pdo) {
    $columns = [
        'doc_form138_verification' => "VARCHAR(32) DEFAULT 'pending'",
        'doc_moral_verification' => "VARCHAR(32) DEFAULT 'pending'",
        'doc_birthcert_verification' => "VARCHAR(32) DEFAULT 'pending'",
        'doc_idpic_verification' => "VARCHAR(32) DEFAULT 'pending'",
        'doc_form138_ocr_text' => 'TEXT NULL',
        'doc_moral_ocr_text' => 'TEXT NULL',
        'doc_birthcert_ocr_text' => 'TEXT NULL',
        'doc_idpic_ocr_text' => 'TEXT NULL'
    ];

    foreach ($columns as $column => $definition) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM admission_applications LIKE ?");
        $stmt->execute([$column]);
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE admission_applications ADD COLUMN $column $definition");
        }
    }
}


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

// --- HANDLE DOCUMENTS UPLOAD ---
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_documents'])) {
    $user_id = $_SESSION['user_id'];

    ensureDocumentVerificationColumns($pdo);

    $stmt = $pdo->prepare("SELECT doc_form138, doc_moral, doc_birthcert, doc_idpic, doc_form138_verification, doc_moral_verification, doc_birthcert_verification, doc_idpic_verification, status FROM admission_applications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $currentApplication = $stmt->fetch(PDO::FETCH_ASSOC);

    $uploads = [];
    $verification = [];
    $allowed = ['jpg', 'jpeg', 'png'];
    $fields = ['doc_form138', 'doc_moral', 'doc_birthcert', 'doc_idpic'];

    foreach ($fields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
            $file = $_FILES[$field];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $new_name = $user_id . '_' . $field . '_' . time() . '.' . $ext;
                $destination = '../uploads/' . $new_name;
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $ocrText = getOCRText($destination);
                    $detectionResult = detectDocumentMatch($field, $ocrText, $destination, $currentApplication);
                    $match = $detectionResult['match'];
                    $uploads[$field] = $destination;
                    $verification[$field . '_verification'] = $match ? 'ocr_passed' : 'ocr_review';
                    $verification[$field . '_ocr_text'] = $match ? substr($ocrText, 0, 1200) : ($detectionResult['reason'] ?? 'Detection failed.');
                } else {
                    $error_msg = "Failed to upload $field.";
                }
            } else {
                $error_msg = "Invalid file type for $field.";
            }
        }
    }

    if (empty($error_msg)) {
        $stmt = $pdo->prepare("UPDATE admission_applications SET doc_form138 = ?, doc_moral = ?, doc_birthcert = ?, doc_idpic = ?, doc_form138_verification = ?, doc_moral_verification = ?, doc_birthcert_verification = ?, doc_idpic_verification = ?, doc_form138_ocr_text = ?, doc_moral_ocr_text = ?, doc_birthcert_ocr_text = ?, doc_idpic_ocr_text = ? WHERE user_id = ?");
        $stmt->execute([
            $uploads['doc_form138'] ?? $currentApplication['doc_form138'],
            $uploads['doc_moral'] ?? $currentApplication['doc_moral'],
            $uploads['doc_birthcert'] ?? $currentApplication['doc_birthcert'],
            $uploads['doc_idpic'] ?? $currentApplication['doc_idpic'],
            $verification['doc_form138_verification'] ?? $currentApplication['doc_form138_verification'],
            $verification['doc_moral_verification'] ?? $currentApplication['doc_moral_verification'],
            $verification['doc_birthcert_verification'] ?? $currentApplication['doc_birthcert_verification'],
            $verification['doc_idpic_verification'] ?? $currentApplication['doc_idpic_verification'],
            $verification['doc_form138_ocr_text'] ?? $currentApplication['doc_form138_ocr_text'],
            $verification['doc_moral_ocr_text'] ?? $currentApplication['doc_moral_ocr_text'],
            $verification['doc_birthcert_ocr_text'] ?? $currentApplication['doc_birthcert_ocr_text'],
            $verification['doc_idpic_ocr_text'] ?? $currentApplication['doc_idpic_ocr_text'],
            $user_id
        ]);

        $finalDocs = [
            'doc_form138' => $uploads['doc_form138'] ?? $currentApplication['doc_form138'],
            'doc_moral' => $uploads['doc_moral'] ?? $currentApplication['doc_moral'],
            'doc_birthcert' => $uploads['doc_birthcert'] ?? $currentApplication['doc_birthcert'],
            'doc_idpic' => $uploads['doc_idpic'] ?? $currentApplication['doc_idpic'],
        ];

        $allDocsUploaded = !empty($finalDocs['doc_form138']) && !empty($finalDocs['doc_moral']) && !empty($finalDocs['doc_birthcert']) && !empty($finalDocs['doc_idpic']);

        if ($allDocsUploaded && $currentApplication && $currentApplication['status'] !== 'Document Checking') {
            $stmt = $pdo->prepare("UPDATE admission_applications SET status = 'Document Checking' WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }

        if ($allDocsUploaded) {
            $ocrPassed = true;
            $failedDocs = [];
            foreach ($fields as $field) {
                $statusKey = $field . '_verification';
                $statusValue = $verification[$statusKey] ?? $currentApplication[$statusKey];
                if ($statusValue !== 'ocr_passed') {
                    $ocrPassed = false;
                    $failedDocs[] = $field;
                }
            }

            if ($ocrPassed) {
                $success_msg = "Documents uploaded successfully. OCR-based document recognition passed and your documents are now pending physical verification by the Admissions Office.";
            } else {
                $docNames = [
                    'doc_form138' => 'Form 138',
                    'doc_moral' => 'Certificate of Good Moral',
                    'doc_birthcert' => 'Birth Certificate',
                    'doc_idpic' => 'ID Picture'
                ];
                $failedNames = array_map(fn($d) => $docNames[$d] ?? $d, $failedDocs);
                $failedList = implode(', ', $failedNames);
                $success_msg = "Documents uploaded successfully. However, the system could not automatically verify: <strong>{$failedList}</strong>. These require manual review by the Admissions Office. Please ensure you uploaded the correct documents.";
            }
        } else {
            $uploadedCount = count(array_filter($finalDocs, fn($value) => !empty($value)));
            $remaining = 4 - $uploadedCount;
            $success_msg = "Documents uploaded successfully. Please upload the remaining {$remaining} required document(s) to continue.";
        }
    }
}

// --- HANDLE SCHEDULE REQUEST ---
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_schedule_request'])) {
    $user_id = $_SESSION['user_id'];
    $preferred_date = $_POST['preferred_date'];
    $preferred_time = $_POST['preferred_time'];
    $preferred_venue = $_POST['preferred_venue'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // For now, just show success, or save to DB if columns added
    $success_msg = "Schedule request submitted successfully. We will review your preferences.";
}

// --- HANDLE REGISTRATION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    $app_id = trim($_POST['application_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $full_name = trim($first_name . ' ' . $last_name);

    $errors = [];

    if (
        $app_id === '' ||
        $password === '' ||
        $confirm_password === '' ||
        $first_name === '' ||
        $last_name === '' ||
        empty($_POST['date_of_birth']) ||
        empty($_POST['gender']) ||
        empty($_POST['email']) ||
        empty($_POST['phone_number']) ||
        empty($_POST['address']) ||
        empty($_POST['course_1']) ||
        empty($_POST['course_2']) ||
        empty($_POST['course_3']) ||
        empty($_POST['previous_school']) ||
        empty($_POST['strand']) ||
        empty($_POST['final_gwa']) ||
        !isset($_POST['privacy_policy'])
    ) {
        $errors[] = "Please complete all required fields.";
    }

    if (!filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must include at least one uppercase letter.";
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must include at least one lowercase letter.";
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must include at least one number.";
    }

    if (!preg_match('/[!@#$%^&*(),.?\":{}|<>]/', $password)) {
        $errors[] = "Password must include at least one special character.";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        try {
        $pdo->beginTransaction();
        $stmtUser = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, 'student')");
        $stmtUser->execute([$app_id, $hashed_password, $full_name]);

        // Check eligibility filters
        $course = $_POST['course_1'];
        $gwa = (float) $_POST['final_gwa'];
        $strand = $_POST['strand'];
        
        $stmtFilter = $pdo->prepare("SELECT * FROM college_filters WHERE college_name = ?");
        $stmtFilter->execute([$course]);
        $filter = $stmtFilter->fetch(PDO::FETCH_ASSOC);
        
        // Default to Exam Status - only set to Registered if filter exists AND student doesn't meet requirements
        $initialStatus = 'Exam Status';
        if ($filter) {
            $min_gwa = (float) $filter['min_gwa'];
            $allowed_strands = $filter['allowed_strands'];
            $strands_array = array_map('trim', explode(',', $allowed_strands));
            
            // If does NOT meet requirements, set to Registered
            if ($gwa < $min_gwa || !in_array($strand, $strands_array)) {
                $initialStatus = 'Registered';
            }
        }

        $sql = "INSERT INTO admission_applications 
                (application_id, user_id, first_name, last_name, date_of_birth, gender, email, phone_number, address, course_1, course_2, course_3, previous_school, strand, final_gwa, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmtApp = $pdo->prepare($sql);
        $stmtApp->execute([$app_id, $app_id, $first_name, $last_name, $_POST['date_of_birth'], $_POST['gender'], $_POST['email'], $_POST['phone_number'], $_POST['address'], $_POST['course_1'], $_POST['course_2'], $_POST['course_3'], $_POST['previous_school'], $_POST['strand'], $_POST['final_gwa'], $initialStatus]);

        $pdo->commit();
        $success_msg = "Application Successful! Your Applicant ID is <strong>$app_id</strong>. Please <a href='login.php'>Login here</a>.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Error: " . $e->getMessage();
    }
    } else {
        $error_msg = implode(' ', $errors);
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
        elseif ($status === 'Registered') $current_step = 4;
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
                                    <div class="info-group"><label>SHS Strand</label><p><?= htmlspecialchars($application['strand'] ?? 'N/A') ?></p></div>
                                    <div class="info-group"><label>Final GWA</label><p><?= htmlspecialchars($application['final_gwa'] ?? 'N/A') ?></p></div>
                                    <div class="info-group full-width"><label>Address</label><p><?= htmlspecialchars($application['address']) ?></p></div>
                                    <div class="info-group full-width"><label>Previous School</label><p><?= htmlspecialchars($application['previous_school']) ?></p></div>
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
                                <div class="schedule-request">
                                    <p>Your examination schedule is being prepared. If you have preferred dates or times, please submit a request below.</p>
                                    <button id="requestScheduleBtn" class="btn-primary" onclick="toggleScheduleForm()">Request Preferred Schedule</button>
                                    <div id="scheduleForm" style="display:none; margin-top:20px;">
                                        <form method="POST" class="schedule-form">
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Preferred Date</label>
                                                    <input type="date" name="preferred_date" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Preferred Time</label>
                                                    <input type="time" name="preferred_time" required>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Preferred Venue (Optional)</label>
                                                <input type="text" name="preferred_venue" placeholder="e.g., TUP Manila">
                                            </div>
                                            <div class="form-group">
                                                <label>Additional Notes</label>
                                                <textarea name="notes" rows="3" placeholder="Any special requests or notes"></textarea>
                                            </div>
                                            <button type="submit" name="submit_schedule_request" class="submit-btn">Submit Request</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="step-4" class="step-pane">
                        <div class="content-card center-aligned">
                            <div class="icon-circle"><i class="fa-solid fa-file-check"></i></div>
                            <h2>Document Submission</h2>
                            <?php
                                $documentLabels = [
                                    'doc_form138' => 'Form 138 (High School Report Card)',
                                    'doc_moral' => 'Certificate of Good Moral Character',
                                    'doc_birthcert' => 'PSA Birth Certificate (Original & Photocopy)',
                                    'doc_idpic' => '2 pcs. 2x2 Recent ID Picture'
                                ];
                                $missing_docs = [];
                                $failed_detection_docs = [];
                                
                                foreach ($documentLabels as $field => $label) {
                                    if (empty($application[$field])) {
                                        $missing_docs[$field] = $label;
                                    } else {
                                        // Check if this document failed detection
                                        $verificationStatus = $application[$field . '_verification'] ?? 'pending';
                                        if ($verificationStatus === 'ocr_review') {
                                            $failureReason = $application[$field . '_ocr_text'] ?? '';
                                            if (strpos($failureReason, 'System could not') === 0 || strpos($failureReason, 'Detection failed') === 0) {
                                                $failed_detection_docs[$field] = ['label' => $label, 'reason' => $failureReason];
                                            }
                                        }
                                    }
                                }
                                $all_docs_uploaded = empty($missing_docs) && empty($failed_detection_docs);
                            ?>

                            <?php if ($all_docs_uploaded): ?>
                                <p class="lead-text">Your documents have been uploaded successfully. Please proceed to the Admissions Office to submit the original physical copies for verification.</p>
                                <div class="status-box success">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <div><h4>Documents Uploaded</h4><p>All required documents have been received electronically.</p></div>
                                </div>
                                <p><strong>Next Step:</strong> Visit the TUP Admissions Office with your original documents for final verification.</p>
                            <?php else: ?>
                                <?php if (!empty($failed_detection_docs)): ?>
                                    <div class="status-box warning" style="background: rgba(241, 169, 78, 0.1); border-color: #f1a94e; color: #f1a94e; margin: 20px 0; text-align: left;">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        <div>
                                            <h4>⚠ Detection Failed - Please Re-upload</h4>
                                            <p style="margin: 10px 0 0 0; font-size: 0.95rem;">The system could not automatically verify the following documents. Please re-upload clearer, legible copies:</p>
                                            <ul style="margin: 10px 0 0 20px; font-size: 0.9rem;">
                                                <?php foreach ($failed_detection_docs as $field => $docInfo): ?>
                                                    <li>
                                                        <strong><?= htmlspecialchars($docInfo['label']) ?></strong><br>
                                                        <small style="color: #ccc;">Reason: <?= htmlspecialchars($docInfo['reason']) ?></small>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($missing_docs)): ?>
                                    <div class="status-box pending">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        <div>
                                            <h4>Missing Documents</h4>
                                            <ul style="margin:10px 0 0 20px;">
                                                <?php foreach ($missing_docs as $missing_label): ?>
                                                    <li><?= htmlspecialchars($missing_label) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div style="background: rgba(255,255,255,0.05); border-left: 3px solid #f1a94e; padding: 15px 20px; margin: 20px 0; border-radius: 8px;">
                                    <p style="margin: 0; font-size: 0.95rem; color: #f1a94e;"><strong>📋 Upload Tips:</strong></p>
                                    <ul style="margin: 10px 0 0 20px; font-size: 0.9rem; color: #e0e0e0;">
                                        <li>Upload <strong>clear, legible scans or photos</strong> of your original documents</li>
                                        <li>The system automatically detects document type from the image content and filename</li>
                                        <li>If detection fails, the Admissions Office will manually verify your documents</li>
                                        <li>Supported formats: JPG, PNG (high resolution recommended)</li>
                                    </ul>
                                </div>

                                <form method="POST" enctype="multipart/form-data" class="documents-upload-form">
                                    <?php 
                                        $fieldsToUpload = array_merge($missing_docs, $failed_detection_docs);
                                        foreach ($fieldsToUpload as $field => $info):
                                            $label = is_array($info) ? $info['label'] : $info;
                                    ?>
                                        <div class="upload-item">
                                            <label><?= htmlspecialchars($label) ?></label>
                                            <input type="file" name="<?= $field ?>" accept="image/*" required>
                                        </div>
                                    <?php endforeach; ?>
                                    <button type="submit" name="upload_documents" class="submit-btn">Upload Documents</button>
                                </form>
                            <?php endif; ?>
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
                <form id="admission-form" method="POST" novalidate>
                    <h2>TUP Admission Application</h2>
                    <p>Complete the form below to create a secure applicant profile.</p>
                    <div id="client-error-box" class="alert-box alert-error" style="display:none; margin-bottom:20px;"></div>
                    
                    <div class="app-id-display">
                        <strong>Application ID:</strong> <span id="gen-id" class="code">Generating...</span>
                        <input type="hidden" name="application_id" id="input-app-id">
                        <small>(This will be your username)</small>
                    </div>

                    <fieldset class="password-section">
                        <legend>Account Security</legend>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Create Password</label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="password" id="password-input" autocomplete="new-password" required>
                                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password-input', this)" aria-label="Show password">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="confirm_password" id="confirm-password-input" autocomplete="new-password" required>
                                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm-password-input', this)" aria-label="Show password">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="password-policy">
                            <strong>Password policy</strong>
                            <ul>
                                <li>At least 8 characters</li>
                                <li>One uppercase letter</li>
                                <li>One lowercase letter</li>
                                <li>At least one number</li>
                                <li>At least one special character</li>
                            </ul>
                        </div>

                        <div class="strength-bar-wrapper">
                            <div class="strength-meter">
                                <span id="password-strength-label">Strength: Short</span>
                                <div class="strength-track">
                                    <div id="password-strength-bar" class="strength-bar"></div>
                                </div>
                            </div>
                            <div id="password-match-label" class="password-match-label">Passwords must match</div>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Personal Information</legend>
                        <div class="form-row">
                            <div class="form-group"><label>First Name</label><input type="text" name="first_name" required></div>
                            <div class="form-group"><label>Last Name</label><input type="text" name="last_name" required></div>
                        </div>
                        <div class="form-row" style="margin-top:10px;">
                            <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth" required></div>
                            <div class="form-group">
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
                        <div class="form-group"><label>Address</label><textarea name="address" rows="2" required></textarea></div>
                    </fieldset>

                    <fieldset>
                        <legend>Academic Preferences</legend>
                        <div class="form-group">
                            <label>Previous School</label>
                            <input type="text" name="previous_school" required>
                        </div>
                        <div class="form-group">
                            <label>SHS Strand</label>
                            <select name="strand" required onchange="filterCoursesByEligibility()">
                                <option value="" selected disabled>-- Select SHS Strand --</option>
                                <option value="STEM">STEM (Science, Technology, Engineering, Mathematics)</option>
                                <option value="ABM">ABM (Accountancy, Business, Management)</option>
                                <option value="HUMSS">HUMSS (Humanities and Social Sciences)</option>
                                <option value="ICT">ICT (Information and Communications Technology)</option>
                                <option value="GAS">GAS (General Academic Strand)</option>
                                <option value="Other">Other/Not from SHS</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Final GWA (General Weighted Average)</label>
                            <input type="number" name="final_gwa" step="0.01" min="0" max="100" placeholder="e.g., 85.50" required onchange="filterCoursesByEligibility()" oninput="filterCoursesByEligibility()">
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

                    <div class="form-group privacy-policy">
                        <h3 style="margin-bottom: 10px; color: #333; font-size: 1.2rem;">Privacy Policy</h3>
                        <div class="privacy-text">
                            This Privacy Policy explains how we collect, use, and protect your personal information when you use our website/system. We are committed to safeguarding your data in accordance with the Data Privacy Act of 2012.
                        </div>
                        <label>
                            <input type="checkbox" name="privacy_policy" required>
                            <span>I agree to the <a href="#">Privacy Policy</a></span>
                        </label>
                    </div>

                    <button type="submit" name="submit_application" class="submit-btn">Submit Application</button>
                </form>
            </div>
        <?php endif; ?>
    </main>
    

    <script>
        window.courseOfferings = <?= json_encode($course_offerings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.eligibilityFilters = <?= json_encode($eligibilityFilters, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="../static/admission.js"></script>
    <script src="../static/header.js"></script>
</body>
</html>

