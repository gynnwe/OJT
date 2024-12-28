<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

include 'conn.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['plan_id'])) {
        $planId = $_POST['plan_id'];

        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch plan details
        $queryPlan = "SELECT * FROM maintenance_plan WHERE id = :planId";
        $stmtPlan = $conn->prepare($queryPlan);
        $stmtPlan->execute([':planId' => $planId]);
        $maintenancePlan = $stmtPlan->fetch(PDO::FETCH_ASSOC);

        // Fetch plan details with equipment type
        $queryDetails = "
            SELECT 
                pd.*, 
                et.equip_type_name,
                (
                    SELECT COUNT(DISTINCT e.equipment_id) 
                    FROM equipment e
                    JOIN ict_maintenance_logs ml ON e.equipment_id = ml.equipment_id
                    WHERE e.status = 'Serviceable' 
                    AND e.equip_type_id = pd.equip_type_id
                    AND YEAR(ml.maintenance_date) = :planYear
                    AND MONTH(ml.maintenance_date) = pd.month
                ) AS implemented
            FROM plan_details pd
            JOIN equipment_type et ON pd.equip_type_id = et.equip_type_id
            WHERE pd.maintenance_plan_id = :planId
            ORDER BY et.equip_type_id, pd.month";
        $stmtDetails = $conn->prepare($queryDetails);
        $stmtDetails->bindParam(':planYear', $maintenancePlan['year'], PDO::PARAM_INT);
        $stmtDetails->bindParam(':planId', $planId, PDO::PARAM_INT);
        $stmtDetails->execute();
        $planDetails = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

        // Group data by equipment type
        $groupedPlanDetails = [];
        foreach ($planDetails as $detail) {
            $groupedPlanDetails[$detail['equip_type_id']][] = $detail;
        }

        // Create Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set Title
        $sheet->setCellValue('A1', 'Maintenance Plan for ICT Equipment');
        $sheet->setCellValue('A2', 'Year: ' . htmlspecialchars($maintenancePlan['year']));
        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');

        // Center-align and set font for title
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(14);

        // Initialize Row Counter
        $row = 4;

        foreach ($groupedPlanDetails as $equipTypeId => $details) {
            $sheet->setCellValue('A' . $row, 'Equipment Type: ' . htmlspecialchars($details[0]['equip_type_name']));
            $sheet->mergeCells('A' . $row . ':E' . $row);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;

            // Set Table Headers
            $sheet->setCellValue('A' . $row, 'No.');
            $sheet->setCellValue('B' . $row, 'Month');
            $sheet->setCellValue('C' . $row, 'Plan');
            $sheet->setCellValue('D' . $row, 'Implemented');
            $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;

            $counter = 1;
            foreach ($details as $detail) {
                $sheet->setCellValue('A' . $row, $counter);
                $sheet->setCellValue('B' . $row, htmlspecialchars($detail['month']));
                $sheet->setCellValue('C' . $row, (int) $detail['target']);
                $sheet->setCellValue('D' . $row, (int) $detail['implemented']);
                $sheet->getStyle('A' . $row . ':D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $row++;
                $counter++;
            }

            $row++; // Add empty row between groups
        }

        // Auto-Size Columns
        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Set Header for Download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Maintenance_Plan_' . $maintenancePlan['id'] . '.xlsx"');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    } else {
        throw new Exception("Invalid request or missing Plan ID.");
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
