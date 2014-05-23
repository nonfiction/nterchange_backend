<?php
// Check for non-empty "text" parameter
//    
$text = stripslashes(urldecode($_GET['text'])) or die("no text parameter given");
$height       = 15;   // how tall is the button
$cap_width    = 2;    // how wide are the left and right rounded caps
$cap_overlap  = 0;    // how many pixels should the text intrude into each cap
$center_width = 15;   // how wide is the stretchable center part

$left_name   = "button/button_l.gif";
$center_name = "button/button_m.gif";
$right_name  = "button/button_r.gif";
// What font face and size to use
// 
$ttfont = realpath('fonts/mini7.ttf');
$ttsize = 7.5;
// Calculate the bounding box of the given string with
// the above font parameters
// 
$ttbbox = imagettfbbox($ttsize, 0, $ttfont, $text);
// array index 2 contains lower right X, 0 contains lower left X,
// difference is our text width
// 
$ttwidth = $ttbbox[2] - $ttbbox[0];
// The image width is the text width plus the
// space needed for the two caps on each side
// minus the intrusion amount
// 
$img_width = $ttwidth + 2 * ($cap_width - $cap_overlap);
$img_width += 6;

$img_out = ImageCreate($img_width, $height) or die("Unable to create new image");
// Load in the three images needed to create the button
// 
$img_button_left   = ImageCreateFromGIF($left_name) or die("Unable to open $left_name");
$img_button_center = ImageCreateFromGIF($center_name) or die("Unable to open $center_name");
$img_button_right  = ImageCreateFromGIF($right_name) or die("Unable to open $right_name");
// Fill the empty image canvas by tiling the center image
// 
for ($i =0; $i < $img_width / $center_width; $i++) {
	ImageCopy($img_out, $img_button_center, $i * $center_width, 0, 0, 0, $center_width, $height);
}

// Now add the left and right cap, this finishes the button
// 
ImageCopy($img_out, $img_button_left, 0, 0, 0, 0, $cap_width, $height);
ImageCopy($img_out, $img_button_right, $img_width - $cap_width, 0, 0, 0, $cap_width, $height);

// Define text color black and render the text onto the button image
// 
$text_color = ImageColorAllocate($img_out, 255, 255, 255);
ImageTTFText($img_out, $ttsize, 0, $cap_width-$cap_overlap+3, 10, $text_color, $ttfont, $text);

// That's it, output the result
// 
header("Content-Type: image/gif");
imagegif($img_out);
imagedestroy($img_button_left);
imagedestroy($img_button_center);
imagedestroy($img_button_right);
imagedestroy($img_out);
?>
