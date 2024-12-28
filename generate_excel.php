<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

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

// Fetch maintenance logs for the specific property number
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

// Create a new Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set header
$headerColumns = ['A1' => 'Date', 'B1' => 'JO Number', 'C1' => 'Actions Taken', 'D1' => 'Remarks', 'E1' => 'Responsible SDMD Personnel'];
foreach ($headerColumns as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

// Apply bold styling to header row
$headerStyle = $sheet->getStyle('A1:E1');
$headerStyle->getFont()->setBold(true);
$headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$headerStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

// Populate data and center-align
$row = 2; // Start from the second row
foreach ($logs as $log) {
    $sheet->setCellValue('A' . $row, $log['maintenance_date']);
    $sheet->setCellValue('B' . $row, $log['jo_number']);
    $sheet->setCellValue('C' . $row, $log['actions_taken']);
    $sheet->setCellValue('D' . $row, $log['remarks']);
    $sheet->setCellValue('E' . $row, $log['firstname'] . ' ' . $log['lastname']);

    // Center-align each row
    $sheet->getStyle('A' . $row . ':E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A' . $row . ':E' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $row++;
}

// Auto-adjust column width
foreach (range('A', 'E') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// Set header for downloading Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $property_num . '.xlsx"');

// Write to output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
