<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS ictmms";
    $conn->exec($sql);
    echo "Database created successfully<br>";

    // Use the created database
    $conn->exec("USE ictmms");

    // --- Create the User Account Table ---
    $sql = "CREATE TABLE IF NOT EXISTS user (
        admin_id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(50) NOT NULL,
		firstname VARCHAR(25) NOT NULL,
		lastname VARCHAR(25) NOT NULL,
        username VARCHAR(10) NOT NULL,
        password VARCHAR(60) NOT NULL,
		role ENUM('Admin', 'Assistant') NOT NULL
    )";
	
    $conn->exec($sql);
    echo "User Table created successfully<br>";

    // Insert default admin values
    $defaultEmail = 'superadmin@example.com';
    $defaultFirstname = 'Super';
    $defaultLastname = 'Admin';
    $defaultUsername = 'superadmin';
    $defaultPassword = password_hash('yourdefaultpassword', PASSWORD_DEFAULT); // Change 'yourdefaultpassword' as needed
	$defaultRole = 'Admin';

    $sql = "INSERT INTO user (email, firstname, lastname, username, password, role) 
        VALUES (:email, :firstname, :lastname, :username, :password, :role)";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':email', $defaultEmail);
    $stmt->bindParam(':firstname', $defaultFirstname);
    $stmt->bindParam(':lastname', $defaultLastname);
    $stmt->bindParam(':username', $defaultUsername);
    $stmt->bindParam(':password', $defaultPassword);
    $stmt->bindParam(':role', $defaultRole);
    $stmt->execute();

    echo "Default admin created successfully<br>";

