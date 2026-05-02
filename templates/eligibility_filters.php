<?php
require 'get_filters.php'; // Ensure this provides $collegeOptions and $filters
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Eligibility Filters | TUP Admin</title>
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../static/admin.css">
    <style>
        .action-cell {
            white-space: nowrap; 
        }
        
        .action-cell .btn-action {
            display: inline-flex;
            vertical-align: middle;
        }

        .action-form {
            margin: 0;
            display: inline-block;
            vertical-align: middle;
            margin-left: 8px;
        }
    </style>
</head>
<body>

<div class="admin-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo tup .svg" alt="TUP Logo" class="admin-logo">
            <h3>TUP ADMIN</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php" class="nav-link">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>
            <a href="analytics.php" class="nav-link">
                <i class="fa-solid fa-chart-pie"></i> Analytics
            </a>
            <a href="register_admin.php" class="nav-link">
                <i class="fa-solid fa-user-shield"></i> New Admin
            </a>
            <a href="eligibility_filters.php" class="nav-link active">
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
                <h2>Eligibility Management</h2>
                <p>Define minimum GWA and allowed strands for each college.</p>
            </div>
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="adminSearch" placeholder="Search filters...">
            </div>
        </header>

        <div class="sub-nav">
            <button class="btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New Filter
            </button>
        </div>

        <section class="table-section">
            <table>
                <thead>
                    <tr>
                        <th>College</th>
                        <th>Min GWA</th>
                        <th>Allowed Strands</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="appTableBody">
                    <?php if (!empty($filters)): ?>
                        <?php foreach ($filters as $f): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($f['college_name']) ?></strong></td>
                            <td><?= htmlspecialchars($f['min_gwa']) ?></td>
                            <td><?= htmlspecialchars($f['allowed_strands']) ?></td>
                            <td class="action-cell">
                                <button class="btn-action view" onclick='openEditModal(<?= json_encode($f) ?>)'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                
                                <!-- Added Delete Form -->
                                <form action="update_status.php" method="POST" class="action-form" onsubmit="return confirm('Are you sure you want to delete the filter for <?= htmlspecialchars($f['college_name']) ?>?');">
                                    <input type="hidden" name="action" value="delete_filter">
                                    <input type="hidden" name="filter_id" value="<?= $f['id'] ?>">
                                    <button type="submit" class="btn-action reject">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>

                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;">No filters found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

<!-- INTEGRATED MODAL HTML -->
<div id="filterModal" class="modal-backdrop" style="display:none;">
    <div class="modal-dialog modal-card modal-card--small">
        <div class="modal-header">
            <h3 id="filterModalTitle">Manage Filter</h3>
            <span class="close-modal" onclick="closeFilterModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="filterForm" method="POST" action="update_status.php">
                <input type="hidden" name="action" id="formAction" value="add_filter">
                <input type="hidden" name="filter_id" id="filterId">
                
                <div class="form-group">
                    <label>College Name</label>
                    <select name="college_name" id="collegeName" required>
                        <?php 
                        /** @var array $collegeOptions */
                        foreach ($collegeOptions as $option): 
                        ?>
                            <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Min GWA</label>
                    <input type="number" name="min_gwa" id="minGwa" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Allowed Strands</label>
                    <input type="text" name="allowed_strands" id="allowedStrands" placeholder="STEM, ABM, ICT" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    /* --- INTEGRATED JS --- */
    const modal = document.getElementById('filterModal');

    function openAddModal() {
        document.getElementById('filterModalTitle').innerText = "Add New Filter";
        document.getElementById('formAction').value = "add_filter";
        document.getElementById('filterForm').reset();
        modal.style.display = 'flex';
    }

    function openEditModal(data) {
        document.getElementById('filterModalTitle').innerText = "Edit Filter";
        document.getElementById('formAction').value = "update_filter"; 
        document.getElementById('filterId').value = data.id;
        document.getElementById('collegeName').value = data.college_name;
        document.getElementById('minGwa').value = data.min_gwa;
        document.getElementById('allowedStrands').value = data.allowed_strands;
        modal.style.display = 'flex';
    }

    function closeFilterModal() {
        modal.style.display = 'none';
    }

    // Search Logic
    document.getElementById('adminSearch').addEventListener('input', function (e) {
        const term = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#appTableBody tr');
        rows.forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
        });
    });

    window.onclick = function(event) {
        if (event.target == modal) closeFilterModal();
    }
</script>

</body>
</html>