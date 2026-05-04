<?php
$pdo = new PDO('mysql:host=localhost;dbname=tup_system', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = $pdo->query('SHOW COLUMNS FROM admission_applications');
foreach ($stmt as $row) {
    echo $row['Field'] . "\n";
}
