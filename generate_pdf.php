<?php
require_once 'tcpdf/tcpdf.php'; // Ensure this path is correct


class CustomPDF extends TCPDF
{
    // Override Header method to ensure nothing appears at the top
    public function Header()
    {
        // Do nothing (no header, no line)
    }

    // Override Footer method to add your custom footer
    public function Footer()
    {
        // Set position of the footer
        $this->SetY(-15); // Position 15mm from the bottom

        // Set font for the footer
        $this->SetFont('arial', '', 9);

        // Define column widths (in mm)
        $firstColumnWidth = 233.1; // 23.31 cm
        $secondColumnWidth = 32;   // 3.2 cm
        $rowHeight = 6;            // Row height = 6mm

        // Draw first column
        $this->Cell($firstColumnWidth, $rowHeight, 'Systems and Data Management Division', 1, 0, 'C');

        // Draw second column
        $this->Cell($secondColumnWidth, $rowHeight, '       Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 1, 1, 'C');
    }
}

// Create new PDF instance
$pdf = new CustomPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Name');
$pdf->SetTitle('TCPDF Custom Footer Example');
$pdf->SetSubject('TCPDF Example');
$pdf->SetKeywords('TCPDF, PDF, example, custom footer');

// Set margins
$pdf->SetMargins(19, 12.7, 12.7); // left = 1.9cm, top = 1.27cm, right = 1.27cm
$pdf->SetAutoPageBreak(true, 4.6); // bottom margin = 0.46cm

// Add a page
$pdf->AddPage();

// Set font and default styles
$pdf->SetFont('arial', '', 9);

// Define column widths (in mm)
$pageWidth = $pdf->GetPageWidth() - 19 - 12.7; // Total width excluding margins
$secondColumnWidth = 150.6; // 15.06 cm
$thirdColumnWidth = 35;     // 3.5 cm
$fourthColumnWidth = 34.2;  // 3.42 cm
$firstColumnWidth = $pageWidth - ($secondColumnWidth + $thirdColumnWidth + $fourthColumnWidth); // Remaining width for 1st column

$rowHeight = 7.3; // 0.73cm row height in mm

// Draw first and second columns (spanning 5 rows each)
$pdf->Cell($firstColumnWidth, $rowHeight * 5, '', 1, 0, 'C'); // Column 1
$pdf->Cell($secondColumnWidth, $rowHeight * 5, '', 1, 0, 'C'); // Column 2

// Draw 3rd and 4th columns split into 5 rows each
for ($i = 0; $i < 5; $i++) {
    // Set font for the 3rd and 4th column text
    $pdf->SetFont('arial', '', 9);

    // Text for the 3rd and 4th columns
    $thirdColumnText = ["Form No.", "Issue Status", "Revision No.", "Date Effective", "Approved by"];
    $fourthColumnText = ["FM-USeP-ICT-10", "01", "00", "23 December 2022", "President"];

    // Loop to fill 3rd and 4th columns
    for ($i = 0; $i < 5; $i++) {
        $pdf->SetX(19 + $firstColumnWidth + $secondColumnWidth); // Move to 3rd column X position
        $pdf->Cell($thirdColumnWidth, $rowHeight, $thirdColumnText[$i], 1, 0, 'L'); // Add text to 3rd column
        $pdf->Cell($fourthColumnWidth, $rowHeight, $fourthColumnText[$i], 1, 1, 'L'); // Add text to 4th column
    }
}
// Add single column below the 4 columns with a row height of 1.47cm
$singleColumnHeight = 14.7; // 1.47cm in mm

// Draw the single cell
$pdf->Cell($pageWidth, $singleColumnHeight, '', 1, 1, 'C'); // Empty bordered cell

// Calculate position to center the text inside the cell
$centerX = 19; // Left margin
$centerY = $pdf->GetY() - $singleColumnHeight; // Top of the single column

// Set X and Y to position text inside the cell
$pdf->SetXY($centerX, $centerY + 3); // Adjusted Y position for vertical centering

$pdf->SetY($pdf->GetY() - 1); // Reduce Y position by 4mm to move the second line higher

// Add the first line of text: "ANNUAL PREVENTIVE MAINTENANCE PLAN FOR ICT EQUIPMENT"
$pdf->SetFont('arialbd', 'B', 14); // Arial Bold with size 14
$pdf->MultiCell($pageWidth, 1, 'ANNUAL PREVENTIVE MAINTENANCE PLAN FOR ICT EQUIPMENT', 0, 'C', 0, 1);

// Add the second line of text: "Year ____________"
$pdf->SetFont('arial', '', 12); // Set to Arial Regular, size 12
$pdf->Cell($pageWidth, 1, 'Year ____________', 0, 1, 'C');

// Add text below the single column cell
$pdf->SetFont('arial', '', 10); // Set font to Arial Regular, size 10
$pdf->Cell($pageWidth, 15, 'Name of Office/College/School/Unit: ______________________________                      Campus: ______________________________', 0, 1, 'L');

// Add space below the previous table
$pdf->Ln(0.5); // Adjust spacing as needed

// Set font to Arial size 10 for the table
$pdf->SetFont('arial', '', 10);

// Define dimensions for the table
$col1Width = 9.3;    // 0.93 cm = 9.3 mm
$col2Width = 40.3;   // 4.03 cm = 40.3 mm
$col3Width = 57.0;   // 5.7 cm = 57 mm
$col4Width = 157.5;  // Total width for 4th column
$col4SubWidth = $col4Width / 13; // Divide the 4th column into 12 smaller cells
$rowHeight = 12.8;   // Full row height
$splitRowHeight = $rowHeight / 2; // Half row height

// Define text for each cell in the table
$tableData = [
    ["No.", "Equipment Type/Name", "Areas to be Maintained / Checked", ""], // Header row
    ["1", "", "", ""] // Placeholder row
];

// Draw the table
foreach ($tableData as $key => $row) {
    // Draw the 1st column
    $pdf->Cell($col1Width, $rowHeight, $row[0], 1, 0, 'C');

    // Draw the 2nd column
    $pdf->Cell($col2Width, $rowHeight, $row[1], 1, 0, 'C');

    if ($key === 0) {
        // Draw the 3rd column
        $pdf->Cell($col3Width, $rowHeight, $row[2], 1, 0, 'C');

        // 4th Column: Divide vertically with top and bottom interchanged
        $currentX = $pdf->GetX();
        $currentY = $pdf->GetY();

        // Top half: Single cell
        $pdf->Cell($col4Width, $splitRowHeight, 'Schedule', 1, 0, 'C');
        $pdf->Ln();

        // Bottom half: Adjust first column and redistribute remaining width
        $pdf->SetX($currentX); // Reset X position

        // Adjust widths
        $firstColWidth = $col4SubWidth + 20; // Increase first column width
        $remainingColWidth = ($col4Width - $firstColWidth) / 12;

        // First cell: Blank but adjusted width
        $pdf->Cell($firstColWidth, $splitRowHeight, '', 1, 0, 'C');

        // Array of months
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        // Populate months in the remaining 12 cells
        foreach ($months as $month) {
            $pdf->Cell($remainingColWidth, $splitRowHeight, $month, 1, 0, 'C');
        }
        $pdf->Ln();
    } else {
        // 1st part: Add "Plan" in the first column of 13 cells
        $pdf->SetX(19 + $col1Width + $col2Width);
        $pdf->Cell($col3Width, $splitRowHeight, 'Hardware', 1, 0, 'C');

        for ($i = 0; $i < 13; $i++) {
            if ($i === 0) {
                $pdf->Cell($firstColWidth, $splitRowHeight, 'Plan', 1, 0, 'C'); // Larger first cell
            } else {
                $pdf->Cell($remainingColWidth, $splitRowHeight, '', 1, 0, 'C'); // Adjusted remaining cells
            }
        }
        $pdf->Ln();

        // 2nd part: Add "Implemented" in the first column of 13 cells
        $pdf->SetX(19 + $col1Width + $col2Width);
        $pdf->Cell($col3Width, $splitRowHeight, 'Software', 1, 0, 'C');

        for ($i = 0; $i < 13; $i++) {
            if ($i === 0) {
                $pdf->Cell($firstColWidth, $splitRowHeight, 'Implemented', 1, 0, 'C'); // Larger first cell
            } else {
                $pdf->Cell($remainingColWidth, $splitRowHeight, '', 1, 0, 'C'); // Adjusted remaining cells
            }
        }
        $pdf->Ln();
    }
}

// Insert image into the first column
$imagePath = 'C:/xampp/htdocs/OJT/assets/usep-logo.jpg';
$imageWidth = 28.1; // 2.81 cm = 28.1 mm
$imageHeight = 28.1; // 2.81 cm = 28.1 mm

$pdf->Image($imagePath, 19 + (($firstColumnWidth - $imageWidth) / 2), -2 + (($rowHeight * 5 - $imageHeight) / 2), $imageWidth, $imageHeight, '', '', '', false, 300, '', false, false, 0);

// Add text content centered in the second column
$column2StartX = 19 + $firstColumnWidth; // X position for column 2
$column2StartY = 12.7 + (($rowHeight * 5 - 36) / 2); // Vertically centered in column
$pdf->SetXY($column2StartX, $column2StartY);

$pdf->SetFont('arial', '', 10);
$pdf->SetY($column2StartY + -8); // Shift content slightly right
$pdf->SetX($column2StartX + 2); // Shift content slightly right
$pdf->Cell($secondColumnWidth, 1, "Republic of the Philippines", 0, 1, 'C');

$pdf->SetFont('oldenglishtextmt', '', 16); // Old English Text MT
$pdf->SetX($column2StartX + 2); // Shift content slightly right
$pdf->Cell($secondColumnWidth, 1, "University of Southeastern Philippines", 0, 1, 'C');

$pdf->SetFont('arial', '', 10);
$pdf->SetX($column2StartX + 2); // Shift content slightly right
$pdf->Cell($secondColumnWidth, 1, "Iñigo St., Bo. Obrero, Davao City 8000", 0, 1, 'C');
$pdf->SetX($column2StartX + 2); // Shift content slightly right
$pdf->Cell($secondColumnWidth, 1, "Telephone (082) 227-8192", 0, 1, 'C');

$pdf->SetTextColor(0, 0, 255); // Blue color
$pdf->SetFont('arial', 'U', 10); // Underline and font size
$pdf->SetX($column2StartX + 37); // Shift content slightly right
$pdf->Write(0, "www.usep.edu.ph;", 'https://www.usep.edu.ph'); // Blue and clickable website link

// Set black color for "email:"
$pdf->SetTextColor(0, 0, 0); // Black color
$pdf->SetFont('arial', '', 10); // Remove underline
$pdf->Write(0, " email: "); // Write "email:" in black

// Set blue color and underline for the email hyperlink
$pdf->SetTextColor(0, 0, 255); // Blue color
$pdf->SetFont('arial', 'U', 10); // Underline and font size
$pdf->Write(0, "president@usep.edu.ph", 'mailto:president@usep.edu.ph'); // Blue and clickable email

// Reset text color to black and remove underline
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('arial', '', 10); // Remove underline
$pdf->Ln(6); // Line break

// Add space below the table
$pdf->Ln(10);

// Define dimensions for the 2x1 table
$rowHeight = 35.9;  // 3.59 cm = 35.9 mm
$colWidth = 132.1;  // 12.62 cm = 132.1 mm

// Add a space before the new table
$pdf->Ln(49); // Add spacing if needed

// Draw the 2x1 table
for ($i = 0; $i < 1; $i++) {
    // Draw the first cell in the row (with text)
    $currentX = $pdf->GetX(); // Get current X position
    $currentY = $pdf->GetY(); // Get current Y position
    $pdf->Cell($colWidth, $rowHeight, '', 1, 0, 'C'); // First cell (border only)

    // Set position for text inside the first cell
    $pdf->SetXY($currentX + 5, $currentY + 5); // Padding
    $pdf->SetFont('Arialbd', 'B', 10);
    $pdf->Cell($colWidth - 10, 5, 'Prepared by:', 0, 1, 'L'); // "Prepared by"

    $pdf->SetXY($currentX + 5, $currentY + 15);
    $pdf->Cell($colWidth - 10, 5, '______________________________', 0, 1, 'C'); // Underline

    $pdf->SetXY($currentX + 5, $currentY + 20);
    $pdf->Cell($colWidth - 10, 5, 'SDMD Deputy Director/Authorized Representative', 0, 1, 'C');

    $pdf->SetFont('arial', '', 10); // Italics
    $pdf->SetXY($currentX + 5, $currentY + 25);
    $pdf->Cell($colWidth - 10, 5, '(Signature Over Printed Name)', 0, 1, 'C');

    $pdf->SetFont('Arial', '', 10); // Regular
    $pdf->SetXY($currentX + 5, $currentY + 30);
    $pdf->Cell($colWidth - 10, 5, 'Date: ____________________', 0, 1, 'C');

    // Draw the second cell in the row
    $currentX2 = $currentX + $colWidth; // Move to second cell
    $pdf->SetXY($currentX2, $currentY);
    $pdf->Cell($colWidth, $rowHeight, '', 1, 0, 'C'); // Second cell (border only)

    // Add "Approved by" text inside the second cell
    $pdf->SetXY($currentX2 + 5, $currentY + 5); // Slight padding inside cell
    $pdf->SetFont('Arialbd', 'B', 10);
    $pdf->Cell($colWidth - 10, 5, 'Approved by:', 0, 1, 'L');

    $pdf->SetXY($currentX2 + 5, $currentY + 15);
    $pdf->Cell($colWidth - 10, 5, '______________________________', 0, 1, 'C'); // Underline

    $pdf->SetXY($currentX2 + 5, $currentY + 20);
    $pdf->Cell($colWidth - 10, 5, 'SDMD Director', 0, 1, 'C');

    $pdf->SetFont('arial', '', 10); // Italics
    $pdf->SetXY($currentX2 + 5, $currentY + 25);
    $pdf->Cell($colWidth - 10, 5, '(Signature Over Printed Name)', 0, 1, 'C');

    $pdf->SetFont('Arial', '', 10); // Regular
    $pdf->SetXY($currentX2 + 5, $currentY + 30);
    $pdf->Cell($colWidth - 10, 5, 'Date: ____________________', 0, 1, 'C');

    // Move to next row
    $pdf->Ln();
}


// Close and output PDF
define('OUTPUT_PATH', __DIR__ . '/'); // Save in current directory
$pdf->Output(OUTPUT_PATH . 'tcpdf_custom_footer_with_table.pdf', 'I');
?>