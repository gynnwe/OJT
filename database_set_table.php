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
        password VARCHAR(255) NOT NULL
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
        status ENUM('Serviceable', 'Non-serviceable') NOT NULL
    )";	
	
    $conn->exec($sql);
    echo "Table Equipment created successfully<br>";

	// --- Create Location Table ---
	$sql = "CREATE TABLE IF NOT EXISTS location (
        location_id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        college VARCHAR(50) NOT NULL,
		office VARCHAR(50) NOT NULL,
		unit VARCHAR(50) NOT NULL
    )";
	
	$conn->exec($sql);
    echo "Table Location created successfully<br>";
	
	// --- Create Personnel Table ---
	$sql = "CREATE TABLE IF NOT EXISTS personnel (
        personnel_id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
		department VARCHAR(50) NOT NULL
    )";
	
	$conn->exec($sql);
    echo "Table Personnel created successfully<br>";

    // --- Create Maintenance Plan Table ---
    $sql = "CREATE TABLE IF NOT EXISTS maintenance_plan (
        maintenance_plan_id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        year YEAR NOT NULL,
        date_prepared DATE NOT NULL,
		count INT(7) NOT NULL
    )";
	
    $conn->exec($sql);
    echo "Table Maintenance Plan created successfully<br>";
	
    // --- Create Equipment Baseline Table ---
    $sql = "CREATE TABLE IF NOT EXISTS equipment_baseline (
        equipment_baseline_id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        equipment_id INT(7) UNSIGNED NOT NULL,
        maintenance_plan_id INT(7) UNSIGNED NOT NULL,
        plan_equipment_count INT(10) NOT NULL,
        year YEAR NOT NULL,
        baseline_data VARCHAR(50) NOT NULL,
        FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id),
        FOREIGN KEY (maintenance_plan_id) REFERENCES maintenance_plan(maintenance_plan_id)
    )";
	
    $conn->exec($sql);
    echo "Table Equipment Baseline created successfully<br>";
	
	// --- Create Maintenance History ICT Table ---
	$sql = "CREATE TABLE IF NOT EXISTS maintenance_history_ict (
        jo_number INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        personnel_id INT(7) UNSIGNED NOT NULL,
		maintenance_date Date NOT NULL,
		equipment_name VARCHAR(15) NOT NULL,
		equipment_serial_Num VARCHAR(30) NOT NULL,
		location_Num VARCHAR(50) NOT NULL,
		actions_taken VARCHAR(50) NOT NULL,
		remarks VARCHAR(50) NOT NULL,
		FOREIGN KEY (personnel_id) REFERENCES personnel(personnel_id)
    )";
	
	$conn->exec($sql);
    echo "Table Maintenance History ICT created successfully<br>";
	
	// --- Create Maintenance History ICT Table ---
	$sql = "CREATE TABLE IF NOT EXISTS maintenance_history_faculty (
        jo_number INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        personnel_id INT(7) UNSIGNED NOT NULL,
		location_id INT(7) UNSIGNED NOT NULL,
		maintenance_date DATE NOT NULL,
		equipment_name VARCHAR(15) NOT NULL,
		equipment_serial_num VARCHAR(30) NOT NULL,
		date_purchase DATE NOT NULL,
		actions_taken VARCHAR(50) NOT NULL,
		remarks VARCHAR(50) NOT NULL,
		FOREIGN KEY (personnel_id) REFERENCES personnel(personnel_id),
		FOREIGN KEY (location_id) REFERENCES location(location_id)
    )";
	
	$conn->exec($sql);
    echo "Table Maintenance History Faculty created successfully<br>";
	
	// --- Create Plan Details Table ---
	$sql = "CREATE TABLE IF NOT EXISTS plan_details (
        plan_ID INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        maintenance_plan_id INT(7) UNSIGNED NOT NULL,
        month ENUM('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December') NOT NULL,
        target INT(3) NOT NULL,
        FOREIGN KEY (maintenance_plan_id) REFERENCES maintenance_plan(maintenance_plan_id)
    )";
	
	$conn->exec($sql);
    echo "Table Plan Details created successfully<br>";
	
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>
