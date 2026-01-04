<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli("localhost", "root", "", "registrar_db");
    $db->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database connection failed.");
}

date_default_timezone_set('Asia/Manila');

session_start();
$is_logged_in = isset($_SESSION['user_id']); 
$role = $_SESSION['role'] ?? 'guest';

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'load_requests':
            load_requests($db);
            break;
        case 'submit_request':
            submit_request($db);
            break;
        case 'load_document_types':
            load_document_types($db);
            break;
    }
    exit;
}

function load_requests($db) {
    $sql = "SELECT student_name, document, status, date_requested 
            FROM registrar_requests
            WHERE status != 'Completed'";

    $where = [];
    $params = [];
    $types = "";

    // DOCUMENT TAB FILTER
    if (!empty($_POST['docType'])) {
        $where[] = "document = ?";
        $params[] = $_POST['docType'];
        $types .= "s";
    }

    // SEARCH FILTER
    if (!empty($_POST['search'])) {
        $search = "%" . trim($_POST['search']) . "%";
        $where[] = "(student_name LIKE ? OR document LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $types .= "ss";
    }

    // STATUS FILTER (prevent Completed from being selected)
    if (!empty($_POST['status']) && $_POST['status'] !== "Completed") {
        $where[] = "status = ?";
        $params[] = $_POST['status'];
        $types .= "s";
    }

    // DATE FILTER
    if (!empty($_POST['date'])) {
        $where[] = "DATE(date_requested) = ?";
        $params[] = $_POST['date'];
        $types .= "s";
    }

    if (!empty($where)) {
        $sql .= " AND " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY date_requested DESC";

    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    echo '<table class="custom-table">
            <thead>
                <tr class="table-header-row">
                    <th>Student Name</th>
                    <th>Document</th>
                    <th>Status</th>
                    <th>Date Requested</th>
                </tr>
            </thead>
            <tbody>';

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr class='request-row'>
                <td>{$row['student_name']}</td>
                <td>{$row['document']}</td>
                <td><span class='status-tag status-{$row['status']}'>{$row['status']}</span></td>
                <td>" . date("F d, Y h:i A", strtotime($row['date_requested'])) . "</td>
            </tr>";
        }
    } else {
        echo "<tr>
                <td colspan='4' style='text-align:center; padding:20px;'>
                    No active requests found
                </td>
              </tr>";
    }

    echo '</tbody></table>';
}


