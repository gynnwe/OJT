<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms";

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
            ml.maintenance_date = (
                SELECT MAX(ml_inner.maintenance_date)
                FROM ict_maintenance_logs ml_inner
                WHERE ml_inner.equipment_id = ml.equipment_id
            )
        GROUP BY e.property_num
        ORDER BY e.property_num, last_maintenance_date DESC
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
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
</head>
<body>
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
                            <td><?php echo htmlspecialchars($log['equipment_name']); ?></td>
                            <td><?php echo htmlspecialchars($log['property_num']); ?></td>
                            <td><?php echo htmlspecialchars($log['last_maintenance_date']); ?></td>
                            <td><?php echo htmlspecialchars($log['latest_remarks']); ?></td>
                            <td><?php echo htmlspecialchars($log['firstname'] . ' ' . $log['lastname']); ?></td>
                            <td>
                                <a href="generate_report.php?property_num=<?php echo urlencode($log['property_num']); ?>" class="btn btn-primary">View & Print</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No maintenance logs found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pie Chart Section -->
        <div class="mt-5 text-center">
            <h4>Maintained Equipment Pie Chart</h4>
            <div style="display: inline-block; width: 400px; height: 400px;">
                <canvas id="remarksChart"></canvas>
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

            // Prepare data for the chart
            const remarksData = <?= json_encode(array_count_values(array_column($maintenanceLogs, 'latest_remarks'))); ?>;
            const totalData = Object.values(remarksData).reduce((a, b) => a + b, 0);

            // Chart.js Configuration
            const ctx = document.getElementById('remarksChart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: Object.keys(remarksData),
                    datasets: [{
                        data: Object.values(remarksData),
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'
                        ],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    const value = tooltipItem.raw;
                                    const percentage = ((value / totalData) * 100).toFixed(1);
                                    return `${tooltipItem.label}: ${value} (${percentage}%)`;
                                }
                            }
                        },
                        datalabels: {
                            formatter: (value, ctx) => {
                                const percentage = ((value / totalData) * 100).toFixed(1);
                                return `${percentage}%`;
                            },
                            color: '#fff',
                            font: { weight: 'bold', size: 12 }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
