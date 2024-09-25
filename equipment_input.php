<!DOCTYPE html>
<html>
<head>
    <title>Add Equipment</title>
</head>
<body>
    <h1>Add Equipment</h1>

    <?php
    // To show any error messages
    if (isset($_GET['error'])) {
        echo "<p style='color:red;'>" . $_GET['error'] . "</p>";
    }
    ?>

    <form action="equipment_input.php" method="POST">
        <label for="location_id">Location ID:</label>
        <input type="text" name="location_id" id="location_id" required><br>

        <label for="equipment_type">Equipment Type:</label>
        <select name="equipment_type" id="equipment_type" required>
            <option value="ICT">ICT</option>
            <option value="Office">Office</option>
        </select><br>

        <label for="equipment_name">Equipment Name:</label>
        <input type="text" name="equipment_name" id="equipment_name" required><br>

        <label for="equipment_serial_num">Equipment Serial Number:</label>
        <input type="text" name="equipment_serial_num" id="equipment_serial_num" required><br>

        <label for="model_name">Model Name:</label>
        <input type="text" name="model_name" id="model_name" required><br>

        <label for="status">Status:</label>
        <select name="status" id="status" required>
            <option value="Serviceable">Serviceable</option>
            <option value="Non-serviceable">Non-serviceable</option>
        </select><br>

        <button type="submit">Submit</button>
    </form>

    <form action="equipment_input.php" method="GET">
        <button type="submit" name="ict_table">ICT Equipments Table</button>
        <button type="submit" name="office_table">Office Equipments Table</button>
    </form>

    <?php
    // Database connection settings
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "pms";

    // Handle POST request to insert data into the equipment table
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $location_id = $_POST['location_id'];
        $equipment_type = $_POST['equipment_type'];
        $equipment_name = $_POST['equipment_name'];
        $equipment_serial_num = $_POST['equipment_serial_num'];
        $model_name = $_POST['model_name'];
        $status = $_POST['status'];

        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if the serial number already exists
            $sql = "SELECT * FROM equipment WHERE equipment_serial_num = :equipment_serial_num";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':equipment_serial_num', $equipment_serial_num);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Redirect to the form with an error message if the serial number exists
                header("Location: equipment_input.php?error=Equipment+already+exists");
                exit();
            }

            // If serial number is unique, insert the new data
            $sql = "INSERT INTO equipment (location_id, equipment_type, equipment_name, equipment_serial_num, model_name, status)
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

    // Handle GET request to display the ICT equipment table
    if (isset($_GET['ict_table'])) {
        try {
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
            } else {
                echo "No ICT equipment found.";
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }

        $conn = null;
    }

    // Handle GET request to display the Office equipment table
    if (isset($_GET['office_table'])) {
        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "SELECT * FROM equipment WHERE equipment_type = 'Office'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $equipments = $stmt->fetchAll();

            if (count($equipments) > 0) {
                echo "<h2>Office Equipments Table</h2>";
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
            } else {
                echo "No Office equipment found.";
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }

        $conn = null;
    }
    ?>
</body>
</html>