function submit_request($db) {
    $name = $_POST['student_name'] ?? '';
    $tup_id = $_POST['tup_id'] ?? '';
    $course = $_POST['course'] ?? '';
    $email = $_POST['email'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $document = $_POST['document'] ?? '';

    if (!$name || !$tup_id || !$course || !$email || !$contact || !$document) {
        echo "All fields are required.";
        return;
    }

    $request_id = "TUP-" . strtoupper(uniqid());

    $stmt = $db->prepare(
        "INSERT INTO registrar_requests 
        (request_id, student_name, tup_id, course, email, contact, document, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')"
    );

    $stmt->bind_param(
        "sssssss",
        $request_id,
        $name,
        $tup_id,
        $course,
        $email,
        $contact,
        $document
    );

    $stmt->execute();
    echo "Request submitted successfully!";
}

function load_document_types($db) {
    $result = $db->query("SELECT name FROM document_types ORDER BY name");
    $options = "<option value=''>-- Select Document --</option>";

    while ($row = $result->fetch_assoc()) {
        $options .= "<option value='{$row['name']}'>{$row['name']}</option>";
    }

    echo json_encode(['options' => $options]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar - Admissions</title>
    <link rel="stylesheet" href="../static/registrar.css">
    <link rel="stylesheet" href="../static/style.css">
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
        
        <div class="top-tabs">
                <button class="tab-btn active" onclick="filterByTab('', this)">Registrar</button>
                <button class="tab-btn" onclick="filterByTab('Transcript of Record (TOR)', this)">Transcript of Record</button>
                <button class="tab-btn" onclick="filterByTab('Certification', this)">Certification</button>
                <button class="tab-btn" onclick="filterByTab('Honorable Dismissal', this)">Honorable Dismissal</button>
                <button class="tab-btn" onclick="filterByTab('Evaluation/Checklist', this)">Evaluation/Checklist</button>
                <button class="tab-btn" onclick="filterByTab('Authentication', this)">Authentication</button>
                <button class="tab-btn" onclick="filterByTab('Lost Registration Form', this)">Lost Registration Form</button>
                <button class="tab-btn" onclick="filterByTab('Others', this)">Others</button>
            </div>

        <div class="filter-card">
            <div class="filter-group">
                <label>Search:</label>
                <input type="text" id="searchInput" class="gray-input" placeholder="Name or ID...">
            </div>
            <div class="filter-group">
                <label>Status:</label>
                 <select id="statusInput" class="gray-input">
                    <option value="">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="Processing">Processing</option>
                    <option value="Ready">Ready</option>
                    <option value="Completed">Completed</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Date:</label>
                <input type="date" id="dateInput" class="gray-input">
            </div>
            
            <button class="clear-btn" onclick="clearFilters()">Clear Filters</button>
              <button class="clear-btn" onclick="openSubmitPopup()">
    + Submit Request
  </button>
        </div>


        <div id="table-container" class="table-body">
        </div>

    </main>
    

    <div id="updateStatusPopup" class="popup-overlay" style="display:none;">
        <div class="popup-content">
            <span class="close-btn" onclick="closeUpdateStatusPopup()">&times;</span>
            <h2>Update Status</h2>
            <form onsubmit="event.preventDefault(); saveStatus();">
                <select id="statusSelect" class="gray-input" required style="width:100%; margin-bottom:15px;">
                    <option value="Pending">Pending</option>
                    <option value="Processing">Processing</option>
                    <option value="Ready">Ready</option>
                    <option value="Completed">Completed</option>
                    <option value="Rejected">Rejected</option>
                </select>
                <input type="hidden" id="updateRequestID">
                <button type="submit" class="submit-btn">Save</button>
            </form>
        </div>
    </div>
    <div id="submitPopup" class="popup-overlay" style="display:none;">
    <div class="popup-content">
        <span class="close-btn" onclick="closeSubmitPopup()">&times;</span>
        <h2>Submit Registrar Request</h2>

        <form onsubmit="event.preventDefault(); submitRegistrarRequest();">

        <input type="text" id="reqName" class="gray-input" placeholder="Full Name" required>
        <input type="text" id="reqTupId" class="gray-input" placeholder="TUP ID" required>
        <input type="text" id="reqCourse" class="gray-input" placeholder="Course" required>
        <input type="email" id="reqEmail" class="gray-input" placeholder="Email" required>
        <input type="text" id="reqContact" class="gray-input" placeholder="Contact Number" required>

        <select id="reqDocument" class="gray-input" required onchange="toggleCertInput()">
            <option value="">-- Select Document --</option>
            <option value="Transcript of Record (TOR)">Transcript of Record (TOR)</option>
            <option value="Certification">Certification</option>
            <option value="Honorable Dismissal">Honorable Dismissal</option>
            <option value="Evaluation/Checklist">Evaluation / Checklist</option>
            <option value="Authentication">Authentication</option>
            <option value="Lost Registration Form">Lost Registration Form</option>
            <option value="Others">Others</option>
        </select>

        <input type="text" id="certType" class="gray-input"
                placeholder="Certification of (e.g. Units Earned)"
                style="display:none;">

        <button type="submit" class="submit-btn" style="margin-top:10px;">
            Submit Request
        </button>
        </form>
    </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="../static/registrar.js"></script>
    <div class="overlay" onclick="toggleMenu()"></div>

    <script src="../static/home.js"></script>
    <script src="../static/header.js"></script>

</body>
</html>

