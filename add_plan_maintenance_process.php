<?php
// Database connection
include 'conn.php';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    function saveMaintenancePlan($conn, $year, $totalPlannedCount) {
        $datePrepared = date('Y-m-d');
    
        $query = "INSERT INTO maintenance_plan (admin_id, year, date_prepared, count)
                  VALUES (:adminId, :year, :datePrepared, :totalPlannedCount)";
    
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
            INSERT INTO plan_details (maintenance_plan_id, month, target, equip_type_id)
            VALUES (:maintenancePlanId, :month, :target, :equipmentTypeId)";
    
        $stmt = $conn->prepare($query);
    
        foreach ($counts as $month => $count) {
            $stmt->execute([
                ':maintenancePlanId' => $maintenancePlanId,
                ':month' => date('F', mktime(0, 0, 0, $month, 1)),
                ':target' => $count,
                ':equipmentTypeId' => $equipmentTypeId
            ]);
        }
    }

    // Handle POST data
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $yearMaintained = $_POST['year_maintained'];
        $equipmentTypes = $_POST['equipment_types'];
        $counts = $_POST['counts'];

        if ($yearMaintained && !empty($equipmentTypes) && !empty($counts)) {
            // Start transaction
            $conn->beginTransaction();

            try {
                // Calculate total count across all equipment types
                $totalPlannedCount = 0;
                foreach ($counts as $equipmentCounts) {
                    $totalPlannedCount += array_sum($equipmentCounts);
                }

                // Save the main maintenance plan
                $maintenancePlanId = saveMaintenancePlan($conn, $yearMaintained, $totalPlannedCount);

                // Save details for each equipment type
                foreach ($equipmentTypes as $index => $equipmentTypeId) {
                    savePlanDetails($conn, $maintenancePlanId, $equipmentTypeId, $counts[$index]);
                }

                $conn->commit();
                header("Location: plan_maintenance.php");
                exit;
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
        }
    }

    // Redirect back to the main page
    header("Location: plan_maintenance.php");
    exit;
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
