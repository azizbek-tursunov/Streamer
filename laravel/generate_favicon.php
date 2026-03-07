<?php
$src = imagecreatefrompng(__DIR__ . '/public/images/univision_logo_transparent.png');
$w = imagesx($src);
$h = imagesy($src);

// Create 32x32 favicon PNG
$ico32 = imagecreatetruecolor(32, 32);
imagealphablending($ico32, false);
imagesavealpha($ico32, true);
$transparent = imagecolorallocatealpha($ico32, 0, 0, 0, 127);
imagefilledrectangle($ico32, 0, 0, 31, 31, $transparent);
imagealphablending($ico32, true);
imagecopyresampled($ico32, $src, 0, 0, 0, 0, 32, 32, $w, $h);
imagepng($ico32, __DIR__ . '/public/favicon-32.png');
echo "Created favicon-32.png\n";

// Create 16x16 favicon PNG
$ico16 = imagecreatetruecolor(16, 16);
imagealphablending($ico16, false);
imagesavealpha($ico16, true);
imagefilledrectangle($ico16, 0, 0, 15, 15, $transparent);
imagealphablending($ico16, true);
imagecopyresampled($ico16, $src, 0, 0, 0, 0, 16, 16, $w, $h);
imagepng($ico16, __DIR__ . '/public/favicon-16.png');
echo "Created favicon-16.png\n";

// Create ICO file (32x32 PNG wrapped in ICO format)
$pngData = file_get_contents(__DIR__ . '/public/favicon-32.png');
$ico = pack('vvv', 0, 1, 1); // ICO header: reserved, type=1(ico), count=1
$ico .= pack('CCCCvvVV',
    32, 32,  // width, height
    0,       // color palette
    0,       // reserved
    1,       // color planes
    32,      // bits per pixel
    strlen($pngData), // size of image data
    22       // offset to image data (6 header + 16 entry)
);
$ico .= $pngData;
file_put_contents(__DIR__ . '/public/favicon.ico', $ico);
echo "Created favicon.ico\n";

// Create 180x180 apple touch icon (white background)
$apple = imagecreatetruecolor(180, 180);
$white = imagecolorallocate($apple, 255, 255, 255);
imagefilledrectangle($apple, 0, 0, 179, 179, $white);
imagecopyresampled($apple, $src, 10, 10, 0, 0, 160, 160, $w, $h);
imagepng($apple, __DIR__ . '/public/apple-touch-icon.png');
echo "Created apple-touch-icon.png\n";

imagedestroy($src);
imagedestroy($ico32);
imagedestroy($ico16);
imagedestroy($apple);
echo "Done!\n";
