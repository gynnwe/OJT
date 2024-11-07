<?php
require_once 'vendor/autoload.php'; // Ensure TCPDF is autoloaded

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

// Get the property number from the URL
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
        e.location_id,
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
        e.property_num = :property_num
";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':property_num', $property_num);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$logs) {
    die('No records found for this property number.');
}

// Create a new PDF document instance
$pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('University of Southeastern Philippines');
$pdf->SetTitle('ICT Equipment History Sheet');
$pdf->SetSubject('Equipment Report');

// Set default margins and auto page break
$pdf->SetMargins(15, 20, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Add a new page
$pdf->AddPage();

// University header
$html = '
    <div style="text-align: center; font-size: 14px;">
        <strong>Republic of the Philippines</strong><br>
        <strong>University of Southeastern Philippines</strong><br>
        Iñigo St., Bo. Obrero, Davao City 8000<br>
        Telephone: (082) 227-8192<br>
        Website: www.usep.edu.ph &nbsp; Email: president@usep.edu.ph<br>
    </div>
    <hr>
    <h2 style="text-align: center;">ICT EQUIPMENT HISTORY SHEET</h2>
';

// Equipment details
$firstLog = $logs[0]; // Use the first record for general equipment details
$html .= '
    <p><strong>Equipment:</strong> ' . htmlspecialchars($firstLog['equipment_name']) . '</p>
    <p><strong>Property/Serial Number:</strong> ' . htmlspecialchars($firstLog['property_num']) . '</p>
    <p><strong>Location:</strong> ' . htmlspecialchars($firstLog['location_id']) . '</p>
    <br>
';

// Start table with headers
$html .= '
    <table border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>Date</th>
                <th>JO Number</th>
                <th>Actions Taken</th>
                <th>Remarks</th>
                <th>Responsible SDMD Personnel</th>
            </tr>
        </thead>
        <tbody>
';

// Loop through each log and add a row in the table
foreach ($logs as $log) {
    $html .= '
        <tr>
            <td>' . htmlspecialchars($log['maintenance_date']) . '</td>
            <td>' . htmlspecialchars($log['jo_number']) . '</td>
            <td>' . htmlspecialchars($log['actions_taken']) . '</td>
            <td>' . htmlspecialchars($log['remarks']) . '</td>
            <td>' . htmlspecialchars($log['firstname'] . ' ' . $log['lastname']) . '</td>
        </tr>
    ';
}

$html .= '
        </tbody>
    </table>
    <br>
    <div style="text-align: center; font-size: 10px;">
        Systems and Data Management Division (SDMD) - Page 1 of 1
    </div>
';

// Output the HTML content to the PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Close and output the PDF
$pdf->Output($firstLog['equipment_name'] . '-' . $firstLog['property_num'] . '.pdf', 'I');
?>
