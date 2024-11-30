<?php
require_once 'vendor/autoload.php'; // Ensure this points to the correct autoload file

// Path to the TTF file inside the TCPDF fonts directory
$fontFile = __DIR__ . '/tcpdf/fonts/old-english-text-mt.ttf';

if (file_exists($fontFile)) {
    $fontName = TCPDF_FONTS::addTTFfont($fontFile, 'TrueTypeUnicode', '', 96);
    echo "Font registered successfully: $fontName\n";
} else {
    echo "Font file not found: $fontFile\n";
}
