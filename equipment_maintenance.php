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
    <title>Equipment Maintenance</title>
</head>
<body>
    <h1>Equipment Maintenance</h1>

	<h3>List of Equipment</h3>

	<form action="maintenance_process.php" method="POST">
        <label for="jo_number">Job Order:</label>
        <input type="text" name="jo_number" id="jo_number" required><br>
		
		<label for="selected_equipment">Selected Equipment</label><label> #</label><label>#</label></br>

        <label for="actions_taken">Actions Taken:</label>
        <textarea name="actions_taken" id="actions_taken" required></textarea><br>

        <label for="remarks">Remarks:</label>
        <select name="status" id="status" required>
            <option value="Pending">Pending</option>
            <option value="Transfer">For Transfer</option>
            <option value="Resolved">Resolved</option>
        </select><br>
		
		<label for="responsible_personnel">Responsible Personnel</label><br>
        <input type="text" name="responsible_firstname" id="responsible_firstname" required placeholder="First Name"><br>
        <input type="text" name="responsible_lastname" id="responsible_firstname" required placeholder="Last Name"><br>
        <input type="text" name="responsible_department" id="responsible_firstname" required placeholder="Department"><br>
		
        <label for="date_purchased">Date Purchased:</label>
        <input type="date" name="date_purchased" id="date_purchased" required><br>

        <button type="submit">Log Maintenance</button>
        <button type="submit">Cancel</button>
    </form>

</body>
</html>