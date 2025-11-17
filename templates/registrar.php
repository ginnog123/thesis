<?php
// Database connection
$Thesis_Project = new mysqli("localhost", "root", "", "registrar_db");
if ($Thesis_Project->connect_error) {
    die("Database Error: " . $Thesis_Project->connect_error);
}

// Set default timezone
date_default_timezone_set('Asia/Manila');

// Main AJAX router
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'load_requests':
            load_requests($Thesis_Project);
            break;
        case 'load_document_types':
            load_document_types($Thesis_Project);
            break;
        case 'submit_request':
            submit_request($Thesis_Project);
            break;
        case 'add_document_type':
            add_document_type($Thesis_Project);
            break;
        case 'delete_document_type':
            delete_document_type($Thesis_Project);
            break;
        case 'update_status':
            update_status($Thesis_Project);
            break;
    }
    exit(); // Stop script execution
}

// --- PHP Functions ---

function load_requests($db) {
    // Base SQL query
    $sql = "SELECT * FROM registrar_requests";
    
    // Filters
    $where_clauses = [];
    $params = [];
    $types = "";

    // Search filter (by student name or request_id)
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $search_term = "%" . $_POST['search'] . "%";
        $where_clauses[] = "(student_name LIKE ? OR request_id LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "ss";
    }

    // Status filter
    if (isset($_POST['status']) && !empty($_POST['status'])) {
        $where_clauses[] = "status = ?";
        $params[] = $_POST['status'];
        $types .= "s";
    }

    // Document type filter
    if (isset($_POST['docType']) && !empty($_POST['docType'])) {
        $where_clauses[] = "document = ?";
        $params[] = $_POST['docType'];
        $types .= "s";
    }

    // Date filter
    if (isset($_POST['date']) && !empty($_POST['date'])) {
        $where_clauses[] = "DATE(date_requested) = ?";
        $params[] = $_POST['date'];
        $types .= "s";
    }

    // Append WHERE clauses if any
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    // Add ordering
    $sql .= " ORDER BY date_requested DESC";

    // Prepare and execute statement
    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $date = date_create($row['date_requested']);
            $formatted_date = date_format($date, "M d, Y h:i A");
            
            $id = htmlspecialchars($row['id']);
            $req_id = htmlspecialchars($row['request_id']);
            $student_name = htmlspecialchars($row['student_name']);
            $document = htmlspecialchars($row['document']);
            $status = htmlspecialchars($row['status']);

            echo "
            <tr class='request-row'>
              <td class='req-id-hidden' data-request-id='{$req_id}'>{$req_id}</td>
              <td>{$student_name}</td>
              <td>{$document}</td>
              <td><span class='status-tag status-{$status}'>{$status}</span></td>
              <td>{$formatted_date}</td>
              <td class='actions'>
                <button class='action-btn update-btn' onclick='showUpdateStatusPopup({$id}, \"{$status}\")'>Update Status</button>
              </td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='6'>No requests found matching your criteria.</td></tr>";
    }
    $stmt->close();
}

function load_document_types($db) {
    $sql = "SELECT * FROM document_types ORDER BY name";
    $result = $db->query($sql);
    
    // Options for forms
    $options = "<option value=''>-- Select Document --</option>";
    
    // Options for filter (includes "All")
    $filter_options = "<option value=''>All Document Types</option>";
    
    $list = "";
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = htmlspecialchars($row['id']);
            $name = htmlspecialchars($row['name']);
            
            $options .= "<option value='{$name}'>{$name}</option>";
            $filter_options .= "<option value='{$name}'>{$name}</option>";
            
            $list .= "
                <li class='doc-type-item'>
                    <span>{$name}</span>
                    <button onclick='deleteDocumentType({$id})'>&times;</button>
                </li>";
        }
    }
    echo json_encode(['options' => $options, 'filterOptions' => $filter_options, 'list' => $list]);
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

