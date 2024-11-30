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

    // Fetch all non-deleted equipment types for the dropdown
    $sqlTypes = "SELECT equip_type_id, equip_type_name FROM equipment_type WHERE deleted_id = 0";
    $stmtTypes = $conn->prepare($sqlTypes);
    $stmtTypes->execute();
    $equipment_types = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);

    // Check if an equipment type is selected
    $selectedTypeId = isset($_GET['equipment_type']) ? $_GET['equipment_type'] : '';
    $searchTerm = isset($_GET['search_term']) ? $_GET['search_term'] : '';

    // Prepare and execute SQL query to fetch equipment based on the selected type and search term
    $sqlEquipment = "SELECT e.equipment_id, e.equip_name, e.property_num, e.status, e.date_purchased
                     FROM equipment e
                     WHERE (:equip_type_id = '' OR e.equip_type_id = :equip_type_id)
                     AND (e.equip_name LIKE :search_term OR e.property_num LIKE :search_term)";
    $stmtEquipment = $conn->prepare($sqlEquipment);
    $stmtEquipment->bindParam(':equip_type_id', $selectedTypeId);
    $searchWildcard = "%$searchTerm%";
    $stmtEquipment->bindParam(':search_term', $searchWildcard);
    $stmtEquipment->execute();
    $equipment = $stmtEquipment->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all non-deleted remarks for the dropdown
    $sqlRemarks = "SELECT remarks_id, remarks_name FROM remarks WHERE deleted_id = 0";
    $stmtRemarks = $conn->prepare($sqlRemarks);
    $stmtRemarks->execute();
    $remarks_options = $stmtRemarks->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch all non-deleted Personnel for the dropdown
    $sqlPersonnel = "SELECT personnel_id, firstname, lastname, office FROM personnel WHERE deleted_id = 0";
    $stmtPersonnel = $conn->prepare($sqlPersonnel);
    $stmtPersonnel->execute();
    $personnel_options = $stmtPersonnel->fetchAll(PDO::FETCH_ASSOC);

    // Fetch data from Maintenance Logs with joins
    $sqlLogs = "
        SELECT 
            ml.jo_number,
            ml.maintenance_date,
            ml.actions_taken,
            r.remarks_name AS remarks,
            e.equip_name AS equipment_name,
            p.firstname,
            p.lastname
        FROM 
            ict_maintenance_logs ml
        LEFT JOIN 
            equipment e ON ml.equipment_id = e.equipment_id
        LEFT JOIN 
            remarks r ON ml.remarks_id = r.remarks_id
        LEFT JOIN 
            personnel p ON ml.personnel_id = p.personnel_id
    ";
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
    <title>ICT Equipment Maintenance</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<style>
/* Styles for Equipment Maintenance Form */
body {
    font-family: Arial, sans-serif;
    background-color: #f8f9fa;
    color: #333;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 1200px;
    margin: 10px auto;
    padding: 8px;
    background-color: #fff;
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

h3 {
    font-size: 1.1em;
    color: #343a40;
    margin-top: 8px;
    margin-bottom: 8px;
}

label {
    font-weight: bold;
    margin-top: 4px;
    font-size: 0.85em;
}

.form-control, .form-select {
    margin-bottom: 4px;
    padding: 5px;
    border-radius: 4px;
    border: 1px solid #ced4da;
    font-size: 0.85em;
}

.btn {
    padding: 5px 12px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 0.85em;
}

.btn-primary {
    background-color: #b53236;
    border: none;
}

.btn-primary:hover {
    background-color: #a12c30;
}

.btn-secondary {
    background-color: #6c757d;
    border: none;
}

.btn-secondary:hover {
    background-color: #5c636a;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 6px;
    font-size: 0.8em;
}

.table th, .table td {
    border: 1px solid #dee2e6;
    padding: 5px;
    text-align: left;
}

.table th {
    background-color: #e9ecef;
    font-weight: bold;
}

#selected_equipment {
    margin-top: 6px;
    font-weight: bold;
    color: #495057;
    font-size: 0.85em;
}

button {
    cursor: pointer;
}

/* Dropdown and input field styling */
input[type="text"], input[type="date"], select {
    width: 100%;
    box-sizing: border-box;
}

