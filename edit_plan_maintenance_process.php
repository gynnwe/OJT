<?php
session_start();

include 'conn.php';

// Create connection
$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // Start a database transaction
    $conn->beginTransaction();

    // Validate input
    if (!isset($_POST['plan_id']) || empty($_POST['plan_id'])) {
        throw new Exception("No maintenance plan selected.");
    }

    $plan_id = $_POST['plan_id'];
    $year_maintained = $_POST['year_maintained'];
    $total_target = 0;

    // Update the maintenance plan year
    $updatePlanQuery = "UPDATE maintenance_plan SET year = :year WHERE id = :plan_id";
    $stmtUpdatePlan = $conn->prepare($updatePlanQuery);
    $stmtUpdatePlan->execute([
        ':year' => $year_maintained,
        ':plan_id' => $plan_id
    ]);

    // Check if counts are set and is an array
    if (!isset($_POST['counts']) || !is_array($_POST['counts'])) {
        throw new Exception("No equipment counts provided.");
    }

    // Prepare statements for updating plan details
    $deleteDetailsQuery = "DELETE FROM plan_details WHERE maintenance_plan_id = :plan_id";
    $stmtDeleteDetails = $conn->prepare($deleteDetailsQuery);
    $stmtDeleteDetails->execute([':plan_id' => $plan_id]);

    // Insert new plan details
    $insertDetailQuery = "
        INSERT INTO plan_details 
        (maintenance_plan_id, month, target, equip_type_id, details, accomplishment) 
        VALUES 
        (:plan_id, :month, :target, :equip_type_id, :details, :accomplishment)
    ";
    $stmtInsertDetail = $conn->prepare($insertDetailQuery);

    // Process each equipment type
    foreach ($_POST['counts'] as $equip_type_id => $monthTargets) {
        // Validate equipment type
        $checkEquipTypeQuery = "SELECT COUNT(*) FROM equipment_type WHERE equip_type_id = :equip_type_id";
        $stmtCheckEquipType = $conn->prepare($checkEquipTypeQuery);
        $stmtCheckEquipType->execute([':equip_type_id' => $equip_type_id]);
        
        if ($stmtCheckEquipType->fetchColumn() == 0) {
            throw new Exception("Invalid equipment type: " . htmlspecialchars($equip_type_id));
        }

        // Months in the same order as your database ENUM
        $months = [
            'January', 'February', 'March', 'April', 
            'May', 'June', 'July', 'August', 
            'September', 'October', 'November', 'December'
        ];

        // Insert details for each month
        foreach ($months as $month) {
            // Use 0 as default if no target is set
            $target = isset($monthTargets[$month]) ? floatval($monthTargets[$month]) : 0;

            $total_target += $target;
            $stmtInsertDetail->execute([
                ':plan_id' => $plan_id,
                ':month' => $month,
                ':target' => $target,
                ':equip_type_id' => $equip_type_id,
                ':details' => "Updated maintenance plan for $month", // You might want to make this dynamic
                ':accomplishment' => "" // Left empty, can be updated later
            ]);
        }
    }

    // Commit the transaction
    $conn->commit();

    // Update the count of equipment types in the maintenance plan
    $countDetailsQuery = "
        UPDATE maintenance_plan 
        SET count = :count
        WHERE id = :plan_id
    ";
    $stmtUpdateCount = $conn->prepare($countDetailsQuery);
    $stmtUpdateCount->execute([':count' => $total_target,':plan_id' => $plan_id]);

    // Redirect with success message
    $_SESSION['success_message'] = "Maintenance plan successfully updated.";
    header("Location: plan_maintenance.php");
    exit();

} catch (Exception $e) {
    // Rollback the transaction in case of error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log the error (implement proper logging in production)
    error_log("Maintenance Plan Update Error: " . $e->getMessage());

    // Redirect with error message
    $_SESSION['error_message'] = "Error updating maintenance plan: " . $e->getMessage();
    header("Location: maintenance_plan_list.php");
    exit();
}