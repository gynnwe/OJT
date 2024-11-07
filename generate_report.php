<?php
// Ensure no spaces or newlines before this PHP opening tag

require_once 'vendor/autoload.php'; // Correct path to autoload.php

// Create a new PDF document instance
$pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('University of Southeastern Philippines');
$pdf->SetTitle('ICT Equipment History Sheet');
$pdf->SetSubject('Equipment Report');

// Set default margins
$pdf->SetMargins(15, 20, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Add a new page
$pdf->AddPage();

// University header
$html = '
    <div style="text-align: center; font-size: 14px;">
        <strong>Republic of the Philippines</strong><br>
        <strong>University of Southeastern Philippines</strong><br>
        Iñigo St., Bo. Obrero, Davao City 8000<br>
        Telephone: (082) 227-8192<br>
        Website: www.usep.edu.ph &nbsp; Email: president@usep.edu.ph<br>
    </div>
    <hr>
    <h2 style="text-align: center;">ICT EQUIPMENT HISTORY SHEET</h2>
';

// Fetch details from the URL parameters safely
$equipment = isset($_GET['equipment_name']) ? htmlspecialchars($_GET['equipment_name']) : 'N/A';
$serialNumber = isset($_GET['property_num']) ? htmlspecialchars($_GET['property_num']) : 'N/A';
$location = isset($_GET['location_id']) ? htmlspecialchars($_GET['location_id']) : 'N/A';
$date = isset($_GET['maintenance_date']) ? htmlspecialchars($_GET['maintenance_date']) : 'N/A';
$joNumber = isset($_GET['jo_number']) ? htmlspecialchars($_GET['jo_number']) : 'N/A';
$actionsTaken = isset($_GET['actions_taken']) ? htmlspecialchars($_GET['actions_taken']) : 'N/A';
$remarks = isset($_GET['remarks']) ? htmlspecialchars($_GET['remarks']) : 'N/A';
$responsiblePersonnel = isset($_GET['personnel']) ? htmlspecialchars($_GET['personnel']) : 'N/A';

// Equipment details table
$html .= '
    <p><strong>Equipment:</strong> ' . $equipment . '</p>
    <p><strong>Property/Serial Number:</strong> ' . $serialNumber . '</p>
    <p><strong>Location:</strong> ' . $location . '</p>
    <br>
    <table border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>Date</th>
                <th>JO Number</th>
                <th>Actions Taken</th>
                <th>Remarks</th>
                <th>Responsible SDMD Personnel</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>' . $date . '</td>
                <td>' . $joNumber . '</td>
                <td>' . $actionsTaken . '</td>
                <td>' . $remarks . '</td>
                <td>' . $responsiblePersonnel . '</td>
            </tr>
        </tbody>
    </table>
    <br>
    <div style="text-align: center; font-size: 10px;">
        Systems and Data Management Division (SDMD) - Page 1 of 1
    </div>
';

// Output the HTML content to the PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Close and output the PDF (force download)
$pdf->Output($equipment . '-' . $serialNumber . '.pdf', 'I');
?>
