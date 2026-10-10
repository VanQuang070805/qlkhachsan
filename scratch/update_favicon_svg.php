<?php
$data = base64_encode(file_get_contents(__DIR__ . '/../public/favicon-192x192.png'));
$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 192" width="192" height="192">' . PHP_EOL .
       '  <image href="data:image/png;base64,' . $data . '" width="192" height="192"/>' . PHP_EOL .
       '</svg>' . PHP_EOL;
file_put_contents(__DIR__ . '/../public/favicon.svg', $svg);
echo "favicon.svg updated successfully!\n";
