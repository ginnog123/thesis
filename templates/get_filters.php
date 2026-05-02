<?php
// get_filters.php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$host = "localhost"; $dbname = "tup_system"; $dbuser = "root"; $dbpass = "";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch college filters
    $stmtFilters = $pdo->query("SELECT * FROM college_filters ORDER BY college_name");
    $filters = $stmtFilters->fetchAll(PDO::FETCH_ASSOC);

    // Options for the dropdown
    $collegeOptions = [
        'College of Science',
        'College of Engineering',
        'College of Architecture and Fine Arts',
        'College of Industrial Education',
        'Engineering Technology (BET)',
        'Teacher Education (BTVTEd)'
    ];
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>