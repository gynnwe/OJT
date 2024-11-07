<?php
require_once 'vendor/autoload.php';

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

// Create a new PDF document instance with A4 size
$pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('University of Southeastern Philippines');
$pdf->SetTitle('ICT Equipment History Sheet');
$pdf->SetSubject('Equipment Report');

// Set 1cm margins on all sides
$pdf->SetMargins(10, 10, 10); // left, top, right margins set to 1cm (10mm)
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 10); // bottom margin also set to 1cm

$pdf->AddPage();

// Logo and Header Section
$logoPath = 'assets/usep-logo.jpg';
$html = '
    <table border="0" cellpadding="5" cellspacing="0" width="100%">
        <tr>
            <td width="20%" align="center"><img src="' . $logoPath . '" width="70" height="70" /></td>
            <td width="60%" align="center" style="font-size: 9px;">
                <strong>Republic of the Philippines</strong><br>
                <strong>University of Southeastern Philippines</strong><br>
                Iñigo St., Bo. Obrero, Davao City 8000<br>
                Telephone: (082) 227-8192<br>
                Website: <a href="http://www.usep.edu.ph">www.usep.edu.ph</a><br> 
                Email: <a href="mailto:president@usep.edu.ph">president@usep.edu.ph</a>
            </td>
            <td width="25%" align="left">
                <table border="0.5" cellpadding="2" cellspacing="0" style="font-size:7px; width: 100%;">
                    <tr><td>Form No.</td><td>FM-USeP-ICT-04</td></tr>
                    <tr><td>Issue Status</td><td>01</td></tr>
                    <tr><td>Revision No.</td><td>00</td></tr>
                    <tr><td>Date Effective</td><td>23 December 2022</td></tr>
                    <tr><td>Approved by</td><td>President</td></tr>
                </table>
            </td>
        </tr>
    </table>
    <hr>
    <h2 align="center">ICT EQUIPMENT HISTORY SHEET</h2>
';

// Equipment details
$firstLog = $logs[0];
$html .= '
    <table border="0.5" cellpadding="4" cellspacing="0" width="100%">
        <tr>
            <td width="30%"><strong>Equipment:</strong></td>
            <td width="70%">' . htmlspecialchars($firstLog['equipment_name']) . '</td>
        </tr>
        <tr>
            <td><strong>Property/Serial Number:</strong></td>
            <td>' . htmlspecialchars($firstLog['property_num']) . '</td>
        </tr>
        <tr>
            <td><strong>Location:</strong></td>
            <td>' . htmlspecialchars($firstLog['location_id']) . '</td>
        </tr>
    </table>
    <br>
';

// Maintenance Logs table
$html .= '
    <table border="0.5" cellpadding="4" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th width="15%">Date</th>
                <th width="15%">JO Number</th>
                <th width="30%">Actions Taken</th>
                <th width="20%">Remarks</th>
                <th width="20%">Responsible SDMD Personnel</th>
            </tr>
        </thead>
        <tbody>';

// Loop through the logs to populate the rows
foreach ($logs as $log) {
    $html .= '
        <tr>
            <td>' . htmlspecialchars($log['maintenance_date']) . '</td>
            <td>' . htmlspecialchars($log['jo_number']) . '</td>
            <td>' . htmlspecialchars($log['actions_taken']) . '</td>
            <td>' . htmlspecialchars($log['remarks']) . '</td>
            <td>' . htmlspecialchars($log['firstname'] . ' ' . $log['lastname']) . '</td>
        </tr>';
}

$html .= '
        </tbody>
    </table>
    <br>
    <div style="text-align: center; font-size: 10px;">
        Systems and Data Management Division (SDMD) - Page 1 of 1
    </div>';

// Write the HTML content to the PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Output the PDF document
$pdf->Output($firstLog['equipment_name'] . '-' . $firstLog['property_num'] . '.pdf', 'I');
?>