function delete_document_type($db) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id === 0) {
        echo "Invalid ID.";
        return;
    }
    $stmt = $db->prepare("DELETE FROM document_types WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "Document type deleted.";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

function update_status($db) {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    // Added "Completed" to the allowed list
    $allowed_status = ['Pending', 'Processing', 'Ready', 'Completed', 'Rejected'];

    if ($id === 0 || !in_array($status, $allowed_status)) {
        echo "Invalid data!";
        return;
    }
    $stmt = $db->prepare("UPDATE registrar_requests SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    if ($stmt->execute()) {
        echo "Status updated successfully!";
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
<title>Registrar</title>
<link rel="stylesheet" href="../static/registrar.css">
<link rel="stylesheet" href="../static/style.css">
</head>
<body>

<header id="header" class="header">
    <img src="logo tup .svg" alt="Logo" class="logo2" />
    <nav class="nav-menu">
        <a href="login.html" class="nav-item">Home</a>
        <a href="admission.html" class="nav-item">Admission Inquiries</a>
        <a href="#" class="nav-item active">Registrar</a>
        <a href="#" class="nav-item">Departments</a>
        <a href="#" class="nav-item">About TUP</a>
    </nav>
</header>

<section class="content">
  <h1>Registrar – Document Requests</h1>

  

  <!-- --- NEW FILTER BAR --- -->
  <div class="filter-container">
      <div class="filter-item">
          <label for="searchInput">Search:</label>
          <input type="text" id="searchInput" placeholder="Search by Name or Request ID...">
      </div>
      <div class="filter-item">
          <label for="statusFilter">Status:</label>
          <select id="statusFilter">
              <option value="">All Statuses</option>
              <option value="Pending">Pending</option>
              <option value="Processing">Processing</option>
              <option value="Ready">Ready</option>
              <option value="Completed">Completed</option>
              <option value="Rejected">Rejected</option>
          </select>
      </div>
      <div class="filter-item">
          <label for="docTypeFilter">Document:</label>
          <select id="docTypeFilter">
              <!-- Loaded by JS -->
          </select>
      </div>
      <div class="filter-item">
          <label for="dateFilter">Date:</label>
          <input type="date" id="dateFilter">
      </div>
      <div class="filter-item">
          <button class="action-btn clear-btn" id="clearFiltersBtn">Clear Filters</button>
      </div>
  </div>

  <div class="table-container">
    <table class="registrar-table">
      <thead>
        <tr>
          <!-- This column is hidden via CSS but used for data -->
          <th class="req-id-hidden">Request ID</th>
          <th>Student Name</th>
          <th>Document</th>
          <th>Status</th>
          <th>Date Requested</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="request-table">
        <!-- Data loaded by JS -->
      </tbody>
    </table>
  </div>
  <div class="table-actions">
      <button class="action-btn" onclick="showRequestPopup()">Submit New Request</button>
      <button class="action-btn" onclick="showManageDocsPopup()">Manage Document Types</button>
  </div>
</section>


<!-- --- POPUP MODALS --- -->

<!-- Add New Request Popup -->
<div id="addRequestPopup" class="popup-overlay" style="display:none;">
    <div class="popup-content">
      <span class="close-btn" onclick="closeRequestPopup()">&times;</span>
      <h2>Submit New Request</h2>
      <form onsubmit="event.preventDefault(); submitRequest();">
        <label for="studentName">Student Name:</label>
        <input type="text" id="studentName" placeholder="Enter student's full name" required>

        <label for="documentName">Document Type:</label>
        <select id="documentName" required>
          <!-- Options loaded by JS -->
        </select>

        <button type="submit" class="submit-btn">Submit Request</button>
      </form>
    </div>
</div>

<!-- Manage Document Types Popup -->
<div id="manageDocsPopup" class="popup-overlay" style="display:none;">
    <div class="popup-content">
      <span class="close-btn" onclick="closeManageDocsPopup()">&times;</span>
      <h2>Manage Document Types</h2>
      <form onsubmit="event.preventDefault(); addDocumentType();">
        <label for="docTypeName">New Document Name:</label>
        <input type="text" id="docTypeName" placeholder="e.g., Certification of Grades" required>
        <button type="submit" class="submit-btn">Add Type</button>
      </form>
      <hr class="popup-divider">
      <h3>Existing Document Types</h3>
      <ul id="doc-type-list" class="doc-type-list">
        <!-- List loaded by JS -->
      </ul>
    </div>
</div>

<!-- Update Status Popup -->
<div id="updateStatusPopup" class="popup-overlay" style="display:none;">
    <div class="popup-content">
      <span class="close-btn" onclick="closeUpdateStatusPopup()">&times;</span>
      <h2>Update Request Status</h2>
      <form onsubmit="event.preventDefault(); saveStatus();">
        <label for="statusSelect">New Status:</label>
        <select id="statusSelect" required>
            <option value="Pending">Pending</option>
            <option value="Processing">Processing</option>
            <option value="Ready">Ready for Pickup</option>
            <option value="Completed">Mark as Completed</option>
            <option value="Rejected">Rejected</option>
        </select>
        <input type="hidden" id="updateRequestID">
        <button type="submit" class="submit-btn">Save Status</button>
      </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="../static/registrar.js"></script>
</body>
</html>