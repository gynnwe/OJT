<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>ICT Equipment</title>
</head>
<body>
    <h1>Add ICT Equipment</h1>

    <?php
    // To show any error messages
    if (isset($_GET['error'])) {
        echo "<p style='color:red;'>" . $_GET['error'] . "</p>";
    }
    ?>

    <form action="equipment_input_ict.php" method="POST">
        <label for="location_id">Location ID:</label>
        <input type="text" name="location_id" id="location_id" required><br>

        <label for="equipment_type">Equipment Type:</label>
        <input type="hidden" name="equipment_type" value="ICT"> 
		<span>ICT</span><br>

        <label for="equipment_name">Equipment Name:</label>
        <input type="text" name="equipment_name" id="equipment_name" required><br>

        <label for="serial_num">Equipment Serial Number:</label>
        <input type="text" name="serial_num" id="serial_num" required><br>

        <label for="model_name">Model Name:</label>
        <input type="text" name="model_name" id="model_name" required><br>

        <label for="status">Status:</label>
        <select name="status" id="status" required>
            <option value="Serviceable">Serviceable</option>
            <option value="Non-serviceable">Non-serviceable</option>
        </select><br>

        <button type="submit">Submit</button>
    </form>

    <?php
    // Database connection settings
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "ictmms";

    // Handle POST request to insert data into the equipment table
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $location_id = $_POST['location_id'];
        $equipment_type = $_POST['equipment_type'];
        $equipment_name = $_POST['equipment_name'];
        $equipment_serial_num = $_POST['serial_num'];
        $model_name = $_POST['model_name'];
        $status = $_POST['status'];

        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if the serial number already exists
            $sql = "SELECT * FROM equipment WHERE serial_num = :serial_num";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':serial_num', $serial_num);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Redirect to the form with an error message if the serial number exists
                header("Location: equipment_input_ict.php?error=Equipment+already+exists");
                exit();
            }

            // If serial number is unique, insert the new data
            $sql = "INSERT INTO equipment (location_id, equipment_type, equipment_name, serial_num, model_name, status)
                    VALUES (:location_id, :equipment_type, :equipment_name, :serial_num, :model_name, :status)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':location_id', $location_id);
            $stmt->bindParam(':equipment_type', $equipment_type);
            $stmt->bindParam(':equipment_name', $equipment_name);
            $stmt->bindParam(':equipment_serial_num', $equipment_serial_num);
            $stmt->bindParam(':model_name', $model_name);
            $stmt->bindParam(':status', $status);

            $stmt->execute();
            echo "Equipment data inserted successfully!";
			header("location: equipment_input_ict.php");
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }

        $conn = null;
    }
	
	//Show ICT Table Details when database table has been populated
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "SELECT * FROM equipment WHERE equipment_type = 'ICT'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $equipments = $stmt->fetchAll();

            if (count($equipments) > 0) {
                echo "<h2>ICT Equipments Table</h2>";
                echo "<table border='1'>";
                echo "<tr><th>ID</th><th>Location ID</th><th>Equipment Type</th><th>Equipment Name</th><th>Serial Number</th><th>Model Name</th><th>Status</th></tr>";
                foreach ($equipments as $equipment) {
                    echo "<tr>";
                    echo "<td>" . $equipment['equipment_id'] . "</td>";
                    echo "<td>" . $equipment['location_id'] . "</td>";
                    echo "<td>" . $equipment['equipment_type'] . "</td>";
                    echo "<td>" . $equipment['equipment_name'] . "</td>";
                    echo "<td>" . $equipment['equipment_serial_num'] . "</td>";
                    echo "<td>" . $equipment['model_name'] . "</td>";
                    echo "<td>" . $equipment['status'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
    ?>
</body>
</html>
