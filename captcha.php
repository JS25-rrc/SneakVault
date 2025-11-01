<?php
    /**
     * SneakVault CMS - CAPTCHA Generator
     * 
     * Generates a CAPTCHA image to prevent spam comments.
     * Uses PHP GD library to create dynamic images.
     * 
     * Requirements Met:
     * - 2.10: CAPTCHA system using PHP with dynamically generated image (5%)
     */

    session_start();

    // Generate random CAPTCHA code (6 characters)
    $code = '';
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Exclude confusing characters
    for ($i = 0; $i < 6; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }

    // Store in session for verification
    $_SESSION['captcha'] = $code;

    // Image dimensions
    $width = 200;
    $height = 60;

    // Create image
    $image = imagecreatetruecolor($width, $height);

    // Define colors
    $bg_color = imagecolorallocate($image, 255, 255, 255); // White background
    $text_color = imagecolorallocate($image, 0, 0, 0); // Black text
    $line_color = imagecolorallocate($image, 150, 150, 150); // Gray lines
    $noise_color = imagecolorallocate($image, 200, 200, 200); // Light gray noise

    // Fill background
    imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

    // Add random lines for security
    for ($i = 0; $i < 5; $i++) {
        imageline(
            $image, 
            rand(0, $width), 
            rand(0, $height), 
            rand(0, $width), 
            rand(0, $height), 
            $line_color
        );
    }

    // Add random noise dots
    for ($i = 0; $i < 100; $i++) {
        imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
    }

    // Add text with variations
    $x = 15;
    $y = 40;

    for ($i = 0; $i < strlen($code); $i++) {
        // Random color for each character
        $char_color = imagecolorallocate(
            $image, 
            rand(0, 100), 
            rand(0, 100), 
            rand(0, 100)
        );
        
        // Use built-in font (5 is the largest)
        // Add random vertical offset for each character
        imagestring(
            $image, 
            5, 
            $x, 
            $y - 15 + rand(-5, 5), 
            $code[$i], 
            $char_color
        );
        
        // Increment X position with slight randomness
        $x += 28 + rand(-3, 3);
    }

    // Add more distortion lines on top
    for ($i = 0; $i < 3; $i++) {
        imageline(
            $image, 
            rand(0, $width), 
            rand(0, $height), 
            rand(0, $width), 
            rand(0, $height), 
            imagecolorallocate($image, rand(150, 200), rand(150, 200), rand(150, 200))
        );
    }

    // Output image headers
    header('Content-Type: image/png');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Output image
    imagepng($image);

    // Clean up
    imagedestroy($image);
?>