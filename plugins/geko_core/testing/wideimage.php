<?php

ini_set( 'display_errors', 1 );
ini_set( 'scream.enabled', 1 );		// >= v.5.2.0
error_reporting( E_ALL ^ E_NOTICE );
error_reporting( E_ALL );

require_once realpath( '../wp-load.php' );
require_once realpath( '../wp-admin/includes/admin.php' );

// ---------------------------------------------------------------------------------------------- //

/* /
// do checks
if ( !is_user_logged_in() || !current_user_can( 'administrator' ) ) {
	die();
}

ini_set( 'display_errors', 1 );
ini_set( 'scream.enabled', 1 );		// >= v.5.2.0
error_reporting( E_ALL ^ E_NOTICE );
error_reporting( E_ALL );
/* */


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">

<head profile="http://gmpg.org/xfn/11">
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
	<title>WideImage</title>
</head>

<body>

<h1>WideImage</h1>

<?php

$filename = 'images/autorotate_test.jpg';
// var_dump( class_exists( 'WideImage' ) );

$exif = exif_read_data( $filename );
$ort = $exif[ 'Orientation' ];

// GD doesn't support EXIF, so all information is removed.
if ( ( $ort >= 2 ) && ( $ort <= 8 ) ) {
	$image = WideImage::load( $filename );
	// print_r( $image );
	$image->exifOrient( $ort )->saveToFile( $filename );
	echo 'Re-orient performed!';
} else {
	echo 'No re-orientation!';
}

?>

</body>

</html>