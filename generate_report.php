<?php
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Database connection
include 'conn.php';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

// Get the property number
$property_num = isset($_GET['property_num']) ? $_GET['property_num'] : '';
if (!$property_num) {
    die('Property number is required');
}

// Fetch all maintenance logs for the specific property number
$sql = "
    SELECT 
        ml.maintenance_date,
        ml.jo_number,
        ml.actions_taken,
        r.remarks_name AS remarks,
        e.equip_name AS equipment_name,
        e.property_num,
        CONCAT(l.building, ' - ', l.office, ' - ', l.room) AS location_name,
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
    LEFT JOIN 
        location l ON e.location_id = l.location_id
    WHERE 
        e.property_num = :property_num
";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':property_num', $property_num);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$logs) {
    die('No records found for this property number.');
}

// Prepare the HTML content
$html = "
<!DOCTYPE html>
<html>
<head>
    <title>Maintenance Logs</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: left;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .table th {
            background-color: #f2f2f2;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class='header'>
        <h1>Maintenance Logs</h1>
        <p><strong>Equipment Name:</strong> {$logs[0]['equipment_name']}</p>
        <p><strong>Property Number:</strong> {$logs[0]['property_num']}</p>
        <p><strong>Location:</strong> {$logs[0]['location_name']}</p>
    </div>
    <table class='table'>
        <thead>
            <tr>
                <th>Date</th>
                <th>JO Number</th>
                <th>Actions Taken</th>
                <th>Remarks</th>
                <th>Personnel</th>
            </tr>
        </thead>
        <tbody>";
foreach ($logs as $log) {
    $html .= "
            <tr>
                <td>{$log['maintenance_date']}</td>
                <td>{$log['jo_number']}</td>
                <td>{$log['actions_taken']}</td>
                <td>{$log['remarks']}</td>
                <td>{$log['firstname']} {$log['lastname']}</td>
            </tr>";
}
$html .= "
        </tbody>
    </table>
</body>
</html>
";

// Configure DOMPDF
$options = new Options();
$options->set('isRemoteEnabled', true); // Enable remote content if needed
$dompdf = new Dompdf($options);

// Load HTML content
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the PDF
$dompdf->render();

// Output the PDF to browser
$filename = $property_num . '.pdf'; // Use the property number as the filename
$dompdf->stream($filename, ['Attachment' => 0]);
?>
