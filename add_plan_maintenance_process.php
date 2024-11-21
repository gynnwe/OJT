<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    function saveMaintenancePlan($conn, $equipmentTypeId, $year, $counts) {
        $totalPlannedCount = array_sum($counts);
        $datePrepared = date('Y-m-d');
    
        $query = "
            INSERT INTO maintenance_plan (admin_id, year, date_prepared, count)
            VALUES (:adminId, :year, :datePrepared, :totalPlannedCount)
            ON DUPLICATE KEY UPDATE count = :totalPlannedCount";
    
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':adminId' => 1, // Replace with dynamic admin ID as required
            ':year' => $year,
            ':datePrepared' => $datePrepared,
            ':totalPlannedCount' => $totalPlannedCount
        ]);
    
        return $conn->lastInsertId();
    }
    
    function savePlanDetails($conn, $maintenancePlanId, $equipmentTypeId, $counts) {
        $query = "
            INSERT INTO plan_details (maintenance_plan_id, month, target, equipment_id, details, accomplishment)
            VALUES (:maintenancePlanId, :month, :target, :equipmentType, '', '')";
    
        $stmt = $conn->prepare($query);
    
        foreach ($counts as $month => $count) {
            $stmt->execute([
                ':maintenancePlanId' => $maintenancePlanId,
                ':month' => date('F', mktime(0, 0, 0, $month, 1)),
                ':target' => $count,
                ':equipmentType' => $equipmentTypeId
            ]);
        }
    }

    // Handle POST data
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $selectedEquipmentType = $_POST['equipment_type'];
        $yearMaintained = $_POST['year_maintained'];
        $counts = $_POST['counts'];

        if ($selectedEquipmentType && $yearMaintained && !empty($counts)) {
            // Save the maintenance plan and retrieve the maintenance_plan_id
            $maintenancePlanId = saveMaintenancePlan($conn, $selectedEquipmentType, $yearMaintained, $counts);

            if ($maintenancePlanId) {
                // Save plan details
                savePlanDetails($conn, $maintenancePlanId, $selectedEquipmentType, $counts);
            }
        }
    }

    // Redirect back to the main page
    header("Location: plan_maintenance.php");
    exit;
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
