<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "pms";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL to create office equipment table
    $sql = "CREATE TABLE IF NOT EXISTS office_equipment (
        equipment_id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        location_id INT(7) NOT NULL,
        equipment_type ENUM('Office') NOT NULL, -- Locked to 'Office'
        equipment_name VARCHAR(15) NOT NULL,
        equipment_serial_num VARCHAR(30) NOT NULL,
        model_name VARCHAR(15) NOT NULL,
        status ENUM('Serviceable', 'Non-serviceable') NOT NULL
    )";

    $conn->exec($sql);
    echo "Table office_equipment created successfully.";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>
