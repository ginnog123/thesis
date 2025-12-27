<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = "localhost"; $dbname = "tup_system"; $dbuser = "root"; $dbpass = "";
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);

    $action = $_POST['action'];
    $app_id = $_POST['app_id'];
    $new_status = "";

    // Determine Logic based on Action Button
    if ($action === 'accept_student') {
        // Pending -> Exam Status
        $stmt = $pdo->prepare("UPDATE admission_applications SET status = 'Exam Status' WHERE application_id = ?");
        $stmt->execute([$app_id]);

    } elseif ($action === 'set_exam') {
        // Exam Status -> Exam Schedule (With Date/Time)
        $date = $_POST['exam_date'];
        $time = $_POST['exam_time'];
        $venue = $_POST['exam_venue'];
        
        $stmt = $pdo->prepare("UPDATE admission_applications SET status = 'Exam Schedule', exam_date = ?, exam_time = ?, exam_venue = ? WHERE application_id = ?");
        $stmt->execute([$date, $time, $venue, $app_id]);

    } elseif ($action === 'exam_passed') {
        // Exam Schedule -> Document Checking
        $stmt = $pdo->prepare("UPDATE admission_applications SET status = 'Document Checking' WHERE application_id = ?");
        $stmt->execute([$app_id]);

    } elseif ($action === 'exam_failed' || $action === 'reject') {
        // Fail or Reject
        $stmt = $pdo->prepare("UPDATE admission_applications SET status = 'Rejected' WHERE application_id = ?");
        $stmt->execute([$app_id]);

    } elseif ($action === 'enroll') {
        // Document Checking -> Enrolled
        $stmt = $pdo->prepare("UPDATE admission_applications SET status = 'Enrolled' WHERE application_id = ?");
        $stmt->execute([$app_id]);
    }

    header("Location: admin_dashboard.php");
    exit;
}