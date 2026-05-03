<?php

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host = 'localhost';
    $dbname = 'tup_system';
    $dbuser = 'root';
    $dbpass = '';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    initializeCollegeFilters($pdo);

    return $pdo;
}

function initializeCollegeFilters(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS college_filters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        college_name VARCHAR(255) NOT NULL,
        min_gwa DECIMAL(5,2) NOT NULL,
        allowed_strands TEXT NOT NULL
    )");

    $pdo->exec("INSERT IGNORE INTO college_filters (college_name, min_gwa, allowed_strands) VALUES
        ('College of Science', 85.00, 'STEM'),
        ('College of Engineering', 80.00, 'STEM,ICT'),
        ('College of Architecture and Fine Arts', 82.00, 'STEM,HUMSS'),
        ('College of Industrial Education', 78.00, 'STEM,ABM,ICT'),
        ('Engineering Technology (BET)', 75.00, 'STEM,ICT'),
        ('Teacher Education (BTVTEd)', 80.00, 'HUMSS,GAS')
    ");
}
