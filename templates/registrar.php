<?php
$Thesis_Project = new mysqli("localhost", "root", "", "registrar_db");
if ($Thesis_Project->connect_error) {
    die("Database Error: " . $Thesis_Project->connect_error);
}

date_default_timezone_set('Asia/Manila');

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'load_requests':
            load_requests($Thesis_Project);
            break;
        case 'update_status':
            update_status($Thesis_Project);
            break;
        case 'submit_request':
            submit_request($Thesis_Project);
            break;
        case 'load_document_types':
            load_document_types($Thesis_Project);
            break;
        case 'add_document_type':
            add_document_type($Thesis_Project);
            break;
    }
    exit();
}

function load_requests($db) {
    $sql = "SELECT * FROM registrar_requests";
    $where_clauses = [];
    $params = [];
    $types = "";
    $is_admin_view = true; 

    if (isset($_POST['docType']) && !empty($_POST['docType'])) {
        $where_clauses[] = "document = ?";
        $params[] = $_POST['docType'];
        $types .= "s";
        $is_admin_view = false; 
    }

    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $search_term = "%" . $_POST['search'] . "%";
        $where_clauses[] = "(student_name LIKE ? OR request_id LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "ss";
    }

    if (isset($_POST['status']) && !empty($_POST['status'])) {
        $where_clauses[] = "status = ?";
        $params[] = $_POST['status'];
        $types .= "s";
    }

    if (isset($_POST['date']) && !empty($_POST['date'])) {
        $where_clauses[] = "DATE(date_requested) = ?";
        $params[] = $_POST['date'];
        $types .= "s";
    }

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
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
                    <th style="border-radius: 15px 0 0 15px;">Student Name</th>
                    <th>Document</th>
                    <th>Status</th>
                    <th>Date Requested</th>';
    
    if ($is_admin_view) {
        echo '<th style="border-radius: 0 15px 15px 0;">Action</th>';
    } else {
        echo '<th style="display:none;"></th>';
    }
    
    echo '      </tr>
            </thead>
            <tbody>';

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $date = date_create($row['date_requested']);
            $formatted_date = date_format($date, "F d, Y, h:i A");
            
            $id = htmlspecialchars($row['id']);
            $student_name = htmlspecialchars($row['student_name']);
            $status = htmlspecialchars($row['status']);
            $document = htmlspecialchars($row['document']);

            echo "<tr class='request-row'>
                    <td class='student-name'>{$student_name}</td>
                    <td>{$document}</td>
                    <td><span class='status-tag status-{$status}'>{$status}</span></td>
                    <td>{$formatted_date}</td>";
            
            if ($is_admin_view) {
                echo "<td class='actions'>
                        <button class='action-btn blue-btn' onclick='showUpdateStatusPopup({$id}, \"{$status}\")'>
                           Update Status
                        </button>
                      </td>";
            } else {
                echo "<td style='display:none;'></td>";
            }

            echo "</tr>";
        }
    } else {
        $colspan = $is_admin_view ? 5 : 4;
        echo "<tr><td colspan='{$colspan}' style='text-align:center; padding: 20px;'>No requests found.</td></tr>";
    }
    echo '</tbody></table>';
    $stmt->close();
}

function update_status($db) {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $stmt = $db->prepare("UPDATE registrar_requests SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    if ($stmt->execute()) {
        echo "Status updated successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

function load_document_types($db) {
    $sql = "SELECT * FROM document_types ORDER BY name";
    $result = $db->query($sql);
    $options = "<option value=''>-- Select Document --</option>";
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $name = htmlspecialchars($row['name']);
            $options .= "<option value='{$name}'>{$name}</option>";
        }
    }
    echo json_encode(['options' => $options]);
}

function submit_request($db) {
    $student_name = $_POST['student_name'] ?? '';
    $document = $_POST['document'] ?? '';
    $request_id = "TUP-" . strtoupper(uniqid());

    if (empty($student_name) || empty($document)) {
        echo "Error: All fields are required.";
        return;
    }

    $stmt = $db->prepare("INSERT INTO registrar_requests (request_id, student_name, document, status) VALUES (?, ?, ?, 'Pending')");
    $stmt->bind_param("sss", $request_id, $student_name, $document);
    if ($stmt->execute()) {
        echo "Request submitted successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

function add_document_type($db) {
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        echo "Document name cannot be empty!";
        return;
    }
    $stmt = $db->prepare("INSERT INTO document_types (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    if ($stmt->execute()) {
        echo "Document type added successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
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
          <img src="logo tup .svg" alt="TUP Logo" class="logo2" />
      </div>
      <nav class="nav-menu">
        <a href="login.php" class="nav-item">HOME</a>
        <a href="#" class="nav-item active">ADMISSIONS</a>
        <a href="#" class="nav-item">REQUIREMENTS</a>
        <a href="#" class="nav-item">PROGRAMS</a>
        <a href="#" class="nav-item">HELP</a>
      </nav>
      
      <div class="nav-footer" onclick="toggleMenu()">
        <i class="fa-solid fa-arrow-left"></i>
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
            <button class="tab-btn" onclick="filterByTab('Transcript of Record (TOR)', this)">TRANSCRIPT OF RECORD</button>
            <button class="tab-btn" onclick="filterByTab('Certificate of Registration (COR)', this)">CERTIFICATE OF REGISTRATION</button>
            <button class="tab-btn" onclick="filterByTab('Certificate of Graduation (COG)', this)">CERTIFICATE OF GRADUATION</button>
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
        </div>

        <div id="admin-actions" class="table-actions" style="margin-bottom: 20px; text-align: right;">
            <button class="submit-btn" onclick="showRequestPopup()">Submit New Request</button>
            <button class="submit-btn" onclick="showManageDocsPopup()">Manage Documents</button>
        </div>

        <div id="table-container" class="table-body">
        </div>

    </main>

    <div id="addRequestPopup" class="popup-overlay" style="display:none;">
        <div class="popup-content">
            <span class="close-btn" onclick="closeRequestPopup()">&times;</span>
            <h2>Submit New Request</h2>
            <form onsubmit="event.preventDefault(); submitRequest();">
                <label for="studentName">Student Name:</label>
                <input type="text" id="studentName" class="gray-input" required style="width:100%; margin-bottom:15px;">

                <label for="documentName">Document Type:</label>
                <select id="documentName" class="gray-input" required style="width:100%; margin-bottom:15px;">
                </select>

                <button type="submit" class="submit-btn">Submit</button>
            </form>
        </div>
    </div>

    <div id="manageDocsPopup" class="popup-overlay" style="display:none;">
        <div class="popup-content">
            <span class="close-btn" onclick="closeManageDocsPopup()">&times;</span>
            <h2>Add Document Type</h2>
            <form onsubmit="event.preventDefault(); addDocumentType();">
                <input type="text" id="docTypeName" class="gray-input" placeholder="Document Name" required style="width:100%; margin-bottom:15px;">
                <button type="submit" class="submit-btn">Add</button>
            </form>
        </div>
    </div>

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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="../static/registrar.js"></script>
    <div class="overlay" onclick="toggleMenu()"></div>

    <script src="../static/home.js"></script>

</body>
</html>