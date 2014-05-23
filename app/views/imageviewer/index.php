<?php
$images = false;
if ($_GET['images']) {
	$images = $_GET['images'];
}
if (isset($_GET['pic']) && $_GET['pic']) {
	$pic = $_GET['pic'];
}
$directions = '';
if ($images) {
	$key = array_search($pic, $images);
	$image_str = '';
	foreach($images as $path) {
		if ($image_str != '') $image_str .= '&amp;';
		$image_str .= urlencode('images[]=' . $path);
	}
	foreach($images as $num=>$path) {
		$i = $num+1;
		if ($key == $num) {
			$directions .= ' ' . $i;
		} else {
			$directions .= ' <a href="/imageviewer.php?pic=' . $num . '&amp;' . $image_str . '">' . $i . '</a>';
		}
	}
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1">
	<title>Image Zoom<?php echo (($_GET['alt'])?' - ' . urldecode(stripslashes($_GET['alt'])):''); ?></title>
<link rel="stylesheet" href="/stylesheets/reset.css" type="text/css">
<link rel="stylesheet" href="/stylesheets/default.css" type="text/css">
<link rel="stylesheet" href="/stylesheets/print.css" type="text/css">
<style type="text/css">
<!--
body {margin:-10px;padding:0 0 0 0; } /* For most versions of NN.4.x */
html body {margin:0px;} /* For CSS-compliant browsers */
-->
</style>
<script type="text/javascript" language="Javascript">
<!--
<?php
$size = getimagesize($_SERVER['DOCUMENT_ROOT'] . $pic);
$jswidth = $size[0]+50;
$jsheight = $size[1]+80;
print "window.resizeTo(" . $jswidth . ', ' . $jsheight . ");\n";
?>
//-->
</script>
</head>
<body bgcolor="#ffffff" style="background:url('') #fff;">
<table border="0" cellspacing="0" cellpadding="0" width="100%" height="100%">
<tr>
<td align="center" valign="middle">

<a href="<?php echo urldecode($_GET['referer']); ?>" onclick="window.close();return false;"><img src="<?php echo $pic; ?>" alt="<?php echo urldecode($alt); ?>" <?php echo $size[3]; ?> border="0"></a>

<p>
<a href="<?php echo urldecode($_GET['referer']); ?>" onclick="window.close();return false;"><?php echo ((!$_GET['referer'])?'Close Window':'Return to Page'); ?></a>
</p>
</td>
</tr>
</table></td>
</tr>
</table>
</body>
</html>