// --- Create Location Table ---
$sql = "CREATE TABLE IF NOT EXISTS location(
    location_id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    college VARCHAR(50) NOT NULL,
    office VARCHAR(50) NOT NULL,
    unit VARCHAR(50) NOT NULL,
    deleted_id TINYINT(1) NOT NULL DEFAULT 0
)";
$conn->exec($sql);
echo "Location Table created successfully<br>";

	
	// --- Create Equipment Type Table ---
    $sql = "CREATE TABLE IF NOT EXISTS equipment_type (
        equip_type_id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		equip_type_name VARCHAR(15) NOT NULL,
		deleted_id TINYINT(1) NOT NULL DEFAULT 0
    )";	
	
    $conn->exec($sql);
    echo "Equipment Type Table created successfully<br>";
	
	// --- Create Model Table ---
    $sql = "CREATE TABLE IF NOT EXISTS model (
        model_id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		equip_type_id INT(7) UNSIGNED NOT NULL,
		model_name VARCHAR(20) NOT NULL,
		deleted_id TINYINT(1) NOT NULL DEFAULT 0,
		FOREIGN KEY (equip_type_id) REFERENCES equipment_type(equip_type_id)
    )";	
	
    $conn->exec($sql);
    echo "Model Table created successfully<br>";
	
    // --- Create Equipment Table ---
    $sql = "CREATE TABLE IF NOT EXISTS equipment (
        equipment_id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        location_id INT(7) UNSIGNED NOT NULL,
		equip_type_id INT(7) UNSIGNED NOT NULL,
		model_id INT(7) UNSIGNED NOT NULL,
		equip_name VARCHAR(35) NOT NULL,
        property_num VARCHAR(30) NOT NULL UNIQUE,
		status ENUM('Serviceable', 'Non-serviceable') NOT NULL,
		date_purchased DATE NOT NULL,
		FOREIGN KEY (location_id) REFERENCES location(location_id),
		FOREIGN KEY (equip_type_id) REFERENCES equipment_type(equip_type_id),
		FOREIGN KEY (model_id) REFERENCES model(model_id)
	)";	
	
    $conn->exec($sql);
    echo "Equipment Table created successfully<br>";

	// --- Create Location Details Table ---
    $sql = "CREATE TABLE IF NOT EXISTS location_details (
        id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        location_id INT(7) UNSIGNED NOT NULL,
		equipment_id INT(7) UNSIGNED NOT NULL,
		date DATETIME NOT NULL,
		FOREIGN KEY (location_id) REFERENCES location(location_id),
		FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id)
    )";	
	
    $conn->exec($sql);
    echo "Location Details Table created successfully<br>";
	
	// --- Create Personnel Table ---
	$sql = "CREATE TABLE IF NOT EXISTS personnel (
        personnel_id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        firstname VARCHAR(25) NOT NULL,
		lastname VARCHAR(25) NOT NULL,
		department VARCHAR(50) NOT NULL,
		deleted_id TINYINT(1) NOT NULL DEFAULT 0
    )";
	
	$conn->exec($sql);
    echo "Personnel Table created successfully<br>";
	
	// --- Create Remarks Table ---
    $sql = "CREATE TABLE IF NOT EXISTS remarks (
        remarks_id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		remarks_name VARCHAR(20) NOT NULL,
		deleted_id TINYINT(1) NOT NULL DEFAULT 0
    )";	
	
    $conn->exec($sql);
    echo "Remarks Table created successfully<br>";
	
    // --- ICT Maintenance Logs Table ---
    $sql = "CREATE TABLE IF NOT EXISTS ict_maintenance_logs (
        jo_number VARCHAR(100) PRIMARY KEY,
		personnel_id INT(7) UNSIGNED NULL,
		equipment_id INT(7) UNSIGNED NOT NULL,
		maintenance_date DATE NOT NULL,
		actions_taken VARCHAR(100) NOT NULL,
		remarks_id INT(7) UNSIGNED NOT NULL,
		FOREIGN KEY (personnel_id) REFERENCES personnel(personnel_id),
		FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id),		
		FOREIGN KEY (remarks_id) REFERENCES remarks(remarks_id)		
    )";
	
    $conn->exec($sql);
    echo "ICT Maintenance Logs Table created successfully<br>";

	// --- Create Maintenance Plan ICT Table ---
	$sql = "CREATE TABLE IF NOT EXISTS maintenance_plan (
        id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        admin_id INT(7) UNSIGNED NOT NULL,
		year YEAR NOT NULL,
		date_prepared DATE NOT NULL,
		count INT(5) NOT NULL,
		FOREIGN KEY (admin_id) REFERENCES user(admin_id)
    )";
	
	$conn->exec($sql);
    echo "Maintenance Plan Table created successfully<br>";
	
    // --- Create Equipment Baseline Table ---
    $sql = "CREATE TABLE IF NOT EXISTS equipment_baseline (
        id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        equipment_id INT(7) UNSIGNED NOT NULL,
		maintenance_plan_id INT(7) UNSIGNED NOT NULL,
        count INT(5) NOT NULL,
        baseline_date DATE NOT NULL,
		equipment_type ENUM('Computer', 'Laptop', 'Printer', 'Projector') NOT NULL,
        FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id),
		FOREIGN KEY (maintenance_plan_id) REFERENCES maintenance_plan(id)
    )";
	
    $conn->exec($sql);
    echo "Equipment Baseline Table created successfully<br>";
	
	// --- Create Plan Details Table ---
	$sql = "CREATE TABLE IF NOT EXISTS plan_details (
        id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		maintenance_plan_id INT(7) UNSIGNED NOT NULL,
        month ENUM('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December') NOT NULL,
        target DECIMAL(5,2) NOT NULL,
		equipment_type ENUM('Computer', 'Laptop', 'Printer', 'Projector') NOT NULL,
		details DECIMAL(5,2) NOT NULL,
		accomplishment DECIMAL(5,2) NOT NULL,
		FOREIGN KEY (maintenance_plan_id) REFERENCES maintenance_plan(id)
    )";
	
	$conn->exec($sql);
    echo "Plan Details Table created successfully<br>";
	
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>
