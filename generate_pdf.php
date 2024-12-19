<?php
require_once 'tcpdf/tcpdf.php';

// === Backend: Fetch Year and Equipment Type/Name from Database ===
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms";

try {
    // Connect to the database
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check for plan_id passed via POST
    if (!empty($_POST['plan_id'])) {
        $planId = $_POST['plan_id'];

        // Fetch Year and Equipment Type/Name using the provided Plan ID
        $query = "
            SELECT mp.year, et.equip_type_name 
            FROM maintenance_plan mp
            JOIN plan_details pd ON mp.id = pd.maintenance_plan_id
            JOIN equipment_type et ON pd.equip_type_id = et.equip_type_id
            WHERE mp.id = :planId
            LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':planId', $planId, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Store fetched values into variables
        if ($data) {
            $year = $data['year'];
            $equipmentType = $data['equip_type_name'];
        } else {
            throw new Exception("No data found for the provided Plan ID.");
        }
    } else {
        throw new Exception("Plan ID is missing.");
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

class CustomPDF extends TCPDF
{
    public function Header() {}
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('arial', '', 9);
        $firstColumnWidth = 233.1;
        $secondColumnWidth = 32;
        $rowHeight = 6;
        $this->Cell($firstColumnWidth, $rowHeight, 'Systems and Data Management Division', 1, 0, 'C');
        $this->Cell($secondColumnWidth, $rowHeight, '       Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 1, 1, 'C');
    }
}

$pdf = new CustomPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Name');
$pdf->SetTitle('TCPDF Custom Footer Example');
$pdf->SetSubject('TCPDF Example');
$pdf->SetKeywords('TCPDF, PDF, example, custom footer');
$pdf->SetMargins(19, 12.7, 12.7);
$pdf->SetAutoPageBreak(true, 4.6);
$pdf->AddPage();
$pdf->SetFont('arial', '', 9);
$pageWidth = $pdf->GetPageWidth() - 19 - 12.7;
$secondColumnWidth = 150.6;
$thirdColumnWidth = 35;
$fourthColumnWidth = 34.2;
$firstColumnWidth = $pageWidth - ($secondColumnWidth + $thirdColumnWidth + $fourthColumnWidth);
$rowHeight = 7.3;
$pdf->Cell($firstColumnWidth, $rowHeight * 5, '', 1, 0, 'C');
$pdf->Cell($secondColumnWidth, $rowHeight * 5, '', 1, 0, 'C');

for ($i = 0; $i < 5; $i++) {
    $pdf->SetFont('arial', '', 9);
    $thirdColumnText = ["Form No.", "Issue Status", "Revision No.", "Date Effective", "Approved by"];
    $fourthColumnText = ["FM-USeP-ICT-10", "01", "00", "23 December 2022", "President"];
    for ($i = 0; $i < 5; $i++) {
        $pdf->SetX(19 + $firstColumnWidth + $secondColumnWidth);
        $pdf->Cell($thirdColumnWidth, $rowHeight, $thirdColumnText[$i], 1, 0, 'L');
        $pdf->Cell($fourthColumnWidth, $rowHeight, $fourthColumnText[$i], 1, 1, 'L');
    }
}

$singleColumnHeight = 14.7;
$pdf->Cell($pageWidth, $singleColumnHeight, '', 1, 1, 'C');
$centerX = 19;
$centerY = $pdf->GetY() - $singleColumnHeight;
$pdf->SetXY($centerX, $centerY + 3);
$pdf->SetY($pdf->GetY() - 1);
$pdf->SetFont('arialbd', 'B', 14);
$pdf->MultiCell($pageWidth, 1, 'ANNUAL PREVENTIVE MAINTENANCE PLAN FOR ICT EQUIPMENT', 0, 'C', 0, 1);
$pdf->SetFont('arial', 'U', 12);
$pdf->Cell($pageWidth, 5, 'Year ' . $year, 0, 1, 'C');
$pdf->SetFont('arial', '', 10);
$pdf->Cell($pageWidth, 15, 'Name of Office/College/School/Unit: ______________________________                      Campus: ______________________________', 0, 1, 'L');
$pdf->Ln(0.5);
$pdf->SetFont('arial', '', 10);
$col1Width = 9.3;
$col2Width = 40.3;
$col3Width = 57.0;
$col4Width = 157.5;
$col4SubWidth = $col4Width / 13;
$rowHeight = 12.8;
$splitRowHeight = $rowHeight / 2;
$tableData = [
    ["No.", "Equipment Type/Name", "Areas to be Maintained / Checked", ""],
    ["1", $equipmentType, "", ""]
];

foreach ($tableData as $key => $row) {
    $pdf->Cell($col1Width, $rowHeight, $row[0], 1, 0, 'C');
    $pdf->Cell($col2Width, $rowHeight, $row[1], 1, 0, 'C');
    if ($key === 0) {
        $pdf->Cell($col3Width, $rowHeight, $row[2], 1, 0, 'C');
        $currentX = $pdf->GetX();
        $currentY = $pdf->GetY();
        $pdf->Cell($col4Width, $splitRowHeight, 'Schedule', 1, 0, 'C');
        $pdf->Ln();
        $pdf->SetX($currentX);
        $firstColWidth = $col4SubWidth + 20;
        $remainingColWidth = ($col4Width - $firstColWidth) / 12;
        $pdf->Cell($firstColWidth, $splitRowHeight, '', 1, 0, 'C');
        // This is the row part that you need to insert the data. I know there is two dynamic table but let's deal wirth the 1st group table first
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        foreach ($months as $month) {
            $pdf->Cell($remainingColWidth, $splitRowHeight, $month, 1, 0, 'C');
        }
        $pdf->Ln();
    } else {
        $pdf->SetX(19 + $col1Width + $col2Width);
        $pdf->Cell($col3Width, $splitRowHeight, 'Hardware', 1, 0, 'C');
        for ($i = 0; $i < 13; $i++) {
            if ($i === 0) {
                $pdf->Cell($firstColWidth, $splitRowHeight, 'Plan', 1, 0, 'C');
            } else {
                $pdf->Cell($remainingColWidth, $splitRowHeight, '', 1, 0, 'C');
            }
        }
        $pdf->Ln();
        $pdf->SetX(19 + $col1Width + $col2Width);
        $pdf->Cell($col3Width, $splitRowHeight, 'Software', 1, 0, 'C');
        for ($i = 0; $i < 13; $i++) {
            if ($i === 0) {
                $pdf->Cell($firstColWidth, $splitRowHeight, 'Implemented', 1, 0, 'C');
            } else {
                $pdf->Cell($remainingColWidth, $splitRowHeight, '', 1, 0, 'C');
            }
        }
        $pdf->Ln();
    }
}

$imagePath = 'C:/xampp/htdocs/OJT/assets/usep-logo.jpg';
$imageWidth = 28.1;
$imageHeight = 28.1;
$pdf->Image($imagePath, 19 + (($firstColumnWidth - $imageWidth) / 2), -2 + (($rowHeight * 5 - $imageHeight) / 2), $imageWidth, $imageHeight);
$column2StartX = 19 + $firstColumnWidth;
$column2StartY = 12.7 + (($rowHeight * 5 - 36) / 2);
$pdf->SetXY($column2StartX, $column2StartY);
$pdf->SetFont('arial', '', 10);
$pdf->SetY($column2StartY + -8);
$pdf->SetX($column2StartX + 2);
$pdf->Cell($secondColumnWidth, 1, "Republic of the Philippines", 0, 1, 'C');
$pdf->SetFont('oldenglishtextmt', '', 16);
$pdf->SetX($column2StartX + 2);
$pdf->Cell($secondColumnWidth, 1, "University of Southeastern Philippines", 0, 1, 'C');
$pdf->SetFont('arial', '', 10);
$pdf->SetX($column2StartX + 2);
$pdf->Cell($secondColumnWidth, 1, "Iñigo St., Bo. Obrero, Davao City 8000", 0, 1, 'C');
$pdf->SetX($column2StartX + 2);
$pdf->Cell($secondColumnWidth, 1, "Telephone (082) 227-8192", 0, 1, 'C');
$pdf->SetTextColor(0, 0, 255);
$pdf->SetFont('arial', 'U', 10);
$pdf->SetX($column2StartX + 37);
$pdf->Write(0, "www.usep.edu.ph;", 'https://www.usep.edu.ph');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('arial', '', 10);
$pdf->Write(0, " email: ");
$pdf->SetTextColor(0, 0, 255);
$pdf->SetFont('arial', 'U', 10);
$pdf->Write(0, "president@usep.edu.ph", 'mailto:president@usep.edu.ph');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('arial', '', 10);
$pdf->Ln(6);
$pdf->Ln(10);
$rowHeight = 35.9;
$colWidth = 132.1;
$pdf->Ln(100);

for ($i = 0; $i < 1; $i++) {
    $currentX = $pdf->GetX();
    $currentY = $pdf->GetY();
    $pdf->Cell($colWidth, $rowHeight, '', 1, 0, 'C');
    $pdf->SetXY($currentX + 5, $currentY + 5);
    $pdf->SetFont('Arialbd', 'B', 10);
    $pdf->Cell($colWidth - 10, 5, 'Prepared by:', 0, 1, 'L');
    $pdf->SetXY($currentX + 5, $currentY + 15);
    $pdf->Cell($colWidth - 10, 5, '______________________________', 0, 1, 'C');
    $pdf->SetXY($currentX + 5, $currentY + 20);
    $pdf->Cell($colWidth - 10, 5, 'SDMD Deputy Director/Authorized Representative', 0, 1, 'C');
    $pdf->SetFont('arial', '', 10);
    $pdf->SetXY($currentX + 5, $currentY + 25);
    $pdf->Cell($colWidth - 10, 5, '(Signature Over Printed Name)', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY($currentX + 5, $currentY + 30);
    $pdf->Cell($colWidth - 10, 5, 'Date: ____________________', 0, 1, 'C');
    $currentX2 = $currentX + $colWidth;
    $pdf->SetXY($currentX2, $currentY);
    $pdf->Cell($colWidth, $rowHeight, '', 1, 0, 'C');
    $pdf->SetXY($currentX2 + 5, $currentY + 5);
    $pdf->SetFont('Arialbd', 'B', 10);
    $pdf->Cell($colWidth - 10, 5, 'Approved by:', 0, 1, 'L');
    $pdf->SetXY($currentX2 + 5, $currentY + 15);
    $pdf->Cell($colWidth - 10, 5, '______________________________', 0, 1, 'C');
    $pdf->SetXY($currentX2 + 5, $currentY + 20);
    $pdf->Cell($colWidth - 10, 5, 'SDMD Director', 0, 1, 'C');
    $pdf->SetFont('arial', '', 10);
    $pdf->SetXY($currentX2 + 5, $currentY + 25);
    $pdf->Cell($colWidth - 10, 5, '(Signature Over Printed Name)', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY($currentX2 + 5, $currentY + 30);
    $pdf->Cell($colWidth - 10, 5, 'Date: ____________________', 0, 1, 'C');
    $pdf->Ln();
}

define('OUTPUT_PATH', __DIR__ . '/');
$pdf->Output(OUTPUT_PATH . 'tcpdf_custom_footer_with_table.pdf', 'I');
?>
