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
                    margin: 1.27cm;
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
                    font-size: 10px;
                    font-weight: normal;
                }
                .col-1 {
                    width: 4.45cm;
                    height: 3.73cm;
                }
                .col-2 {
                    width: 12.06cm;
                    height: 3.73cm;
                    text-align: center;
                    font-size: 10px;
                    line-height: 1;
                }
                .col-3,
                .col-4 {
                    width: 3.46cm;
                    height: 3.73cm;
                }
                .hyperlink {
                    color: blue;
                    text-decoration: underline;
                }
                .header-text {
                    font-family: Arial, sans-serif;
                }
                .header-text-bold {
                    font-family: "Old English Text MT", serif;
                    font-size: 16px;
                }
            </style>';

        // Add table with the logo and second column text
        $html .= '
            <table class="fixed-table">
                <tr>
                    <td class="col-1">
                        <img src="' . $imagePath . '" style="width: 2.81cm; height: 2.81cm;" alt="Logo">
                    </td>
                    <td class="col-2">
                        <p class="header-text" style="margin: 0;">Republic of the Philippines</p>
                        <p class="header-text-bold" style="margin: 0;">University of Southeastern Philippines</p>
                        <p class="header-text" style="margin: 0;">I&ntilde;igo St., Bo. Obrero, Davao City 8000</p>
                        <p class="header-text" style="margin: 0;">Telephone (082) 227-8192</p>
                        <p class="header-text" style="margin: 0;">
                            <a href="http://www.usep.edu.ph" class="hyperlink">www.usep.edu.ph</a>; 
                            email: <a href="mailto:president@usep.edu.ph" class="hyperlink">president@usep.edu.ph</a>
                        </p>
                    </td>
                    <td class="col-3">Column 3</td>
                    <td class="col-4">Column 4</td>
                </tr>
            </table>
            <br>';

        $html .= '<p style="font-family: Arial, sans-serif; font-size: 12px; font-weight: normal;">Year: <span style="text-decoration: underline;">' . htmlspecialchars($maintenancePlan['year']) . '</span></p>';

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
