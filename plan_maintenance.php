<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Maintenance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-container {
            margin-top: 30px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Equipment Maintenance</h1>

    <!-- Trigger Button for Modal -->
    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#maintenanceModal">
        Open Maintenance Form
    </button>

    <?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms";

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the current year
    $currentYear = date('Y');

    // Fetch equipment types for the dropdown
    $query = "SELECT equip_type_id, equip_type_name FROM equipment_type WHERE deleted_id = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $equipmentTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch years with maintenance logs
    $yearQuery = "SELECT DISTINCT YEAR(maintenance_date) AS year_maintained FROM ict_maintenance_logs ORDER BY year_maintained DESC";
    $yearStmt = $conn->prepare($yearQuery);
    $yearStmt->execute();
    $years = $yearStmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $selectedEquipmentType = $_POST['equipment_type'];
        $counts = $_POST['counts'];
        $yearMaintained = $_POST['year_maintained'];
        
            


        if ($selectedEquipmentType && !empty($counts)) {
            
        
        $queryTypeName = "
        SELECT equip_type_name 
        FROM equipment_type 
        WHERE equip_type_id = :selectedEquipmentType AND deleted_id = 0";
    $stmtTypeName = $conn->prepare($queryTypeName);
    $stmtTypeName->bindParam(':selectedEquipmentType', $selectedEquipmentType, PDO::PARAM_INT);
    $stmtTypeName->execute();
    $typeNameResult = $stmtTypeName->fetch(PDO::FETCH_ASSOC);
    
                    // Fetch total serviceable equipment
                    $sqlServiceable = "
                        SELECT COUNT(*) as total_serviceable 
                        FROM equipment 
                        WHERE status = 'Serviceable' 
                        AND equip_type_id = :selectedEquipmentType";
                    $stmtServiceable = $conn->prepare($sqlServiceable);
                    $stmtServiceable->bindParam(':selectedEquipmentType', $selectedEquipmentType);
                    $stmtServiceable->execute();
                    $resultServiceable = $stmtServiceable->fetch(PDO::FETCH_ASSOC);
                    $totalServiceable = $resultServiceable['total_serviceable'];

                    echo "<div class='table-container'>";
                    echo "<h3>Maintenance Report</h3>";
                    echo "<p><strong>Selected Equipment Type:</strong> " . htmlspecialchars($typeNameResult['equip_type_name']) . "</p>";
                    echo "<p><strong>Total Serviceable Equipment:</strong> " . htmlspecialchars($totalServiceable) . "</p>";
            
                    echo "<table class='table table-bordered'>";
                    echo "<thead class='table-light'>";
                    echo "<tr>
                            <th>Month</th>
                            <th>Planned Maintenance Count</th>
                            <th>Equipment Maintained</th>
                          </tr>";
                    echo "</thead><tbody>";
            
                    for ($month = 1; $month <= 12; $month++) {
                        $plannedCount = intval($counts[$month]);
                        if ($plannedCount >= 0) {
                            $sqlMaintained = "
                                SELECT COUNT(DISTINCT e.equipment_id) as total_maintained 
                                FROM equipment e
                                JOIN ict_maintenance_logs ml ON e.equipment_id = ml.equipment_id
                                WHERE e.status = 'Serviceable' 
                                AND e.equip_type_id = :selectedEquipmentType
                                AND YEAR(ml.maintenance_date) = :currentYear
                                AND MONTH(ml.maintenance_date) = :selectedMonth";
                            $stmtMaintained = $conn->prepare($sqlMaintained);
                            $stmtMaintained->bindParam(':currentYear', $yearMaintained, PDO::PARAM_INT);
                            $stmtMaintained->bindParam(':selectedMonth', $month, PDO::PARAM_INT);
                            $stmtMaintained->bindParam(':selectedEquipmentType', $selectedEquipmentType);
                            $stmtMaintained->execute();
                            $resultMaintained = $stmtMaintained->fetch(PDO::FETCH_ASSOC);
                            $totalMaintained = $resultMaintained['total_maintained'];
            
                            echo "<tr>
                                    <td>" . date('F', mktime(0, 0, 0, $month, 1)) . "</td>
                                    <td>" . htmlspecialchars($plannedCount) . "</td>
                                    <td>" . htmlspecialchars($totalMaintained) . "</td>
                                  </tr>";
                        }
                    }
            
                    echo "</tbody></table>";
                    echo "</div>";
        } else {
            echo "<div class='alert alert-warning'>Please select an equipment type and enter counts for all months.</div>";
        }
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>


    <!-- Modal -->
    <div class="modal fade" id="maintenanceModal" tabindex="-1" aria-labelledby="maintenanceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="maintenanceModalLabel">Equipment Maintenance Form</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                         <!-- Year Dropdown -->
                         <div class="mb-3">
                            <label for="year_maintained" class="form-label">Select Year:</label>
                            <select name="year_maintained" id="year_maintained" class="form-select" required>
                                <option value="">--Select Year--</option>
                                <?php
                                foreach ($years as $year) {
                                    echo '<option value="' . htmlspecialchars($year['year_maintained']) . '">' . htmlspecialchars($year['year_maintained']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="equipment_type" class="form-label">Select Equipment Type:</label>
                            <select name="equipment_type" id="equipment_type" class="form-select" required>
                                <option value="">--Select Equipment Type--</option>
                                <?php
                                foreach ($equipmentTypes as $type) {
                                    echo '<option value="' . htmlspecialchars($type['equip_type_id']) . '">' . htmlspecialchars($type['equip_type_name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <?php
                        for ($month = 1; $month <= 12; $month++) {
                            echo '<div class="mb-3">
                                <label for="count' . $month . '" class="form-label">' . date('F', mktime(0, 0, 0, $month, 1)) . ' Maintenance Count:</label>
                                <input type="number" name="counts[' . $month . ']" id="count' . $month . '" class="form-control" min="0" required>
                              </div>';
                        }
                        ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
