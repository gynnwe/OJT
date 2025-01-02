<?php
require 'vendor/autoload.php'; // Include PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

if (!empty($_POST['plan_id'])) {
    include 'conn.php';

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $planId = $_POST['plan_id'];

        // Fetch maintenance plan details
        $queryPlan = "SELECT id, year, date_prepared FROM maintenance_plan WHERE id = :planId";
        $stmtPlan = $conn->prepare($queryPlan);
        $stmtPlan->execute([':planId' => $planId]);
        $maintenancePlan = $stmtPlan->fetch(PDO::FETCH_ASSOC);

        // Fetch plan details grouped by equipment type
        $queryDetails = "
            SELECT pd.*, et.equip_type_name 
            FROM plan_details pd
            JOIN equipment_type et ON pd.equip_type_id = et.equip_type_id
            WHERE pd.maintenance_plan_id = :planId
            ORDER BY et.equip_type_id, pd.month";
        $stmtDetails = $conn->prepare($queryDetails);
        $stmtDetails->execute([':planId' => $planId]);
        $planDetails = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

        // Group details by equipment type
        $groupedPlanDetails = [];
        foreach ($planDetails as $detail) {
            $groupedPlanDetails[$detail['equip_type_id']]['equip_type_name'] = $detail['equip_type_name'];
            $groupedPlanDetails[$detail['equip_type_id']]['details'][] = $detail;
        }

        // Create Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Maintenance Plan');

        // Set Plan Information
        $sheet->setCellValue('A1', 'Maintenance Plan ID:');
        $sheet->setCellValue('B1', $maintenancePlan['id']);
        $sheet->setCellValue('A2', 'Year:');
        $sheet->setCellValue('B2', $maintenancePlan['year']);
        $sheet->setCellValue('A3', 'Date Prepared:');
        $sheet->setCellValue('B3', $maintenancePlan['date_prepared']);

        // Dynamic start row for each table
        $currentRow = 5;

        // Loop through each equipment type
        foreach ($groupedPlanDetails as $equipTypeId => $data) {
            // Add Equipment Type header
            $sheet->setCellValue("A$currentRow", 'Equipment Type:');
            $sheet->setCellValue("B$currentRow", $data['equip_type_name']);
            $currentRow++;

            // Add table headers in column A
            $sheet->setCellValue("A$currentRow", 'Month');
            $sheet->setCellValue("A" . ($currentRow + 1), 'Plan');
            $sheet->setCellValue("A" . ($currentRow + 2), 'Implemented');

            // Make column titles bold
            $sheet->getStyle("A$currentRow:A" . ($currentRow + 2))->applyFromArray([
                'font' => [
                    'bold' => true,
                ],
            ]);

            // Fill data horizontally
            $column = 'B'; // Start filling data from column B
            foreach ($data['details'] as $detail) {
                // Calculate the implemented count
                $monthNumeric = date('m', strtotime($detail['month']));
                $queryImplemented = "
                    SELECT COUNT(DISTINCT e.equipment_id) AS implemented_count
                    FROM ict_maintenance_logs ml
                    JOIN equipment e ON ml.equipment_id = e.equipment_id
                    WHERE e.equip_type_id = :equipTypeId
                    AND YEAR(ml.maintenance_date) = :year
                    AND MONTH(ml.maintenance_date) = :month";
                $stmtImplemented = $conn->prepare($queryImplemented);
                $stmtImplemented->execute([
                    ':equipTypeId' => $detail['equip_type_id'],
                    ':year' => $maintenancePlan['year'],
                    ':month' => $monthNumeric,
                ]);
                $implementedCount = $stmtImplemented->fetchColumn();

                // Populate data in the sheet
                $sheet->setCellValue("$column" . $currentRow, $detail['month']);
                $sheet->setCellValue("$column" . ($currentRow + 1), $detail['target']);
                $sheet->setCellValue("$column" . ($currentRow + 2), $implementedCount);
                $column++;
            }

            // Move to the next row for the next table
            $currentRow += 4; // Add space between tables
        }

        // Apply Font Style (Arial, Size 11)
        $sheet->getStyle("A1:$column$currentRow")->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 11,
            ],
        ]);

        // Apply Global Center Alignment for All Data
        $sheet->getStyle("A1:$column$currentRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Set Auto Width
        foreach (range('A', $column) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Dynamic Filename with Maintenance Plan ID
        $filename = "Maintenance Plan {$maintenancePlan['id']}.xlsx";

        // Export to Excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=\"$filename\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    die("No plan ID provided.");
}
?>
