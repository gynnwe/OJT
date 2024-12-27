<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: index.php");
    exit;
}

// Database connection
include 'conn.php';

// Pagination settings
$records_per_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Pagination settings for non-serviceable
$ns_records_per_page = 5;
$ns_page = isset($_GET['ns_page']) ? (int)$_GET['ns_page'] : 1;
$ns_offset = ($ns_page - 1) * $ns_records_per_page;

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Count total records for pagination
    $count_sql = "SELECT COUNT(*) as total FROM ict_maintenance_logs";
    $count_stmt = $conn->query($count_sql);
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $records_per_page);

    // Count total non-serviceable equipment
    $ns_count_sql = "SELECT COUNT(*) as total FROM equipment WHERE status = 'Non-serviceable'";
    $ns_count_stmt = $conn->query($ns_count_sql);
    $ns_total_records = $ns_count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $ns_total_pages = ceil($ns_total_records / $ns_records_per_page);

    // Fetch the latest maintenance logs with pagination
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
        LIMIT :offset, :records_per_page
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':records_per_page', $records_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $maintenanceLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all non-serviceable equipment with pagination
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
        ORDER BY equipment.equipment_id
        LIMIT :offset, :limit
    ";
    $stmtNonServiceable = $conn->prepare($sqlNonServiceable);
    $stmtNonServiceable->bindValue(':offset', $ns_offset, PDO::PARAM_INT);
    $stmtNonServiceable->bindValue(':limit', $ns_records_per_page, PDO::PARAM_INT);
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
    $nonServiceableEquipmentCount = $stmtNonServiceableCount->fetchColumn();

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
    <style>
        body {
            background-color: transparent !important;
        }

        .container {
            max-width: 1350px;
        margin: 0 auto;
        padding: 16px;
        margin-right: 2.6rem !important;
    }

        /* Equipment Overview Section */
        .equipment-overview {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .overview-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 25px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 8px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-box.total {
            background: #e9ecef;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #6c757d;
        }

        /* Equipment List Section */
        .equipment-list {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .list-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 8px;
        }

        .equipment-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 14px;
        }

        .equipment-table th {
            background: #f8f9fa;
            padding: 10px 15px;
            font-weight: 500;
            color: #495057;
            border-bottom: 1px solid #dee2e6;
            text-transform: capitalize;
        }

        .equipment-table td {
            padding: 10px 15px;
            border-bottom: 1px solid #dee2e6;
            color: #495057;
        }

        .equipment-table tr:hover {
            background-color: #f8f9fa;
        }

        .view-plan-btn {
            padding: 6px 12px;
            font-size: 10px;
            background-color: #8B0000;
            border: none;
            border-radius: 15px;
            color: white;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .view-plan-btn:hover {
            background-color: #6d0000;
            color: white;
        }

        .view-non-serviceable-btn {
            display: block;
            width: 25%;
            padding: 5px;
            background-color: #8B0000;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .view-non-serviceable-btn:hover {
            background-color: #6d0000;
        }

        /* Non-serviceable Section */
        #non-serviceable-section {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .non-serviceable-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 8px;
        }

        .non-serviceable-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 14px;
        }

        .non-serviceable-table th {
            background: #f8f9fa;
            padding: 10px 15px;
            font-weight: 500;
            color: #495057;
            border-bottom: 1px solid #dee2e6;
        }

        .non-serviceable-table td {
            padding: 10px 15px;
            border-bottom: 1px solid #dee2e6;
            color: #495057;
        }

        .non-serviceable-table tr:hover {
            background-color: #f8f9fa;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination-btn {
            color: black;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .pagination-btn:hover {
            color: maroon;
            text-decoration: none;
        }

        .pagination-btn.disabled {
            color: gray;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Filter and Search */
        .filter-search-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            margin-right: 50%;
        }

        .filter-wrapper,
        .search-wrapper {
            flex: 1;
        }

        #equipmentFilter{
            background-color: lightgray;
        }

        #nonServiceableFilter{
            background-color: lightgray; 
        }

        .filter-select,
        .search-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 20px;
            font-size: 14px;
            transition: border-color 0.2s ease-in-out;
        }

        .filter-select:focus,
        .search-input:focus {
            outline: none;
            border-color: #8B0000;
            box-shadow: 0 0 0 0.2rem rgba(139, 0, 0, 0.25);
        }

        /* Charts Section */
        .charts-section {
            margin-top: 2rem;
            padding: 1.5rem;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .charts-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1.5rem;
        }

        .charts-container {
            display: flex;
            justify-content: space-between;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .chart-wrapper {
            flex: 1;
            min-width: 300px;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        @media (max-width: 768px) {
            .chart-wrapper {
                flex: 100%;
            }
        }

        /* Charts Section Styles */
        .chart-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            height: 100%;
            transition: transform 0.2s;
        }

        .chart-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .chart-header {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            padding-bottom: 0.8rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .chart-body {
            position: relative;
            height: 300px;
            padding: 0.5rem;
        }

        .section-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .stats-container {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .equipment-table {
                display: block;
                overflow-x: auto;
            }
        }

        @media (max-width: 576px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 992px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .chart-wrapper {
                min-height: 250px;
            }
        }

        @media (max-width: 576px) {
            .chart-wrapper {
                min-height: 200px;
            }
            
            .non-serviceable-table {
                display: block;
                overflow-x: auto;
            }
        }

    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <!-- Equipment Overview Section -->
        <div class="equipment-overview">
            <h4 class="overview-title">Equipment Overview</h4>
            <div class="stats-container">
                <div class="stat-box total">
                    <div class="stat-number"><?= htmlspecialchars($totalEquipment); ?></div>
                    <div class="stat-label">Total Equipment</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= htmlspecialchars($maintainedCount); ?></div>
                    <div class="stat-label">Maintained Equipment</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= htmlspecialchars($notMaintainedCount); ?></div>
                    <div class="stat-label">Not Maintained Equipment</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= htmlspecialchars($serviceableEquipment); ?></div>
                    <div class="stat-label">Serviceable Equipment</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= htmlspecialchars($nonServiceableEquipmentCount); ?></div>
                    <div class="stat-label">Non-Serviceable Equipment</div>
                </div>
            </div>
            <button class="view-non-serviceable-btn" id="toggle-non-serviceable">VIEW NON-SERVICEABLE EQUIPMENTS</button>
        </div>

        <!-- Equipment List Section -->
        <div class="equipment-list">
            <h4 class="list-title">List of Registered Equipments</h4>
            <div class="filter-search-container">
                <div class="filter-wrapper">
                    <select id="equipmentFilter" class="filter-select">
                        <option value="">ID</option>
                        <option value="equipment_name">Equipment Name</option>
                        <option value="property_num">Property Number</option>
                        <option value="last_maintenance_date">Last Maintenance Date</option>
                        <option value="latest_remarks">Remarks</option>
                        <option value="responsible_personnel">Responsible Personnel</option>
                    </select>
                </div>
                <div class="search-wrapper">
                    <input type="text" id="equipmentSearch" class="search-input" placeholder="Search">
                </div>
            </div>
            <table class="equipment-table">
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
                <tbody id="equipmentTableBody">
                    <?php if (!empty($maintenanceLogs)): ?>
                        <?php foreach ($maintenanceLogs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['equipment_name']); ?></td>
                                <td><?= htmlspecialchars($log['property_num']); ?></td>
                                <td><?= htmlspecialchars($log['last_maintenance_date']); ?></td>
                                <td><?= htmlspecialchars($log['latest_remarks']); ?></td>
                                <td><?= htmlspecialchars($log['firstname'] . ' ' . $log['lastname']); ?></td>
                                <td>
                                    <a href="generate_report.php?property_num=<?= urlencode($log['property_num']); ?>" class="btn btn-primary view-plan-btn">VIEW PLAN</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No maintenance logs found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="pagination">
                <a href="?page=<?= max(1, $page - 1) ?>" class="pagination-btn <?= ($page <= 1) ? 'disabled' : '' ?>">Previous</a>
                <a href="?page=<?= min($total_pages, $page + 1) ?>" class="pagination-btn <?= ($page >= $total_pages) ? 'disabled' : '' ?>">Next</a>
            </div>
        </div>

        <!-- Non-serviceable Equipments Section -->
        <div id="non-serviceable-section" style="display: none;">
            <h4 class="non-serviceable-title">Non-serviceable Equipments</h4>
            <div class="filter-search-container">
                <div class="filter-wrapper">
                    <select id="nonServiceableFilter" class="filter-select">
                        <option value="">ID</option>
                        <option value="equipment_id">Equipment ID</option>
                        <option value="equip_name">Equipment Name</option>
                        <option value="location_id">Location ID</option>
                        <option value="equipment_type_name">Equipment Type</option>
                        <option value="model_name">Model Name</option>
                        <option value="property_num">Property Number</option>
                        <option value="status">Status</option>
                        <option value="date_purchased">Date Purchased</option>
                    </select>
                </div>
                <div class="search-wrapper">
                    <input type="text" id="nonServiceableSearch" class="search-input" placeholder="Search">
                </div>
            </div>
            <table class="non-serviceable-table">
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
                <tbody id="nonServiceableTableBody">
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
            <div class="pagination">
                <a href="?ns_page=<?= max(1, $ns_page - 1) ?>&page=<?= $page ?>" class="pagination-btn <?= ($ns_page <= 1) ? 'disabled' : '' ?>">Previous</a>
                <a href="?ns_page=<?= min($ns_total_pages, $ns_page + 1) ?>&page=<?= $page ?>" class="pagination-btn <?= ($ns_page >= $ns_total_pages) ? 'disabled' : '' ?>">Next</a>
            </div>
        </div>

        <!-- Charts Section -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="chart-card">
                        <div class="chart-header">Latest Maintenance Remarks</div>
                        <div class="chart-body">
                            <canvas id="remarksChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="chart-card">
                        <div class="chart-header">Maintenance Status</div>
                        <div class="chart-body">
                            <canvas id="maintainedChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle non-serviceable section
            const toggleButton = document.getElementById('toggle-non-serviceable');
            if (toggleButton) {
                toggleButton.addEventListener('click', function() {
                    const section = document.getElementById('non-serviceable-section');
                    if (section) {
                        section.style.display = section.style.display === 'none' ? 'block' : 'none';
                        this.textContent = section.style.display === 'none' ? 
                            'VIEW NON-SERVICEABLE EQUIPMENTS' : 'HIDE NON-SERVICEABLE EQUIPMENTS';
                    }
                });
            }

            // Maintenance Status Chart (Bar)
            const maintainedCtx = document.getElementById('maintainedChart').getContext('2d');
            new Chart(maintainedCtx, {
                type: 'bar',
                data: {
                    labels: ['Maintained', 'Not Maintained'],
                    datasets: [{
                        label: 'Number of Equipment',
                        data: [<?= $maintainedCount ?>, <?= $notMaintainedCount ?>],
                        backgroundColor: ['#2196F3', '#FF9800'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { 
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Equipment'
                            }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });

            // Remarks Distribution Chart (Pie)
            const remarksData = <?= json_encode(array_count_values(array_column($maintenanceLogs, 'latest_remarks'))); ?>;
            const pieCtx = document.getElementById('remarksChart').getContext('2d');
            new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: Object.keys(remarksData),
                    datasets: [{
                        data: Object.values(remarksData),
                        backgroundColor: [
                            '#9C27B0', '#3F51B5', '#009688', 
                            '#FFC107', '#795548', '#607D8B'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            position: 'bottom',
                            labels: {
                                boxWidth: 12
                            }
                        }
                    }
                }
            });

            // Add event listeners for equipment table
            const equipmentFilter = document.getElementById('equipmentFilter');
            const equipmentSearch = document.getElementById('equipmentSearch');
            
            if (equipmentFilter && equipmentSearch) {
                equipmentFilter.addEventListener('change', () => {
                    filterAndSearch('equipmentTableBody', 'equipmentFilter', 'equipmentSearch', ['equipment_name', 'property_num', 'last_maintenance_date', 'latest_remarks', 'responsible_personnel']);
                });
                
                equipmentSearch.addEventListener('input', () => {
                    filterAndSearch('equipmentTableBody', 'equipmentFilter', 'equipmentSearch', ['equipment_name', 'property_num', 'last_maintenance_date', 'latest_remarks', 'responsible_personnel']);
                });
            }

            function filterAndSearch(tableId, filterId, searchId, columnMap) {
                const filterSelect = document.getElementById(filterId);
                const searchInput = document.getElementById(searchId);
                const tbody = document.getElementById(tableId);
                const rows = tbody.getElementsByTagName('tr');

                filterSelect.addEventListener('change', filterRows);
                searchInput.addEventListener('input', filterRows);

                function filterRows() {
                    const filterValue = filterSelect.value;
                    const searchValue = searchInput.value.toLowerCase();

                    Array.from(rows).forEach(row => {
                        const cells = row.getElementsByTagName('td');
                        let matchFound = false;

                        if (filterValue === '') {
                            // If no filter is selected, search all columns
                            Array.from(cells).forEach(cell => {
                                if (cell.textContent.toLowerCase().includes(searchValue)) {
                                    matchFound = true;
                                }
                            });
                        } else {
                            // Get the index for the selected column
                            const columnIndex = columnMap.indexOf(filterValue);
                            if (columnIndex !== -1 && cells[columnIndex]) {
                                if (cells[columnIndex].textContent.toLowerCase().includes(searchValue)) {
                                    matchFound = true;
                                }
                            }
                        }

                        row.style.display = matchFound ? '' : 'none';
                    });
                }
            }

            // Column mappings for equipment table
            const equipmentColumns = {
                'equipment_name': 0,
                'property_num': 1,
                'last_maintenance_date': 2,
                'latest_remarks': 3,
                'responsible_personnel': 4
            };

            // Column mappings for non-serviceable table
            const nonServiceableColumns = {
                'equipment_id': 0,
                'equip_name': 1,
                'location_id': 2,
                'equipment_type_name': 3,
                'model_name': 4,
                'property_num': 5,
                'status': 6,
                'date_purchased': 7
            };

            // Initialize filters
            filterAndSearch('equipmentTableBody', 'equipmentFilter', 'equipmentSearch', equipmentColumns);
            filterAndSearch('nonServiceableTableBody', 'nonServiceableFilter', 'nonServiceableSearch', nonServiceableColumns);
        });
    </script>
</body>
</html>