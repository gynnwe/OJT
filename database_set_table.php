<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS pms";
    $conn->exec($sql);
    echo "Database created successfully<br>";

    // Use the created database
    $conn->exec("USE pms");

    // --- Create the Account Table ---
    $sql = "CREATE TABLE IF NOT EXISTS account (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(50) NOT NULL,
        username VARCHAR(30) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Table Account created successfully<br>";

    // --- Create Equipment Table ---
    $sql = "CREATE TABLE IF NOT EXISTS equipment (
        equipment_id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        location_id INT(7) NOT NULL,
        equipment_type ENUM('ICT', 'Office') NOT NULL,
        equipment_name VARCHAR(50) NOT NULL,
        equipment_serial_num VARCHAR(30) NOT NULL,
        model_name VARCHAR(50) NOT NULL,
        status ENUM('Serviceable', 'Non-serviceable') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Table ICT Equipment created successfully<br>";

    $conn->exec($sql);
    echo "Table Office Equipment created successfully<br>";

    // --- Create Equipment Baseline Table ---
    $sql = "CREATE TABLE IF NOT EXISTS equipment_baseline (
        baseline_id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        baseline_name VARCHAR(50) NOT NULL,
        baseline_description TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Table Equipment Baseline created successfully<br>";

    // --- Create Maintenance Plan Table ---
    $sql = "CREATE TABLE IF NOT EXISTS maintenance_plan (
        plan_id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        equipment_id INT(7) UNSIGNED NOT NULL,
        maintenance_date DATE NOT NULL,
        maintenance_description TEXT NOT NULL,
        FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Table Maintenance Plan created successfully<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>
