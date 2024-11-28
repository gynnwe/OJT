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

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    $pdf->AddPage();

    // Header Section
    $html = '
    <table border="1" cellpadding="5" cellspacing="0" width="100%">
        <tr>
            <td rowspan="2" width="15%" style="text-align: center; vertical-align: middle; padding-top: 20px;">
                <img src="C:/xampp/htdocs/OJT/assets/usep-logo.jpg" alt="Logo" width="90" height="90">
            </td>
            <td rowspan="2" width="60%" style="text-align: center;">
                <h3>Republic of the Philippines</h3>
                <h2>University of Southeastern Philippines</h2>
                <p>Iñigo St., Bo. Obrero, Davao City 8000<br>
                Telephone: (082) 227-8192<br>
                <a href="mailto:president@usep.edu.ph">president@usep.edu.ph</a></p>
            </td>
            <td width="25%" style="text-align: left; vertical-align: top;">
                <table border="0" cellpadding="2" cellspacing="0" width="100%">
                    <tr>
                        <td><strong>Form No.</strong></td>
                        <td>: FM-UseP-ICT-10</td>
                    </tr>
                    <tr>
                        <td><strong>Issue Status</strong></td>
                        <td>: 01</td>
                    </tr>
                    <tr>
                        <td><strong>Revision No.</strong></td>
                        <td>: 00</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td width="25%" style="text-align: left; vertical-align: top;">
                <table border="0" cellpadding="2" cellspacing="0" width="100%">
                    <tr>
                        <td><strong>Date Effective</strong></td>
                        <td>: 23 December 2022</td>
                    </tr>
                    <tr>
                        <td><strong>Approved by</strong></td>
                        <td>: President</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <h3 style="text-align: center; margin-top: 10px;">ANNUAL PREVENTIVE MAINTENANCE PLAN FOR ICT EQUIPMENT</h3>
    <p style="text-align: left;"><strong>Year</strong>: ________________________________</p>
    <p style="text-align: left;"><strong>Name of Office/College/School/Unit</strong>: ________________________________</p>
    ';
    

    // Table Content
    $html .= '
        <table border="1" cellpadding="4">
            <thead>
                <tr>
                    <th style="text-align:center;" colspan="13">Schedule</th>
                </tr>
                <tr>
                    <th></th>
                    <th>Jan</th>
                    <th>Feb</th>
                    <th>Mar</th>
                    <th>Apr</th>
                    <th>May</th>
                    <th>Jun</th>
                    <th>Jul</th>
                    <th>Aug</th>
                    <th>Sep</th>
                    <th>Oct</th>
                    <th>Nov</th>
                    <th>Dec</th>
                </tr>
            </thead>
            <tbody>';

    // Plan Row
    $html .= '<tr>
                <td><strong>Plan</strong></td>';
    foreach ($planDetails as $detail) {
        $html .= '<td style="text-align:center;">' . htmlspecialchars($detail['target']) . '</td>';
    }
    $html .= '</tr>';

    // Implemented Row
    $html .= '<tr>
                <td><strong>Implemented</strong></td>';
    foreach ($planDetails as $detail) {
        $monthNumeric = date_parse($detail['month'])['month'];
        $queryTotalMaintained = "
            SELECT COUNT(DISTINCT e.equipment_id) AS total_maintained 
            FROM equipment e
            JOIN ict_maintenance_logs ml ON e.equipment_id = ml.equipment_id
            WHERE e.status = 'Serviceable' 
            AND e.equip_type_id = :equipmentTypeId
            AND YEAR(ml.maintenance_date) = :planYear
            AND MONTH(ml.maintenance_date) = :month";
        $stmtTotalMaintained = $conn->prepare($queryTotalMaintained);
        $stmtTotalMaintained->bindParam(':equipmentTypeId', $detail['equipment_id'], PDO::PARAM_INT);
        $stmtTotalMaintained->bindParam(':planYear', $maintenancePlan['year'], PDO::PARAM_INT);
        $stmtTotalMaintained->bindParam(':month', $monthNumeric, PDO::PARAM_INT);
        $stmtTotalMaintained->execute();
        $totalMaintained = $stmtTotalMaintained->fetch(PDO::FETCH_ASSOC)['total_maintained'];

        $html .= '<td style="text-align:center;">' . htmlspecialchars($totalMaintained ?? 0) . '</td>';
    }
    $html .= '</tr>';

    $html .= '</tbody></table>';

// Signature Section
$html .= '
<table border="1" cellpadding="10" cellspacing="0" width="100%" style="margin-top: 20px;">
    <tr>
        <td width="50%" style="text-align: center; vertical-align: middle;">
            <strong>Prepared by:</strong><br><br>
            <span>______________________________</span><br>
            <span><strong>SDMD Deputy Director/Authorized Representative</strong></span><br>
            <span>(Signature Over Printed Name)</span><br>
            <span>Date: ____________________</span>
        </td>
        <td width="50%" style="text-align: center; vertical-align: middle;">
            <strong>Approved by:</strong><br><br>
            <span>______________________________</span><br>
            <span><strong>SDMD Director</strong></span><br>
            <span>(Signature Over Printed Name)</span><br>
            <span>Date: ____________________</span>
        </td>
    </tr>
</table>
';


    // Write the HTML to the PDF
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('MaintenancePlan.pdf', 'I');
}
?>
