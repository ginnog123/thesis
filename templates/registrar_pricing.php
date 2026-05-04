<?php
$db = new mysqli("localhost", "root", "", "registrar_db");
if ($db->connect_errno) {
    die("Database connection failed: (" . $db->connect_errno . ") " . htmlspecialchars($db->connect_error));
}
$db->set_charset("utf8mb4");

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_prices'])) {
    $stmt = $db->prepare("INSERT INTO registrar_pricing (document_type, price) VALUES (?, ?) ON DUPLICATE KEY UPDATE price = VALUES(price)");
    foreach ($defaultPrices as $documentType => $price) {
        $fieldName = 'price_' . str_replace(' ', '_', strtolower($documentType));
        $submittedPrice = isset($_POST[$fieldName]) ? floatval($_POST[$fieldName]) : $price;
        $stmt->bind_param('sd', $documentType, $submittedPrice);
        $stmt->execute();
    }
    $stmt->close();
    $successMessage = 'Pricing values have been updated successfully.';
}

$prices = [];
$result = $db->query("SELECT document_type, price FROM registrar_pricing");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $prices[$row['document_type']] = $row['price'];
    }
}

foreach ($defaultPrices as $documentType => $price) {
    if (!isset($prices[$documentType])) {
        $prices[$documentType] = $price;
        $stmt = $db->prepare("INSERT INTO registrar_pricing (document_type, price) VALUES (?, ?) ON DUPLICATE KEY UPDATE price = VALUES(price)");
        $stmt->bind_param('sd', $documentType, $price);
        $stmt->execute();
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Pricing</title>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="../static/registrar_admin.css?v=2">
    <style>
        .pricing-form input[type="number"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 14px;
            color: #111827;
            background: #fff;
        }
        .pricing-form .button-row {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        .pricing-form .success-message {
            padding: 14px 18px;
            margin-bottom: 20px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            border-radius: 12px;
        }
    </style>
</head>
<body>

<div class="admin-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo tup .svg" alt="TUP Logo" class="admin-logo">
        </div>

        <a href="registrar_admin.php" class="nav-link">
            <i class="fa-solid fa-folder-open"></i>
            Requests
        </a>

        <a href="registrar_pricing.php" class="nav-link active">
            <i class="fa-solid fa-tags"></i>
            Pricing
        </a>

        <a href="home.php" class="nav-link logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            Exit
        </a>
    </aside>

    <!-- CONTENT -->
    <main class="content-area">

        <!-- TOP HEADER -->
        <div class="top-header">
            <div class="page-title">
                <h2>Registrar Pricing</h2>
                <p>Review or update current document fees.</p>
            </div>
            <div class="page-actions">
                <a href="registrar_admin.php" class="btn-action secondary">Back to Requests</a>
            </div>
        </div>

        <div class="table-section pricing-form">
            <div class="form-card">
                <h3>Document Pricing</h3>
                <p>Update pricing values and save them for the registrar requests system.</p>

                <?php if (!empty($successMessage)): ?>
                    <div class="success-message"><?php echo htmlspecialchars($successMessage); ?></div>
                <?php endif; ?>

                <form method="POST" action="registrar_pricing.php">
                    <table>
                        <thead>
                            <tr>
                                <th>Document Type</th>
                                <th>Unit Price (₱)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prices as $documentType => $price): ?>
                                <?php $fieldName = 'price_' . str_replace(' ', '_', strtolower($documentType)); ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($documentType); ?></td>
                                    <td>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            name="<?php echo htmlspecialchars($fieldName); ?>"
                                            value="<?php echo htmlspecialchars(number_format((float)$price, 2, '.', '')); ?>"
                                            required
                                        />
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="button-row">
                        <button type="submit" name="update_prices" class="btn-action save">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>

</body>
</html>
