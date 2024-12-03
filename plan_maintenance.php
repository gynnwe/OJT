<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms";

    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    function getEquipmentTypes($conn) {
        $query = "SELECT equip_type_id, equip_type_name FROM equipment_type WHERE deleted_id = 0";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getYears($conn) {
        $currentYear = date('Y');
        $years = array();
        
        // Generates next 5 years including current year
        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear + $i;
            $years[] = array('year_maintained' => $year);
        }
        
        return $years;
    }

    // Fetch Maintenance Plan and Details
    function fetchMaintenancePlan($conn, $planId) {
        $query = "SELECT * FROM maintenance_plan WHERE id = :planId";
        $stmt = $conn->prepare($query);
        $stmt->execute([':planId' => $planId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function fetchPlanDetails($conn, $planId) {
        $query = "SELECT month, target, equipment_id, details, accomplishment 
                  FROM plan_details 
                  WHERE maintenance_plan_id = :planId";
        $stmt = $conn->prepare($query);
        $stmt->execute([':planId' => $planId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getTotalServiceableEquipment($conn, $equipmentTypeId) {
        $query = "SELECT COUNT(*) as total_serviceable FROM equipment WHERE status = 'Serviceable' AND equip_type_id = :equipmentTypeId";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':equipmentTypeId', $equipmentTypeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total_serviceable'];
    }
    
    $equipmentTypes = getEquipmentTypes($conn);
    $years = getYears($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Maintenance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Equipment Maintenance</h1>

    <!-- Trigger Button for Modal -->
    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#maintenanceModal">
        Open Maintenance Form
    </button>

    <?php
    // Fetch all maintenance plans
    $queryPlans = "SELECT * FROM maintenance_plan ORDER BY date_prepared DESC";
    $stmtPlans = $conn->prepare($queryPlans);
    $stmtPlans->execute();
    $maintenancePlans = $stmtPlans->fetchAll(PDO::FETCH_ASSOC);

    if ($maintenancePlans):
    ?>
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Plan</th>
                <th>Year</th>
                <th>Date Prepared</th>
                <th>Total Count</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($maintenancePlans as $plan): ?>
            <tr>
                <td>Maintenance Plan <?= htmlspecialchars($plan['id']) ?></td>
                <td><?= htmlspecialchars($plan['year']) ?></td>
                <td><?= htmlspecialchars($plan['date_prepared']) ?></td>
                <td><?= htmlspecialchars($plan['count']) ?></td>
                <td>
                    <a href="maintenance_plan_view.php?plan_id=<?= $plan['id'] ?>" class="btn btn-info btn-sm">View Plan</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No maintenance plans available.</p>
    <?php endif; ?>

    <!-- Modal -->
    <div class="modal fade" id="maintenanceModal" tabindex="-1" aria-labelledby="maintenanceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="add_plan_maintenance_process.php">
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
                        <!-- Input monthly maintenance counts -->
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <div class="mb-3">
                                <label for="count<?= $i ?>" class="form-label"><?= date("F", mktime(0, 0, 0, $i, 1)) ?> Count:</label>
                                <input type="number" name="counts[<?= $i ?>]" id="count<?= $i ?>" class="form-control" min="0" required>
                            </div>
                        <?php endfor; ?>
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
