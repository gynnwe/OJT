<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Function to get equipment type details using its ID
    function getEquipmentTypesWithId($conn, $equipmentId) {
        $query = "SELECT * FROM equipment_type WHERE equip_type_id = $equipmentId";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Function to get total serviceable equipment count
    function getTotalServiceableEquipment($conn, $equipmentTypeId) {
        $query = "SELECT COUNT(*) as total_serviceable FROM equipment WHERE status = 'Serviceable' AND equip_type_id = :equipmentTypeId";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':equipmentTypeId', $equipmentTypeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total_serviceable'];
    }

    // Fetch maintenance plan and details if plan_id is provided
    if (!empty($_GET['plan_id'])) {
        $planId = $_GET['plan_id'];

        // Fetch plan details
        $queryPlan = "SELECT * FROM maintenance_plan WHERE id = :planId";
        $stmtPlan = $conn->prepare($queryPlan);
        $stmtPlan->execute([':planId' => $planId]);
        $maintenancePlan = $stmtPlan->fetch(PDO::FETCH_ASSOC);

        // Fetch plan details
        $queryDetails = "SELECT * FROM plan_details WHERE maintenance_plan_id = :planId";
        $stmtDetails = $conn->prepare($queryDetails);
        $stmtDetails->execute([':planId' => $planId]);
        $planDetails = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

        $equipmentName = getEquipmentTypesWithId($conn, $planDetails[0]['equipment_id']);
        $totalServiceable = getTotalServiceableEquipment($conn, $planDetails[0]['equipment_id']);
    } else {
        throw new Exception("No Plan ID provided.");
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Plan View</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <?php if ($maintenancePlan): ?>
        <a href="plan_maintenance.php" class="btn btn-secondary mb-3">Back</a>

        <h1>Maintenance Plan <?= htmlspecialchars($maintenancePlan['id']) ?></h1>
        <p><strong>Equipment Name:</strong> <?= htmlspecialchars($equipmentName[0]['equip_type_name']) ?></p>
        <p><strong>Year:</strong> <?= htmlspecialchars($maintenancePlan['year']) ?></p>
        <p><strong>Date Prepared:</strong> <?= htmlspecialchars($maintenancePlan['date_prepared']) ?></p>
        <p><strong>Total Serviceable:</strong> <?= htmlspecialchars($totalServiceable) ?></p>
        <p><strong>Count:</strong> <?= htmlspecialchars($maintenancePlan['count']) ?></p>

        <h2>Plan Details</h2>
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th></th>
                    <?php foreach ($planDetails as $detail): ?>
                        <th><?= htmlspecialchars($detail['month']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                <tr>
    <td><strong>Target</strong></td>
    <?php foreach ($planDetails as $detail): ?>
        <td><?= htmlspecialchars((int)$detail['target']) ?></td>
    <?php endforeach; ?>
</tr>
<tr>
    <td><strong>Implemented</strong></td>
    <?php foreach ($planDetails as $detail): 
        $monthNumeric = date_parse($detail['month'])['month'];

        $queryTotalMaintained = "
        SELECT COUNT(DISTINCT e.equipment_id) AS total_maintained 
            FROM equipment e
            JOIN ict_maintenance_logs ml ON e.equipment_id = ml.equipment_id
            WHERE e.status = 'Serviceable' 
            AND e.equip_type_id = :equipmentTypeId
            AND YEAR(ml.maintenance_date) = :planYear
            AND MONTH(ml.maintenance_date) = :month";
        $stmtTotalMaintained = $conn->prepare($queryTotalMaintained);
        $stmtTotalMaintained->bindParam(':equipmentTypeId', $detail['equipment_id'], PDO::PARAM_INT);
        $stmtTotalMaintained->bindParam(':planYear', $maintenancePlan['year'], PDO::PARAM_INT);
        $stmtTotalMaintained->bindParam(':month', $monthNumeric, PDO::PARAM_INT);
        $stmtTotalMaintained->execute();
        $totalMaintained = $stmtTotalMaintained->fetch(PDO::FETCH_ASSOC)['total_maintained'];
    ?>
        <td><?= htmlspecialchars((int)$totalMaintained) ?></td>
    <?php endforeach; ?>
</tr>
            </tbody>
        </table>

        <!-- Button to View and Print PDF -->
        <form action="generate_pdf.php" method="POST" target="_blank">
            <input type="hidden" name="plan_id" value="<?= htmlspecialchars($maintenancePlan['id']) ?>">
            <button type="submit" class="btn btn-primary mt-3">View & Print PDF</button>
        </form>

    <?php else: ?>
        <p>No maintenance plan found for the provided ID.</p>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
