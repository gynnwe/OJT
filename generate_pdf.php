<?php
require_once 'tcpdf/tcpdf.php'; // Ensure this file exists in the specified directory

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

        // Fetch maintenance plan data
        $stmt = $conn->prepare("SELECT * FROM maintenance_plan WHERE id = :planId");
        $stmt->bindParam(':planId', $planId, PDO::PARAM_INT);
        $stmt->execute();
        $maintenancePlan = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch plan details
        $stmtDetails = $conn->prepare("SELECT * FROM plan_details WHERE maintenance_plan_id = :planId");
        $stmtDetails->bindParam(':planId', $planId, PDO::PARAM_INT);
        $stmtDetails->execute();
        $planDetails = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }

    // Create a new PDF document
    $pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetTitle('Maintenance Plan');

    $pdf->AddPage();

    $html = '
        <h1>Maintenance Plan ' . htmlspecialchars($maintenancePlan['id']) . '</h1>
        <p><strong>Year:</strong> ' . htmlspecialchars($maintenancePlan['year']) . '</p>
        <p><strong>Date Prepared:</strong> ' . htmlspecialchars($maintenancePlan['date_prepared']) . '</p>
        <table border="1" cellpadding="4">
            <thead>
                <tr><th>Month</th><th>Target</th><th>Implemented</th></tr>
            </thead>
            <tbody>';
    foreach ($planDetails as $detail) {
        $html .= '<tr>
            <td>' . htmlspecialchars($detail['month']) . '</td>
            <td>' . htmlspecialchars($detail['target']) . '</td>
            <td>' . htmlspecialchars($detail['implemented'] ?? 0) . '</td>
        </tr>';
    }
    $html .= '</tbody></table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('MaintenancePlan.pdf', 'I');
}
?>
