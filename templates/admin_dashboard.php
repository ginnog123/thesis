<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$host = "localhost"; $dbname = "tup_system"; $dbuser = "root"; $dbpass = "";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch Stats
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM admission_applications")->fetchColumn(),
        'registered' => $pdo->query("SELECT COUNT(*) FROM admission_applications WHERE status IN ('Registered', 'Exam Status')")->fetchColumn(),
        'exam_schedule' => $pdo->query("SELECT COUNT(*) FROM admission_applications WHERE status='Exam Schedule'")->fetchColumn(),
        'document_checking' => $pdo->query("SELECT COUNT(*) FROM admission_applications WHERE status='Document Checking'")->fetchColumn(),
        'enrolled' => $pdo->query("SELECT COUNT(*) FROM admission_applications WHERE status='Enrolled'")->fetchColumn()
    ];

    $stmt = $pdo->query("SELECT * FROM admission_applications ORDER BY applied_at DESC");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // College options for filter dropdown
    $collegeOptions = [
        'College of Science',
        'College of Engineering',
        'College of Architecture and Fine Arts',
        'College of Industrial Education',
        'Engineering Technology (BET)',
        'Teacher Education (BTVTEd)'
    ];

    // Fetch college filters
    $stmtFilters = $pdo->query("SELECT * FROM college_filters ORDER BY college_name");
    $filters = $stmtFilters->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | TUP</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../static/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo tup .svg" alt="TUP Logo" class="admin-logo">
            <h3>TUP ADMIN</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php" class="nav-link active">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>
            <a href="analytics.php" class="nav-link">
                <i class="fa-solid fa-chart-pie"></i> Analytics
            </a>
            <a href="register_admin.php" class="nav-link">
                <i class="fa-solid fa-user-shield"></i> New Admin
            </a>
            <a href="eligibility_filters.php" class="nav-link" onclick="showFiltersSection()">
                <i class="fa-solid fa-filter"></i> Eligibility Filters
            </a>
            
            <a href="logout.php" class="nav-link logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
            </a>
        </nav>
    </aside>
    

    <main class="content-area">
        <header class="top-header">
            <div class="page-title">
                <h2>Dashboard</h2>
                <p>Welcome back, Admin</p>
            </div>
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="adminSearch" placeholder="Search by name or ID...">
            </div>
        </header>

        <section class="stats-container">
            <div class="stat-card">
                <div class="stat-icon total"><i class="fa-solid fa-users"></i></div>
                <div class="stat-info">
                    <span class="label">Total Applicants</span>
                    <span class="value"><?= $stats['total'] ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pending"><i class="fa-solid fa-clock"></i></div>
                <div class="stat-info">
                    <span class="label">Registered</span>
                    <span class="value"><?= $stats['registered'] ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon enrolled"><i class="fa-solid fa-graduation-cap"></i></div>
                <div class="stat-info">
                    <span class="label">Enrolled Students</span>
                    <span class="value"><?= $stats['enrolled'] ?></span>
                </div>
            </div>
        </section>

        <section class="table-section">
            <div class="sub-nav">
                <button class="tab-btn active" onclick="filterTable('all')">All Applicants</button>
                <button class="tab-btn" onclick="filterTable('Exam Status')">For Exam</button>
                <button class="tab-btn" onclick="filterTable('Exam Schedule')">Ongoing Exam</button>
                <button class="tab-btn" onclick="filterTable('Document Checking')">Documents</button>
                <button class="tab-btn" onclick="filterTable('Enrolled')">Enrolled</button>
            </div>

            <div id="batchActionContainer" style="display:none; margin-bottom: 8px; margin-top: 8px;">
    <button onclick="openBatchScheduleModal()" class="btn-action schedule" style="padding: 10px 15px; font-size: 14px;">
        <i class="fa-solid fa-calendar-plus"></i> Schedule Selected Applicants
    </button>
</div>

