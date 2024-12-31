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

    $sqlEquipment = "
    SELECT e.equipment_id, e.equip_name, e.property_num, e.status, e.date_purchased
    FROM equipment e
    WHERE (:equip_type_id = '' OR e.equip_type_id = :equip_type_id)
    AND (:status = '' OR e.status = :status)
    AND (:date_purchased = '' OR e.date_purchased = :date_purchased)
    AND (e.equip_name LIKE :search_term OR e.property_num LIKE :search_term)";
$stmtEquipment = $conn->prepare($sqlEquipment);
$stmtEquipment->bindParam(':equip_type_id', $selectedTypeId);

$status = isset($_GET['status']) ? $_GET['status'] : '';
$stmtEquipment->bindParam(':status', $status);

$datePurchased = isset($_GET['date_purchased']) ? $_GET['date_purchased'] : '';
$stmtEquipment->bindParam(':date_purchased', $datePurchased);

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
        background-color: transparent !important;
    }

    .main-container {
        max-width: 1350px;
        margin: 43px 40px 30px 30px !important;
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
        color: #3A3A3A;
        font-weight: bold !important;
        font-size: 13px !important;
    }
		
	.section-divider1, .section-divider2 {
        border: none;
        height: 2px;
		background-color: #ddd;
        margin-top: 12px;
		margin-bottom: 19px;
    }

    label {
        font-weight: normal !important;
		font-size: 12px;
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

    .form-control, textarea, input[type="date"], input[type="text"], input[type="search"] {
        font-size: 12px;
        width: 100%;
        box-sizing: border-box;
		border: 2px solid #646464;
        border-radius: 24px;
        color: #646464;
    }

    .form-select {
        background-color: #e9ecef;
		font-size: 12px;
        width: 100%;
        box-sizing: border-box;
        border-radius: 24px;
        color: #646464;
    }
		
	#equipment_type_filter {
		width: 240px !important;
	}
		
	.search-form-control {
		width: 240px !important;
	}

    textarea {
        resize: vertical;
        flex: 1;
    }
		
	.btn-primary, .btn-secondary {
        color: white;
        font-weight: bold;
        font-size: 12px;
        border: none;
        border-radius: 14px;
        cursor: pointer;	
		margin-bottom: 10px;
	}

    .btn-primary {
        background-color: #a81519 !important;
    }

    .btn-primary:hover {
        background-color: #a12c30 !important;
    }

    .btn-secondary {
        background-color: #6c757d !important;
    }

    .btn-secondary:hover {
        background-color: #5c636a !important;
    }

    .btn-select {
        background-color: maroon;
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
        font-weight: normal !important;
        font-size: 13px;
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
        margin-right: 50%;
    }

    .maintenance-logs-section {
        background: #fff;
        padding: 16px;
        border-radius: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-top: 24px;
    }

    .equipment-list-title {
        padding: 8px 0;
        margin-bottom: 2px;
        margin-top: -10px;
        font-weight: bold;
        font-size: 13px;
    }

    .log-buttons {
        display: flex;
        justify-content: end;
        gap: 8px;
    }
		
		
		
		
	table-responsive {
		border-radius: 10px;
		overflow: hidden;
		margin-top: -10px;
	}

	table {
		width: 100%;
		border: none;
		dispaly: block;
	}
		
	tbody {
		height: 210px;
		display: inline-block;
		width: 100%;
		overflow: auto;
		}

	table th,
	table td {
		color: #646464;
		padding: 10px;
		text-align: left;
		font-size: 12px;
	}
		
	table th, .maintainance-thead th {
		text-align:left ;
		font-size :13px ;
		font-weight: normal !important;
		color:#646464 ;
		border: none ;
		display: inline-block;
		margin-top: -5px;
	}

	table tbody tr:nth-child(odd), table tbody tr:nth-child(even), .maintainance-table tr:nth-child(odd), .maintainance-table tr:nth-child(even) {
		background-color: transparent;
		border: 1px solid #DFDFDF;
		border-radius: 14px; 
		display: block;
		width: 100%;
		height: 36px;
		margin-bottom: 5px !important;
	}
		
	table td, .maintainance-table td {
		color:#646464 ; 
		font-weight :bold ;
		border-collapse: separate; 
		border-spacing: 10px 40px;
		border: none; 
		display: inline-block;
		padding: 7px 10px 0px 10px;
		background-color: transparent;
	}
		
	tbody tr:hover {
		background-color: #ebebeb !important;
	}
		
	td:nth-child(1) {
		width: 27%;
	}

	td:nth-child(2) {
		width: 21%; 
		margin-left: -5px;
	}

	td:nth-child(3) {
		width: 19%;
		margin-left: -5px;
	}

	td:nth-child(4) {
		width: 19%;
		margin-left: -5px;
	}
		
	td:nth-child(5) {
		width: 14%;
		margin-left: -5px;
	}
		
	th:nth-child(1) {
		width: 27%; 
	}

	th:nth-child(2) {
		width: 21%; 
		margin-left: -5px;
	}

	th:nth-child(3) {
		width: 19%; 
		margin-left: -4px;
	}

	th:nth-child(4) {
		width: 19%;
		margin-left: -4px;
	}
	th:nth-child(5) {
		width: 14%;
		margin-left: -5px;
	}
		
	.maintainance-table tbody {
		overflow-y: auto; 
    	max-height: 200px;
	}

	.maintainance-thead tr {
		border-color: transparent !important;
	}
	.maintainance-table tbody tr {
		padding-top: 2px !important;
	}
		
	.maintainance-table td:nth-child(1) {
		width: 20%;
	}
	.maintainance-table td:nth-child(2) {
		width: 12%; 
		margin-left: -5px;
	}

	.maintainance-table td:nth-child(3) {
		width: 16%;
		margin-left: -5px;
	}

	.maintainance-table td:nth-child(4) {
		width: 16%;
		margin-left: -5px;
	}
		
	.maintainance-table td:nth-child(5) {
		width: 16%;
		margin-left: -5px;
	}
	.maintainance-table td:nth-child(6) {
		width: 16%;
		margin-left: -5px;
	}
		
	.maintainance-table th:nth-child(1) {
		width: 20%; 
	}

	.maintainance-table th:nth-child(2) {
		width: 12%; 
		margin-left: -5px;
	}

	.maintainance-table th:nth-child(3) {
		width: 16%; 
		margin-left: -5px;
	}

	.maintainance-table th:nth-child(4) {
		width: 16%;
		margin-left: -7px;
	}
	.maintainance-table th:nth-child(5) {
		width: 16%;
		margin-left: -8px;
	}
	.maintainance-table th:nth-child(6) {
		width: 16%;
		margin-left: -8px;
	}

    </style>
