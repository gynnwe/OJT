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

// Place Equipment Details
$pdf->SetXY(57, 61.4); // Adjust for Equipment
$pdf->Write(0, $logs[0]['equipment_name']);

$pdf->SetXY(57, 67.7); // Adjust for Property/Serial Number
$pdf->Write(0, $logs[0]['property_num']);

$pdf->SetXY(57, 74.2); // Adjust for Location
$pdf->Write(0, $logs[0]['location_name']);

// Place Maintenance Logs
$startY = 90.5; // Starting Y coordinate for the first row of the logs table
$lineHeight = 5.1; // Height between rows

foreach ($logs as $log) {
    $pdf->SetXY(19, $startY);
    $pdf->Write(0, $log['maintenance_date']); // Date

    $pdf->SetXY(47, $startY);
    $pdf->Write(0, $log['jo_number']); // JO Number

    $pdf->SetXY(70, $startY);
    $pdf->Write(0, $log['actions_taken']); // Actions Taken

    $pdf->SetXY(144, $startY);
    $pdf->Write(0, $log['remarks']); // Remarks

    $pdf->SetXY(170, $startY);
    $pdf->Write(0, $log['firstname'] . ' ' . $log['lastname']); // Responsible Personnel

    $startY += $lineHeight; // Move to the next row
}

// Output PDF
$pdf->Output('output.pdf', 'I');
?>
