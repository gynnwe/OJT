<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Office Equipment Data</title>
</head>
<body>
    <h1>Input Office Equipment Data</h1>
    <form action="office_equipment_process.php" method="POST">
        <label for="location_id">Location ID:</label>
        <input type="number" name="location_id" id="location_id" required><br>

        <label for="equipment_type">Equipment Type:</label>
        <input type="text" name="equipment_type" id="equipment_type" value="Office" readonly><br> <!-- Locked to Office -->

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
</body>
</html>
