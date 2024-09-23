<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "pms";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL to create Equipment Baseline table
	$sql = "CREATE TABLE IF NOT EXISTS equipment_baseline (
        equipment_baseline_id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        equipment_id INT(7) UNSIGNED,
        maintenance_plan_id INT(7) UNSIGNED,
        plan_equipment_count INT(10) NOT NULL,
        year YEAR NOT NULL,
        baseline_data VARCHAR(50) NOT NULL,
        FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id),
        FOREIGN KEY (maintenance_plan_id) REFERENCES maintenance_plan(maintenance_plan_id)
    )";

    $conn->exec($sql);
    echo "Table Equipment Baseline created successfully";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>
