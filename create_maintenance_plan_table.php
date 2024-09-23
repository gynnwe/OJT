<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "pms";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL to create Maintenance Plan table
    $sql = "CREATE TABLE IF NOT EXISTS maintenance_plan (
        maintenance_plan_id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        year YEAR NOT NULL,
        date_prepared DATE NOT NULL,
		count INT(7) NOT NULL
    )";

    $conn->exec($sql);
    echo "Table Maintenance Plan created successfully";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>
