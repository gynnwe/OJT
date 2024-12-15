<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $location_id = $_POST['location_id'];
    $equipment_type = $_POST['equipment_type'];
    $equip_name = $_POST['equip_name'];
    $model_id = $_POST['model_id'];
    $property_num = $_POST['property_num'];
    $status = $_POST['status'];
    $date_purchased = $_POST['date_purchased'];

    try {
        // Connect to the database
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
            // Check if Property Number already exists
            $prop_check_sql = "SELECT property_num FROM equipment WHERE property_num = :property_num";
            $prop_stmt = $conn->prepare($prop_check_sql);
            $prop_stmt->bindParam(':property_num', $property_num);
            $prop_stmt->execute();

            if ($prop_stmt->rowCount() > 0) {
                // Property number already exists
                echo "<script>alert('Equipment with this property number already exists.'); window.location.href='equipment_input_ict.php';</script>";
            } else {
                // Insert into equipment if location and property are valid
                $sql = "INSERT INTO equipment (location_id, equip_type_id, model_id, equip_name, property_num, status, date_purchased) 
                        VALUES (:location_id, :equipment_type, :model_id, :equip_name, :property_num, :status, :date_purchased)";
                
                $stmt = $conn->prepare($sql);
                // Bind parameters to prevent SQL injection
                $stmt->bindParam(':location_id', $location_id);
                $stmt->bindParam(':equipment_type', $equipment_type);
                $stmt->bindParam(':model_id', $model_id);
                $stmt->bindParam(':equip_name', $equip_name);
                $stmt->bindParam(':property_num', $property_num);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':date_purchased', $date_purchased);
        
                // Execute statement and check success
                if ($stmt->execute()) {
                    echo "<script>alert('Equipment data inserted successfully!'); window.location.href='equipment_input_ict.php';</script>";
                } else {
                    echo "<script>alert('Failed to insert equipment data.'); window.location.href='equipment_input_ict.php';</script>";
                }
            }
        }

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

    // Close connection
    unset($conn);
}
?>