<?php
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Database connection
include 'conn.php';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

// Get the property number
$property_num = isset($_GET['property_num']) ? $_GET['property_num'] : '';
if (!$property_num) {
    die('Property number is required');
}

// Fetch all maintenance lo gs for the specific property number
$sql = "
    SELECT 
        ml.maintenance_date,
        ml.jo_number,
        ml.actions_taken,
        r.remarks_name AS remarks,
        e.equip_name AS equipment_name,
        e.property_num,
        CONCAT(l.building, ' - ', l.office, ' - ', l.room) AS location_name,
        p.firstname,
        p.lastname
    FROM 
        ict_maintenance_logs ml
    LEFT JOIN 
        equipment e ON ml.equipment_id = e.equipment_id
    LEFT JOIN 
        remarks r ON ml.remarks_id = r.remarks_id
    LEFT JOIN 
        personnel p ON ml.personnel_id = p.personnel_id
    LEFT JOIN 
        location l ON e.location_id = l.location_id
    WHERE 
        e.property_num = :property_num
";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':property_num', $property_num);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$logs) {
    die('No records found for this property number.');
}

// Load custom font
$options = new Options();
$options->set("isHtml5ParserEnabled", true); // Enable HTML5 parser
$options->set("isPhpEnabled", true); // Allow PHP inside HTML (e.g., for loading fonts)

$dompdf = new Dompdf($options);

// Set the font directory
$font_dir = 'C:/xampp/htdocs/OJT/assets'; // Font directory path

// Manually register the custom font
$dompdf->getOptions()->set("fontDir", $font_dir); // Set font directory
$dompdf->getOptions()->set("fontCache", $font_dir); // Set font cache directory

// Convert image to Base64 encoding
$imagePath = 'C:/xampp/htdocs/OJT/assets/images/usep-logo.png'; // Full image path
$imageData = base64_encode(file_get_contents($imagePath));

// Prepare the HTML content
$html = "
<!DOCTYPE html>
<html>
<head>
    <title>ICT EQUIPMENT HISTORY SHEET</title>
    <style>
        @page {
            margin: 1cm; /* Set 1 cm margins for all sides */
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-family: 'Old English Text MT', serif;
            font-size: 16px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .table th {
            background-color: #f2f2f2
            text-align: center;
        }
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .custom-table td {
            border: 1px solid #000;
        }
        .column-1 {
            width: 2.98cm;
            height: 3.85cm;
            text-align: center;
        }
        .column-2 {
            width: 9.54cm;
            height: 3.85cm;
            font-family: Arial, sans-serif;
            font-size: 9px;
            text-align: center;
            line-height: 1;
        }
        .column-2 .title {
            font-family: 'Old English Text MT', serif;
            font-size: 16px;
            text-align: center;
            line-height: 1;
        }
        .column-3 {
            width: 2.42cm;
            height: 3.85cm;
            vertical-align: top;
        }
        .column-4 {
            width: 3.49cm;
            height: 3.85cm;
            vertical-align: top;
        }
        .row-divider {
            border-bottom: 1px solid #000;
            height: 0.77cm;
        }
        .row-divider:last-child {
            border-bottom: none;
        }
        .link {
            color: blue;
            text-decoration: underline;
        }
 .box {
    width: 18.95cm; /* Exact width as requested */
    height: 0.84cm; /* Exact height as requested */
    border-left: 1px solid #000; /* Only left and right borders */
    border-right: 1px solid #000;
    margin: -20px auto; /* Adjust to move the box position */
    font-family: Arial, sans-serif; /* Arial font */
    font-size: 14px; /* Font size 14 */
    font-weight: bold; /* Bold font */
    line-height: 0.7cm; /* Adjust line height to move text higher */
    text-align: center; /* Centering text horizontally */
    padding-top: 0; /* Remove padding if present */
}

.header-table {
    width: 100%; /* Full width of the table */
    border-collapse: collapse; /* Removes gaps between cells */
    margin-bottom: 20px; /* Adds spacing below the table */
    margin-top: 20px; /* Adds spacing above the table */
    font-family: Arial, sans-serif; /* Sets the font to Arial */
    font-size: 11px; /* Sets the font size to 11 */
}

.header-table td {
    border: 1px solid #000; /* Adds a solid border around cells */
    height: 0.62cm; /* Row height set to 0.62cm */
    padding-left: 5px; /* Moves text slightly to the right */
    vertical-align: middle; /* Aligns content vertically */
}

.header-col-1 {
    width: 4.28cm; /* First column width */
    font-weight: bold; /* Bold for labels */
    text-align: left; /* Align text to the left */
}

.header-col-2 {
    width: 14.15cm; /* Second column width */
    text-align: left; /* Align text to the left */
}


    </style>
</head>
<body>
    <table class='custom-table'>
        <tr>
            <td class='column-1'>
                <img src='data:image/png;base64,{$imageData}' style='width: 2.54cm; height: 2.54cm;'>
            </td>
            <td class='column-2'>
                <p>Republic of the Philippines</p>
                <p class='title'>University of Southeastern Philippines</p>
                <p>Iñigo St., Bo. Obrero, Davao City 8000</p>
                <p>Telephone: (082) 227-8192</p>
                <p>Website: <a href='http://www.usep.edu.ph' class='link'>www.usep.edu.ph</a></p>
                <p>Email: <a href='mailto:president@usep.edu.ph' class='link'>president@usep.edu.ph</a></p>
            </td>
            <td class='column-3'>
                <div class='row-divider'>Form No.</div>
                <div class='row-divider'>Issue Status</div>
                <div class='row-divider'>Revision No.</div>
                <div class='row-divider'>Date Effective</div>
                <div class='row-divider' style='border-bottom: none;'>Approved by</div>
            </td>
            <td class='column-4'>
                <div class='row-divider'>FM-USeP-ICT-04</div>
                <div class='row-divider'>01</div>
                <div class='row-divider'>00</div>
                <div class='row-divider'>23 December 2022</div>
                <div class='row-divider' style='border-bottom: none;'>President</div>
            </td>
        </tr>
    </table>
    <div class='box'>ICT EQUIPMENT HISTORY SHEET</div>
<table class='header-table'>
    <tr>
        <td class='header-col-1'>Equipment Name:</td>
        <td class='header-col-2'>{$logs[0]['equipment_name']}</td>
    </tr>
    <tr>
        <td class='header-col-1'>Property Number:</td>
        <td class='header-col-2'>{$logs[0]['property_num']}</td>
    </tr>
    <tr>
        <td class='header-col-1'>Location:</td>
        <td class='header-col-2'>{$logs[0]['location_name']}</td>
    </tr>
</table>

    <table class='table'>
        <thead>
            <tr>
                <th>Date</th>
                <th>JO Number</th>
                <th>Actions Taken</th>
                <th>Remarks</th>
                <th>Personnel</th>
            </tr>
        </thead>
        <tbody>";
foreach ($logs as $log) {
    $html .= "
            <tr>
                <td>{$log['maintenance_date']}</td>
                <td>{$log['jo_number']}</td>
                <td>{$log['actions_taken']}</td>
                <td>{$log['remarks']}</td>
                <td>{$log['firstname']} {$log['lastname']}</td>
            </tr>";
}
$html .= "
        </tbody>
    </table>
</body>
</html>
";

// Configure DOMPDF
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the PDF
$dompdf->render();

// Output the PDF to browser
$filename = $property_num . '.pdf'; // Use the property number as the filename
$dompdf->stream($filename, ['Attachment' => 0]);
?>
