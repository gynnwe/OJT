<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1); 

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: index.php");
    exit;
}

include 'conn.php';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sqlTypes = "SELECT equip_type_id, equip_type_name FROM equipment_type WHERE deleted_id = 0";
    $stmtTypes = $conn->prepare($sqlTypes);
    $stmtTypes->execute();
    $equipment_types = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);

    $selectedTypeId = isset($_GET['equipment_type']) ? $_GET['equipment_type'] : '';
    $searchTerm = isset($_GET['search_term']) ? $_GET['search_term'] : '';

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

    $sqlRemarks = "SELECT remarks_id, remarks_name FROM remarks WHERE deleted_id = 0";
    $stmtRemarks = $conn->prepare($sqlRemarks);
    $stmtRemarks->execute();
    $remarks_options = $stmtRemarks->fetchAll(PDO::FETCH_ASSOC);
    
    $sqlPersonnel = "SELECT personnel_id, firstname, lastname, office FROM personnel WHERE deleted_id = 0";
    $stmtPersonnel = $conn->prepare($sqlPersonnel);
    $stmtPersonnel->execute();
    $personnel_options = $stmtPersonnel->fetchAll(PDO::FETCH_ASSOC);

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $jo_number = $_POST['jo_number'];
    $actions_taken = $_POST['actions_taken'];
    $remarks_id = $_POST['remarks'];
    $maintaindate = $_POST['maintaindate'];

    if (isset($_POST['equipment_id'])) {
        $equipment_id = $_POST['equipment_id'];

        try {
            // Create a new PDO connection
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if personnel is selected from dropdown
            if (isset($_POST['personnel_id']) && !empty($_POST['personnel_id'])) {
                $personnel_id = intval($_POST['personnel_id']); // Get selected personnel ID

                try {
                    // Insert into maintenance logs table with remarks_id included
                    $stmtInsertLog = $conn->prepare("INSERT INTO ict_maintenance_logs (jo_number, personnel_id, equipment_id, maintenance_date, actions_taken, remarks_id) VALUES (?, ?, ?, ?, ?, ?)");
                    if (!$stmtInsertLog->execute([$jo_number, $personnel_id, $equipment_id, $maintaindate, $actions_taken, $remarks_id])) {
                        print_r($stmtInsertLog->errorInfo()); 
                    } else {
                        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
                    }
                } catch (PDOException $e) {
                }
            } else {
                header("Location: " . $_SERVER['PHP_SELF']);
        exit;
            }

        } catch (PDOException $e) {
            header("Location: " . $_SERVER['PHP_SELF']);
        exit;
        }
    } else {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($conn)) {
        $conn = null; 
    }
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

    .main-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 16px;
    }

    .top-section {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 24px;
    }

    .card-section {
        background: #fff;
        padding: 16px;
        border-radius: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        min-width: 300px;
    }

    .left-card {
        max-width: 400px;
    }

    .right-card {
        flex: 1.5;
    }

    h3 {
        font-size: 0.90em;
        color: #343a40;
        margin-bottom: 16px;
        font-weight: bold;
        border-bottom: 1px solid #ccc;
        padding-bottom: 8px;
    }

    label {
        font-weight: normal !important;
    }

    .log-maintenance-form .mb-3, .log-maintenance-form .mb-4 {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .log-maintenance-form label {
        width: 150px;
        font-weight: bold;
        font-size: 0.85em;
        margin-bottom: 0;
        color: #333;
    }

    .form-control, .form-select, textarea, input[type="date"], input[type="text"], input[type="search"] {
        font-size: 0.85em;
        border-radius: 20px; 
        border: 1px solid #ced4da;
        padding: 6px;
        width: 100%; /* Uniform width */
        box-sizing: border-box;
    }

    /* Make all dropdowns grey */
    .form-select {
        background-color: #e9ecef;
    }

    textarea {
        resize: vertical;
        flex: 1;
    }

    .btn-primary {
        background-color: #b53236 !important;
        border: none !important;
        border-radius: 20px;
    }

    .btn-primary:hover {
        background-color: #a12c30 !important;
    }

    .btn-secondary {
        background-color: #6c757d !important;
        border: none !important;
        border-radius: 20px;
    }

    .btn-secondary:hover {
        background-color: #5c636a !important;
    }

    .btn-select {
        background-color: #b53236;
        color: #fff;
        font-weight: bold;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8em;
        cursor: pointer;
        border: none;
    }

    .btn-select:hover {
        background-color: #a12c30;
        color: #fff;
    }

    .selected-equipment-label {
        font-weight: normal;
        font-size: 0.9em;
        color: #333;
        margin-bottom: 16px;
    }

    .selected-equipment-item {
        background-color: #b53236;
        font-weight: bold;
        font-size: 0.80em;
        color: #fff;
        padding: 2px 6px;
        border-radius: 20px;
        margin-left: 8px;
        display: inline-block;
    }

    .filter-search-row {
        display: flex;
        gap: 16px;
        align-items: end;
        margin-bottom: 16px;
    }

    .maintenance-logs-section {
        background: #fff;
        padding: 16px;
        border-radius: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-top: 24px;
    }

    .maintenance-logs-section h3 {
        border-bottom: 1px solid #ccc;
        padding-bottom: 8px;
        margin-bottom: 16px;
    }

    .equipment-list-title {
        padding: 8px 0;
        margin-bottom: 8px;
        margin-top: 8px;
        font-weight: bold;
        font-size: 1em;
    }

    .log-buttons {
        display: flex;
        justify-content: end;
        gap: 8px;
    }

    /* Table styling */
    /* Set table-layout:fixed so that column widths align between header and body tables */
    .header-table, .body-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
        table-layout: fixed;
    }

    .header-table thead th {
        border: none;
        background: none;
        font-weight: bold;
        font-size: 0.85em;
        text-align: left;
        color: #343a40;
        padding-bottom: 4px;
    }

    .body-table tbody tr {
        background: #fff;
        font-size: 0.80em;
        border-radius: 20px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .body-table tbody tr td {
        vertical-align: middle;
        padding: 8px;
    }

    .body-table tbody tr td:first-child {
        border-top-left-radius: 8px;
        border-bottom-left-radius: 8px;
    }

    .body-table tbody tr td:last-child {
        border-top-right-radius: 8px;
        border-bottom-right-radius: 8px;
    }

    /* Scrollable area for the body only */
    .scroll-container {
        max-height: 200px; /* Adjust as needed */
        overflow-y: auto;
    }

    </style>
</head>
<body>
    <!-- PHP variables assumed to be defined elsewhere:
         $equipment_types, $selectedTypeId, $searchTerm,
         $equipment, $remarks_options, $personnel_options, $maintenanceLogs -->

    <div class="main-container mt-4">
        
        <div class="top-section">
            <!-- Log Maintenance Form Card -->
            <div class="card-section left-card">
                <h3>Log Maintenance</h3>
                <form action="equipment_maintenance.php" method="POST" onsubmit="incrementJobOrder()" class="log-maintenance-form">
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

                    <div class="log-buttons">
                        <button type="submit" class="btn btn-primary">Log Maintenance</button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='equipment_maintenance.php'">Cancel</button>
                    </div>
                </form>
            </div>

            <!-- Right Panel: Select Registered Equipment -->
            <div class="card-section right-card">
                <h3>Select Registered Equipment to Log for Maintenance</h3>
                <form method="GET" action="equipment_maintenance.php" class="filter-search-row">
                    <div style="flex:1;">
                        <label for="equipment_type_filter" style="display:block; font-weight:bold; font-size:0.85em; margin-bottom:4px;">Filter by Equipment Type</label>
                        <select id="equipment_type_filter" name="equipment_type" class="form-select" onchange="this.form.submit()">
                            <option value="">All</option>
                            <?php foreach ($equipment_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['equip_type_id']); ?>" <?php echo ($type['equip_type_id'] == $selectedTypeId) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['equip_type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="flex:1;">
                        <input type="text" id="search_term" name="search_term" class="form-control" placeholder="Search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                </form>

                <div class="equipment-list-title">List of Equipments</div>

                <!-- Table with fixed header and scrollable body -->
                <table class="header-table">
                    <thead>
                        <tr>
                            <th>Equipment Name</th>
                            <th>Property Number</th>
                            <th>Status</th>
                            <th>Date Purchased</th>
                            <th></th>
                        </tr>
                    </thead>
                </table>
                <div class="scroll-container">
                    <table class="body-table">
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
    <!-- Use the same filter-search-row class and styling as above -->
    <form class="filter-search-row" onsubmit="return false;">
        <div style="flex:1;">
            <label for="column_filter" style="display:block; font-weight:bold; font-size:0.85em; margin-bottom:4px;"></label>
            <select id="column_filter" class="form-select">
                <option value="all">All Columns</option>
                <option value="0">Equipment Name</option>
                <option value="1">Maintenance Date</option>
                <option value="2">Job Order Number</option>
                <option value="3">Actions Taken</option>
                <option value="4">Remarks</option>
                <option value="5">Responsible Personnel</option>
            </select>
        </div>
        <div style="flex:1;">
            <label for="search_logs" style="display:block; font-weight:bold; font-size:0.85em; margin-bottom:4px;"></label>
            <input type="text" id="search_logs" class="form-control" placeholder="Search">
        </div>
    </form>

    <table class="table header-table">
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
    </table>
    <div class="scroll-container">
        <table class="body-table">
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
                    <tr>
                        <td colspan="6">No maintenance logs found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

    <script>
        function generateJobOrderAndUpdate(equipmentId, equipmentName) {
            const selectedEquipmentDiv = document.getElementById('selected_equipment');
            selectedEquipmentDiv.innerHTML = 'Selected Equipments <span class="selected-equipment-item">' + equipmentName + ' (ID: ' + equipmentId + ')</span>';

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

<script>
        document.getElementById('search_logs').addEventListener('input', filterLogs);
        document.getElementById('column_filter').addEventListener('change', filterLogs);

        function filterLogs() {
            const searchValue = document.getElementById('search_logs').value.toLowerCase();
            const columnIndex = document.getElementById('column_filter').value;
            const rows = document.querySelectorAll('.maintenance-logs-section .body-table tbody tr');

            rows.forEach(row => {
                let match = false;

                if (columnIndex === "all") {
                    row.querySelectorAll('td').forEach(cell => {
                        if (cell.textContent.toLowerCase().includes(searchValue)) {
                            match = true;
                        }
                    });
                } else {
                    const cell = row.querySelectorAll('td')[columnIndex];
                    if (cell && cell.textContent.toLowerCase().includes(searchValue)) {
                        match = true;
                    }
                }

                row.style.display = match ? '' : 'none';
            });
        }
    </script>
</body>
</html>