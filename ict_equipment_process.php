<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "pms";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $location_id = $_POST['location_id'];
    $equipment_type = $_POST['equipment_type']; // This will always be 'ICT'
    $equipment_name = $_POST['equipment_name'];
    $equipment_serial_num = $_POST['equipment_serial_num'];
    $model_name = $_POST['model_name'];
    $status = $_POST['status'];

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "INSERT INTO ict_equipment (location_id, equipment_type, equipment_name, equipment_serial_num, model_name, status)
                VALUES (:location_id, :equipment_type, :equipment_name, :equipment_serial_num, :model_name, :status)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':location_id', $location_id);
        $stmt->bindParam(':equipment_type', $equipment_type);
        $stmt->bindParam(':equipment_name', $equipment_name);
        $stmt->bindParam(':equipment_serial_num', $equipment_serial_num);
        $stmt->bindParam(':model_name', $model_name);
        $stmt->bindParam(':status', $status);

        $stmt->execute();

        echo "Equipment data inserted successfully!";
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

    $conn = null;
}
?>
