<?php
// generate_nature_images.php

$folder = __DIR__ . '/storage/app/mock_images';
if (!file_exists($folder)) {
    mkdir($folder, 0777, true);
}

for ($i = 1; $i <= 100; $i++) {
    // Use Picsum.photos to get random nature-like images
    $url = "https://picsum.photos/1200/800?random=$i";

    $imgContent = file_get_contents($url);
    if ($imgContent) {
        file_put_contents("$folder/product_$i.jpg", $imgContent);
        echo "Downloaded product_$i.jpg\n";
    } else {
        echo "Failed to download image $i\n";
    }
}

echo "Downloaded 100 nature images in $folder\n";