</head>
<body>
    <div class="main-container mt-4">
        <div class="top-section">
            <div class="card-section left-card">
                <h3>Log Maintenance</h3>
				<hr class="section-divider1">
                <form action="equipment_maintenance.php" method="POST" onsubmit="incrementJobOrder()" class="log-maintenance-form">
                    <div class="mb-3">
                        <label for="jo_number">Job Order:</label>
                        <input type="text" name="jo_number" id="jo_number" class="form-control" required readonly>
                    </div>

                    <div id="selected_equipment" class="selected-equipment-label">Selected Equipments:</div>

                    <div class="mb-3">
                        <label for="actions_taken">Actions Taken:</label>
                        <textarea name="actions_taken" id="actions_taken" class="form-control" required maxlength="200" placeholder="Required"></textarea>
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
				<hr class="section-divider1">
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
                        <input type="text" id="search_term" name="search_term" class="form-control search-form-control" placeholder="Search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                </form>

                <div class="equipment-list-title">List of Registered Equipments</div>
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
                <table class="body-table">
                	<tbody>
						<?php 
						$maxRows = 5; 
						$totalEntries = !empty($equipment) ? count($equipment) : 0;

						if ($totalEntries > 0): ?>
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

							<?php for ($j = $totalEntries; $j < $maxRows; $j++): ?>
								<tr class="empty-row"><td colspan="5"></td></tr>
							<?php endfor; ?>

						<?php else: ?>
							<tr><td colspan="5">No equipment found.</td></tr>
							<?php for ($j = 1; $j < $maxRows; $j++): ?>
								<tr class="empty-row"><td colspan="5"></td></tr>
							<?php endfor; ?>
						<?php endif; ?>
					</tbody>

                </table>
            </div>
        </div>

        <!-- Maintenance Logs -->
        <div class="maintenance-logs-section">
        <h3>Maintenance Logs</h3>
		<hr class="section-divider2">
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

		<table class="table maintainance-table">
			<thead class="maintainance-thead">
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
				<?php 
				$maxRows = 5; // Set maximum rows to display for the initial view
				$totalEntries = count($maintenanceLogs);

				if ($totalEntries === 0): ?>
					<tr><td colspan="6">No maintenance logs found.</td></tr>
					<?php for ($j = 1; $j < $maxRows; $j++): ?>
						<tr class="empty-row maintainance"><td colspan="6"></td></tr>
					<?php endfor; 
				else:
					$rowCount = 0;
					foreach ($maintenanceLogs as $log): 
						// Display all logs without limiting to maxRows
						?>
						<tr>
							<td><?php echo htmlspecialchars($log['equipment_name']); ?></td>
							<td><?php echo htmlspecialchars($log['maintenance_date']); ?></td>
							<td><?php echo htmlspecialchars($log['jo_number']); ?></td>
							<td><?php echo htmlspecialchars($log['actions_taken']); ?></td>
							<td><?php echo htmlspecialchars($log['remarks']); ?></td>
							<td><?php echo htmlspecialchars($log['firstname'] . ' ' . $log['lastname']); ?></td>
						</tr>
						<?php 
						$rowCount++; // Increment row count
					endforeach;

					// Add empty rows if total entries are less than maxRows
					for ($j = $rowCount; $j < $maxRows; $j++): ?>
						<tr class="empty-row"><td colspan="6"></td></tr>
					<?php endfor; 
				endif; ?>
			</tbody>
		</table>
    </div>
