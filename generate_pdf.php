<?php
require 'vendor/autoload.php'; // Include DOMPDF library

use Dompdf\Dompdf;
use Dompdf\Options;

// Database connection
include 'conn.php';

try {
    ob_start(); // Start output buffering

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['plan_id'])) {
        $planId = $_POST['plan_id'];

        // Fetch maintenance plan
        $queryPlan = "SELECT * FROM maintenance_plan WHERE id = :planId";
        $stmtPlan = $conn->prepare($queryPlan);
        $stmtPlan->execute([':planId' => $planId]);
        $maintenancePlan = $stmtPlan->fetch(PDO::FETCH_ASSOC);

        // Fetch plan details
        $queryDetails = "
                SELECT pd.*, et.equip_type_name 
                FROM plan_details pd
                JOIN equipment_type et ON pd.equip_type_id = et.equip_type_id
                WHERE pd.maintenance_plan_id = :planId
                ORDER BY et.equip_type_id, pd.month";
        $stmtDetails = $conn->prepare($queryDetails);
        $stmtDetails->execute([':planId' => $planId]);
        $planDetails = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

        $groupedPlanDetails = [];
        foreach ($planDetails as $detail) {
            $groupedPlanDetails[$detail['equip_type_id']][] = $detail;
        }

        // HTML content for PDF with CSS for margins
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
                    text-align: left;
                }
            </style>';
        $html .= '<h1>Maintenance Plan ' . htmlspecialchars($maintenancePlan['id']) . '</h1>';
        $html .= '<p><strong>Year:</strong> ' . htmlspecialchars($maintenancePlan['year']) . '</p>';
        $html .= '<p><strong>Date Prepared:</strong> ' . htmlspecialchars($maintenancePlan['date_prepared']) . '</p>';

        foreach ($groupedPlanDetails as $equipTypeId => $details) {
            $html .= '<h4>Equipment Type: ' . htmlspecialchars($details[0]['equip_type_name']) . '</h4>';
            $html .= '<table>';
            $html .= '<thead>';
            // Schedule Row with Merged Cell
            $html .= '<tr><th rowspan="2">Equipment Type/Name</th><th rowspan="2">Areas to be Maintained / Checked</th><th colspan="' . (count($details) + 1) . '" style="text-align: center;">Schedule</th></tr>';

            // Month Acronyms Row
            $html .= '<tr><th></th>'; // Empty first cell for "Plan"/"Implemented"
            foreach ($details as $detail) {
                $monthAcronym = substr($detail['month'], 0, 3); // Get the first 3 letters of the month
                $html .= '<th>' . htmlspecialchars($monthAcronym) . '</th>';
            }
            $html .= '</tr>';
            $html .= '</thead>';

            // Table Body
            $html .= '<tbody>';

            // Add duplicate columns in the rows with merged cell for Equipment Type/Name
            $html .= '<tr>';
            $html .= '<td rowspan="2">Blank Text</td>'; // Merge the two rows beneath this column
            $html .= '<td>Hardware</td><td><strong>Plan</strong></td>';
            foreach ($details as $detail) {
                $html .= '<td>' . htmlspecialchars((int) $detail['target']) . '</td>';
            }
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<td>Software</td><td><strong>Implemented</strong></td>';
            foreach ($details as $detail) {
                $html .= '<td>' . htmlspecialchars((int) $detail['implemented'] ?? 0) . '</td>';
            }
            $html .= '</tr>';

            $html .= '</tbody>';

            $html .= '</table><br>';
        }

        // DOMPDF Configuration
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);

        // Set the HTML content
        $dompdf->loadHtml($html);

        // Set paper size to A4 and orientation to landscape
        $dompdf->setPaper('A4', 'landscape');

        // Render the PDF
        $dompdf->render();

        // Clear buffer and send proper headers
        ob_end_clean();
        header('Content-Type: application/pdf');

        // Output the generated PDF
        $dompdf->stream('Maintenance_Plan_' . $maintenancePlan['id'] . '.pdf', ['Attachment' => false]);
    } else {
        throw new Exception("Invalid request or missing Plan ID.");
    }
} catch (Exception $e) {
    ob_end_clean();
    die("Error: " . $e->getMessage());
}
?>
