<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db.php';
    $pdo = getPDO();

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
    } elseif ($action === 'proceed') {
        $stmt = $pdo->prepare("SELECT doc_form138_verification, doc_moral_verification, doc_birthcert_verification, doc_idpic_verification FROM admission_applications WHERE application_id = ?");
        $stmt->execute([$app_id]);
        $verification = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($verification && $verification['doc_form138_verification'] === 'ocr_passed' && $verification['doc_moral_verification'] === 'ocr_passed' && $verification['doc_birthcert_verification'] === 'ocr_passed' && $verification['doc_idpic_verification'] === 'ocr_passed') {
            // All OCR checks passed, but admin must still physically verify
            // Set status to "Physical Verification Pending" instead of auto-enrollment
            $stmt = $pdo->prepare("UPDATE admission_applications SET status = 'Physical Verification Pending' WHERE application_id = ?");
            $stmt->execute([$app_id]);
            echo 'ok';
        } else {
            echo 'not_allowed';
        }
        exit;

    } elseif ($action === 'add_filter') {
        $stmt = $pdo->prepare("INSERT INTO college_filters (college_name, min_gwa, allowed_strands) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['college_name'], $_POST['min_gwa'], $_POST['allowed_strands']]);

    } elseif ($action === 'update_filter') {
        $stmt = $pdo->prepare("UPDATE college_filters SET college_name = ?, min_gwa = ?, allowed_strands = ? WHERE id = ?");
        $stmt->execute([$_POST['college_name'], $_POST['min_gwa'], $_POST['allowed_strands'], $_POST['filter_id']]);

    } elseif ($action === 'delete_filter') {
        $stmt = $pdo->prepare("DELETE FROM college_filters WHERE id = ?");
        $stmt->execute([$_POST['filter_id']]);
    }

    header("Location: admin_dashboard.php");
    exit;
}
?>
