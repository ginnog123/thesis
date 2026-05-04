<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit;
}

$host = "localhost"; $dbname = "tup_system"; $dbuser = "root"; $dbpass = "";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    exit;
}

function ensureDocumentColumns($pdo) {
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
        }
    }
}

ensureDocumentColumns($pdo);

$app_id = $_GET['app_id'] ?? '';
if (!$app_id) {
    http_response_code(400);
    exit;
}

$stmt = $pdo->prepare("SELECT doc_form138, doc_moral, doc_birthcert, doc_idpic,
    doc_form138_verification, doc_moral_verification, doc_birthcert_verification, doc_idpic_verification,
    doc_form138_ocr_text, doc_moral_ocr_text, doc_birthcert_ocr_text, doc_idpic_ocr_text
    FROM admission_applications WHERE application_id = ?");
$stmt->execute([$app_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/json');
echo json_encode($data);
?>