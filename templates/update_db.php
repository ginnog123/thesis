<?php
$host = "localhost"; $dbname = "tup_system"; $dbuser = "root"; $dbpass = "";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to database.\n";

    // Create college_filters table
    $sql = "CREATE TABLE IF NOT EXISTS college_filters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        college_name VARCHAR(255) NOT NULL,
        min_gwa DECIMAL(5,2) NOT NULL,
        allowed_strands TEXT NOT NULL
    )";
    $pdo->exec($sql);
    echo "Table created.\n";

    // Insert sample data
    $pdo->exec("INSERT IGNORE INTO college_filters (college_name, min_gwa, allowed_strands) VALUES
        ('College of Science', 85.00, 'STEM'),
        ('College of Engineering', 80.00, 'STEM,ICT'),
        ('College of Architecture and Fine Arts', 82.00, 'STEM,HUMSS'),
        ('College of Industrial Education', 78.00, 'STEM,ABM,ICT'),
        ('Engineering Technology (BET)', 75.00, 'STEM,ICT'),
        ('Teacher Education (BTVTEd)', 80.00, 'HUMSS,GAS')
    ");
    echo "Sample data inserted.\n";

    // Add document columns and verification columns if needed
    $columns = [
        'doc_form138',
        'doc_moral',
        'doc_birthcert',
        'doc_idpic',
        'doc_form138_verification',
        'doc_moral_verification',
        'doc_birthcert_verification',
        'doc_idpic_verification',
        'doc_form138_ocr_text',
        'doc_moral_ocr_text',
        'doc_birthcert_ocr_text',
        'doc_idpic_ocr_text'
    ];

    foreach ($columns as $column) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM admission_applications LIKE ?");
        $stmt->execute([$column]);
        if ($stmt->rowCount() === 0) {
            if (str_ends_with($column, '_ocr_text')) {
                $pdo->exec("ALTER TABLE admission_applications ADD COLUMN $column TEXT NULL");
            } elseif (in_array($column, ['doc_form138', 'doc_moral', 'doc_birthcert', 'doc_idpic'])) {
                $pdo->exec("ALTER TABLE admission_applications ADD COLUMN $column VARCHAR(255) NULL");
            } else {
                $pdo->exec("ALTER TABLE admission_applications ADD COLUMN $column VARCHAR(32) DEFAULT 'pending'");
            }
            echo "Added column $column.\n";
        }
    }

    echo "Database updated successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>