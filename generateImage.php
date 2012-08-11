<?

$jpg_quality   = 75; // the quality of any generated JPGs on a scale of 0 to 100
$sharpen       = TRUE; // Shrinking images can blur details, perform a sharpen on re-scaled images?
/* sharpen images function */
function findSharp($intOrig, $intFinal) {
  $intFinal = $intFinal * (750.0 / $intOrig);
  $intA     = 52;
  $intB     = -0.27810650887573124;
  $intC     = .00047337278106508946;
  $intRes   = $intA + $intB * $intFinal + $intC * $intFinal * $intFinal;
  return max(round($intRes), 0);
}

/* generates the given cache file for the given source file with the given resolution */
function generateImage($source_file, $cache_file, $resolution) {
  global $sharpen, $jpg_quality;

  $extension = strtolower(pathinfo($source_file, PATHINFO_EXTENSION));

  // Check the image dimensions
  $dimensions   = GetImageSize($source_file);
  $width        = $dimensions[0];
  $height       = $dimensions[1];

  // Do we need to downscale the image?
  if ($width <= $resolution) { // no, because the width of the source image is already less than the client width
    return $source_file;
  }

  // We need to resize the source image to the width of the resolution breakpoint we're working with
  $ratio      = $height/$width;
  $new_width  = $resolution;
  $new_height = ceil($new_width * $ratio);
  $dst        = ImageCreateTrueColor($new_width, $new_height); // re-sized image

  switch ($extension) {
    case 'png':
      $src = @ImageCreateFromPng($source_file); // original image
    break;
    case 'gif':
      $src = @ImageCreateFromGif($source_file); // original image
    break;
    default:
      $src = @ImageCreateFromJpeg($source_file); // original image
      ImageInterlace($dst, true); // Enable interlancing (progressive JPG, smaller size file)
    break;
  }

  if($extension=='png'){
    imagealphablending($dst, false);
    imagesavealpha($dst,true);
    $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
    imagefilledrectangle($dst, 0, 0, $new_width, $new_height, $transparent);
  }
  
  ImageCopyResampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height); // do the resize in memory
  ImageDestroy($src);

  // sharpen the image?
  // NOTE: requires PHP compiled with the bundled version of GD (see http://php.net/manual/en/function.imageconvolution.php)
  if($sharpen == TRUE && function_exists('imageconvolution')) {
    $intSharpness = findSharp($width, $new_width);
    $arrMatrix = array(
      array(-1, -2, -1),
      array(-2, $intSharpness + 12, -2),
      array(-1, -2, -1)
    );
    imageconvolution($dst, $arrMatrix, $intSharpness, 0);
  }

  $cache_dir = dirname($cache_file);

  // does the directory exist already?
  if (!is_dir($cache_dir)) { 
    if (!mkdir($cache_dir, 0755, true)) {
      // check again if it really doesn't exist to protect against race conditions
      if (!is_dir($cache_dir)) {
        // uh-oh, failed to make that directory
        ImageDestroy($dst);
        //sendErrorImage("Failed to create cache directory: $cache_dir");
		echo "Failed to create cache directory: $cache_dir";
      }
    }
  }

  if (!is_writable($cache_dir)) {
    //sendErrorImage("The cache directory is not writable: $cache_dir");
	echo "The cache directory is not writable: $cache_dir";
  }

  // save the new file in the appropriate path, and send a version to the browser
  switch ($extension) {
    case 'png':
      $gotSaved = ImagePng($dst, $cache_file);
    break;
    case 'gif':
      $gotSaved = ImageGif($dst, $cache_file);
    break;
    default:
      $gotSaved = ImageJpeg($dst, $cache_file, $jpg_quality);
    break;
  }
  ImageDestroy($dst);

  if (!$gotSaved && !file_exists($cache_file)) {
    //sendErrorImage("Failed to create image: $cache_file");
	echo "Failed to create image: $cache_file";
  }

  return $cache_file;
}
$resolutions   = array(1382, 992, 768, 480); // the resolution break-points to use (screen widths, in pixels)
$source_file=$_SERVER['DOCUMENT_ROOT']."/Adaptive-Images/01.png";
$cache_file=$_SERVER['DOCUMENT_ROOT']."/Adaptive-Images/small01.png"; 
$resolution=480; //cache_file==output file_path/file_name

 generateImage($source_file, $cache_file, $resolution);
?>