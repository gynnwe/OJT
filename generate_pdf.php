<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

include 'conn.php';

try {
    ob_start();

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['plan_id'])) {
        $planId = $_POST['plan_id'];

        $queryPlan = "SELECT * FROM maintenance_plan WHERE id = :planId";
        $stmtPlan = $conn->prepare($queryPlan);
        $stmtPlan->execute([':planId' => $planId]);
        $maintenancePlan = $stmtPlan->fetch(PDO::FETCH_ASSOC);

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

        $groupedPlanDetails = [];
        foreach ($planDetails as $detail) {
            $groupedPlanDetails[$detail['equip_type_id']][] = $detail;
        }

        // Generate the image path
        $imagePath = 'http://' . $_SERVER['HTTP_HOST'] . '/OJT/assets/images/usep-logo.png';

        $html = '
            <style>
                body {
                    margin: 0.5cm;
                    font-family: Arial, sans-serif;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                table, th, td {
                    border: 1px solid black;
                }
                th, td {
                    padding: 5px;
                    text-align: center;
                    vertical-align: middle;
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    font-weight: normal;
                }

                .hyperlink {
                    color: blue;
                    text-decoration: underline;
                }
    
            </style>';
        $html .= '
            <table style="border: 1px solid black; border-collapse: collapse; width: 100%; height: 3.73cm;">
                <tr>
                    <td style="border: 1px solid black; width: 4.45cm; text-align: center;" rowspan="5">
                        <img src="http://' . $_SERVER['HTTP_HOST'] . '/OJT/assets/images/usep-logo.png" 
                             alt="USeP Logo" 
                             style="width: 2.50cm; height: 2.50cm;">
                    </td>
                    <td style="border: 1px solid black; width: 12.06cm; text-align: center; font-family: Arial, sans-serif; line-height: 1; padding: 5px;" rowspan="5">
                        <p style="font-family: Arial, sans-serif; font-size: 12px; margin: 0; line-height: 1;">Republic of the Philippines</p>
                        <p style="font-family: \'Old English Text MT\', serif; font-size: 16px; margin: 0; line-height: 1;">University of Southeastern Philippines</p>
                        <p style="font-family: Arial, sans-serif; font-size: 12px; margin: 0; line-height: 1;">Iñigo St., Bo. Obrero, Davao City 8000</p>
                        <p style="font-family: Arial, sans-serif; font-size: 12px; margin: 0; line-height: 1;">Telephone (082) 227-8192</p>
                        <p style="font-family: Arial, sans-serif; font-size: 12px; margin: 0; line-height: 1;">
                            <a href="http://www.usep.edu.ph" style="color: blue; text-decoration: underline;">www.usep.edu.ph</a>; 
                            <span style="font-family: Arial, sans-serif; font-size: 12px;">email:</span>
                            <a href="mailto:president@usep.edu.ph" style="color: blue; text-decoration: underline;">president@usep.edu.ph</a>
                        </p>
                    </td>
    <td style="border: 1px solid black; width: 3.5cm; height: 0.50cm; text-align: left; font-family: Arial, sans-serif; font-size: 12px;">Form No.</td>
    <td style="border: 1px solid black; width: 3.5cm; height: 0.50cm; text-align: left; font-family: Arial, sans-serif; font-size: 12px;">FM-USeP-ICT-10</td>
</tr>
<tr>
    <td style="border: 1px solid black; width: 3.5cm; height: 0.50cm; text-align: left; font-family: Arial, sans-serif; font-size: 12px;">Issue Status</td>
    <td style="border: 1px solid black; width: 3.5cm; height: 0.50cm; text-align: left; font-family: Arial, sans-serif; font-size: 12px;">01</td>
</tr>
<tr>
    <td style="border: 1px solid black; width: 3.5cm; height: 0.50cm; text-align: left; font-family: Arial, sans-serif; font-size: 12px;">Revision No.</td>
    <td style="border: 1px solid black; width: 3.5cm; height: 0.50cm; text-align: left; font-family: Arial, sans-serif; font-size: 12px;">00</td>
</tr>
<tr>
    <td style="border: 1px solid black; width: 3.5cm; height: 0.50cm; text-align: left; font-family: Arial, sans-serif; font-size: 12px;">Date Effective</td>
    <td style="border: 1px solid black; width: 3.5cm; height: 0.50cm; text-align: left; font-family: Arial, sans-serif; font-size: 12px;">23 December 2022</td>
</tr>
<tr>
    <td style="border: 1px solid black; width: 3.5cm; height: 0.50cm; text-align: left; font-family: Arial, sans-serif; font-size: 12px;">Approved by</td>
    <td style="border: 1px solid black; width: 3.5cm; height: 0.50cm; text-align: left; font-family: Arial, sans-serif; font-size: 12px;">President</td>
</tr>

                <tr>
                    <td style="border: 1px solid black; text-align: left; padding: 5px; height: .9cm;" colspan="4">
                        <div style="font-family: Arial, sans-serif; text-align: center;">
                            <span style="font-size: 14px; font-weight: bold;">ANNUAL PREVENTIVE MAINTENANCE PLAN FOR ICT EQUIPMENT</span><br>
                            <span style="font-size: 12px; font-weight: normal;">Year: <span style="text-decoration: underline;">' . htmlspecialchars($maintenancePlan['year']) . '</span></span>
                        </div>
                    </td>
                </tr>
            </table>
            
    <div style="font-family: Arial, sans-serif; font-size: 12px; line-height: 1.5; margin-top: 12px;">
        Name of Office/College/School/Unit: ______________________________
        <span style="display: inline-block; margin-left: 70px;">Campus: _____________________________</span>
    </div>
    <br>
';



        $tableCounter = 1;

        foreach ($groupedPlanDetails as $equipTypeId => $details) {
            $html .= '<table>';
            $html .= '<thead>';
            $html .= '<tr><th rowspan="2">No.</th><th rowspan="2">Equipment Type/Name</th><th rowspan="2">Areas to be Maintained / Checked</th><th colspan="' . (count($details) + 1) . '" style="text-align: center;">Schedule</th></tr>';
            $html .= '<tr><th></th>';
            foreach ($details as $detail) {
                $monthAcronym = substr($detail['month'], 0, 3);
                $html .= '<th>' . htmlspecialchars($monthAcronym) . '</th>';
            }
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
            $html .= '<tr>';
            $html .= '<td rowspan="2">' . $tableCounter . '</td>';
            $html .= '<td rowspan="2">' . htmlspecialchars($details[0]['equip_type_name']) . '</td>';
            $html .= '<td>Hardware</td><td>Plan</td>';
            foreach ($details as $detail) {
                $html .= '<td>' . htmlspecialchars((int) $detail['target']) . '</td>';
            }
            $html .= '</tr>';
            $html .= '<tr>';
            $html .= '<td>Software</td><td>Implemented</td>';
            foreach ($details as $detail) {
                $html .= '<td>' . htmlspecialchars((int) $detail['implemented']) . '</td>';
            }
            $html .= '</tr>';
            $html .= '</tbody>';
            $html .= '</table><br>';

            $tableCounter++;
        }

        $html .= '
        <table style="border: 1px solid black; border-collapse: collapse; width: 100%; margin-top: 12px;">
            <tr>
                <!-- Column 1 Content -->
                <td style="border: 1px solid black; width: 50%; text-align: left; padding: 5px; height: 3.59cm;">
                    <span style="font-family: Arial, sans-serif; font-size: 12px; font-weight: bold;">Prepared by:</span><br><br>
                    <div style="text-align: center;">
                        <span style="font-family: Arial, sans-serif; font-size: 12px;">______________________________</span><br>
                        <span style="font-family: Arial, sans-serif; font-size: 12px; font-weight: bold;">SDMD Deputy Director/Authorized Representative</span><br>
                        <span style="font-family: Arial, sans-serif; font-size: 12px; font-style: italic;">(Signature Over Printed Name)</span><br>
                        <span style="font-family: Arial, sans-serif; font-size: 12px;">Date: ____________________</span>
                    </div>
                </td>
    
                <!-- Column 2 Content (Same as Column 1) -->
                <td style="border: 1px solid black; width: 50%; text-align: left; padding: 5px; height: 3.59cm;">
                    <span style="font-family: Arial, sans-serif; font-size: 12px; font-weight: bold;">Approved by:</span><br><br>
                    <div style="text-align: center;">
                        <span style="font-family: Arial, sans-serif; font-size: 12px;">______________________________</span><br>
                        <span style="font-family: Arial, sans-serif; font-size: 12px; font-weight: bold;">SDMD Director</span><br>
                        <span style="font-family: Arial, sans-serif; font-size: 12px; font-style: italic;">(Signature Over Printed Name)</span><br>
                        <span style="font-family: Arial, sans-serif; font-size: 12px;">Date: ____________________</span>
                    </div>
                </td>
            </tr>
        </table>
    ';



        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true); // Enable remote loading for external resources
        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        ob_end_clean();
        header('Content-Type: application/pdf');

        $dompdf->stream('Maintenance_Plan_' . $maintenancePlan['id'] . '.pdf', ['Attachment' => false]);
    } else {
        throw new Exception("Invalid request or missing Plan ID.");
    }
} catch (Exception $e) {
    ob_end_clean();
    die("Error: " . $e->getMessage());
}
?>