</div>

    <script>
        function generateJobOrderAndUpdate(equipmentId, equipmentName) {
            const selectedEquipmentDiv = document.getElementById('selected_equipment');
            selectedEquipmentDiv.innerHTML = 'Selected Equipments: <span class="selected-equipment-item">' + equipmentName + ' (ID: ' + equipmentId + ')</span>';

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
    
    function restrictToCurrentMonth() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');

        // Set the minimum and maximum dates to the first and last day of the current month
        const firstDay = `${year}-${month}-01`;
        const lastDay = new Date(year, today.getMonth() + 1, 0).toISOString().split("T")[0];

        dateInput.setAttribute("min", firstDay);
        dateInput.setAttribute("max", lastDay);
    }

    restrictToCurrentMonth();
    dateInput.addEventListener("focus", restrictToCurrentMonth);
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

<script>
let debounceTimeout;

// Search Equipment List
document.getElementById('search_term').addEventListener('input', function () {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        this.form.submit();
    }, 300); // Adjust the delay as needed (300ms works well)
});

// Search Maintenance Logs
document.getElementById('search_logs').addEventListener('input', function () {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        filterLogs();
    }, 300); // Adjust the delay as needed (300ms works well)
});

document.getElementById('column_filter').addEventListener('change', filterLogs);

function filterLogs() {
    const searchValue = document.getElementById('search_logs').value.toLowerCase();
    const columnIndex = document.getElementById('column_filter').value;
    const rows = document.querySelectorAll('.maintenance-logs-section tbody tr');

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

document.querySelectorAll('#search_term, #equipment_type_filter').forEach(input => {
    input.addEventListener('input', function () {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(() => {
            const form = document.querySelector('.filter-search-row');
            const searchParams = new URLSearchParams(new FormData(form));
            
            fetch('equipment_maintenance.php?' + searchParams.toString())
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const tableBody = doc.querySelector('.body-table tbody');
                    document.querySelector('.body-table tbody').innerHTML = tableBody.innerHTML;
                });
        }, 300); // Adjust debounce delay if necessary
    });
});


</script>

</body>
</html>