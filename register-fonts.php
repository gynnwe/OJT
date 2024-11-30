<?php
require_once 'vendor/autoload.php'; // Ensure this points to the correct autoload file

// Path to the Calibri font file inside the TCPDF fonts directory
$calibriFontFile = __DIR__ . '/tcpdf/fonts/calibri.ttf';

if (file_exists($calibriFontFile)) {
    $calibriFontName = TCPDF_FONTS::addTTFfont($calibriFontFile, 'TrueTypeUnicode', '', 96);
    echo "Font registered successfully: $calibriFontName\n";
} else {
    echo "Calibri font file not found: $calibriFontFile\n";
}

// Path to Old English Text MT font file
$oldEnglishFontFile = __DIR__ . '/tcpdf/fonts/old-english-text-mt.ttf';

if (file_exists($oldEnglishFontFile)) {
    $oldEnglishFontName = TCPDF_FONTS::addTTFfont($oldEnglishFontFile, 'TrueTypeUnicode', '', 96);
    echo "Font registered successfully: $oldEnglishFontName\n";
} else {
    echo "Old English Text MT font file not found: $oldEnglishFontFile\n";
}
