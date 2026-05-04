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
    <!-- Main Admin CSS (Sidebar, Layout, Colors) -->
    <link rel="stylesheet" href="../static/admin.css">
    
    <!-- ISOLATED CSS FOR ELIGIBILITY FILTERS PAGE -->
    <style>
        /* --- Add Filter Button --- */
        .ef-btn-add {
            background: var(--success-green, #27ae60);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .ef-btn-add:hover {
            background: #219653;
            transform: translateY(-2px);
        }

        /* --- Table Actions --- */
        .ef-action-cell {
            white-space: nowrap; 
        }
        
        .ef-action-form {
            margin: 0;
            display: inline-block;
            vertical-align: middle;
            margin-left: 8px;
        }

        .ef-btn {
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: white;
            transition: transform 0.2s, opacity 0.2s;
        }
        .ef-btn:hover {
            transform: translateY(-1px);
            opacity: 0.9;
        }
        .ef-btn-edit {
            background-color: #4a6fa5;
        }
        .ef-btn-delete {
            background-color: #e74c3c;
        }

        /* --- Isolated Modal Styling --- */
        .ef-modal-backdrop {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            display: flex;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }

        .ef-modal-dialog {
            background: var(--bg-card, #567C8D);
            width: 100%;
            max-width: 500px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
            animation: efSlideDown 0.3s ease;
        }

        @keyframes efSlideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .ef-modal-header {
            background: rgba(255, 255, 255, 0.08);
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .ef-modal-header h3 {
            margin: 0;
            color: white;
            font-size: 1.25rem;
        }

        .ef-close-modal {
            color: rgba(255,255,255,0.6);
            font-size: 24px;
            cursor: pointer;
            transition: color 0.2s;
        }
        .ef-close-modal:hover { color: white; }

        .ef-modal-body {
            padding: 25px;
        }

        .ef-form-group {
            margin-bottom: 18px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .ef-form-group label {
            color: white;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .ef-form-group input, 
        .ef-form-group select {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.1);
            color: white;
            font-family: inherit;
            box-sizing: border-box;
        }

        .ef-form-group input:focus, 
        .ef-form-group select:focus {
            outline: none;
            border-color: var(--success-green, #27ae60);
        }

        .ef-form-group select option {
            background: var(--bg-body, #2F4156);
            color: white;
        }

        .ef-modal-footer {
            margin-top: 25px;
            display: flex;
            justify-content: flex-end;
        }

        .ef-btn-save {
            background: var(--success-green, #27ae60);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, background 0.2s;
        }
        .ef-btn-save:hover {
            background: #219653;
            transform: translateY(-2px);
        }

        /* --- Fix Table Column Spacing --- */
        .table-section table th:nth-child(1),
        .table-section table td:nth-child(1) { width: 35%; } /* College */

        .table-section table th:nth-child(2),
        .table-section table td:nth-child(2) { width: 15%; } /* Min GWA */

        .table-section table th:nth-child(3),
        .table-section table td:nth-child(3) { width: 30%; } /* Allowed Strands */

        .table-section table th:nth-child(4),
        .table-section table td:nth-child(4) { width: 20%; text-align: right; } /* Actions */

        /* --- Strand Checkbox Grid UI --- */
        .ef-checkbox-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr; /* 3 columns */
            gap: 12px;
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .ef-checkbox-grid label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: white;
            font-size: 0.85rem;
            cursor: pointer;
            font-weight: normal;
        }

        .ef-checkbox-grid input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin: 0;
            accent-color: var(--success-green, #27ae60);
            cursor: pointer;
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

        <!-- NAMESPACED ADD BUTTON -->
        <div>
            <button class="ef-btn-add" onclick="openAddModal()">
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
                            <td class="ef-action-cell">
                                <!-- NAMESPACED EDIT BUTTON -->
                                <button class="ef-btn ef-btn-edit" onclick='openEditModal(<?= json_encode($f) ?>)'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                
                                <!-- NAMESPACED DELETE BUTTON AND FORM -->
                                <form action="update_status.php" method="POST" class="ef-action-form" onsubmit="return confirm('Are you sure you want to delete the filter for <?= htmlspecialchars($f['college_name']) ?>?');">
                                    <input type="hidden" name="action" value="delete_filter">
                                    <input type="hidden" name="filter_id" value="<?= $f['id'] ?>">
                                    <button type="submit" class="ef-btn ef-btn-delete">
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

<!-- NAMESPACED MODAL HTML -->
<div id="filterModal" class="ef-modal-backdrop" style="display:none;">
    <div class="ef-modal-dialog">
        <div class="ef-modal-header">
            <h3 id="filterModalTitle">Manage Filter</h3>
            <span class="ef-close-modal" onclick="closeFilterModal()">&times;</span>
        </div>
        <div class="ef-modal-body">
            <form id="filterForm" method="POST" action="update_status.php">
                <input type="hidden" name="action" id="formAction" value="add_filter">
                <input type="hidden" name="filter_id" id="filterId">
                
                <div class="ef-form-group">
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
                <div class="ef-form-group">
                    <label>Min GWA</label>
                    <input type="number" name="min_gwa" id="minGwa" step="0.01" required>
                </div>
                <div class="ef-form-group">
                    <label>Allowed Strands</label>
                    <!-- Hidden input sends the comma-separated string to PHP -->
                    <input type="hidden" name="allowed_strands" id="allowedStrands" required>
                    
                    <div class="ef-checkbox-grid">
                        <label><input type="checkbox" class="strand-cb" value="STEM" onchange="updateStrands()"> STEM</label>
                        <label><input type="checkbox" class="strand-cb" value="ABM" onchange="updateStrands()"> ABM</label>
                        <label><input type="checkbox" class="strand-cb" value="HUMSS" onchange="updateStrands()"> HUMSS</label>
                        <label><input type="checkbox" class="strand-cb" value="ICT" onchange="updateStrands()"> ICT</label>
                        <label><input type="checkbox" class="strand-cb" value="GAS" onchange="updateStrands()"> GAS</label>
                        <label><input type="checkbox" class="strand-cb" value="Other" onchange="updateStrands()"> Other</label>
                    </div>
                </div>
                <div class="ef-modal-footer">
                    <button type="submit" class="ef-btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    /* --- JS LOGIC --- */
    /* --- JS LOGIC --- */
    const modal = document.getElementById('filterModal');

    // Automatically update the hidden input whenever a checkbox is clicked
    function updateStrands() {
        const checked = Array.from(document.querySelectorAll('.strand-cb:checked')).map(cb => cb.value);
        document.getElementById('allowedStrands').value = checked.join(',');
    }

    function openAddModal() {
        document.getElementById('filterModalTitle').innerText = "Add New Filter";
        document.getElementById('formAction').value = "add_filter";
        document.getElementById('filterForm').reset();
        
        // Uncheck all boxes and clear hidden input
        document.querySelectorAll('.strand-cb').forEach(cb => cb.checked = false);
        updateStrands();
        
        modal.style.display = 'flex';
    }

    function openEditModal(data) {
        document.getElementById('filterModalTitle').innerText = "Edit Filter";
        document.getElementById('formAction').value = "update_filter"; 
        document.getElementById('filterId').value = data.id;
        document.getElementById('collegeName').value = data.college_name;
        document.getElementById('minGwa').value = data.min_gwa;
        
        // Check the correct boxes based on the database string
        const strandsArray = data.allowed_strands.split(',').map(s => s.trim());
        document.querySelectorAll('.strand-cb').forEach(cb => {
            cb.checked = strandsArray.includes(cb.value);
        });
        updateStrands(); // Sync with hidden input

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

