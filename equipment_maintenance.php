<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1); 

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

    // Fetch locations
    $sqlLocations = "SELECT location_id, college, office, unit FROM location";
    $stmtLocations = $conn->prepare($sqlLocations);
    $stmtLocations->execute();
    $locations = $stmtLocations->fetchAll(PDO::FETCH_ASSOC);

    // Fetch equipment types
    $sqlEquipmentTypes = "SELECT DISTINCT equipment_type FROM equipment";
    $stmtEquipmentTypes = $conn->prepare($sqlEquipmentTypes);
    $stmtEquipmentTypes->execute();
    $equipmentTypes = $stmtEquipmentTypes->fetchAll(PDO::FETCH_COLUMN);

    // Fetch all equipment based on selected type (optional filtering)
    $sqlEquipment = "SELECT equipment_id, equipment_name, serial_num, equipment_type FROM equipment"; // Ensure to fetch equipment_type
    $stmtEquipment = $conn->prepare($sqlEquipment);
    $stmtEquipment->execute();
    $equipmentList = $stmtEquipment->fetchAll(PDO::FETCH_ASSOC);

	// Fetch maintenance logs for table display
	$sqlLogs = "SELECT ict_maintenance_logs.jo_number, personnel.firstname, personnel.lastname, equipment.equipment_name, 
            ict_maintenance_logs.maintenance_date, ict_maintenance_logs.actions_taken, ict_maintenance_logs.remarks 
            FROM ict_maintenance_logs 
            LEFT JOIN personnel ON ict_maintenance_logs.personnel_id = personnel.personnel_id 
            JOIN equipment ON ict_maintenance_logs.equipment_id = equipment.equipment_id 
            ORDER BY ict_maintenance_logs.maintenance_date DESC";

$stmtLogs = $conn->prepare($sqlLogs);
$stmtLogs->execute();
$maintenanceLogs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT Equipment</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>ICT Equipment</h1>
        <div class="row mt-4">
            <!-- Add Equipment Button -->
            <div class="col-md-4 mb-3">
                <a href="equipment_input_ict.php" class="btn btn-primary w-100 py-4">
                    <i class="bi bi-plus-circle"></i> Add Equipment
                </a>
            </div>
            <!-- Equipment Maintenance Button -->
            <div class="col-md-4 mb-3">
                <a href="equipment_maintenance.php" class="btn btn-success w-100 py-4">
                    <i class="bi bi-wrench"></i> Equipment Maintenance
                </a>
            </div>
            <!-- Plan Maintenance Button -->
            <div class="col-md-4 mb-3">
                <a href="#" class="btn btn-warning w-100 py-4">
                    <i class="bi bi-calendar"></i> Plan Maintenance
                </a>
            </div>
        </div>
    <script>
        function updateSelectedEquipment(equipmentId, equipmentName) {
            const selectedEquipmentDiv = document.getElementById('selected_equipment');
            selectedEquipmentDiv.innerHTML = `Selected Equipment: ${equipmentName} (ID: ${equipmentId})`;
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'equipment_id';
            hiddenInput.value = equipmentId;
            selectedEquipmentDiv.appendChild(hiddenInput);
        }

        function addPersonnel() {
            const personnelContainer = document.getElementById('personnel_container');
            const newPersonnelDiv = document.createElement('div');
            newPersonnelDiv.innerHTML = `
                <input type="text" name="responsible_firstname[]" placeholder="First Name" required>
                <input type="text" name="responsible_lastname[]" placeholder="Last Name" required>
                <input type="text" name="responsible_department[]" placeholder="Department" required>
            `;
            personnelContainer.appendChild(newPersonnelDiv);
        }

        function filterEquipment() {
            const filterValue = document.getElementById('equipment_type_filter').value;
            const equipmentItems = document.querySelectorAll('.equipment-item'); // Assuming each equipment item has this class

            equipmentItems.forEach(item => {
                const equipmentType = item.getAttribute('data-type'); // Assuming you have a data-type attribute
                if (filterValue === "" || equipmentType === filterValue) {
                    item.style.display = ""; // Show item
                } else {
                    item.style.display = "none"; // Hide item
                }
            });
        }
    </script>
