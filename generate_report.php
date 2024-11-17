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

// Fetch all maintenance logs for the specific property number, including the concatenated location name with dashes
$sql = "
    SELECT 
        ml.maintenance_date,
        ml.jo_number,
        ml.actions_taken,
        r.remarks_name AS remarks,
        e.equip_name AS equipment_name,
        e.property_num,
        CONCAT(l.building, ' - ', l.office, ' - ', l.room) AS location_name,  -- Concatenate location fields with dashes
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

// Create a new PDF document instance with A4 size
$pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('University of Southeastern Philippines');
$pdf->SetTitle('ICT Equipment History Sheet');
$pdf->SetSubject('Equipment Report');

// Set 1cm margins on all sides
$pdf->SetMargins(10, 10, 10);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 10);

$pdf->AddPage();

// Logo and Header Section
$logoPath = 'assets/usep-logo.jpg';

// Set default font to Arial for most text
$pdf->SetFont('helvetica', '', 10);

// Use the custom font for "University of Southeastern Philippines"
$pdf->SetFont('oldenglishttextmt', '', 11); // Ensure the font name matches exactly with the font file

$html = '
    <table border="0" cellpadding="5" cellspacing="0" width="100%">
        <tr>
            <td width="15%" align="center"><img src="' . $logoPath . '" width="70" height="70" /></td>
            <td width="60%" align="center" style="font-size: 9px; padding-left: 5px;">
                <strong style="font-family: Arial;">Republic of the Philippines</strong><br>
                <strong style="font-family: OldEnglishTextMT;">University of Southeastern Philippines</strong><br>
                <span style="font-family: Arial;">Iñigo St., Bo. Obrero, Davao City 8000</span><br>
                <span style="font-family: Arial;">Telephone: (082) 227-8192</span><br>
                <span style="font-family: Arial;">Website: <a href="http://www.usep.edu.ph">www.usep.edu.ph</a></span><br> 
                <span style="font-family: Arial;">Email: <a href="mailto:president@usep.edu.ph">president@usep.edu.ph</a></span>
            </td>
            <td width="25%" align="left">
                <table border="0.5" cellpadding="2" cellspacing="0" style="font-size:7px; width: 100%; font-family: Arial;">
                    <tr><td style="text-align: left; padding-left: 5px;">Form No.</td><td>FM-USeP-ICT-04</td></tr>
                    <tr><td style="text-align: left; padding-left: 5px;">Issue Status</td><td>01</td></tr>
                    <tr><td style="text-align: left; padding-left: 5px;">Revision No.</td><td>00</td></tr>
                    <tr><td style="text-align: left; padding-left: 5px;">Date Effective</td><td>23 December 2022</td></tr>
                    <tr><td style="text-align: left; padding-left: 5px;">Approved by</td><td>President</td></tr>
                </table>
            </td>
        </tr>
    </table>
    <hr>
    <h2 align="center" style="font-size: 12px; font-family: Arial;">ICT EQUIPMENT HISTORY SHEET</h2>
';

// Reset font back to Arial for the rest of the document
$pdf->SetFont('helvetica', '', 10);

// Equipment details table
$firstLog = $logs[0];
$html .= '
    <table border="0.5" cellpadding="4" cellspacing="0" width="100%">
        <tr>
            <td width="35%" style="font-family: Arial;"><strong>Equipment:</strong></td>
            <td width="65%" style="font-family: Arial;">' . htmlspecialchars($firstLog['equipment_name']) . '</td>
        </tr>
        <tr>
            <td style="font-family: Arial;"><strong>Property/Serial Number:</strong></td>
            <td style="font-family: Arial;">' . htmlspecialchars($firstLog['property_num']) . '</td>
        </tr>
        <tr>
            <td style="font-family: Arial;"><strong>Location:</strong></td>
            <td style="font-family: Arial;">' . htmlspecialchars($firstLog['location_name']) . '</td>
        </tr>
    </table>
    <br>
';

// Maintenance Logs table with precise column widths
$html .= '
    <table border="0.5" cellpadding="4" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th width="15%" style="font-family: Arial;">Date</th>
                <th width="15%" style="font-family: Arial;">JO Number</th>
                <th width="30%" style="font-family: Arial;">Actions Taken</th>
                <th width="20%" style="font-family: Arial;">Remarks</th>
                <th width="20%" style="font-family: Arial;">Responsible SDMD Personnel</th>
            </tr>
        </thead>
        <tbody>';

// Loop through the logs to populate the rows
foreach ($logs as $log) {
    $html .= '
        <tr>
            <td style="font-family: Arial;">' . htmlspecialchars($log['maintenance_date']) . '</td>
            <td style="font-family: Arial;">' . htmlspecialchars($log['jo_number']) . '</td>
            <td style="font-family: Arial;">' . htmlspecialchars($log['actions_taken']) . '</td>
            <td style="font-family: Arial;">' . htmlspecialchars($log['remarks']) . '</td>
            <td style="font-family: Arial;">' . htmlspecialchars($log['firstname'] . ' ' . $log['lastname']) . '</td>
        </tr>';
}

$html .= '
        </tbody>
    </table>
    <br>
    <div style="text-align: center; font-size: 10px; font-family: Arial;">
        Systems and Data Management Division (SDMD) - Page 1 of 1
    </div>';

// Write the HTML content to the PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Output the PDF document
$pdf->Output($firstLog['equipment_name'] . '-' . $firstLog['property_num'] . '.pdf', 'I');
?>
