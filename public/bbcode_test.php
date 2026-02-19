<?php
require __DIR__ . '/../app/Helpers/BBCodeParser.php';

use App\Helpers\BBCodeParser;

$input = '[URL="https://t.me/orsozox_file/341"]الجزء الاول[/URL]';
$parsed = BBCodeParser::parse($input);

echo "Input: " . htmlspecialchars($input) . "<br>";
echo "Parsed: " . htmlspecialchars($parsed) . "<br>";
echo "Raw Parsed: " . $parsed . "<br>";

$input2 = '[URL="https://www.google.com"][IMG]https://via.placeholder.com/150[/IMG][/URL]';
$parsed2 = BBCodeParser::parse($input2);
echo "Input 2: " . htmlspecialchars($input2) . "<br>";
echo "Raw Parsed 2: " . $parsed2 . "<br>";
