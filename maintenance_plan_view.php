<?php
// Database connection
include 'conn.php';

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

        // Modify the query to group plan details by equipment type
        $queryDetails = "
            SELECT pd.*, et.equip_type_name 
            FROM plan_details pd
            JOIN equipment_type et ON pd.equip_type_id = et.equip_type_id
            WHERE pd.maintenance_plan_id = :planId
            ORDER BY et.equip_type_id, pd.month";
        $stmtDetails = $conn->prepare($queryDetails);
        $stmtDetails->execute([':planId' => $planId]);
        $planDetails = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

        // Group plan details by equipment type
        $groupedPlanDetails = [];
        foreach ($planDetails as $detail) {
            $groupedPlanDetails[$detail['equip_type_id']][] = $detail;
        }
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
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: transparent;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }

        h1, h4, h5 {
            color: #343a40;
        }

        .btn {
            border-radius: 20px;
            font-weight: bold;
        }

        .btn-primary {
            background-color: #800000; /* Maroon color */
            border-color: #800000;
        }

        .btn-success {
            background-color: #006400; /* Dark Green color */
            border-color: #006400;
        }

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .table {
            border: 1px solid #dee2e6;
            border-radius: 12px; /* Rounded corners for the table */
            overflow: hidden;
        }

        .table-bordered th, .table-bordered td {
            border: 1px solid #dee2e6;
            vertical-align: middle;
            text-align: center;
            padding: 12px;
        }

        .table-light {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .table tbody tr td:first-child {
            font-weight: bold;
            color: #495057;
        }

        .table-hover tbody tr:hover {
            background-color: #e9ecef;
        }

        input[type="search"], select {
            border-radius: 25px;
            border: 1px solid #ced4da;
            padding: 5px 15px;
            font-size: 16px;
            outline: none;
            transition: 0.3s;
        }

        input[type="search"]:focus, select:focus {
            border-color: #007bff;
            box-shadow: 0 0 4px rgba(0, 123, 255, 0.4);
        }

        button.btn-action {
            border: none;
            padding: 8px 20px;
            font-size: 14px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }

        button.btn-action.selected {
            background-color: #dc3545;
            color: white;
            cursor: not-allowed;
        }

        button.btn-action:hover:not(.selected) {
            background-color: #ffc107;
            color: white;
            transition: background-color 0.3s ease;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <?php if ($maintenancePlan): ?>
        <a href="plan_maintenance.php" class="btn btn-secondary mb-3">Back</a>

        <h1>Maintenance Plan <?= htmlspecialchars($maintenancePlan['id']) ?></h1>
        <p><strong>Year:</strong> <?= htmlspecialchars($maintenancePlan['year']) ?></p>
        <p><strong>Date Prepared:</strong> <?= htmlspecialchars($maintenancePlan['date_prepared']) ?></p>

        <?php foreach ($groupedPlanDetails as $equipTypeId => $details): ?>
            <div class="mt-4">
                <h4>Equipment Type: <?= htmlspecialchars($details[0]['equip_type_name']) ?></h4>
                <p><strong>Total Serviceable:</strong> 
                    <?= htmlspecialchars(getTotalServiceableEquipment($conn, $equipTypeId)) ?>
                </p>

                <h5>Plan Details</h5>
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th></th>
                            <?php foreach ($details as $detail): ?>
                                <th><?= htmlspecialchars($detail['month']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Plan</strong></td>
                            <?php foreach ($details as $detail): ?>
                                <td><?= htmlspecialchars((int)$detail['target']) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td><strong>Implemented</strong></td>
                            <?php foreach ($details as $detail): 
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
                                $stmtTotalMaintained->bindParam(':equipmentTypeId', $equipTypeId, PDO::PARAM_INT);
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
            </div>
        <?php endforeach; ?>

        <form action="generate_pdf.php" method="POST" target="_blank" style="display: inline;">
    <input type="hidden" name="plan_id" value="<?= htmlspecialchars($maintenancePlan['id']) ?>">
    <button type="submit" class="btn btn-primary mt-3">Print PDF</button>
</form>

<form action="plan_excel.php" method="POST" target="_blank" style="display: inline;">
    <input type="hidden" name="plan_id" value="<?= htmlspecialchars($maintenancePlan['id']) ?>">
    <button type="submit" class="btn btn-success mt-3">Export to Excel</button>
</form>

    <?php else: ?>
        <p>No maintenance plan found for the provided ID.</p>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
