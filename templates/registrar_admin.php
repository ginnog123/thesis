<?php
$db = new mysqli("localhost", "root", "", "registrar_db");
$db->set_charset("utf8mb4");

// Update status
if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];

    $stmt = $db->prepare("UPDATE registrar_requests SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
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
    <link rel="stylesheet" href="../static/registrar_admin.css">
</head>
<body>

<div class="admin-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo tup .svg" alt="TUP Logo" class="admin-logo">
        </div>

        <a href="#" class="nav-link active">
            <i class="fa-solid fa-folder-open"></i>
            Requests
        </a>

        <a href="../logout.php" class="nav-link logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            Exit
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

</body>
</html>
