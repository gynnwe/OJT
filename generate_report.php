<?php
require_once 'vendor/autoload.php';

// Custom TCPDF class to remove the default footer
class CustomPDF extends TCPDF {
    // Disable default footer
    public function Footer() {
        // Leave this function empty to suppress all footers
    }
}

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

// Create a new PDF document instance
$pdf = new CustomPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('University of Southeastern Philippines');
$pdf->SetTitle('ICT Equipment History Sheet');
$pdf->SetSubject('Equipment Report');

// Set margins
$pdf->SetMargins(10.9, 11.6, 10.9); // Left: 1.09 cm, Top: 1.16 cm, Right: 1.09 cm
$pdf->SetHeaderMargin(12.7); // Header margin (1.27 cm)
$pdf->SetAutoPageBreak(TRUE, 4.9); // Bottom margin (0.49 cm)

// Add a new page
$pdf->AddPage();

// Logo and Header Section
$logoPath = 'assets/usep-logo.jpg';

// Header Content
$html = '
<table border="0.5" cellpadding="0.5" cellspacing="0" width="100%">
    <tr>
        <td width="20%" align="center"><img src="' . $logoPath . '" width="70" height="70" /></td>
        <td width="50%" align="center">
<span style="font-family: Arial; font-size: 9px;">Republic of the Philippines</span><br>
            <strong style="font-family: oldenglishttextmt; font-size: 16px;">University of Southeastern Philippines</strong><br>
            <span style="font-family: Arial; font-size: 9px; ">Iñigo St., Bo. Obrero, Davao City 8000</span><br>
            <span style="font-family: Arial; font-size: 9px; ">Telephone: (082) 227-8192</span><br>
            <span style="font-family: Arial; font-size: 9px; ">Website: <a href="http://www.usep.edu.ph">www.usep.edu.ph</a></span><br>
            <span style="font-family: Arial; font-size: 9px; ">Email: <a href="mailto:president@usep.edu.ph">president@usep.edu.ph</a></span>
        </td>
        <td width="30%">
<table border="0.5" cellpadding="4" cellspacing="0" style="font-size: 9px; width: 100%; font-family: Arial;">
    <tr>
        <td>Form No.</td>
        <td>FM-USeP-ICT-04</td>
    </tr>
    <tr>
        <td>Issue Status</td>
        <td>01</td>
    </tr>
    <tr>
        <td>Revision No.</td>
        <td>00</td>
    </tr>
    <tr>
        <td>Date Effective</td>
        <td>23 December 2022</td>
    </tr>
    <tr>
        <td>Approved by</td>
        <td>President</td>
    </tr>
</table>


        </td>
    </tr>
</table>
<hr>
<h2 align="center" style="font-size: 12px; font-family: Arial;">ICT EQUIPMENT HISTORY SHEET</h2>
';

// Equipment Details Section (Calibri, 11pt)
$firstLog = $logs[0];
$html .= '
    <table border="0.5" cellpadding="4" cellspacing="0" width="100%" style="font-family: calibri; font-size: 11px;">
        <tr>
            <td width="35%"><strong>Equipment:</strong></td>
            <td width="65%">' . htmlspecialchars($firstLog['equipment_name']) . '</td>
        </tr>
        <tr>
            <td><strong>Property/Serial Number:</strong></td>
            <td>' . htmlspecialchars($firstLog['property_num']) . '</td>
        </tr>
        <tr>
            <td><strong>Location:</strong></td>
            <td>' . htmlspecialchars($firstLog['location_name']) . '</td>
        </tr>
    </table>
    <br>
';

// Maintenance Logs Table (Calibri, 11pt)
$html .= '
    <table border="0.5" cellpadding="4" cellspacing="0" width="100%" style="font-family: calibri; font-size: 11px;">
        <thead>
            <tr>
                <th width="20%">Date</th>
                <th width="15%">JO Number</th>
                <th width="30%">Actions Taken</th>
                <th width="20%">Remarks</th>
                <th width="15%">Responsible Personnel</th>
            </tr>
        </thead>
        <tbody>';

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
';

// Write the HTML content to the PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Output the PDF document
$pdf->Output($firstLog['equipment_name'] . '-' . $firstLog['property_num'] . '.pdf', 'I');
?>