/* For responsiveness */
@media (max-width: 768px) {
    .container {
        padding: 6px;
    }

    .btn {
        width: 100%;
        margin-bottom: 4px;
    }

    .table th, .table td {
        padding: 4px;
    }
}
</style>
<body>
    <div class="container mt-5">
        <!-- Filter Equipment by Type -->
        <form method="GET" action="equipment_maintenance.php">
            <label for="equipment_type_filter">Filter by Equipment Type:</label>
            <select id="equipment_type_filter" name="equipment_type" class="form-select" onchange="this.form.submit()">
                <option value="">All</option>
                <?php foreach ($equipment_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type['equip_type_id']); ?>" <?php echo ($type['equip_type_id'] == $selectedTypeId) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type['equip_type_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Search Filter -->
            <label for="search_term" class="mt-3">Search by Equipment Name or Property Number:</label>
            <div class="input-group mb-3">
                <input type="text" id="search_term" name="search_term" class="form-control" placeholder="Enter keyword" value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button type="submit" class="btn btn-danger">Search</button>
            </div>
        </form>

        <!-- Display Equipment -->
        <h3 class="mt-4">List of Equipment</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Equipment Name</th>
                    <th>Property Number</th>
                    <th>Status</th>
                    <th>Date Purchased</th>
                    <th>Select</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($equipment)): ?>
                    <?php foreach ($equipment as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['equip_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['property_num']); ?></td>
                            <td><?php echo htmlspecialchars($item['status']); ?></td>
                            <td><?php echo htmlspecialchars($item['date_purchased']); ?></td>
                            <td>
                                <button type="button" class="btn btn-primary" onclick="generateJobOrderAndUpdate('<?php echo htmlspecialchars($item['equipment_id']); ?>', '<?php echo htmlspecialchars($item['equip_name']); ?>')">
                                    Select
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No equipment found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Maintenance Form -->
        <h3>Log Maintenance</h3>
        <form action="maintenance_process.php" method="POST" onsubmit="incrementJobOrder()">
            <label for="jo_number">Job Order:</label>
            <input type="text" name="jo_number" id="jo_number" class="form-control mb-3" required readonly>

            <div id="selected_equipment">Selected Equipment:</div>

            <label for="actions_taken">Actions Taken:</label>
<textarea name="actions_taken" id="actions_taken" class="form-control mb-3" required maxlength="45" 
          placeholder="Required"></textarea>
          <label for="remarks">Remarks:</label>
            <select name="remarks" id="remarks" class="form-control mb-3" required>
                <?php foreach ($remarks_options as $remark): ?>
                    <option value="<?php echo htmlspecialchars($remark['remarks_id']); ?>">
                        <?php echo htmlspecialchars($remark['remarks_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="personnel">Personnel:</label>
            <select name="personnel_id" id="personnel" class="form-control mb-3" required>
                <?php if (!empty($personnel_options)): ?>
                    <?php foreach ($personnel_options as $personnel): ?>
                        <option value="<?php echo htmlspecialchars($personnel['personnel_id']); ?>">
                            <?php echo htmlspecialchars($personnel['firstname'] . " " . $personnel['lastname'] . " - " . $personnel['office']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="">No personnel added.</option>
                <?php endif; ?>
            </select>
            
            <label for="maintaindate">Date:</label>
            <input type="date" name="maintaindate" id="maintaindate" class="form-control mb-3" required max="<?php echo date('Y-m-d'); ?>">

            <button type="submit" class="btn btn-primary">Log Maintenance</button>
            <button type="button" class="btn btn-secondary" onclick="window.location.href='equipment_maintenance.php'">Cancel</button>
        </form>

        <!-- Maintenance Logs Section -->
        <h3>Maintenance Logs</h3>
        <table border="1" class="table table-bordered">
            <thead>
                <tr>
                    <th>Equipment Name</th>
                    <th>Maintenance Date</th>
                    <th>Job Order Number</th>
                    <th>Actions Taken</th>
                    <th>Remarks</th>
                    <th>Responsible Personnel</th>
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

    </div>

    <!-- JavaScript -->
    <script>
        function generateJobOrderAndUpdate(equipmentId, equipmentName) {
            const selectedEquipmentDiv = document.getElementById('selected_equipment');
            selectedEquipmentDiv.innerHTML = `Selected Equipment: ${equipmentName} (ID: ${equipmentId})`;

            const date = new Date();
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            let jobOrderNum = localStorage.getItem(`jobOrderNum_${year}_${month}`) || 1;

            const jobOrder = `CIC${year}${month}${jobOrderNum}`;
            document.getElementById('jo_number').value = jobOrder;

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'equipment_id';
            hiddenInput.value = equipmentId;
            selectedEquipmentDiv.appendChild(hiddenInput);
        }

        function incrementJobOrder() {
            const date = new Date();
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            let jobOrderNum = parseInt(localStorage.getItem(`jobOrderNum_${year}_${month}`)) || 1;
            jobOrderNum++;
            localStorage.setItem(`jobOrderNum_${year}_${month}`, jobOrderNum);
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.onload = function() {
            const dateInput = document.getElementById("maintaindate");
            function setMaxDate() {
                const today = new Date().toISOString().split("T")[0];
                dateInput.setAttribute("max", today);
            }
            setMaxDate();
            dateInput.addEventListener("focus", setMaxDate);
        };
    </script>
</body>
</html>
