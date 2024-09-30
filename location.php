<?php
// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- Predefined location entries ---
    $sql = "INSERT IGNORE INTO location (location_id, college, office, unit) VALUES 
            (1000001, 'College 1', 'Office 1', 'Room 1'),
            (1000002, 'College 2', 'Office 2', 'Room 2'),
            (1000003, 'College 3', 'Office 3', 'Room 3'),
            (1000004, 'College 4', 'Office 4', 'Room 4'),
            (1000005, 'College 5', 'Office 5', 'Room 5'),
            (1000006, 'College 6', 'Office 6', 'Room 6'),
            (1000007, 'College 7', 'Office 7', 'Room 7'),
            (1000008, 'College 8', 'Office 8', 'Room 8'),
            (1000009, 'College 9', 'Office 9', 'Room 9'),
            (1000010, 'College 10', 'Office 10', 'Room 10')";

    $conn->exec($sql);
    echo "Predefined Locations inserted successfully<br>";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Close connection
$conn = null;
?>
