<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms";

try {
    // Connect to the database
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch all locations from the database
    $sql = "SELECT location_id, college, office, unit FROM location";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

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

<form action="equipment_process.php" method="POST">
        <label for="location_id">Location ID:</label>
        <input type="text" name="location_id" id="location_id" required><br>


        <label for="equipment_type">Equipment Type:</label>
        <select name="equipment_type" id="equipment_type" required>
            <option value="Computer">Computer</option>
            <option value="Laptop">Laptop</option>
            <option value="Printer">Printer</option>
            <option value="Projector">Projector</option>
        </select><br>

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

        <label for="date_purchased">Date Purchased:</label>
        <input type="date" name="date_purchased" id="date_purchased" required><br>

        <button type="submit">Submit</button>
    </form>

</body>
</html>
