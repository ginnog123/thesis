<?php
$db = new mysqli("localhost", "root", "", "registrar_db");
if ($db->connect_errno) {
    die("Database connection failed: (" . $db->connect_errno . ") " . htmlspecialchars($db->connect_error));
}
$db->set_charset("utf8mb4");

// Update status
if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];

    $stmt = $db->prepare("UPDATE registrar_requests SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
}

$pricingData = [];
$db->query(
    "CREATE TABLE IF NOT EXISTS registrar_pricing (
        id INT AUTO_INCREMENT PRIMARY KEY,
        document_type VARCHAR(100) NOT NULL UNIQUE,
        price DECIMAL(10,2) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$defaultPrices = [
    'Transcript of Records' => 150.00,
    'Certification' => 180.00,
    'Honorable Dismissal' => 200.00,
    'Evaluation / Checklist' => 220.00,
    'Authentication' => 240.00,
    'Lost Registration Form' => 160.00,
    'Others' => 120.00,
];

$result = $db->query("SELECT document_type, price FROM registrar_pricing");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pricingData[$row['document_type']] = (float)$row['price'];
    }
}

if (empty($pricingData)) {
    $stmt = $db->prepare("INSERT INTO registrar_pricing (document_type, price) VALUES (?, ?) ON DUPLICATE KEY UPDATE price = VALUES(price)");
    foreach ($defaultPrices as $documentType => $price) {
        $stmt->bind_param('sd', $documentType, $price);
        $stmt->execute();
        $pricingData[$documentType] = $price;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Admin</title>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="../static/registrar_admin.css?v=2">
    <script>
        window.registrarPricing = <?php echo json_encode($pricingData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo tup .svg" alt="TUP Logo" class="admin-logo">
        </div>

        <a href="registrar_admin.php" class="nav-link active">
            <i class="fa-solid fa-folder-open"></i>
            Requests
        </a>

        <a href="registrar_pricing.php" class="nav-link">
            <i class="fa-solid fa-tags"></i>
            Pricing
        </a>

        <a href="home.php" class="nav-link logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>
    </aside>

    <!-- CONTENT -->
    <main class="content-area">

        <!-- TOP HEADER -->
        <div class="top-header">
            <div class="page-title">
                <h2>Registrar Requests</h2>
                <p>Manage and update document requests</p>
            </div>
        </div>

        <div class="request-filter-bar">
            <div class="filter-item">
                <label for="requestSearch">Search</label>
                <input type="text" id="requestSearch" placeholder="Search by request ID or Name"/>
            </div>
            <div class="filter-item">
                <label for="statusFilter">Status</label>
                <select id="statusFilter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="Pending">Pending</option>
                    <option value="Processing">Processing</option>
                    <option value="Ready">Ready</option>
                    <option value="Completed">Completed</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>
            <div class="filter-item">
                <label for="documentFilter">Document</label>
                <select id="documentFilter" class="filter-select">
                    <option value="">All Documents</option>
                    <option value="Transcript of Records">Transcript of Records</option>
                    <option value="Certification">Certification</option>
                    <option value="Honorable Dismissal">Honorable Dismissal</option>
                    <option value="Evaluation / Checklist">Evaluation / Checklist</option>
                    <option value="Authentication">Authentication</option>
                    <option value="Lost Registration Form">Lost Registration Form</option>
                    <option value="Others">Others</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="button" class="clear-btn" onclick="clearRequestFilters()">Clear Filters</button>
                <button type="button" class="btn-action save" onclick="openRequestFormPopup()">Submit Request</button>
            </div>
        </div>

        <div id="requestFormPopup" class="popup-overlay" style="display:none;" onclick="closeRequestFormPopup()">
            <div class="popup-content form-popup" onclick="event.stopPropagation()">
                <span class="close-btn" onclick="closeRequestFormPopup()">&times;</span>
                <div class="request-popup-card">
                    <div class="popup-header">
                        <h2>Submit Request</h2>
                        <p>Complete the details below to send a registrar request.</p>
                    </div>
                    <form id="requestForm" onsubmit="event.preventDefault(); submitRegistrarRequest();">
                        <div class="form-section">
                            <div class="section-heading">
                                <h3>Student Information</h3>
                            </div>
                            <div class="form-grid">
                                <input type="text" id="reqName" placeholder="Student Name" required>
                                <input type="text" id="reqTupId" placeholder="TUP ID" required>
                                <input type="text" id="reqCourse" placeholder="Course" required>
                                <input type="email" id="reqEmail" placeholder="Email" required>
                                <input type="text" id="reqContact" placeholder="Contact Number" required>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-heading">
                                <h3>Document Request</h3>
                            </div>
                            <div class="form-grid">
                                <select id="reqDocument" onchange="toggleCertInput()" required>
                                    <option value="">Select Document</option>
                                    <option value="Transcript of Records">Transcript of Records</option>
                                    <option value="Certification">Certification</option>
                                    <option value="Honorable Dismissal">Honorable Dismissal</option>
                                    <option value="Evaluation / Checklist">Evaluation / Checklist</option>
                                    <option value="Authentication">Authentication</option>
                                    <option value="Lost Registration Form">Lost Registration Form</option>
                                    <option value="Others">Others</option>
                                </select>
                                <input type="text" id="certType" placeholder="Certification Type" style="display:none;" />
                                <input type="text" id="otherType" placeholder="Specify other document" style="display:none;" />
                            </div>
                        </div>

                        <div class="form-section quantity-section">
                            <div class="section-heading">
                                <h3>Quantity</h3>
                            </div>
                            <div class="quantity-row">
                                <label>Document Quantity</label>
                                <div class="quantity-controls">
                                    <button type="button" onclick="changeQuantity(-1)">-</button>
                                    <input type="number" id="reqQuantity" value="1" min="1" readonly>
                                    <button type="button" onclick="changeQuantity(1)">+</button>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn-action reject" onclick="closeRequestFormPopup()">Cancel</button>
                            <button type="submit" class="btn-action save">Submit Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="table-section">
            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Student Name</th>
                        <th>Document</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>

<?php
$result = $db->query("
    SELECT * FROM registrar_requests
    WHERE status != 'Completed'
    ORDER BY date_requested DESC
");

if ($result->num_rows > 0):
while ($row = $result->fetch_assoc()):
    $badgeClass = match ($row['status']) {
        'Pending' => 'badge-pending',
        'Ready' => 'badge-enrolled',
        'Rejected' => 'badge-exam-status',
        default => 'badge-exam-status'
    };
?>
<tr>
    <td><?= htmlspecialchars($row['request_id']) ?></td>
    <td><?= htmlspecialchars($row['student_name']) ?></td>
    <td><?= htmlspecialchars($row['document']) ?></td>

    <td>
   <span class="badge badge-<?= strtolower($row['status']) ?>">
    <?= htmlspecialchars($row['status']) ?>
</span>
    </td>

    <td>
        <form method="POST" style="display:flex; gap:10px;">
            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">

            <select name="status" class="tab-btn">
                <?php
                $statuses = ["Pending", "Processing", "Ready", "Completed", "Rejected"];
                foreach ($statuses as $s):
                    $selected = ($row['status'] === $s) ? "selected" : "";
                ?>
                    <option value="<?= $s ?>" <?= $selected ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>

<button type="submit" name="update" class="btn-action save">
    <i class="fa-solid fa-check"></i>
</button>
        </form>
    </td>
</tr>
<?php endwhile; else: ?>
<tr>
    <td colspan="5" style="text-align:center; padding:30px;">
        No active requests 🎉
    </td>
</tr>
<?php endif; ?>

                </tbody>
            </table>
        </div>

    </main>
</div>

<div id="requestSummaryPopup" class="popup-overlay" style="display:none;" onclick="closeSummaryPopup()">
    <div class="popup-content summary-popup receipt-popup" onclick="event.stopPropagation()">
        <span class="close-btn" onclick="closeSummaryPopup()">&times;</span>
        <div class="receipt-header">
            <p class="receipt-brand">TUP REGISTRAR</p>
            <p class="receipt-title">Request Receipt</p>
            <p class="receipt-subtitle">Official copy for the student request</p>
        </div>

        <div class="receipt-divider"></div>

        <div class="receipt-body">
            <div class="receipt-row header">
                <span>DESCRIPTION</span>
                <span>DETAILS</span>
            </div>
            <div class="receipt-row"><span>Request ID</span><span id="summaryRequestId"></span></div>
            <div class="receipt-row"><span>Name</span><span id="summaryName"></span></div>
            <div class="receipt-row"><span>TUP ID</span><span id="summaryTupId"></span></div>
            <div class="receipt-row"><span>Course</span><span id="summaryCourse"></span></div>
            <div class="receipt-row"><span>Email</span><span id="summaryEmail"></span></div>
            <div class="receipt-row"><span>Contact</span><span id="summaryContact"></span></div>
            <div class="receipt-row"><span>Document</span><span id="summaryDocument"></span></div>
            <div class="receipt-row"><span>Quantity</span><span id="summaryQuantity"></span></div>
            <div class="receipt-row"><span>Unit Price</span><span id="summaryUnitPrice"></span></div>
            <div class="receipt-row"><span>Total Price</span><span id="summaryTotalPrice"></span></div>
            <div class="receipt-row"><span>Date Requested</span><span id="summaryDate"></span></div>
        </div>

        <div class="receipt-divider dashed"></div>

        <div class="summary-qr receipt-barcode">
            <div id="summaryQr" class="qr-container"></div>
        </div>

        <div class="receipt-footer">
            <span>CUSTOMER COPY</span>
            <span>Thank you for your request</span>
        </div>

        <button type="button" class="btn-action save" onclick="closeSummaryPopup()">Close</button>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="../static/registrar_admin.js"></script>
</body>
</html>
