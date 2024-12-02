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

        // Fetch the maintenance plan and details
        $stmtPlan = $conn->prepare("SELECT * FROM maintenance_plan WHERE id = :planId");
        $stmtPlan->bindParam(':planId', $planId, PDO::PARAM_INT);
        $stmtPlan->execute();
        $maintenancePlan = $stmtPlan->fetch(PDO::FETCH_ASSOC);

        $stmtDetails = $conn->prepare("SELECT * FROM plan_details WHERE maintenance_plan_id = :planId ORDER BY month ASC");
        $stmtDetails->bindParam(':planId', $planId, PDO::PARAM_INT);
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

    // Add dynamic content to the template
    $pdf->SetFont('Helvetica', '', 10);

    // Fill Year and Equipment Name (use null coalescing operator to avoid errors)
    $pdf->SetXY(300, 30); // Adjust based on template coordinates
    $pdf->Write(0, htmlspecialchars($maintenancePlan['year'] ?? 'N/A'));
    $pdf->SetXY(200, 40); // Adjust based on template coordinates
    $pdf->Write(0, htmlspecialchars($maintenancePlan['equipment_name'] ?? 'N/A'));

    // Fill Table Data (Targets and Implemented)
    $startX = 160;
    $startY = 96.7;
    $cellWidth = 10;
    $rowHeight = 6;

    // Targets Row
    $x = $startX;
    foreach ($planDetails as $detail) {
        $pdf->SetXY($x, $startY);
        $pdf->Write(0, htmlspecialchars($detail['target'] ?? '0')); // Use null coalescing operator
        $x += $cellWidth;
    }

    // Implemented Row
    $startY += $rowHeight;
    $x = $startX;
    foreach ($planDetails as $detail) {
        $pdf->SetXY($x, $startY);
        $pdf->Write(0, htmlspecialchars($detail['implemented'] ?? '0')); // Use null coalescing operator
        $x += $cellWidth;
    }

    // Output the PDF
    $pdf->Output('MaintenancePlan.pdf', 'I');
}
