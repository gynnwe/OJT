<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: index.php");
    exit;
}

// Database connection
include 'conn.php';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch the latest maintenance logs with the latest remarks
    $sql = "
        SELECT 
            e.equip_name AS equipment_name,
            e.property_num,
            ml.maintenance_date AS last_maintenance_date,
            ml.actions_taken,
            r.remarks_name AS latest_remarks,
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
        WHERE 
            (ml.equipment_id, ml.maintenance_date) IN (
                SELECT equipment_id, MAX(maintenance_date)
                FROM ict_maintenance_logs
                GROUP BY equipment_id
            )
        ORDER BY e.property_num, ml.maintenance_date DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $maintenanceLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all non-serviceable equipment
    $sqlNonServiceable = "
        SELECT 
            equipment.equipment_id,
            equipment.equip_name,
            equipment.location_id, 
            equipment_type.equip_type_name AS equipment_type_name, 
            model.model_name AS model_name, 
            equipment.property_num, 
            equipment.status, 
            equipment.date_purchased 
        FROM equipment
        JOIN equipment_type ON equipment.equip_type_id = equipment_type.equip_type_id  
        JOIN model ON equipment.model_id = model.model_id
        WHERE equipment.status = 'Non-serviceable'
    ";
    $stmtNonServiceable = $conn->prepare($sqlNonServiceable);
    $stmtNonServiceable->execute();
    $nonServiceableEquipments = $stmtNonServiceable->fetchAll(PDO::FETCH_ASSOC);

    // Fetch counts for maintained and not-maintained equipment
    $sqlMaintained = "
        SELECT COUNT(DISTINCT equipment.equipment_id) AS maintained_count
        FROM equipment
        JOIN ict_maintenance_logs im ON equipment.equipment_id = im.equipment_id
    ";
    $stmtMaintained = $conn->prepare($sqlMaintained);
    $stmtMaintained->execute();
    $maintainedCount = $stmtMaintained->fetchColumn();

    $sqlNotMaintained = "
        SELECT COUNT(*) AS not_maintained_count
        FROM equipment
        WHERE equipment_id NOT IN (
            SELECT DISTINCT equipment_id FROM ict_maintenance_logs
        )
    ";
    $stmtNotMaintained = $conn->prepare($sqlNotMaintained);
    $stmtNotMaintained->execute();
    $notMaintainedCount = $stmtNotMaintained->fetchColumn();

    // Fetch total number of equipment
    $sqlTotalEquipment = "SELECT COUNT(*) AS total_equipment FROM equipment";
    $stmtTotalEquipment = $conn->prepare($sqlTotalEquipment);
    $stmtTotalEquipment->execute();
    $totalEquipment = $stmtTotalEquipment->fetchColumn();

    // Fetch number of serviceable equipment
    $sqlServiceable = "SELECT COUNT(*) AS serviceable_equipment FROM equipment WHERE status = 'Serviceable'";
    $stmtServiceable = $conn->prepare($sqlServiceable);
    $stmtServiceable->execute();
    $serviceableEquipment = $stmtServiceable->fetchColumn();

    // Fetch number of non-serviceable equipment
    $sqlNonServiceableCount = "SELECT COUNT(*) AS non_serviceable_equipment FROM equipment WHERE status = 'Non-serviceable'";
    $stmtNonServiceableCount = $conn->prepare($sqlNonServiceableCount);
    $stmtNonServiceableCount->execute();
    $nonServiceableEquipment = $stmtNonServiceableCount->fetchColumn();

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container mt-4">
        <!-- Equipment Overview Section -->
        <h4>Equipment Overview</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Total Equipment</th>
                    <th>Maintained Equipment</th>
                    <th>Not Maintained Equipment</th>
                    <th>Serviceable Equipment</th>
                    <th>Non-serviceable Equipment</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($totalEquipment); ?></td>
                    <td><?= htmlspecialchars($maintainedCount); ?></td>
                    <td><?= htmlspecialchars($notMaintainedCount); ?></td>
                    <td><?= htmlspecialchars($serviceableEquipment); ?></td>
                    <td><?= htmlspecialchars($nonServiceableEquipment); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="container mt-5">
        <h3>Reports</h3>

        <!-- Button to Expand Non-serviceable Equipments Section -->
        <div class="mb-4">
            <button id="toggle-non-serviceable" class="btn btn-danger">View Non-serviceable Equipments</button>
        </div>

        <!-- Non-serviceable Equipments Section -->
        <div id="non-serviceable-section" class="mb-4" style="display: none;">
            <h4>Non-serviceable Equipments</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Equipment ID</th>
                        <th>Equipment Name</th>
                        <th>Location ID</th>
                        <th>Equipment Type</th>
                        <th>Model Name</th>
                        <th>Property Number</th>
                        <th>Status</th>
                        <th>Date Purchased</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($nonServiceableEquipments)): ?>
                        <?php foreach ($nonServiceableEquipments as $equipment): ?>
                            <tr>
                                <td><?= htmlspecialchars($equipment['equipment_id']); ?></td>
                                <td><?= htmlspecialchars($equipment['equip_name']); ?></td>
                                <td><?= htmlspecialchars($equipment['location_id']); ?></td>
                                <td><?= htmlspecialchars($equipment['equipment_type_name']); ?></td>
                                <td><?= htmlspecialchars($equipment['model_name']); ?></td>
                                <td><?= htmlspecialchars($equipment['property_num']); ?></td>
                                <td><?= htmlspecialchars($equipment['status']); ?></td>
                                <td><?= htmlspecialchars($equipment['date_purchased']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8">No non-serviceable equipment found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Maintenance Logs Table -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Equipment Name</th>
                    <th>Property Number</th>
                    <th>Last Maintenance Date</th>
                    <th>Remarks</th>
                    <th>Responsible Personnel</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($maintenanceLogs)): ?>
                    <?php foreach ($maintenanceLogs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['equipment_name']); ?></td>
                            <td><?= htmlspecialchars($log['property_num']); ?></td>
                            <td><?= htmlspecialchars($log['last_maintenance_date']); ?></td>
                            <td><?= htmlspecialchars($log['latest_remarks']); ?></td>
                            <td><?= htmlspecialchars($log['firstname'] . ' ' . $log['lastname']); ?></td>
                            <td>
                                <a href="generate_report.php?property_num=<?= urlencode($log['property_num']); ?>" class="btn btn-primary">View & Print</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No maintenance logs found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Charts Section -->
        <div class="mt-5 text-center">
            <h4>Charts</h4>
            <div class="row">
                <div class="col-md-6">
                    <canvas id="remarksChart" style="width: 100%; height: 400px;"></canvas>
                </div>
                <div class="col-md-6">
                    <canvas id="maintainedChart" style="width: 100%; height: 400px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#toggle-non-serviceable').on('click', function() {
                $('#non-serviceable-section').toggle();
                $(this).text(function(i, text) {
                    return text === "View Non-serviceable Equipments" ? "Hide Non-serviceable Equipments" : "View Non-serviceable Equipments";
                });
            });

            // Pie Chart
            const remarksData = <?= json_encode(array_count_values(array_column($maintenanceLogs, 'latest_remarks'))); ?>;
            const totalData = Object.values(remarksData).reduce((a, b) => a + b, 0);

            const pieCtx = document.getElementById('remarksChart').getContext('2d');
            new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: Object.keys(remarksData),
                    datasets: [{
                        data: Object.values(remarksData),
                        backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' }
                    }
                }
            });

            // Bar Chart
            const barCtx = document.getElementById('maintainedChart').getContext('2d');
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: ['Maintained', 'Not Maintained'],
                    datasets: [{
                        data: [<?= $maintainedCount ?>, <?= $notMaintainedCount ?>],
                        backgroundColor: ['#36A2EB', '#FF6384']
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        });
    </script>
</body>
</html>
