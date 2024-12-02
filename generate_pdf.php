<?php
require_once 'tcpdf/tcpdf.php'; // Ensure this path is correct
require_once 'vendor/autoload.php'; // Ensure this path is correct if FPDI is installed via Composer

use setasign\Fpdi\Tcpdf\Fpdi;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['plan_id'])) {
    $planId = $_POST['plan_id'];

    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "ictmms";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch the maintenance plan
        $stmtPlan = $conn->prepare("SELECT * FROM maintenance_plan WHERE id = :planId");
        $stmtPlan->bindParam(':planId', $planId, PDO::PARAM_INT);
        $stmtPlan->execute();
        $maintenancePlan = $stmtPlan->fetch(PDO::FETCH_ASSOC);

        // Fetch equipment name using equipment_id from the maintenance plan
        $equipmentId = $maintenancePlan['equipment_id'] ?? null; // Ensure equipment_id exists
        $equipmentName = 'N/A'; // Default if not found

        if ($equipmentId) {
            $stmtEquipment = $conn->prepare("SELECT equip_type_name FROM equipment_type WHERE equip_type_id = :equipmentId");
            $stmtEquipment->bindParam(':equipmentId', $equipmentId, PDO::PARAM_INT);
            $stmtEquipment->execute();
            $equipment = $stmtEquipment->fetch(PDO::FETCH_ASSOC);
            if ($equipment) {
                $equipmentName = $equipment['equip_type_name'];
            }
        }

        // Fetch plan details and calculate implemented values
        $stmtDetails = $conn->prepare("
            SELECT pd.*, 
                   (SELECT COUNT(DISTINCT ml.equipment_id)
                    FROM ict_maintenance_logs ml
                    WHERE YEAR(ml.maintenance_date) = :planYear
                      AND MONTH(ml.maintenance_date) = MONTH(STR_TO_DATE(pd.month, '%M'))
                      AND ml.equipment_id = pd.equipment_id) AS implemented
            FROM plan_details pd
            WHERE maintenance_plan_id = :planId
            ORDER BY FIELD(pd.month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')
        ");
        $stmtDetails->bindParam(':planId', $planId, PDO::PARAM_INT);
        $stmtDetails->bindParam(':planYear', $maintenancePlan['year'], PDO::PARAM_INT);
        $stmtDetails->execute();
        $planDetails = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }

    if (!$maintenancePlan || empty($planDetails)) {
        die("No data found for the provided plan ID.");
    }

    // Create a new FPDI document
    $pdf = new Fpdi('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetTitle('Maintenance Plan');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Load the template
    $templatePath = 'assets/Maintenance_Plan_Template.pdf';
    if (!file_exists($templatePath)) {
        die('Template PDF not found.');
    }
    $pdf->AddPage();
    $pdf->setSourceFile($templatePath);
    $tplId = $pdf->importPage(1);
    $pdf->useTemplate($tplId);

    // Fill Year and Equipment Name
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->SetXY(150, 63); // Adjust coordinates for the year
    $pdf->Write(0, htmlspecialchars($maintenancePlan['year'] ?? 'N/A'));
    $pdf->SetXY(50, 50); // Adjust coordinates for the equipment name
    $pdf->Write(0, 'Equipment Name: ' . htmlspecialchars($equipmentName));

    // Fill Table Data (Targets and Implemented)
    $startX = 159.9; // Starting X coordinate
    $startY = 96.7; // Starting Y coordinate
    $cellWidth = 10.5; // Cell width
    $rowHeight = 6; // Row height

    // Render Target Row
    $x = $startX;
    foreach ($planDetails as $detail) {
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY($x, $startY);
        $pdf->Write(0, htmlspecialchars((int)$detail['target']));
        $x += $cellWidth;
    }

    // Render Implemented Row
    $startY += $rowHeight;
    $x = $startX;
    foreach ($planDetails as $detail) {
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY($x, $startY);
        $pdf->Write(0, htmlspecialchars((int)($detail['implemented'] ?? 0)));
        $x += $cellWidth;
    }

    // Output the PDF
    $pdf->Output('MaintenancePlan.pdf', 'I');
}
