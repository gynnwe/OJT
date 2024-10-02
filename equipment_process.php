<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $location_id = $_POST['location_id'];
    $equipment_type = $_POST['equipment_type'];
    $equipment_name = $_POST['equipment_name'];
    $serial_num = $_POST['serial_num'];
    $model_name = $_POST['model_name'];
    $status = $_POST['status'];
    $date_purchased = $_POST['date_purchased'];

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if location_id exists
        $location_check_sql = "SELECT location_id FROM location WHERE location_id = :location_id";
        $location_stmt = $conn->prepare($location_check_sql);
        $location_stmt->bindParam(':location_id', $location_id);
        $location_stmt->execute();

        if ($location_stmt->rowCount() === 0) {
            // Location ID doesn't exist
            echo "<script>alert('This location doesn\'t exist. Please enter a valid location ID.'); window.location.href='equipment_input_ict.php';</script>";
        } else {
            // Check if serial_num already exists
            $serial_check_sql = "SELECT serial_num FROM equipment WHERE serial_num = :serial_num";
            $serial_stmt = $conn->prepare($serial_check_sql);
            $serial_stmt->bindParam(':serial_num', $serial_num);
            $serial_stmt->execute();

            if ($serial_stmt->rowCount() > 0) {
                // Serial number already exists
                echo "<script>alert('Equipment with this serial number already exists.'); window.location.href='equipment_input_ict.php';</script>";
            } else {
                // Insert into equipment if location and serial are valid
                $sql = "INSERT INTO equipment (location_id, equipment_type, equipment_name, serial_num, model_name, status, date_purchased) 
                        VALUES (:location_id, :equipment_type, :equipment_name, :serial_num, :model_name, :status, :date_purchased)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':location_id', $location_id);
                $stmt->bindParam(':equipment_type', $equipment_type);
                $stmt->bindParam(':equipment_name', $equipment_name);
                $stmt->bindParam(':serial_num', $serial_num);
                $stmt->bindParam(':model_name', $model_name);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':date_purchased', $date_purchased);
        
                $stmt->execute();
                echo "<script>alert('Equipment data inserted successfully!'); window.location.href='equipment_page.php';</script>";
            }
        }

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

    $conn = null;
}
?>