<div style="overflow-x: auto;">
    <table>
        <thead>
            <tr>
                <!-- 2. Add Select All Checkbox -->
                <th id="th-checkbox" style="display:none;">
                    <input type="checkbox" id="selectAllExams" onclick="toggleAllExams(this)">
                </th>
                <th>App ID</th>
                <th>Student Name</th>
                <th>Priority Course</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="appTableBody">
            <?php foreach ($applications as $app): ?>
            <tr data-status="<?= $app['status'] ?>">
                <!-- 3. Add Individual Checkboxes -->
                <td class="td-checkbox" style="display:none;">
                    <?php if ($app['status'] === 'Exam Status'): ?>
                        <input type="checkbox" class="batch-exam-cb" value="<?= htmlspecialchars($app['application_id']) ?>">
                    <?php endif; ?>
                </td>
                
                <td><strong><?= htmlspecialchars($app['application_id']) ?></strong></td>
                <td><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></td>
                            <td><?= htmlspecialchars($app['course_1']) ?></td>
                            <td>
                                <span class="badge badge-<?= strtolower(str_replace(' ', '-', $app['status'])) ?>">
                                    <?= htmlspecialchars($app['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($app['status'] === 'Registered' || $app['status'] === 'Exam Status'): ?>
                                    <button type="button" class="btn-action view" title="View info" onclick="openPendingInfo(this)"
                                        data-app-id="<?= htmlspecialchars($app['application_id'] ?: '-') ?>"
                                        data-first-name="<?= htmlspecialchars($app['first_name'] ?: '-') ?>"
                                        data-last-name="<?= htmlspecialchars($app['last_name'] ?: '-') ?>"
                                        data-date-of-birth="<?= htmlspecialchars($app['date_of_birth'] ?: '-') ?>"
                                        data-gender="<?= htmlspecialchars($app['gender'] ?: '-') ?>"
                                        data-gwa="<?= htmlspecialchars($app['final_gwa'] ?: '-') ?>"
                                        data-strand="<?= htmlspecialchars($app['strand'] ?: '-') ?>"
                                        data-email="<?= htmlspecialchars($app['email'] ?: '-') ?>"
                                        data-phone-number="<?= htmlspecialchars($app['phone_number'] ?: '-') ?>"
                                        data-address="<?= htmlspecialchars($app['address'] ?: '-') ?>"
                                        data-course1="<?= htmlspecialchars($app['course_1'] ?: '-') ?>"
                                        data-course2="<?= htmlspecialchars($app['course_2'] ?: '-') ?>"
                                        data-course3="<?= htmlspecialchars($app['course_3'] ?: '-') ?>"
                                        data-previous-school="<?= htmlspecialchars($app['previous_school'] ?: '-') ?>"
                                        data-status="<?= htmlspecialchars($app['status'] ?: '-') ?>"
                                        data-applied-at="<?= htmlspecialchars($app['applied_at'] ?: '-') ?>">
                                        <i class="fa-solid fa-eye"></i> View Info
                                    </button>
                                    <?php if ($app['status'] === 'Exam Status'): ?>
                                        <button onclick="openScheduleModal('<?= $app['application_id'] ?>', '<?= htmlspecialchars($app['first_name'].' '.$app['last_name']) ?>')" class="btn-action schedule" style="margin-left: 8px;">
                                            <i class="fa-solid fa-calendar-plus"></i> Schedule Exam
                                        </button>
                                    <?php endif; ?>
                                <?php elseif ($app['status'] === 'Exam Schedule'): ?>
                                    <form action="update_status.php" method="POST" style="display:flex; gap:5px;">
                                        <input type="hidden" name="app_id" value="<?= $app['application_id'] ?>">
                                        <button type="submit" name="action" value="exam_passed" class="btn-action accept">Passed</button>
                                        <button type="submit" name="action" value="exam_failed" class="btn-action reject">Failed</button>
                                    </form>
                                <?php elseif ($app['status'] === 'Document Checking'): ?>
                                    <button onclick="viewDocuments('<?= $app['application_id'] ?>', '<?= htmlspecialchars($app['first_name'].' '.$app['last_name']) ?>')" class="btn-action verify">
                                        <i class="fa-solid fa-images"></i> View Docs
                                    </button>
                                <?php elseif ($app['status'] === 'Enrolled'): ?>
                                    <span style="color:var(--success-green); font-size:12px; font-weight:bold;">Completed</span>
                                <?php else: ?>
                                    <span style="color:#aaa;">--</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="filtersSection" class="table-section" style="display:none;">
            <h3>Eligibility Filters</h3>
            <button class="btn-primary" onclick="openAddFilterModal()">Add New Filter</button>
            <div style="overflow-x: auto; margin-top:20px;">
                <table>
                    <thead>
                        <tr>
                            <th>College</th>
                            <th>Min GWA</th>
                            <th>Allowed Strands</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filters as $filter): ?>
                        <tr>
                            <td><?= htmlspecialchars($filter['college_name']) ?></td>
                            <td><?= htmlspecialchars($filter['min_gwa']) ?></td>
                            <td><?= htmlspecialchars($filter['allowed_strands']) ?></td>
                            <td>
                                <button onclick="editFilter(<?= $filter['id'] ?>, '<?= htmlspecialchars($filter['college_name']) ?>', <?= $filter['min_gwa'] ?>, '<?= htmlspecialchars($filter['allowed_strands']) ?>')" class="btn-action view">Edit</button>
                                <button onclick="deleteFilter(<?= $filter['id'] ?>)" class="btn-action reject">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div id="checklistModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin:0; color:white;">Verify Documents</h3>
                <span class="close-modal" onclick="closeChecklist()" style="color:white;">&times;</span>
            </div>
            <div class="modal-body">
                <p style="margin-bottom:15px; color:var(--text-muted);">
                    Applicant: <strong id="modalStudentName" style="color:var(--text-main);"></strong><br>
                    <small>ID: <span id="modalAppId" style="color:var(--primary-accent);"></span></small>
                </p>

                <form action="update_status.php" method="POST" id="checklistForm">
                    <input type="hidden" name="app_id" id="formAppId">
                    <input type="hidden" name="action" value="verify_docs">
                    <input type="hidden" name="missing_list" id="missingListInput">
                    
                    <div class="checklist-box">
                        <label class="check-item" style="display:flex; gap:10px;">
                            <input type="checkbox" name="docs[]" value="Form 138" class="doc-check" onchange="updateModalBtn()">
                            <span>Form 138 (Report Card)</span>
                        </label>
                        <label class="check-item" style="display:flex; gap:10px;">
                            <input type="checkbox" name="docs[]" value="Good Moral" class="doc-check" onchange="updateModalBtn()">
                            <span>Certificate of Good Moral</span>
                        </label>
                        <label class="check-item" style="display:flex; gap:10px;">
                            <input type="checkbox" name="docs[]" value="Birth Certificate" class="doc-check" onchange="updateModalBtn()">
                            <span>PSA Birth Certificate</span>
                        </label>
                        <label class="check-item" style="display:flex; gap:10px;">
                            <input type="checkbox" name="docs[]" value="ID Picture" class="doc-check" onchange="updateModalBtn()">
                            <span>2x2 ID Picture</span>
                        </label>
                    </div>

                    <button type="submit" id="enrollBtn" class="btn-submit full-enroll" style="width:100%; border:none; color:white; font-weight:bold; cursor:pointer;">Enroll Student</button>
                </form>
            </div>
        </div>
    </div>

    <div id="pendingInfoModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin:0; color:white;">Applicant Info</h3>
                <span class="close-modal" onclick="closePendingInfo()" style="color:white;">&times;</span>
            </div>
            <div class="modal-body">
                <div class="profile-layout">
                    <div class="avatar-section">
                        <div class="large-avatar" id="infoAvatar"></div>
                        <h3 id="infoStudentName"></h3>
                    </div>
                    <div class="info-grid">
                        <div class="info-group"><label>Application ID</label><p id="infoAppId"></p></div>
                        <div class="info-group"><label>Status</label><p id="infoStatus"></p></div>
                        <div class="info-group"><label>Email</label><p id="infoEmail"></p></div>
                        <div class="info-group"><label>Phone</label><p id="infoPhoneNumber"></p></div>
                        <div class="info-group"><label>Birth Date</label><p id="infoDateOfBirth"></p></div>
                        <div class="info-group"><label>Gender</label><p id="infoGender"></p></div>
                        <div class="info-group"><label>SHS Strand</label><p id="infoStrand"></p></div>
                        <div class="info-group"><label>Final GWA</label><p id="infoGwa"></p></div>
                        <div class="info-group full-width"><label>Address</label><p id="infoAddress"></p></div>
                        <div class="info-group full-width"><label>Previous School</label><p id="infoPreviousSchool"></p></div>
                        <div class="info-group full-width highlight-bg"><label>Priority Program</label><p id="infoCourse1"></p></div>
                        <div class="info-group"><label>Course 2</label><p id="infoCourse2"></p></div>
                        <div class="info-group"><label>Course 3</label><p id="infoCourse3"></p></div>
                        <div class="info-group"><label>Applied At</label><p id="infoAppliedAt"></p></div>
                    </div>
                </div>

                <div class="status-box success" style="margin-top:20px; border-color: rgba(39, 174, 96, 0.3); background: rgba(39, 174, 96, 0.08); color: #27ae60;">
                    <i class="fa-solid fa-circle-check"></i>
                    <div>
                        <h4>Registration Complete</h4>
                        <p style="margin: 10px 0 0 0; font-size: 0.95rem; color: #fff;">This student has successfully registered and already passed the first step. No admin approval is required at this stage.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../static/admin.js"></script>
    <script>
        // Update your existing filterTable function to show/hide the batch checkboxes
            function filterTable(status) {
                const tabs = document.querySelectorAll('.tab-btn');
                tabs.forEach(tab => {
                    tab.classList.remove('active');
                    if (tab.getAttribute('onclick').includes(`'${status}'`)) {
                        tab.classList.add('active');
                    }
                });

                const rows = document.querySelectorAll('#appTableBody tr');
                rows.forEach(row => {
                    const rowStatus = row.getAttribute('data-status');
                    if (status === 'all' || rowStatus === status) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                        // Uncheck hidden rows
                        const cb = row.querySelector('.batch-exam-cb');
                        if (cb) cb.checked = false; 
                    }
                });

                // Show batch features ONLY when "For Exam" is selected
                const isExamTab = (status === 'Exam Status');
                document.getElementById('batchActionContainer').style.display = isExamTab ? 'block' : 'none';
                document.getElementById('th-checkbox').style.display = isExamTab ? 'table-cell' : 'none';
                document.querySelectorAll('.td-checkbox').forEach(td => td.style.display = isExamTab ? 'table-cell' : 'none');
                document.getElementById('selectAllExams').checked = false;
            }

                    function toggleAllExams(masterCheckbox) {
                        const checkboxes = document.querySelectorAll('.batch-exam-cb');
                        let checkedCount = 0;
                        let totalVisible = 0;

                        checkboxes.forEach(cb => {
                           
                            if (cb.closest('tr').style.display !== 'none') {
                                totalVisible++;
                                if (masterCheckbox.checked) {
                                    if (checkedCount < 20) {
                                        cb.checked = true;
                                        checkedCount++;
                                    } else {
                                        cb.checked = false; 
                                    }
                                } else {
                                    cb.checked = false;
                                }
                            }
                        });

                    // Notify the admin if the table had more than 20 applicants and got capped
                    if (masterCheckbox.checked && totalVisible > 20) {
                        document.getElementById('warningModalMessage').innerText = 'To prevent overloading the schedule, only the first 20 applicants were selected.';
                        document.getElementById('warningModal').style.display = 'flex';
                    }
                }

            // Open Modal with multiple IDs (Enforce the 20 limit)
            function openBatchScheduleModal() {
                const selectedCbs = document.querySelectorAll('.batch-exam-cb:checked');
                
                // Check if 0 are selected
                if (selectedCbs.length === 0) {
                    document.getElementById('warningModalMessage').innerText = 'Please select at least one applicant to schedule before proceeding.';
                    document.getElementById('warningModal').style.display = 'flex';
                    return;
                }

                // Check if more than 20 are selected manually
                if (selectedCbs.length > 20) {
                    document.getElementById('warningModalMessage').innerText = 'You can only schedule a maximum of 20 applicants per batch. Please uncheck some applicants.';
                    document.getElementById('warningModal').style.display = 'flex';
                    return;
                }

                const selectedIds = Array.from(selectedCbs).map(cb => cb.value);
                
                document.getElementById('schedStudentName').innerText = selectedIds.length + " Selected Applicant(s)";
                document.getElementById('schedAppId').value = selectedIds.join(',');
                
                document.getElementById('scheduleModal').style.display = 'flex';
            }

            function closeWarningModal() {
                document.getElementById('warningModal').style.display = 'none';
            }
                    function openPendingInfo(button) {
            const modal = document.getElementById('pendingInfoModal');
            const data = button.dataset;
            const fullName = data.firstName + ' ' + data.lastName;
            document.getElementById('infoStudentName').innerText = fullName;
            document.getElementById('infoAvatar').innerText = data.firstName.charAt(0).toUpperCase();
            document.getElementById('infoAppId').innerText = data.appId;
            document.getElementById('infoStatus').innerText = data.status;
            document.getElementById('infoEmail').innerText = data.email;
            document.getElementById('infoPhoneNumber').innerText = data.phoneNumber;
            document.getElementById('infoDateOfBirth').innerText = data.dateOfBirth;
            document.getElementById('infoGender').innerText = data.gender;
            document.getElementById('infoGwa').innerText = data.gwa;
            document.getElementById('infoStrand').innerText = data.strand;
            document.getElementById('infoCourse1').innerText = data.course1;
            document.getElementById('infoCourse2').innerText = data.course2;
            document.getElementById('infoCourse3').innerText = data.course3;
            document.getElementById('infoAddress').innerText = data.address;
            document.getElementById('infoPreviousSchool').innerText = data.previousSchool;
            document.getElementById('infoAppliedAt').innerText = data.appliedAt;
            modal.style.display = 'flex';
        }

        function closePendingInfo() {
            document.getElementById('pendingInfoModal').style.display = 'none';
        }

        function viewDocuments(appId, name) {
            fetch('get_documents.php?app_id=' + appId)
                .then(response => response.json())
                .then(data => {
                    const modal = document.getElementById('documentsModal');
                    document.getElementById('docStudentName').innerText = name;
                    const container = document.getElementById('documentsContainer');
                    container.innerHTML = '';

                    const docs = [
                        { key: 'doc_form138', label: 'Form 138', subtitle: 'High School Report Card' },
                        { key: 'doc_moral', label: 'Certificate of Good Moral', subtitle: 'Moral Character Document' },
                        { key: 'doc_birthcert', label: 'PSA Birth Certificate', subtitle: 'Birth Certificate' },
                        { key: 'doc_idpic', label: '2x2 ID Picture', subtitle: 'Recent ID Photo' }
                    ];

                    docs.forEach(doc => {
                        if (!data[doc.key]) return;
                        const status = data[doc.key + '_verification'] || 'pending';
                        const ocrText = data[doc.key + '_ocr_text'] || '';
                        const statusClass = status === 'ocr_passed' ? 'status-badge success' : status === 'ocr_review' ? 'status-badge warning' : 'status-badge pending';
                        
                        let statusLabel = status.replace(/_/g, ' ').toUpperCase();
                        let ocrDisplay = '';
                        if (ocrText) {
                            ocrDisplay = `<div class="ocr-snippet"><strong>Detection Signal:</strong> ${ocrText.replace(/\n/g, ' ').substring(0, 300)}${ocrText.length > 300 ? '...' : ''}</div>`;
                        } else {
                            if (status === 'ocr_passed') {
                                ocrDisplay = `<div class="ocr-snippet ocr-fallback"><strong>✓ Document Type Verified</strong> by filename or image analysis</div>`;
                            } else {
                                ocrDisplay = `<div class="ocr-snippet ocr-fallback"><strong>⚠ Requires Review</strong> – Unable to auto-detect document type. Please verify manually.</div>`;
                            }
                        }

                        const item = document.createElement('div');
                        item.className = 'doc-review-item';
                        item.innerHTML = `
                            <div class="doc-review-header">
                                <div>
                                    <h4>${doc.label}</h4>
                                    <p class="doc-subtitle">${doc.subtitle}</p>
                                </div>
                                <span class="${statusClass}">${statusLabel}</span>
                            </div>
                            <div class="doc-image-wrapper">
                                <img src="${data[doc.key]}" alt="${doc.label}" />
                            </div>
                            ${ocrDisplay}
                        `;
                        container.appendChild(item);
                    });

                    modal.dataset.appId = appId;
                    modal.style.display = 'flex';
                });
        }

        function closeDocuments() {
            document.getElementById('documentsModal').style.display = 'none';
        }

        function proceedApplication() {
            const appId = document.getElementById('documentsModal').dataset.appId;
            const statuses = Array.from(document.querySelectorAll('#documentsContainer .status-badge'))
                .map(badge => badge.classList.contains('success') ? 'ocr_passed' : badge.classList.contains('warning') ? 'ocr_review' : 'pending');

            if (statuses.some(status => status !== 'ocr_passed')) {
                alert('Cannot proceed: one or more documents are not fully OCR-verified. Please review the documents before enrolling.');
                return;
            }

            if (confirm('Are you sure you want to proceed this application to Enrolled?')) {
                fetch('update_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'app_id=' + appId + '&action=proceed'
                })
                .then(response => response.text())
                .then(result => {
                    if (result.trim() === 'ok') {
                        closeDocuments();
                        location.reload(); // Refresh to update status
                    } else {
                        alert('This application cannot be enrolled because not all documents have passed OCR verification.');
                    }
                });
            }
        }

        function openScheduleModal(appId, name) {
            document.getElementById('schedStudentName').innerText = name;
            document.getElementById('schedAppId').value = appId;
            document.getElementById('scheduleModal').style.display = 'flex';
        }

        function closeScheduleModal() {
            document.getElementById('scheduleModal').style.display = 'none';
        }
    </script>

    <script>
        function showFiltersSection() {
            document.querySelectorAll('.table-section').forEach(sec => sec.style.display = 'none');
            document.getElementById('filtersSection').style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
        }

        function openAddFilterModal() {
            document.getElementById('filterModalTitle').innerText = 'Add New Filter';
            document.getElementById('filterForm').action = 'update_status.php';
            document.getElementById('filterForm').querySelector('input[name="action"]').value = 'add_filter';
            document.getElementById('filterId').value = '';
            document.getElementById('collegeName').value = '';
            document.getElementById('minGwa').value = '';
            document.getElementById('allowedStrands').value = '';
            document.getElementById('filterModal').style.display = 'flex';
        }

        function editFilter(id, college, gwa, strands) {
            document.getElementById('filterModalTitle').innerText = 'Edit Filter';
            document.getElementById('filterForm').querySelector('input[name="action"]').value = 'update_filter';
            document.getElementById('filterId').value = id;
            document.getElementById('collegeName').value = college;
            document.getElementById('minGwa').value = gwa;
            document.getElementById('allowedStrands').value = strands;
            document.getElementById('filterModal').style.display = 'flex';
        }

        function closeFilterModal() {
            document.getElementById('filterModal').style.display = 'none';
        }

        function deleteFilter(id) {
            if (confirm('Are you sure you want to delete this filter?')) {
                fetch('update_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=delete_filter&filter_id=' + id
                })
                .then(response => response.text())
                .then(() => location.reload());
            }
        }
        
    </script>
    <!-- Custom Warning Modal -->
            <div id="warningModal" class="modal-backdrop" style="display:none; z-index: 9999;">
                <div class="modal-dialog modal-card modal-card--small">
                    <div class="modal-header" style="background: linear-gradient(135deg, #2F4156, #f1a94e); padding: 20px;">
                        <div>
                            <span class="modal-caption" style="color: rgba(255,255,255,0.9);">Notice</span>
                            <h3 style="color: white; margin:0; font-size: 1.2rem;">Action Required</h3>
                        </div>
                        <span class="close-modal" onclick="closeWarningModal()" style="color: white;">&times;</span>
                    </div>
                    <div class="modal-body" style="text-align: center; padding: 40px 20px;">
                        <i class="fa-solid fa-triangle-exclamation" style="font-size: 48px; color: #f1a94e; margin-bottom: 20px;"></i>
                        <p id="warningModalMessage" style="font-size: 1.1rem; color: var(--text-main); margin: 0;">Please select at least one applicant to schedule.</p>
                    </div>
                    <div class="modal-footer" style="justify-content: center;">
                        <button type="button" class="btn-primary" onclick="closeWarningModal()" style="width: 50%;">Understood</button>
                    </div>
                </div>
            </div>

    <!-- Documents Modal -->
    <div id="documentsModal" class="modal-backdrop" style="display:none;">
        <div class="modal-dialog modal-card">
            <div class="modal-header">
                <div>
                    <span class="modal-caption">Document Review</span>
                    <h3>Documents for <span id="docStudentName"></span></h3>
                </div>
                <span class="close-modal" onclick="closeDocuments()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="documentsContainer" class="documents-preview"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeDocuments()">Close</button>
                <button type="button" class="btn-primary" onclick="proceedApplication()">Proceed</button>
            </div>
        </div>
    </div>

    <!-- Schedule Modal -->
    <div id="scheduleModal" class="modal-backdrop" style="display:none;">
        <div class="modal-dialog modal-card modal-card--small">
            <div class="modal-header">
                <div>
                    <span class="modal-caption">Exam Scheduling</span>
                    <h3>Schedule Exam for <span id="schedStudentName"></span></h3>
                </div>
                <span class="close-modal" onclick="closeScheduleModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="scheduleForm" method="POST" action="update_status.php" class="admin-schedule-form">
                    <input type="hidden" name="app_id" id="schedAppId">
                    <input type="hidden" name="action" value="set_exam">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Exam Date</label>
                            <input type="date" name="exam_date" required>
                        </div>
                        <div class="form-group">
                            <label>Exam Time</label>
                            <input type="time" name="exam_time" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Venue</label>
                        <input type="text" name="exam_venue" placeholder="e.g., TUP Manila Auditorium" required>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="exam_notes" rows="3" placeholder="Additional instructions or details"></textarea>
                    </div>
                    <div class="modal-footer modal-footer--space">
                        <button type="button" class="btn-secondary" onclick="closeScheduleModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Schedule Exam</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add/Edit Filter Modal -->
    <div id="filterModal" class="modal-backdrop" style="display:none;">
        <div class="modal-dialog modal-card modal-card--small">
            <div class="modal-header">
                <div>
                    <span class="modal-caption">Eligibility Filter</span>
                    <h3 id="filterModalTitle">Add New Filter</h3>
                </div>
                <span class="close-modal" onclick="closeFilterModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="filterForm" method="POST" action="update_status.php">
                    <input type="hidden" name="action" value="add_filter">
                    <input type="hidden" name="filter_id" id="filterId">
                    <div class="form-group">
                        <label>College Name</label>
                        <select name="college_name" id="collegeName" required>
                            <option value="" selected disabled>-- Select College --</option>
                            <?php foreach ($collegeOptions as $collegeOption): ?>
                                <option value="<?= htmlspecialchars($collegeOption) ?>"><?= htmlspecialchars($collegeOption) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Minimum GWA</label>
                        <input type="number" name="min_gwa" id="minGwa" step="0.01" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label>Allowed Strands (comma-separated)</label>
                        <input type="text" name="allowed_strands" id="allowedStrands" placeholder="e.g., STEM,ABM" required>
                    </div>
                    <div class="modal-footer modal-footer--space">
                        <button type="button" class="btn-secondary" onclick="closeFilterModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Save Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

