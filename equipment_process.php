<?php
include 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $location_id = $_POST['location_id'];
    $equipment_type = $_POST['equipment_type'];
    $equip_name = $_POST['equip_name'];
    $model_id = $_POST['model_id'];
    $property_num = $_POST['property_num'];
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
			$_SESSION['error'] = "Please enter a valid Location ID.";
			header("Location: equipment_input_ict.php");
    		exit;
        } else {
            // Check if Property Number already exists
            $prop_check_sql = "SELECT property_num FROM equipment WHERE property_num = :property_num";
            $prop_stmt = $conn->prepare($prop_check_sql);
            $prop_stmt->bindParam(':property_num', $property_num);
            $prop_stmt->execute();

            if ($prop_stmt->rowCount() > 0) {
				header("Location: equipment_input_ict.php");
    			exit;
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
                    $_SESSION['message'] = "Equipment registered successfully.";
					header("Location: equipment_input_ict.php");
    				exit;
                } else {
                    header("Location: equipment_input_ict.php");
					exit;
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
