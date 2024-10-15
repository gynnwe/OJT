<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "ictmms";

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Pagination setup
    $itemsPerPage = 10;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($currentPage - 1) * $itemsPerPage;

    // Fetch not maintained equipment for the current month
    $currentMonth = date('Y-m');
    $sql = "SELECT e.property_num, e.equipment_id 
            FROM equipment e
            LEFT JOIN ict_maintenance_logs im ON e.equipment_id = im.equipment_id
            WHERE im.equipment_id IS NULL OR im.maintenance_date NOT LIKE :currentMonth
            LIMIT :offset, :itemsPerPage";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':currentMonth', $currentMonth . '%', PDO::PARAM_STR);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
    $stmt->execute();
    $notMaintainedEquipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch total count of not maintained equipment for pagination
    $sqlCount = "SELECT COUNT(DISTINCT e.equipment_id) as total 
                 FROM equipment e
                 LEFT JOIN ict_maintenance_logs im ON e.equipment_id = im.equipment_id
                 WHERE im.equipment_id IS NULL OR im.maintenance_date NOT LIKE :currentMonth";

    $stmtCount = $conn->prepare($sqlCount);
    $stmtCount->bindValue(':currentMonth', $currentMonth . '%', PDO::PARAM_STR);
    $stmtCount->execute();
    $totalCount = $stmtCount->fetchColumn();
    $totalPages = ceil($totalCount / $itemsPerPage);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Not Maintained Equipment</title>
    <link rel="stylesheet" href="path/to/your/styles.css"> <!-- Add your CSS file here -->
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .pagination {
            margin-top: 20px;
            display: flex;
            align-items: center;
        }
        .pagination button {
            margin: 0 5px;
            padding: 10px 15px;
            border: none;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }
        .pagination button:disabled {
            background-color: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
        }
        .pagination button:hover:not(:disabled) {
            background-color: #0056b3;
        }
        .arrow {
            font-size: 20px;
        }
    </style>
</head>
<body>
    <h1>Not Maintained Equipment</h1>

    <table>
        <thead>
            <tr>
                <th>Property Number</th>
                <th>Equipment ID</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notMaintainedEquipment as $equipment): ?>
                <tr>
                    <td><?php echo htmlspecialchars($equipment['property_num']); ?></td>
                    <td><?php echo htmlspecialchars($equipment['equipment_id']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="pagination">
        <button 
            onclick="window.location.href='?page=<?php echo $currentPage - 1; ?>'" 
            <?php if ($currentPage <= 1): ?> disabled <?php endif; ?>
        >
            <span class="arrow">&lt;</span>
        </button>

        <button 
            onclick="window.location.href='?page=<?php echo $currentPage + 1; ?>'" 
            <?php if ($currentPage >= $totalPages): ?> disabled <?php endif; ?>
        >
            <span class="arrow">&gt;</span>
        </button>
    </div>

    <button onclick="window.location.href='plan_maintenance.php'">Back to Plan Maintenance</button>
</body>
</html>
