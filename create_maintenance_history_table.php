<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "pms";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL to create Maintenance_History_ICT table without Personnel_ID as FK
    $sql = "CREATE TABLE IF NOT EXISTS Maintenance_History_ICT (
        JO_Number INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        Personnel_ID INT(11) NOT NULL,
        Personnel_Name VARCHAR(50) NOT NULL,
        Maintenance_Date DATE NOT NULL,
        Equipment_Name VARCHAR(15) NOT NULL,
        Equipment_Serial_Num VARCHAR(30) NOT NULL,
        Location VARCHAR(50) NOT NULL,
        Actions_Taken TEXT NOT NULL,
        Remarks ENUM('Serviceable', 'Non-serviceable') NOT NULL,
        FOREIGN KEY (Equipment_Serial_Num) REFERENCES equipment(equipment_serial_num) ON DELETE CASCADE ON UPDATE CASCADE
    )";

    $conn->exec($sql);
    echo "Table Maintenance_History_ICT created successfully";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>
