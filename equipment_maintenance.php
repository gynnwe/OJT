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

    <style>
    body {
        font-family: Arial, sans-serif;
        background: #f7f7f7;
        margin: 0;
        padding: 0;
        color: #333;
    }

    /* Container for the entire page */
    .main-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 16px;
    }

    /* Top section containing two side-by-side cards */
    .top-section {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 24px;
    }

    .card-section {
        flex: 1;
        background: #fff;
        padding: 16px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        min-width: 300px;
    }

    .left-card {
        max-width: 400px;
    }

    .right-card {
        flex: 1.5;
    }

    /* Headings */
    h3 {
        font-size: 1.1em;
        color: #343a40;
        margin-bottom: 16px;
        font-weight: bold;
    }

    /* Form Labels */
    label {
        display: block;
        font-weight: bold;
        font-size: 0.85em;
        margin-bottom: 4px;
        color: #333;
    }

    /* Inputs and selects */
    .form-control, .form-select, textarea {
        font-size: 0.85em;
        border-radius: 4px;
        border: 1px solid #ced4da;
        margin-bottom: 8px;
        padding: 6px;
    }

    textarea {
        resize: vertical;
    }

    /* Buttons */
    .btn-primary {
        background-color: #b53236 !important;
        border: none !important;
    }

    .btn-primary:hover {
        background-color: #a12c30 !important;
    }

    .btn-secondary {
        background-color: #6c757d !important;
        border: none !important;
    }

    .btn-secondary:hover {
        background-color: #5c636a !important;
    }

    .btn-select {
        background-color: #fff;
        border: 2px solid #b53236;
        color: #b53236;
        font-weight: bold;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8em;
        cursor: pointer;
    }

    .btn-select:hover {
        background-color: #b53236;
        color: #fff;
    }

    /* Selected Equipment label */
    .selected-equipment-label {
        font-weight: bold;
        margin-bottom: 12px;
        font-size: 0.9em;
        color: #495057;
    }

    /* Tables */
    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85em;
    }

    .table th, .table td {
        border: 1px solid #dee2e6;
        padding: 8px;
        vertical-align: middle;
    }

    .table th {
        background-color: #e9ecef;
        font-weight: bold;
        white-space: nowrap;
    }

    /* Equipment list within right card */
    .equipment-list {
        margin-top: 8px;
    }

    /* Maintenance Logs Section */
    .maintenance-logs-section {
        background: #fff;
        padding: 16px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    @media (max-width: 992px) {
        .top-section {
            flex-direction: column;
        }
    }

    @media (max-width: 768px) {
        .btn {
            width: 100%;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            font-size: 0.9em;
        }

        .btn-select {
            width: 100%;
        }
    }
    </style>
</head>
<body>
    <!-- PHP variables are assumed to be defined elsewhere:
         $equipment_types, $selectedTypeId, $searchTerm,
         $equipment, $remarks_options, $personnel_options, $maintenanceLogs -->

    <div class="main-container mt-4">
        
        <!-- Top Section: Left (Log Maintenance) and Right (Select Registered Equipment) -->
        <div class="top-section">
            <!-- Log Maintenance Form Card -->
            <div class="card-section left-card">
                <h3>Log Maintenance</h3>
                <form action="maintenance_process.php" method="POST" onsubmit="incrementJobOrder()">
                    <div class="mb-3">
                        <label for="jo_number">Job Order:</label>
                        <input type="text" name="jo_number" id="jo_number" class="form-control" required readonly>
                    </div>

                    <div id="selected_equipment" class="selected-equipment-label">Selected Equipments</div>

                    <div class="mb-3">
                        <label for="actions_taken">Actions Taken:</label>
                        <textarea name="actions_taken" id="actions_taken" class="form-control" required maxlength="40" placeholder="Required"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="remarks">Remark:</label>
                        <select name="remarks" id="remarks" class="form-select" required>
                            <?php foreach ($remarks_options as $remark): ?>
                                <option value="<?php echo htmlspecialchars($remark['remarks_id']); ?>">
                                    <?php echo htmlspecialchars($remark['remarks_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="personnel">Personnel:</label>
                        <select name="personnel_id" id="personnel" class="form-select" required>
                            <?php if (!empty($personnel_options)): ?>
                                <?php foreach ($personnel_options as $person): ?>
                                    <option value="<?php echo htmlspecialchars($person['personnel_id']); ?>">
                                        <?php echo htmlspecialchars($person['firstname'] . " " . $person['lastname'] . " - " . $person['office']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">No personnel added.</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="maintaindate">Date:</label>
                        <input type="date" name="maintaindate" id="maintaindate" class="form-control" required max="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Log Maintenance</button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='equipment_maintenance.php'">Cancel</button>
                    </div>
                </form>
            </div>

            <!-- Right Panel: Select Registered Equipment -->
            <div class="card-section right-card">
                <h3>Select Registered Equipment to Log for Maintenance</h3>
                <!-- Filter Equipment by Type -->
                <form method="GET" action="equipment_maintenance.php" class="mb-3">
                    <div class="mb-3">
                        <label for="equipment_type_filter">Filter by Equipment Type</label>
                        <select id="equipment_type_filter" name="equipment_type" class="form-select" onchange="this.form.submit()">
                            <option value="">All</option>
                            <?php foreach ($equipment_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['equip_type_id']); ?>" <?php echo ($type['equip_type_id'] == $selectedTypeId) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['equip_type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <label for="search_term" class="mb-2">Search</label>
                    <div class="input-group mb-3">
                        <input type="text" id="search_term" name="search_term" class="form-control" placeholder="Enter keyword" value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button type="submit" class="btn btn-danger">Search</button>
                    </div>
                </form>

                <!-- Display Equipment -->
                <div class="equipment-list">
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
                                            <button type="button" class="btn-select" 
                                                onclick="generateJobOrderAndUpdate('<?php echo htmlspecialchars($item['equipment_id']); ?>', '<?php echo htmlspecialchars($item['equip_name']); ?>')">
                                                SELECT
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5">No equipment found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Maintenance Logs -->
        <div class="maintenance-logs-section">
            <h3>Maintenance Logs</h3>
            <table class="table table-bordered">
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

            let hiddenInput = document.querySelector('input[name="equipment_id"]');
            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'equipment_id';
                selectedEquipmentDiv.appendChild(hiddenInput);
            }
            hiddenInput.value = equipmentId;
        }

        function incrementJobOrder() {
            const date = new Date();
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            let jobOrderNum = parseInt(localStorage.getItem(`jobOrderNum_${year}_${month}`)) || 1;
            jobOrderNum++;
            localStorage.setItem(`jobOrderNum_${year}_${month}`, jobOrderNum);
        }

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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
