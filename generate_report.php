<?php
require_once 'vendor/autoload.php';

use setasign\Fpdi\TcpdfFpdi;

// Custom TCPDF class with FPDI integration
class CustomPDF extends TcpdfFpdi {
    public function Header() {
        // Override this method to prevent the default header from being added
    }

    public function Footer() {
        // Override this method to prevent the footer as well
    }
}

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

// Function to add equipment header details on every page
function addEquipmentHeader($pdf, $logs) {
    // Place Equipment Details
    $pdf->SetXY(57, 61.4); // Adjust for Equipment
    $pdf->Write(0, $logs[0]['equipment_name']);

    $pdf->SetXY(57, 67.7); // Adjust for Property/Serial Number
    $pdf->Write(0, $logs[0]['property_num']);

    $pdf->SetXY(57, 74.2); // Adjust for Location
    $pdf->Write(0, $logs[0]['location_name']);
}

// Create PDF instance
$pdf = new CustomPDF();
$pdf->SetAutoPageBreak(true, 10);
$pdf->SetMargins(0, 0, 0); // Top, left, right margins set to 0

// Import the PDF template
$templatePath = 'assets/ICT_Equipment_Template.pdf'; // Path to the uploaded template
if (!file_exists($templatePath)) {
    die('Template PDF not found.');
}
$pdf->AddPage();
$pdf->setSourceFile($templatePath);
$tplId = $pdf->importPage(1);
$pdf->useTemplate($tplId);

// Add the Calibri font
$pdf->SetFont('calibri', '', 9); // Use Calibri font, size 11

// Add header information to the first page
addEquipmentHeader($pdf, $logs);

// Place Maintenance Logs
$startY = 90.5; // Starting Y coordinate for the first row of the logs table
$lineHeight = 5.1; // Height between rows
$pageHeightLimit = 270; // Approximate usable height of the page (adjust based on your template)

foreach ($logs as $log) {
    // Check if adding the next row exceeds the page height
    if ($startY + $lineHeight > $pageHeightLimit) {
        // Add a new page and re-import the template
        $pdf->AddPage();
        $tplId = $pdf->importPage(1); // Import the first page of the template
        $pdf->useTemplate($tplId);

        // Add header information to the new page
        addEquipmentHeader($pdf, $logs);

        // Reset the starting Y coordinate for the new page
        $startY = 90.5; // Start again from the top of the logs section
    }

    // Write log data to the current page
    $pdf->SetXY(19, $startY);
    $pdf->Write(0, $log['maintenance_date']); // Date

    $pdf->SetXY(46.5, $startY);
    $pdf->Write(0, $log['jo_number']); // JO Number

    $pdf->SetXY(70, $startY);
    $pdf->Write(0, $log['actions_taken']); // Actions Taken

    $pdf->SetXY(142, $startY);
    $pdf->Write(0, $log['remarks']); // Remarks

    $pdf->SetXY(165, $startY);
    $pdf->Write(0, $log['firstname'] . ' ' . $log['lastname']); // Responsible Personnel

    // Move to the next row
    $startY += $lineHeight;
}

// Output PDF
$filename = $property_num . '.pdf'; // Use the property number as the filename
$pdf->Output($filename, 'I');
?>
