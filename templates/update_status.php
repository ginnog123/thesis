<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = "localhost"; $dbname = "tup_system"; $dbuser = "root"; $dbpass = "";
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);

    $action = $_POST['action'];
    $app_id = $_POST['app_id'];

    if ($action === 'accept_student') {
        $stmt = $pdo->prepare("UPDATE admission_applications SET status = 'Exam Status' WHERE application_id = ?");
        $stmt->execute([$app_id]);

    } elseif ($action === 'set_exam') {
        $stmt = $pdo->prepare("UPDATE admission_applications SET status = 'Exam Schedule', exam_date = ?, exam_time = ?, exam_venue = ? WHERE application_id = ?");
        $stmt->execute([$_POST['exam_date'], $_POST['exam_time'], $_POST['exam_venue'], $app_id]);

    } elseif ($action === 'exam_passed') {
        $stmt = $pdo->prepare("UPDATE admission_applications SET status = 'Document Checking' WHERE application_id = ?");
        $stmt->execute([$app_id]);

    } elseif ($action === 'exam_failed' || $action === 'reject') {
        $stmt = $pdo->prepare("UPDATE admission_applications SET status = 'Rejected' WHERE application_id = ?");
        $stmt->execute([$app_id]);

    } elseif ($action === 'verify_docs') {
        // Document Verification Logic
        $docs = $_POST['docs'] ?? [];
        $required = ['Form 138', 'Good Moral', 'Birth Certificate', 'ID Picture'];
        
        $missing = array_diff($required, $docs);
        
        if (empty($missing)) {
            // All present = Clean Enrollment
            $stmt = $pdo->prepare("UPDATE admission_applications SET status = 'Enrolled', remarks = NULL WHERE application_id = ?");
            $stmt->execute([$app_id]);
        } else {
            // Missing Items = Enrolled with Remarks (To Be Followed)
            $missingStr = implode(", ", $missing);
            $stmt = $pdo->prepare("UPDATE admission_applications SET status = 'Enrolled', remarks = ? WHERE application_id = ?");
            $stmt->execute([$missingStr, $app_id]);
        }
    }

    header("Location: admin_dashboard.php");
    exit;
}
?>
