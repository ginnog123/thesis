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
<html>
<head>
    <title>Registrar Admin</title>
    <link rel="stylesheet" href="../static/registrar_admin.css">
</head>
<body>

<div class="admin-header">
    Registrar Admin Dashboard
</div>

<div class="dashboard-container">

<table class="custom-table">
    <thead>
        <tr class="table-header-row">
            <th>Request ID</th>
            <th>Student Name</th>
            <th>Document</th>
            <th>Status</th>
            <th>Update</th>
        </tr>
    </thead>
    <tbody>

<?php
// Show ONLY non-completed requests
$result = $db->query("
    SELECT * FROM registrar_requests 
    WHERE status != 'Completed'
    ORDER BY date_requested DESC
");

if ($result->num_rows > 0):
while ($row = $result->fetch_assoc()):
?>
<tr class="request-row">
    <td><?= htmlspecialchars($row['request_id']) ?></td>
    <td><?= htmlspecialchars($row['student_name']) ?></td>
    <td><?= htmlspecialchars($row['document']) ?></td>

    <td>
        <span class="status-tag status-<?= $row['status'] ?>">
            <?= $row['status'] ?>
        </span>
    </td>

    <td>
        <form method="POST" class="action-form">
            <input type="hidden" name="id" value="<?= $row['id'] ?>">
            <select name="status" class="gray-input">
                <?php
                $statuses = ["Pending", "Processing", "Ready", "Completed", "Rejected"];
                foreach ($statuses as $s) {
                    $selected = ($row['status'] === $s) ? "selected" : "";
                    echo "<option value='$s' $selected>$s</option>";
                }
                ?>
            </select>
            <button type="submit" name="update" class="blue-btn">
                Save
            </button>
        </form>
    </td>
</tr>
<?php endwhile; else: ?>
<tr>
    <td colspan="5" style="text-align:center; padding:20px;">
        No active requests 🎉
    </td>
</tr>
<?php endif; ?>

    </tbody>
</table>

</div>
</body>
</html>