</head>
<body>
    <h1>Equipment Maintenance</h1>

	<h3>List of Equipment</h3>

	<label for="equipment_type_filter">Filter by Equipment Type:</label>
	<select id="equipment_type_filter" onchange="filterEquipment()">
        <option value="">All</option>
        <?php foreach ($equipmentTypes as $type): ?>
            <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
        <?php endforeach; ?>
	</select>

	<!-- Equipment List -->
	<div id="equipment_list">
        <?php foreach ($equipmentList as $equipment): ?>
            <div class="equipment-item" data-type="<?php echo htmlspecialchars($equipment['equipment_type']); ?>" onclick="updateSelectedEquipment(<?php echo htmlspecialchars($equipment['equipment_id']); ?>, '<?php echo htmlspecialchars($equipment['equipment_name']); ?>')">
                <?php echo htmlspecialchars($equipment['equipment_name']); ?> (Serial: <?php echo htmlspecialchars($equipment['serial_num']); ?>)
            </div>
        <?php endforeach; ?>
	</div>

	<form action="maintenance_process.php" method="POST">
        <label for="jo_number">Job Order:</label>
        <input type="text" name="jo_number" id="jo_number" required><br>

        <div id="selected_equipment">Selected Equipment:</div>

        <label for="actions_taken">Actions Taken:</label>
        <textarea name="actions_taken" id="actions_taken" required></textarea><br>

		<label for="remarks">Remarks:</label>
		<select name="remarks" id="remarks" required>
			<option value="Pending">Pending</option>
			<option value="For Transfer">For Transfer</option>
			<option value="Resolved">Resolved</option>
		</select><br>

		<div id="personnel_container">
            <label>Responsible Personnel:</label><br>
            <input type="text" name="responsible_firstname[]" placeholder="First Name"><br>
            <input type="text" name="responsible_lastname[]" placeholder="Last Name"><br>
            <input type="text" name="responsible_department[]" placeholder="Department"><br>
        </div>

        <button type="button" onclick="addPersonnel()">Add Personnel</button><br>

        <label for="maintaindate">Date:</label>
        <input type="date" name="maintaindate" id="maintaindate" required><br>

        <button type="submit">Log Maintenance</button>
        <button type="button" onclick="window.location.href='equipment_maintenance.php'">Cancel</button>
    </form>
	
	<h3>Maintenance Logs</h3>
	<table border="1">
	    <thead>
	        <tr>
				<th>Equipment Name</th>
				<th>Maintenance Date</th>
	            <th>Job Order Number</th>
				<th>Actions Taken</th>
				<th>Remarks</th>
	            <th>Personnel Name</th>
	        </tr>
	    </thead>
	    <tbody>
	        <?php if (!empty($maintenanceLogs)): ?>
	            <?php foreach ($maintenanceLogs as $log): ?>
	                <tr>
	                    <td><?php echo htmlspecialchars($log['equipment_name']); ?></td>
						<td><?php echo htmlspecialchars($log['maintenance_date']); ?></td>
	                    <td><?php echo htmlspecialchars($log['jo_number']); ?></td>
	                    <td><?php echo htmlspecialchars($log['actions_taken']); ?></td>
	                    <td><?php echo htmlspecialchars($log['remarks']); ?></td>
						<td><?php echo htmlspecialchars($log['firstname'] . ' ' . $log['lastname']); ?></td>
	                </tr>
	            <?php endforeach; ?>
	        <?php else: ?>
	            <tr><td colspan="6">No maintenance logs found.</td></tr>
	        <?php endif; ?>
	    </tbody>
	</table>

</body>
</html>